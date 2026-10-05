<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use ampf\Kit\Bootstrap\DoctrineConfiguration;
use ampf\Kit\Bootstrap\MigrationsFactory;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\ORM\Tools\SchemaTool;
use Throwable;

/**
 * The schema's history is the schema's mapping. The migrations of the configuration (`migrations.migrations_paths`, in the
 * order of their version numbers) run on an empty disposable database build exactly what the entities map, on the collation of the tables, and a change of an entity without its migration
 * (`bin/doctrine migrations:diff`) fails here; every migration says what it does; the way back through them undoes them
 * all, and a second run has nothing left. An application extends the guard in one small class of its integration tests
 * that names its project root:
 *
 *     final class MigrationsTest extends MigrationsGuard
 *     {
 *         protected static function projectRoot(): string { return dirname(__DIR__, 2); }
 *     }
 *
 * Every test empties the database, runs the migrations that it needs, and puts the schema of the mapping back.
 */
abstract class MigrationsGuard extends AbstractApplicationGuard
{
    public function testTheMigrationsBuildExactlyTheMappedSchemaOnAnEmptyDatabase(): void
    {
        $this->onAnEmptyDatabase(function (): void {
            $this->migrate('latest', 'run on an empty database');

            DoctrineConfiguration::ignoreMigrationsTable($this->em->getConfiguration());
            $statements = new SchemaTool($this->em)->getUpdateSchemaSql(
                $this->em->getMetadataFactory()->getAllMetadata(),
            );
            $problems = $statements === []
                ? []
                : [
                    'The migrations and the mapping differ; these statements would bring the schema the migrations build'
                    . ' to the one the mapping describes (bin/doctrine migrations:diff writes the migration that is'
                    . ' missing):',
                    ...$statements,
                ];

            $this->assertNoProblems($problems);
        });
    }

    public function testEveryMigrationIsDescribed(): void
    {
        $migrations = MigrationsFactory::create($this->configuration('http'), $this->em)
            ->getMigrationPlanCalculator()
            ->getMigrations()
            ->getItems()
        ;
        $problems = [];

        if ($migrations === []) {
            $problems[] = 'The configuration\'s migrations_paths list no migration: the guard has nothing to check.';
        }

        foreach ($migrations as $migration) {
            if ($migration->getMigration()->getDescription() === '') {
                $problems[] = 'The migration ' . $migration->getVersion() . ' has no description: its getDescription()'
                    . ' says nothing.';
            }
        }

        $this->assertNoProblems($problems);
    }

    public function testTheTablesTheMigrationsBuildAreOnTheCollation(): void
    {
        $this->onAnEmptyDatabase(function (): void {
            $this->migrate('latest', 'run on an empty database');

            $this->assertNoProblems($this->collationProblems());
        });
    }

    public function testTheWayBackUndoesEveryMigration(): void
    {
        $this->onAnEmptyDatabase(function (): void {
            $this->migrate('latest', 'run on an empty database');
            $this->migrate('first', 'run back to the first');
            $tables = $this->em->getConnection()->createSchemaManager()->listTableNames();
            $held = implode('", "', $tables);
            $problems = $tables === [DoctrineConfiguration::MIGRATIONS_TABLE]
                ? []
                : [
                    'After the way back through the migrations the database holds the tables "' . $held . '", not only "'
                    . DoctrineConfiguration::MIGRATIONS_TABLE . '": the down() of a migration does not undo what its up()'
                    . ' made.',
                ];

            $this->assertNoProblems($problems);
        });
    }

    public function testASecondRunHasNothingLeftToMigrate(): void
    {
        $this->onAnEmptyDatabase(function (): void {
            $left = array_map(
                static fn (AvailableMigration $migration): string => (string)$migration->getVersion(),
                $this->migrate('latest', 'run on an empty database')
                    ->getMigrationStatusCalculator()
                    ->getNewMigrations()
                    ->getItems(),
            );
            $problems = $left === []
                ? []
                : [
                    'A second run of the migrations would run ' . count($left) . ' of them again: '
                    . implode(', ', $left) . '.',
                ];

            $this->assertNoProblems($problems);
        });
    }

    /**
     * Runs the test on a database without a table, and puts the schema of the mapping back, whatever the test does.
     *
     * @param callable(): void $test
     */
    private function onAnEmptyDatabase(callable $test): void
    {
        new SchemaTool($this->em)->dropDatabase();

        try {
            $test();
        } finally {
            $this->em->getConfiguration()->setSchemaAssetsFilter(static fn (): bool => true);
            // The other tests run on the schema made from the mapping
            $this->rebuildSchema();
        }
    }

    /**
     * Up or down to a version: its class, or one of Doctrine's aliases ("latest", "first"). The migrations that cannot
     * run fail the guard's test.
     *
     * @param string $what how it is said that they cannot run: "run on an empty database"
     */
    private function migrate(string $version, string $what): DependencyFactory
    {
        try {
            $factory = MigrationsFactory::create($this->configuration('http'), $this->em);
            $factory->getMetadataStorage()->ensureInitialized();
            $plan = $factory->getMigrationPlanCalculator()->getPlanUntilVersion(
                $factory->getVersionAliasResolver()->resolveVersionAlias($version),
            );
            $factory->getMigrator()->migrate($plan, new MigratorConfiguration());
        } catch (Throwable $failure) {
            self::fail('The migrations do not ' . $what . ': ' . $failure->getMessage());
        }

        return $factory;
    }
}
