<?php

declare(strict_types=1);

namespace ampf\Kit\Bootstrap;

use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Doctrine Migrations over the application's configuration (its `migrations` block) and an entity manager: what an
 * application's `bin/doctrine` and the test of the migrations both run.
 */
class MigrationsFactory
{
    /**
     * @param array<array-key, mixed> $config the merged configuration
     *
     * @throws RuntimeException when the configuration has no `migrations` block
     */
    public static function create(array $config, EntityManagerInterface $entityManager): DependencyFactory
    {
        $migrations = $config['migrations'] ?? null;

        if (!is_array($migrations)) {
            throw new RuntimeException(
                'The configuration\'s migrations must be an array, not ' . get_debug_type($migrations) . '.',
            );
        }

        /** @var array<string, mixed> $settings */
        $settings = $migrations;

        return DependencyFactory::fromEntityManager(
            new ConfigurationArray($settings),
            new ExistingEntityManager($entityManager),
        );
    }
}
