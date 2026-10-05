<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Doctrine\Type\UuidType;
use ampf\Kit\Testing\Guard\MergedConfiguration;
use ampf\Service\Hasher\HasherService;
use ampf\Service\Hasher\HasherServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The configuration an application's entry points merge, as the guards read it: ampf's two files, the package's, then
 * the application's own default.php and file of the transport.
 */
#[CoversClass(MergedConfiguration::class)]
final class MergedConfigurationTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function transports(): iterable
    {
        yield 'the web' => ['http'];
        yield 'the command line' => ['cli'];
    }

    #[DataProvider('transports')]
    public function testTheFilesBeforeTheApplicationsAreTheFrameworksTwoAndThePackagesOne(string $transport): void
    {
        $files = MergedConfiguration::packageFiles($transport);

        self::assertCount(3, $files);
        self::assertStringEndsWith('/config/default.php', $files[0]);
        self::assertStringEndsWith('/config/' . $transport . '.php', $files[1]);
        self::assertSame(dirname(__DIR__, 4) . '/config/default.php', $files[2]);
        self::assertSame(dirname($files[0]), dirname($files[1]), 'the framework\'s two files are in one directory');
        self::assertNotSame(dirname($files[0]), dirname($files[2]));
        self::assertFileExists($files[0]);
        self::assertFileExists($files[1]);
    }

    public function testTheConfigurationIsTheFilesMergedInTheOrderOfAnEntryPoint(): void
    {
        $project = dirname(__DIR__, 3) . '/Fixtures/AbstractFileGuard/Project';
        $http = MergedConfiguration::of($project, 'http');
        $cli = MergedConfiguration::of($project, 'cli');

        self::assertSame('http', $http['transport'], 'the application\'s http.php');
        self::assertSame('cli', $cli['transport'], 'the application\'s cli.php');
        self::assertSame('the application', $http['marker'], 'the application\'s default.php');
        self::assertIsArray($http['beans']);
        self::assertSame(
            ['class' => HasherService::class],
            $http['beans'][HasherServiceInterface::class],
            'the framework\'s default.php',
        );
        self::assertIsArray($http['doctrine']);
        self::assertIsArray($http['doctrine']['typeOverrides']);
        self::assertSame(UuidType::class, $http['doctrine']['typeOverrides']['guid'], 'the package\'s default.php');
        self::assertArrayNotHasKey('assets', $http, 'the framework leaves the block to the application');
    }
}
