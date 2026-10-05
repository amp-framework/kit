<?php

declare(strict_types=1);

namespace ampf\Kit\Testing;

use ampf\Bean\BeanFactory;
use ampf\Bean\BeanFactoryInterface;
use ampf\Doctrine\EntityManagerFactoryInterface;
use ampf\Testing\ApplicationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use RuntimeException;

/**
 * The base of an application's integration tests on the package: ampf's ApplicationTestCase — every simulated request
 * and command in a scope of its own, the harness's doubles in place of the hasher and the session —
 * over the application's configuration with the package's config/default.php after ampf's files, as the application's
 * entry points list it, and against a disposable MariaDB (an application's docker/compose.test.yml).
 *
 * The database: before the first test of a process the schema is made from the mapping (rebuildSchema()), and every
 * test starts from empty tables. A database whose name does not match disposableDatabasePattern() is refused before
 * anything is changed in it: there is no silent skip and no fallback to another database.
 *
 * The entity managers: every scope's is closed when the scope is released (the next request starts, or the test ends),
 * so that a transaction a request left open is rolled back and no connection is left behind. The test's own, $em, is
 * the one of a scope of the test (ownBean()), closed when the test ends: for the fixtures and the assertions — what a
 * request changed is read after `$this->em->clear()`, since an entity the test holds is not the one the request changed.
 *
 * A subclass that overrides setUp() or tearDown() calls the parent's.
 */
abstract class IntegrationTestCase extends ApplicationTestCase
{
    /**
     * The tables of the schema the process made, which every test starts with empty; none before the first test of the
     * process, which makes the schema.
     *
     * @var list<string>
     */
    private static array $tables = [];

    /**
     * The test's own entity manager: for the fixtures and the assertions, and the one the beans of ownBean() share.
     */
    protected EntityManagerInterface $em;

    /**
     * The test's own scope, made in setUp(), whose entity manager is $em.
     */
    private BeanFactory $ownScope;

    /**
     * A value the database returned, as a text: what an assertion compares, whether the driver gave a number or a text.
     */
    protected static function dbText(mixed $value): string
    {
        self::assertTrue(is_scalar($value) || $value === null, 'The database returned a value that is no text.');

        return (string)$value;
    }

    /**
     * The harness's doubles (ApplicationTestCase), the test's own scope and its entity manager, and the database: refused
     * unless it is a disposable one, the schema made once in the process, and its tables emptied.
     *
     * @throws RuntimeException for a database that is not a disposable one
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ownScope = $this->newScope('http');
        $this->em = $this->entityManagerOf($this->ownScope);
        $this->prepareDatabase();
    }

    /** The test's own entity manager closed, then the scopes released and the error log checked (ApplicationTestCase). */
    protected function tearDown(): void
    {
        if (isset($this->em)) {
            $this->close($this->em);
        }

        parent::tearDown();
    }

    /**
     * The files the configuration of the transport is booted from: the framework's two, the package's
     * config/default.php, then the application's (ApplicationTestCase::configurationFiles()).
     *
     * @return list<string>
     */
    protected function configurationFiles(string $transport): array
    {
        $files = parent::configurationFiles($transport);

        return [...array_slice($files, 0, 2), dirname(__DIR__, 2) . '/config/default.php', ...array_slice($files, 2)];
    }

    /**
     * The pattern the name of the database has to match before the tests empty its tables: a name that ends in `_test`,
     * or in `_test_<n>` (the database of a parallel process of the mutation testing). An application may tighten it to
     * the names of its own test stack.
     */
    protected function disposableDatabasePattern(): string
    {
        return '/_test(?:_[1-9][0-9]*)?$/D';
    }

    /**
     * A bean of the test's own scope, which shares $em: a service whose work countSelects() counts, or whose repository
     * the test replaces. The scope is the one setUp() made: what useBean() and configure() change afterwards reaches
     * the scopes of the requests, not this one.
     */
    protected function ownBean(string $id): mixed
    {
        return $this->ownScope->get($id);
    }

    /** The entity manager of the scope (a request's: `$request->getBeanFactory()`), made at its first use. */
    protected function entityManagerOf(BeanFactoryInterface $scope): EntityManagerInterface
    {
        $factory = $scope->get(EntityManagerFactoryInterface::class);
        assert($factory instanceof EntityManagerFactoryInterface);

        return $factory->get();
    }

    /**
     * The first column of the rows the query returns on the test's own connection, as texts.
     *
     * @param list<int|string> $parameters
     *
     * @return list<string>
     */
    protected function dbTexts(string $sql, array $parameters = []): array
    {
        return array_map(
            static fn (mixed $value): string => self::dbText($value),
            $this->em->getConnection()->fetchFirstColumn($sql, $parameters),
        );
    }

    /**
     * How many SELECT statements the work sent to the database on the test's own connection: what a page or a service
     * that must cost a fixed number of queries is held to (their beans from ownBean(), `$this->em->clear()` first, so
     * that nothing is loaded already).
     */
    protected function countSelects(callable $work): int
    {
        $before = $this->selectsSoFar();
        $work();

        return $this->selectsSoFar() - $before;
    }

    /** Drops every table and makes the schema from the mapping again, as the first test of the process found it. */
    protected function rebuildSchema(): void
    {
        $tool = new SchemaTool($this->em);
        $tool->dropDatabase();
        $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        self::$tables = $this->em->getConnection()->createSchemaManager()->listTableNames();
    }

    /** A request's scope is done with: its entity manager is closed, and the test's own scope stays open. */
    protected function scopeReleased(BeanFactory $scope): void
    {
        if ($scope !== $this->ownScope) {
            $this->close($this->entityManagerOf($scope));
        }
    }

    /** The entity manager and its connection closed: a transaction left open is rolled back. */
    private function close(EntityManagerInterface $entityManager): void
    {
        $entityManager->close();
        $entityManager->getConnection()->close();
    }

    /** The number of SELECT statements the session of the test's own connection has run. */
    private function selectsSoFar(): int
    {
        $status = $this->em->getConnection()->fetchAllKeyValue("SHOW SESSION STATUS LIKE 'Com_select'");

        return (int)self::dbText($status['Com_select']);
    }

    /**
     * Never any other database than a disposable one; the schema from the mapping once in the process, and empty tables
     * before every test.
     *
     * @throws RuntimeException for a database that is not a disposable one
     */
    private function prepareDatabase(): void
    {
        $connection = $this->em->getConnection();
        $database = self::dbText($connection->fetchOne('SELECT DATABASE()'));
        $pattern = $this->disposableDatabasePattern();

        if (preg_match($pattern, $database) !== 1) {
            throw new RuntimeException(
                'The integration tests empty every table, so they run only against a disposable database whose name'
                . ' matches ' . $pattern . ', not against ' . var_export($database, true) . '.',
            );
        }

        if (self::$tables === []) {
            $this->rebuildSchema();
        }

        // TRUNCATE refuses a table that another one refers to, empty or not: the checks are off while the tables empty
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        foreach (self::$tables as $table) {
            $connection->executeStatement('TRUNCATE TABLE ' . $connection->quoteSingleIdentifier($table));
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
