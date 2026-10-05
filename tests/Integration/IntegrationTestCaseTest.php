<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Integration;

use ampf\Doctrine\EntityManagerFactoryInterface;
use ampf\Kit\Tests\Fixtures\App\Doctrine\Entity\NoteEntity;
use ampf\Kit\Tests\Fixtures\App\Doctrine\Entity\ShelfEntity;
use ampf\Kit\Tests\Support\FixtureApplicationTestCase;
use ampf\Service\Hasher\HasherServiceInterface;
use ampf\Testing\CheapHasherService;
use ampf\Testing\ExpectsExactMessage;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The package's integration test case, used as an application's tests use it: the configuration with the package's
 * file in its place, the test's own entity manager and those of the requests, the disposable database and its tables,
 * and the helpers.
 */
final class IntegrationTestCaseTest extends FixtureApplicationTestCase
{
    use ExpectsExactMessage;

    /**
     * Entity managers the test used that have to be closed once the test is over.
     *
     * @var list<EntityManagerInterface>
     */
    private array $closedAtTheEnd = [];

    /** @return iterable<string, array{string, bool}> the name of a database, whether the tests may empty it */
    public static function databases(): iterable
    {
        yield 'the package\'s' => ['ampf_kit_test', true];
        yield 'an application\'s' => ['shelf_test', true];
        yield 'of a parallel process' => ['shelf_test_7', true];
        yield 'of the last parallel process' => ['shelf_test_32', true];
        yield 'one that is no test\'s' => ['shelf', false];
        yield 'one named test' => ['test', false];
        yield 'a process numbered 0' => ['shelf_test_0', false];
        yield 'a process number with a leading 0' => ['shelf_test_07', false];
        yield 'tests' => ['shelf_tests', false];
        yield 'no process number after the underscore' => ['shelf_test_', false];
        yield 'more after the process number' => ['shelf_test_1a', false];
        yield 'a line feed at the end' => ["shelf_test\n", false];
        yield 'none' => ['', false];
    }

    /** @return iterable<string, array{int}> */
    public static function twice(): iterable
    {
        yield 'the first time' => [1];
        yield 'the second time' => [2];
    }

    public function testThePackagesConfigurationFollowsTheFrameworksAndPrecedesTheApplications(): void
    {
        $ampf = dirname(__DIR__, 2) . '/vendor/amp-framework/ampf/config/';
        $fixture = dirname(__DIR__) . '/Fixtures/App/';

        foreach (['http', 'cli'] as $transport) {
            self::assertSame(
                [
                    realpath($ampf . 'default.php'),
                    realpath($ampf . $transport . '.php'),
                    dirname(__DIR__, 2) . '/config/default.php',
                    $fixture . 'config/default.php',
                    $fixture . 'config/' . $transport . '.php',
                    $fixture . 'tests/Support/config/integration.php',
                ],
                array_map(
                    static fn (string $file): string => realpath($file) ?: $file,
                    $this->configurationFiles($transport),
                ),
                $transport,
            );
        }
    }

    #[DataProvider('databases')]
    public function testTheTestsEmptyOnlyADatabaseThatIsDisposable(string $name, bool $disposable): void
    {
        self::assertSame($disposable, preg_match($this->disposableDatabasePattern(), $name) === 1);
    }

    public function testTheBeansOfTheTestsOwnScopeShareItsEntityManager(): void
    {
        $factory = $this->ownBean(EntityManagerFactoryInterface::class);
        self::assertInstanceOf(EntityManagerFactoryInterface::class, $factory);

        self::assertSame($this->em, $factory->get());
        self::assertInstanceOf(
            CheapHasherService::class,
            $this->ownBean(HasherServiceInterface::class),
            'The harness\'s doubles are in it.',
        );
    }

    public function testARequestHasAnEntityManagerOfItsOwnThatIsClosedWhenTheNextRequestStarts(): void
    {
        $first = $this->get('notes');
        $its = $this->entityManagerOf($first->getBeanFactory());
        self::assertSame('Notes: 0', $first->getResponse());
        self::assertNotSame($this->em, $its);
        self::assertTrue($its->isOpen());
        self::assertSame('1', self::dbText($its->getConnection()->fetchOne('SELECT 1')));

        $this->get('notes');

        self::assertFalse($its->isOpen());
        self::assertFalse($its->getConnection()->isConnected());
        self::assertTrue($this->em->isOpen(), 'The test\'s own stays open.');
        self::assertSame(['0'], $this->dbTexts('SELECT COUNT(*) FROM notes'));
    }

