<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Integration;

use ampf\Bean\BeanFactory;
use ampf\Bootstrap\ApplicationContext;
use ampf\Doctrine\EntityManagerFactoryInterface;
use ampf\Kit\Tests\Support\FixtureApplicationTestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * The first test of a process finds the schema made from the mapping, whatever the database held before it: a table
 * that is none of the mapping's is gone. The test runs in a process of its own, so that it is the first of one, and puts
 * the table there before the harness looks at the database.
 */
#[RunTestsInSeparateProcesses]
final class FirstTestOfAProcessTest extends FixtureApplicationTestCase
{
    public function testTheSchemaIsTheMappingsWhateverTheDatabaseHeldBefore(): void
    {
        $tables = $this->em->getConnection()->createSchemaManager()->listTableNames();
        sort($tables);

        self::assertSame(['notes', 'shelves'], $tables);
    }

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);
        $factory = new BeanFactory(ApplicationContext::boot([
            $root . '/vendor/amp-framework/ampf/config/default.php',
            $root . '/tests/Fixtures/App/tests/Support/config/integration.php',
        ]))->get(EntityManagerFactoryInterface::class);
        assert($factory instanceof EntityManagerFactoryInterface);
        $connection = $factory->get()->getConnection();
        $connection->executeStatement('CREATE TABLE IF NOT EXISTS leftover (id INT NOT NULL, PRIMARY KEY (id))');
        $connection->close();

        parent::setUp();
    }
}
