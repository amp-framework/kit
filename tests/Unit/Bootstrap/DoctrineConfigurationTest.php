<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Bootstrap;

use ampf\Bootstrap\DoctrineConfiguration as AmpfDoctrineConfiguration;
use ampf\Kit\Bootstrap\DoctrineConfiguration;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;

/** What the database set-up of an application on the package shares: the table of the migrations' bookkeeping. */
final class DoctrineConfigurationTest extends TestCase
{
    public function testTheSchemaToolCanBeToldToLeaveTheMigrationsTableAlone(): void
    {
        $configuration = AmpfDoctrineConfiguration::create([]);
        self::assertTrue(
            $configuration->getSchemaAssetsFilter()(DoctrineConfiguration::MIGRATIONS_TABLE),
            'Without the call nothing is hidden.',
        );

        DoctrineConfiguration::ignoreMigrationsTable($configuration);
        $filter = $configuration->getSchemaAssetsFilter();

        self::assertFalse($filter(DoctrineConfiguration::MIGRATIONS_TABLE));
        self::assertFalse($filter(new Table(DoctrineConfiguration::MIGRATIONS_TABLE)));
        self::assertTrue($filter('notes'));
        self::assertTrue($filter(new Table('notes')));
    }
}
