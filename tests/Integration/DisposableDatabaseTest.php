<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Integration;

use ampf\Bean\BeanFactory;
use ampf\Bootstrap\ApplicationContext;
use ampf\Doctrine\EntityManagerFactoryInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/** The disposable MariaDB of docker/compose.test.yml is what the suite's other tests and the mutation runs assume it is. */
final class DisposableDatabaseTest extends TestCase
{
    private Connection $connection;

    public function testItAnswers(): void
    {
        self::assertSame(1, $this->connection->fetchOne('SELECT 1'));
    }

    public function testItIsNewEnoughForTheSchema(): void
    {
        // The schema uses the UUID type (10.7) and the UCA 14 collations (10.10)
        $version = $this->connection->fetchOne('SELECT VERSION()');
        assert(is_string($version));

        self::assertTrue(version_compare($version, '10.10', '>='), 'MariaDB ' . $version . ' is older than 10.10.');
    }

    public function testItComparesWithoutRegardToCaseAndAccents(): void
    {
        // The collation of every table (utf8mb4_uca1400_ai_ci): a name is the same name in any case and with any accent
        self::assertSame(
            1,
            $this->connection->fetchOne("SELECT 'Bäckup' = 'bACKUP' COLLATE utf8mb4_uca1400_ai_ci"),
        );
    }

    public function testEveryParallelProcessOfTheMutationRunHasADatabase(): void
    {
        self::assertSame(
            32,
            $this->connection->fetchOne(
                "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME REGEXP '^ampf_kit_test_[1-9][0-9]*$'",
            ),
        );
    }

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);
        $entityManagerFactory = new BeanFactory(ApplicationContext::boot([
            $root . '/vendor/amp-framework/ampf/config/default.php',
            $root . '/tests/Fixtures/App/tests/Support/config/integration.php',
        ]))->get(EntityManagerFactoryInterface::class);
        assert($entityManagerFactory instanceof EntityManagerFactoryInterface);

        $this->connection = $entityManagerFactory->get()->getConnection();
    }
}
