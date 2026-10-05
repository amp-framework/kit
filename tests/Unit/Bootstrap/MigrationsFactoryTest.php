<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Bootstrap;

use ampf\Kit\Bootstrap\MigrationsFactory;
use ampf\Testing\ExpectsExactMessage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Doctrine Migrations over the configuration's `migrations` block (MigrationsTest runs them on the disposable database):
 * a configuration without the block is refused before anything is asked of the database.
 */
final class MigrationsFactoryTest extends TestCase
{
    use ExpectsExactMessage;

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function configurationsWithoutTheBlock(): iterable
    {
        yield 'no block' => [[], 'null'];
        yield 'a block that is a text' => [['migrations' => 'src/Migration'], 'string'];
    }

    /** @param array<string, mixed> $config */
    #[DataProvider('configurationsWithoutTheBlock')]
    public function testAConfigurationWithoutTheMigrationsBlockIsRefused(array $config, string $type): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageExactly('The configuration\'s migrations must be an array, not ' . $type . '.');

        MigrationsFactory::create($config, self::createStub(EntityManagerInterface::class));
    }

    public function testTheBlockIsDoctrinesConfiguration(): void
    {
        $paths = ['Notes\Migration' => '/srv/notes/src/Migration'];

        $configuration = MigrationsFactory::create(
            ['migrations' => ['migrations_paths' => $paths, 'transactional' => false]],
            self::createStub(EntityManagerInterface::class),
        )->getConfiguration();

        self::assertSame($paths, $configuration->getMigrationDirectories());
        self::assertFalse($configuration->isTransactional());
    }
}