    public function testWhatARequestLeftOpenIsRolledBack(): void
    {
        $request = $this->get('notes');
        $its = $this->entityManagerOf($request->getBeanFactory());
        $its->beginTransaction();
        $shelf = new ShelfEntity('Kitchen');
        $its->persist($shelf);
        $its->persist(new NoteEntity($shelf, 'Milk'));
        $its->flush();

        $this->get('notes');

        self::assertSame([], $this->dbTexts('SELECT text FROM notes'));
        self::assertSame([], $this->dbTexts('SELECT name FROM shelves'));
    }

    public function testTheEntityManagersAreClosedWhenTheTestEnds(): void
    {
        $its = $this->entityManagerOf($this->get('notes')->getBeanFactory());
        $its->getConnection()->fetchOne('SELECT 1');

        // tearDown() holds them to it
        $this->closedAtTheEnd = [$its, $this->em];
        self::assertTrue($its->isOpen() && $this->em->isOpen());
    }

    #[DataProvider('twice')]
    public function testEveryTestStartsWithEmptyTables(int $time): void
    {
        self::assertSame(
            ['0', '0'],
            [
                ...$this->dbTexts('SELECT COUNT(*) FROM shelves'),
                ...$this->dbTexts('SELECT COUNT(*) FROM notes'),
            ],
            'Run ' . $time . '.',
        );

        $shelf = new ShelfEntity('Kitchen');
        $this->em->persist($shelf);
        $this->em->persist(new NoteEntity($shelf, 'Note'));
        $this->em->flush();
    }

    public function testTheTestsConnectionChecksTheReferencesBetweenTheTables(): void
    {
        $this->expectException(ForeignKeyConstraintViolationException::class);

        $this->em->getConnection()->executeStatement(
            "INSERT INTO notes (id, shelf_id, text) VALUES (UUID(), UUID(), 'A note on no shelf')",
        );
    }

    public function testTheSchemaIsMadeAgainFromTheMapping(): void
    {
        $connection = $this->em->getConnection();
        $connection->executeStatement('CREATE TABLE leftover (id INT NOT NULL, PRIMARY KEY (id))');
        $connection->executeStatement('DROP TABLE notes');

        $this->rebuildSchema();

        $tables = $connection->createSchemaManager()->listTableNames();
        sort($tables);
        self::assertSame(['notes', 'shelves'], $tables);
    }

    public function testTheDatabasesValuesAreReadAsTexts(): void
    {
        $shelf = new ShelfEntity('Kitchen');
        $this->em->persist($shelf);
        $this->em->persist(new NoteEntity($shelf, 'Milk'));
        $this->em->persist(new NoteEntity($shelf, 'Bread'));
        $this->em->flush();

        self::assertSame(['Bread', 'Milk'], $this->dbTexts('SELECT text FROM notes ORDER BY text'));
        self::assertSame(['Milk'], $this->dbTexts('SELECT text FROM notes WHERE text = ?', ['Milk']));
        self::assertSame(['2'], $this->dbTexts('SELECT COUNT(*) FROM notes'));
        self::assertSame('', self::dbText(null));
    }

    public function testAValueThatIsNoTextIsRefused(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageExactly(
            'The database returned a value that is no text.' . PHP_EOL . 'Failed asserting that false is true.',
        );

        self::dbText(['a list']);
    }

    public function testTheSelectsOfAWorkAreCountedOnTheTestsConnection(): void
    {
        $connection = $this->em->getConnection();

        self::assertSame(0, $this->countSelects(static fn () => null));
        self::assertSame(1, $this->countSelects(static fn () => $connection->fetchOne('SELECT COUNT(*) FROM notes')));
        self::assertSame(
            2,
            $this->countSelects(static fn () => [
                $connection->fetchOne('SELECT COUNT(*) FROM notes'),
                $connection->fetchOne('SELECT COUNT(*) FROM shelves'),
            ]),
        );
    }

    protected function tearDown(): void
    {
        $closedAtTheEnd = $this->closedAtTheEnd;

        parent::tearDown();

        foreach ($closedAtTheEnd as $entityManager) {
            self::assertFalse($entityManager->isOpen());
            self::assertFalse($entityManager->getConnection()->isConnected());
        }
    }
}
