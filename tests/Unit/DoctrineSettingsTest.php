<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit;

use ampf\Bootstrap\ApplicationContext;
use ampf\Doctrine\Type\UTCDateTimeType;
use ampf\Kit\Doctrine\Type\UuidType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The package's `doctrine` block next to ampf's: the files merge one level deep, so a nested array of a later file
 * replaces the whole one of ampf's, and the package's overrides must still hold every one of ampf's (every datetime in
 * UTC, a database's enum read as a string) beside its own (`guid` as MariaDB's UUID, and a UUID column read back as one).
 */
final class DoctrineSettingsTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function blocks(): iterable
    {
        yield 'the types' => ['typeOverrides'];
        yield 'the database types' => ['mappingOverrides'];
    }

    /** @return array<array-key, mixed> */
    private static function block(string $name, string ...$files): array
    {
        $config = ApplicationContext::boot(array_values(array_map(
            static fn (string $file): string => dirname(__DIR__, 2) . '/' . $file,
            $files,
        )));
        self::assertIsArray($config['doctrine']);
        self::assertIsArray($config['doctrine'][$name]);

        return $config['doctrine'][$name];
    }

    #[DataProvider('blocks')]
    public function testTheOverridesKeepEveryOneOfAmpf(string $name): void
    {
        $ampf = self::block($name, 'vendor/amp-framework/ampf/config/default.php');
        $merged = self::block($name, 'vendor/amp-framework/ampf/config/default.php', 'config/default.php');

        self::assertNotSame([], $ampf);

        foreach ($ampf as $type => $override) {
            self::assertSame($override, $merged[$type] ?? null, 'ampf\'s override of ' . $type . ' is lost.');
        }
    }

    public function testGuidIsMariaDbsUuidBesideAmpfsDatetimesInUtc(): void
    {
        self::assertSame(
            [
                'datetime' => UTCDateTimeType::class,
                'datetimetz' => UTCDateTimeType::class,
                'guid' => UuidType::class,
            ],
            self::block('typeOverrides', 'vendor/amp-framework/ampf/config/default.php', 'config/default.php'),
        );
    }

    public function testAUuidColumnIsReadAsAGuidBesideAmpfsEnumAsAString(): void
    {
        self::assertSame(
            ['enum' => 'string', 'uuid' => 'guid'],
            self::block('mappingOverrides', 'vendor/amp-framework/ampf/config/default.php', 'config/default.php'),
        );
    }

    public function testTheBlockLeavesTheDatabaseToTheApplication(): void
    {
        $doctrine = ApplicationContext::boot([dirname(__DIR__, 2) . '/config/default.php'])['doctrine'] ?? null;
        self::assertIsArray($doctrine);

        self::assertSame(['typeOverrides', 'mappingOverrides'], array_keys($doctrine));
    }
}
