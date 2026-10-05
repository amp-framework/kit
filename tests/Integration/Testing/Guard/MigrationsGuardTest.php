<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Integration\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractApplicationGuard;
use ampf\Kit\Testing\Guard\MigrationsGuard;
use ampf\Kit\Tests\Support\FixtureApplicationTestCase;
use ampf\Kit\Tests\Support\RunnableGuard;
use ampf\Kit\Tests\Support\RunsAsAGuard;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * The guard over the fixture application's migrations, which abide: every one described, run on an empty database they
 * build the schema the mapping describes on the collation of the tables, the way back undoes them all, and a second run
 * has nothing left. And over the same application with one more migration that breaks one rule each
 * (tests/Fixtures/MigrationsGuard/<Variant>/Migration), and one that lists no migration at all. A guard is a TestCase, which takes its name: PHP-CS-Fixer writes
 * `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractApplicationGuard::class)]
#[CoversClass(MigrationsGuard::class)]
final class MigrationsGuardTest extends FixtureApplicationTestCase
{
    /** The guard over the fixture application, which lists its own migrations. */
    private static function abiding(): MigrationsGuard&RunnableGuard
    {
        return new class('abiding') extends MigrationsGuard implements RunnableGuard {
            use RunsAsAGuard;

            protected static function projectRoot(): string
            {
                return dirname(__DIR__, 3) . '/Fixtures/App';
            }
        };
    }

    /**
     * The guard over the fixture application with the migration of the variant in addition to its own (an empty variant:
     * none), or with none at all (`$withTheApplicationsOwn` false).
     */
    private static function variant(string $variant, bool $withTheApplicationsOwn = true): MigrationsGuard&RunnableGuard
    {
        return new class('variant') extends MigrationsGuard implements RunnableGuard {
            use RunsAsAGuard;

            protected static function projectRoot(): string
            {
                return dirname(__DIR__, 3) . '/Fixtures/App';
            }

            /**
             * @return array<string, mixed>
             */
            protected function configuration(string $transport): array
            {
                $configuration = parent::configuration($transport);
                $variant = $this->setting('variant');
                $paths = $this->setting('own') === 'yes'
                    ? ['ampf\Kit\Tests\Fixtures\App\Migration' => dirname(__DIR__, 3) . '/Fixtures/App/Migration']
                    : [];

                if ($variant !== '') {
                    $paths['ampf\Kit\Tests\Fixtures\MigrationsGuard\\' . $variant . '\Migration']
                        = dirname(__DIR__, 3) . '/Fixtures/MigrationsGuard/' . $variant . '/Migration';
                }
                $migrations = $configuration['migrations'];
                self::assertIsArray($migrations);
                $configuration['migrations'] = [...$migrations, 'migrations_paths' => $paths];

                return $configuration;
            }
        }->with('variant', $variant)->with('own', $withTheApplicationsOwn ? 'yes' : 'no');
    }

    public function testTheFixtureApplicationsMigrationsAbideByEveryRule(): void
    {
        $guard = self::abiding();

        foreach (
            [
                'testTheMigrationsBuildExactlyTheMappedSchemaOnAnEmptyDatabase',
                'testEveryMigrationIsDescribed',
                'testTheTablesTheMigrationsBuildAreOnTheCollation',
                'testTheWayBackUndoesEveryMigration',
                'testASecondRunHasNothingLeftToMigrate',
            ] as $test
        ) {
            self::assertNull($guard->failureOf($test), $test);
        }
    }

    public function testMigrationsThatLeaveTheSchemaDifferentFromTheMappingFailAndSayWhatIsMissing(): void
    {
        self::assertSame(
            'The migrations and the mapping differ; these statements would bring the schema the migrations build to the'
            . ' one the mapping describes (bin/doctrine migrations:diff writes the migration that is missing):' . PHP_EOL
            . 'ALTER TABLE notes CHANGE text text VARCHAR(200) NOT NULL' . PHP_EOL
            . 'ALTER TABLE shelves CHANGE name name VARCHAR(100) NOT NULL',
            self::variant('Drifting')->failureOf('testTheMigrationsBuildExactlyTheMappedSchemaOnAnEmptyDatabase'),
        );
    }

    public function testAMigrationThatCannotRunFailsAndSaysWhyOnEveryWayThatRunsThem(): void
    {
        $guard = self::variant('Failing');
        $message = 'The migrations do not run on an empty database: the migration is broken';

        foreach (
            [
                'testTheMigrationsBuildExactlyTheMappedSchemaOnAnEmptyDatabase',
                'testTheTablesTheMigrationsBuildAreOnTheCollation',
                'testTheWayBackUndoesEveryMigration',
                'testASecondRunHasNothingLeftToMigrate',
            ] as $test
        ) {
            self::assertSame($message, $guard->failureOf($test), $test);
        }
    }

    public function testNoMigrationAtAllOrOneThatSaysNothingFails(): void
    {
        self::assertSame(
            'The configuration\'s migrations_paths list no migration: the guard has nothing to check.',
            self::variant('', false)->failureOf('testEveryMigrationIsDescribed'),
        );
        self::assertSame(
            'The migration ampf\Kit\Tests\Fixtures\MigrationsGuard\Undescribed\Migration\Version20270101000000 has no'
            . ' description: its getDescription() says nothing.',
            self::variant('Undescribed')->failureOf('testEveryMigrationIsDescribed'),
        );
    }

    public function testMigrationsThatLeaveTablesAfterTheWayBackOrCannotGoBackOrForgetWhatRanFail(): void
    {
        self::assertSame(
            'After the way back through the migrations the database holds the tables "abandoned",'
            . ' "doctrine_migration_versions", not only "doctrine_migration_versions": the down() of a migration does not undo what its up()'
            . ' made.',
            self::variant('Stubborn')->failureOf('testTheWayBackUndoesEveryMigration'),
        );
        self::assertSame(
            'The migrations do not run back to the first: there is no way back',
            self::variant('BrokenDown')->failureOf('testTheWayBackUndoesEveryMigration'),
        );
        self::assertSame(
            'A second run of the migrations would run 1 of them again: '
            . 'ampf\Kit\Tests\Fixtures\App\Migration\Version20261004000000.',
            self::variant('Wiping')->failureOf('testASecondRunHasNothingLeftToMigrate'),
        );
    }

    public function testTheTableOfTheMigrationsIsOnTheCollationOfTheDatabaseItWasMadeIn(): void
    {
        $database = self::dbText($this->em->getConnection()->fetchOne('SELECT DATABASE()'));
        $this->em->getConnection()->executeStatement(
            'ALTER DATABASE ' . $this->em->getConnection()->quoteSingleIdentifier($database)
            . ' CHARACTER SET latin1 COLLATE latin1_swedish_ci',
        );

        try {
            $message = self::abiding()->failureOf('testTheTablesTheMigrationsBuildAreOnTheCollation');
        } finally {
            $this->em->getConnection()->executeStatement(
                'ALTER DATABASE ' . $this->em->getConnection()->quoteSingleIdentifier($database)
                . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci',
            );
            $this->rebuildSchema();
        }

        self::assertSame(
            'The table "doctrine_migration_versions" is on the collation "latin1_swedish_ci", not on'
            . ' "utf8mb4_uca1400_ai_ci" (character set utf8mb4): put the table of its entity on'
            . ' BaseEntity::TABLE_OPTIONS.' . PHP_EOL
            . 'The column "doctrine_migration_versions.version" is on the collation "latin1_swedish_ci", not on'
            . ' "utf8mb4_uca1400_ai_ci": a column takes the collation of its table and declares none of its own.',
            $message,
        );
    }
}
