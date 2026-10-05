<?php

declare(strict_types=1);

namespace ampf\Kit\Bootstrap;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\ORM\Configuration;

/**
 * What the database set-up of an application on the package shares: the table of the migrations' own bookkeeping, which
 * is no part of the mapping. (The ORM configuration itself is ampf's: `ampf\Bootstrap\DoctrineConfiguration::create()`.)
 */
class DoctrineConfiguration
{
    /** The table of the migrations' own bookkeeping: no part of the mapping. */
    public const string MIGRATIONS_TABLE = 'doctrine_migration_versions';

    /**
     * Makes the schema tool leave the migrations' table alone, so that it does not propose to drop it. Only for what
     * compares the mapping with the database: the migrations themselves must see their table, or they create it again.
     */
    public static function ignoreMigrationsTable(Configuration $configuration): void
    {
        $configuration->setSchemaAssetsFilter(
            static fn (string|AbstractAsset $asset): bool => (
                $asset instanceof AbstractAsset ? $asset->getName() : $asset
            ) !== self::MIGRATIONS_TABLE,
        );
    }
}
