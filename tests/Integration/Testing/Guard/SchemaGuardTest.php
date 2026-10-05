<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Integration\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractApplicationGuard;
use ampf\Kit\Testing\Guard\SchemaGuard;
use ampf\Kit\Tests\Fixtures\SchemaGuard\InvalidMapping\Entity\MismatchedEntity;
use ampf\Kit\Tests\Support\FixtureApplicationTestCase;
use ampf\Kit\Tests\Support\RunnableGuard;
use ampf\Kit\Tests\Support\RunsAsAGuard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The guard over the fixture application's schema, which abides: MariaDB that has the UUID type, a session in UTC, a
 * mapping the schema tool accepts, and tables, columns and ids as the conventions have them. And over the schema of an
 * application that breaks them, one rule at a time: a server it is told is another, a session in another time zone, a
 * mapping with an entity the schema tool finds invalid (tests/Fixtures/SchemaGuard/InvalidMapping) and tables made by
 * hand. A guard is a TestCase, which takes its name: PHP-CS-Fixer writes `new class('name')`, PSR-12
 * `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractApplicationGuard::class)]
#[CoversClass(SchemaGuard::class)]
final class SchemaGuardTest extends FixtureApplicationTestCase
{
    private const string COLLATION = 'utf8mb4_uca1400_ai_ci';

    /** @return iterable<string, array{string, bool}> what the server says its version is, and whether it will do */
    public static function servers(): iterable
    {
        yield 'a recent MariaDB' => ['12.3.3-MariaDB-ubu2404', true];
        yield 'the first MariaDB that has the UUID type' => ['10.10.0-MariaDB', true];
        yield 'a MariaDB that says what it replicates' => ['5.5.5-10.11.2-MariaDB', true];
        yield 'a MariaDB before the UUID type' => ['10.9.9-MariaDB', false];
        yield 'a MariaDB before it, that says what it replicates' => ['5.5.5-10.9.9-MariaDB', false];
        yield 'a MariaDB of the old line' => ['5.5.64-MariaDB', false];
        yield 'MySQL' => ['8.0.36', false];
        yield 'a MySQL whose number is later than the UUID type' => ['11.2.0', false];
    }

    /** The guard over the fixture application. */
    private static function abiding(): SchemaGuard&RunnableGuard
    {
        return new class('abiding') extends SchemaGuard implements RunnableGuard {
            use RunsAsAGuard;

            protected static function projectRoot(): string
            {
                return dirname(__DIR__, 3) . '/Fixtures/App';
            }
        };
    }

    /** The guard over the fixture application, whose server says its version is the one given. */
    private static function onServer(string $version): SchemaGuard&RunnableGuard
    {
        return new class('server') extends SchemaGuard implements RunnableGuard {
            use RunsAsAGuard;

            protected static function projectRoot(): string
            {
                return dirname(__DIR__, 3) . '/Fixtures/App';
            }

            protected function serverVersion(): string
            {
                return $this->setting('version');
            }
        }->with('version', $version);
    }

    /** The guard over the fixture application, whose session the test sets to the time zone given. */
    private static function inTimeZone(string $zone): SchemaGuard&RunnableGuard
    {
        return new class('zone') extends SchemaGuard implements RunnableGuard {
            use RunsAsAGuard;

            protected static function projectRoot(): string
            {
                return dirname(__DIR__, 3) . '/Fixtures/App';
            }

            protected function setUp(): void
            {
                parent::setUp();

                $this->em->getConnection()->executeStatement("SET time_zone = '" . $this->setting('zone') . "'");
            }
        }->with('zone', $zone);
    }

    public function testTheFixtureApplicationsSchemaAbidesByEveryRule(): void
    {
        $guard = self::abiding();

        foreach (
            [
                'testTheServerIsMariaDbThatHasTheUuidTypeAndTheCollations',
                'testTheSessionRunsInUtc',
                'testTheMappingIsValid',
                'testEveryTableAndEveryStringColumnIsOnTheCollationOfTheTableOptions',
                'testEveryTableIsInnoDb',
                'testEveryIdIsAUuidColumn',
            ] as $test
        ) {
            self::assertNull($guard->failureOf($test), $test);
        }
    }

    #[DataProvider('servers')]
    public function testAServerThatIsNoMariaDbOrOlderThanTheUuidTypeFails(string $version, bool $fits): void
    {
        $guard = self::onServer($version);

        self::assertSame(
            $fits
                ? null
                : 'The database server is ' . $version . ', not MariaDB 10.10 or later, which has the UUID type and the'
                    . ' collation of the tables.',
            $guard->failureOf('testTheServerIsMariaDbThatHasTheUuidTypeAndTheCollations'),
        );
    }

    public function testASessionThatRunsInAnotherTimeZoneThanUtcFails(): void
    {
        self::assertSame(
            'The connection to the database runs in the time zone "+02:00", not in UTC: it has to send'
            . " SET time_zone = 'UTC' when it connects.",
            self::inTimeZone('+02:00')->failureOf('testTheSessionRunsInUtc'),
        );
        self::assertNull(self::inTimeZone('UTC')->failureOf('testTheSessionRunsInUtc'));
    }

    public function testAnEntityWhoseMappingTheSchemaToolFindsInvalidFailsAndIsNamed(): void
    {
        $guard = self::abiding()->overlaid(dirname(__DIR__, 3) . '/Fixtures/SchemaGuard/InvalidMapping/overlay.php');

        self::assertSame(
            'The mapping of ' . MismatchedEntity::class . " is invalid: The field '" . MismatchedEntity::class
            . "#count' has the property type 'string' that differs from the metadata field type 'int' returned by the"
            . " 'integer' DBAL type.",
            $guard->failureOf('testTheMappingIsValid'),
        );
    }

    public function testTablesAndColumnsOnAnotherCollationFailAndSoDoesATableThatIsNotInnoDb(): void
    {
        $this->execute(
            'CREATE TABLE latin_notes (id UUID NOT NULL, PRIMARY KEY (id))'
            . ' ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci',
            'CREATE TABLE loud_notes (id UUID NOT NULL, name VARCHAR(20) COLLATE utf8mb4_general_ci NOT NULL,'
            . ' PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=' . self::COLLATION,
            'CREATE TABLE old_notes (id UUID NOT NULL, PRIMARY KEY (id))'
            . ' ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=' . self::COLLATION,
        );
        $guard = self::abiding();

        try {
            self::assertSame(
                'The table "latin_notes" is on the collation "latin1_swedish_ci", not on "' . self::COLLATION . '"'
                . ' (character set utf8mb4): put the table of its entity on BaseEntity::TABLE_OPTIONS.' . PHP_EOL
                . 'The column "loud_notes.name" is on the collation "utf8mb4_general_ci", not on "' . self::COLLATION
                . '": a column takes the collation of its table and declares none of its own.',
                $guard->failureOf('testEveryTableAndEveryStringColumnIsOnTheCollationOfTheTableOptions'),
            );
            self::assertSame(
                'The table "old_notes" is on the engine "MyISAM", not on InnoDB.',
                $guard->failureOf('testEveryTableIsInnoDb'),
            );
        } finally {
            $this->rebuildSchema();
        }
    }

    public function testAnIdThatIsNoUuidFailsAndATableWithoutAnIdDoesNot(): void
    {
        $this->execute(
            'CREATE TABLE numbered_notes (id INT NOT NULL, PRIMARY KEY (id))'
            . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=' . self::COLLATION,
            'CREATE TABLE note_tags (note UUID NOT NULL, tag VARCHAR(20) NOT NULL, PRIMARY KEY (note, tag))'
            . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=' . self::COLLATION,
        );

        try {
            self::assertSame(
                'The id of the table "numbered_notes" is the type "int", not a uuid: its entity extends BaseEntity,'
                . ' whose id is a UUID.',
                self::abiding()->failureOf('testEveryIdIsAUuidColumn'),
            );
        } finally {
            $this->rebuildSchema();
        }
    }

    /** The statements, on the test's own connection. */
    private function execute(string ...$statements): void
    {
        foreach ($statements as $statement) {
            $this->em->getConnection()->executeStatement($statement);
        }
    }
}
