<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use Doctrine\ORM\Tools\SchemaValidator;

/**
 * The database an application's tables live in, as the schema made from the mapping has it: MariaDB 10.10 or later,
 * which has the UUID type and the collation of the tables; a connection in UTC; a mapping the schema tool accepts; every
 * table InnoDB and on the collation of `BaseEntity::TABLE_OPTIONS` (utf8mb4, utf8mb4_uca1400_ai_ci), which makes names
 * unique and searchable without regard to case and accents, and every string column on the collation of its table; every
 * `id` a UUID. An application extends the guard in one small class of its integration tests that names its project root:
 *
 *     final class SchemaTest extends SchemaGuard
 *     {
 *         protected static function projectRoot(): string { return dirname(__DIR__, 2); }
 *     }
 *
 * The tables of the migrations' own bookkeeping count as well, when they exist: MigrationsGuard holds them to the same
 * collation once the migrations have run.
 */
abstract class SchemaGuard extends AbstractApplicationGuard
{
    /** The first MariaDB that has the UUID type and the UCA 14 collations. */
    private const string FIRST_VERSION = '10.10.0';

    public function testTheServerIsMariaDbThatHasTheUuidTypeAndTheCollations(): void
    {
        $version = $this->serverVersion();
        // MariaDB reports itself as 5.5.5-<its number> to a client that expects MySQL's replication protocol
        $number = preg_replace('/^(?:5\.5\.5-)?(\d+(?:\.\d+)*).*$/s', '$1', $version);
        assert(is_string($number));
        $problems = [];

        if (!str_contains($version, 'MariaDB') || version_compare($number, self::FIRST_VERSION, '<')) {
            $problems[] = 'The database server is ' . $version . ', not MariaDB 10.10 or later, which has the UUID type and'
                . ' the collation of the tables.';
        }

        $this->assertNoProblems($problems);
    }

    public function testTheSessionRunsInUtc(): void
    {
        $zone = self::dbText($this->em->getConnection()->fetchOne('SELECT @@session.time_zone'));
        $problems = [];

        if ($zone !== 'UTC') {
            $problems[] = 'The connection to the database runs in the time zone "' . $zone . '", not in UTC: it has to send'
                . " SET time_zone = 'UTC' when it connects.";
        }

        $this->assertNoProblems($problems);
    }

    public function testTheMappingIsValid(): void
    {
        $problems = [];

        foreach (new SchemaValidator($this->em)->validateMapping() as $class => $errors) {
            foreach ($errors as $error) {
                $problems[] = 'The mapping of ' . $class . ' is invalid: ' . $error;
            }
        }

        $this->assertNoProblems($problems);
    }

    public function testEveryTableAndEveryStringColumnIsOnTheCollationOfTheTableOptions(): void
    {
        $this->assertNoProblems($this->collationProblems());
    }

    public function testEveryTableIsInnoDb(): void
    {
        $problems = [];

        foreach (
            $this->em->getConnection()->fetchAllAssociative(
                'SELECT table_name AS name, engine FROM information_schema.tables WHERE table_schema = DATABASE()'
                . " AND table_type = 'BASE TABLE' AND engine <> 'InnoDB' ORDER BY table_name",
            ) as $table
        ) {
            $problems[] = 'The table "' . self::dbText($table['name']) . '" is on the engine "'
                . self::dbText($table['engine']) . '", not on InnoDB.';
        }

        $this->assertNoProblems($problems);
    }

    public function testEveryIdIsAUuidColumn(): void
    {
        $problems = [];

        foreach (
            $this->em->getConnection()->fetchAllAssociative(
                'SELECT table_name AS name, data_type AS type FROM information_schema.columns'
                . " WHERE table_schema = DATABASE() AND column_name = 'id' AND data_type <> 'uuid' ORDER BY table_name",
            ) as $column
        ) {
            $problems[] = 'The id of the table "' . self::dbText($column['name']) . '" is the type "'
                . self::dbText($column['type']) . '", not a uuid: its entity extends BaseEntity, whose id is a UUID.';
        }

        $this->assertNoProblems($problems);
    }

    /** The server's version as it says so (`SELECT VERSION()`): the tests of the guard put another in its place. */
    protected function serverVersion(): string
    {
        return self::dbText($this->em->getConnection()->fetchOne('SELECT VERSION()'));
    }
}
