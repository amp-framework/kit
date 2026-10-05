<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Doctrine\Type\UuidType;
use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Service\Hasher\HasherService;
use ampf\Service\Hasher\HasherServiceInterface;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * What the guards that read an application's files have in common, over a small project (tests/Fixtures/AbstractFileGuard):
 * the files of a directory by their suffix, the contents of a file, the configuration as the entry points merge it
 * (ampf's files, the package's, the application's) and the assertion that lists every problem at once. A guard is a
 * TestCase, which takes its name: PHP-CS-Fixer writes `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
final class AbstractFileGuardTest extends TestCase
{
    /**
     * @param array<string, mixed> $configuration
     */
    private static function beanClass(array $configuration, string $bean): mixed
    {
        $beans = $configuration['beans'];
        self::assertIsArray($beans);
        $definition = $beans[$bean];
        self::assertIsArray($definition);

        return $definition['class'];
    }

    public function testTheFilesOfADirectoryAreListedByTheirSuffixWithTheirPathsInOrder(): void
    {
        $guard = new class('files') extends AbstractFileGuard {
            /**
             * @return array<string, string>
             */
            public static function templates(): array
            {
                return self::filesUnder('views/http', '.html.php');
            }

            /**
             * @return array<string, string>
             */
            public static function texts(): array
            {
                return self::filesUnder('views/http', '.txt');
            }

            /**
             * @return array<string, string>
             */
            public static function ofConfiguration(): array
            {
                return self::filesUnder('config', '.php');
            }

            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractFileGuard/Project';
            }
        };

        self::assertSame(
            [
                'a.html.php' => 'views/http/a.html.php',
                'b.html.php' => 'views/http/b.html.php',
                'partials/c.html.php' => 'views/http/partials/c.html.php',
                'partials/deep/d.html.php' => 'views/http/partials/deep/d.html.php',
            ],
            $guard::templates(),
            'a suffix is the end of the name; the paths are below the directory and from the project, sorted',
        );
        self::assertSame(['readme.txt' => 'views/http/readme.txt'], $guard::texts());
        self::assertSame(
            [
                'cli.php' => 'config/cli.php',
                'default.php' => 'config/default.php',
                'http.php' => 'config/http.php',
            ],
            $guard::ofConfiguration(),
        );
    }

    public function testADirectoryThatIsNotThereFailsAndSaysWhich(): void
    {
        $guard = new class('missing') extends AbstractFileGuard {
            /**
             * @return array<string, string>
             */
            public static function templates(): array
            {
                return self::filesUnder('views/web', '.html.php');
            }

            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractFileGuard/Project';
            }
        };

        try {
            $guard::templates();
            self::fail('The guard passed.');
        } catch (AssertionFailedError $e) {
            self::assertSame('The project has no directory views/web.', $e->getMessage());
        }
    }

    public function testTheContentsOfAFileAreReadFromTheProject(): void
    {
        $guard = new class('contents') extends AbstractFileGuard {
            public static function notes(): string
            {
                return self::contentsOf('docs/notes.md');
            }

            public static function nothing(): string
            {
                return self::contentsOf('docs/missing.md');
            }

            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractFileGuard/Project';
            }
        };

        self::assertSame("The notes of the project.\nA second line.\n", $guard::notes());

        try {
            $guard::nothing();
            self::fail('The guard passed.');
        } catch (AssertionFailedError $e) {
            self::assertSame('The project has no file docs/missing.md.', $e->getMessage());
        }
    }

    public function testAFileOfTheProjectIsGivenByItsPathOrFailsWhenItIsNotThere(): void
    {
        $guard = new class('path') extends AbstractFileGuard {
            public static function pathOf(string $file): string
            {
                return self::existingFile($file);
            }

            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractFileGuard/Project';
            }
        };

        self::assertSame(
            __DIR__ . '/../../../Fixtures/AbstractFileGuard/Project/docs/notes.md',
            $guard::pathOf('docs/notes.md'),
        );

        foreach (['docs/missing.md', 'docs'] as $missing) {
            try {
                $guard::pathOf($missing);
                self::fail('The guard passed.');
            } catch (AssertionFailedError $e) {
                self::assertSame(
                    'The project has no file ' . $missing . '.',
                    $e->getMessage(),
                    'a directory is no file',
                );
            }
        }
    }

    public function testTheSourceIsInSrcUnlessTold(): void
    {
        $guard = new class('source') extends AbstractFileGuard {
            public static function directory(): string
            {
                return self::sourceDirectory();
            }

            protected static function projectRoot(): string
            {
                return __DIR__;
            }
        };

        self::assertSame('src', $guard::directory());
    }

    public function testTheConfigurationIsTheEntryPointsMergeFrameworkPackageApplication(): void
    {
        $guard = new class('configuration') extends AbstractFileGuard {
            /**
             * @return array<string, mixed>
             */
            public static function of(string $transport): array
            {
                return self::configuration($transport);
            }

            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractFileGuard/Project';
            }
        };
        $http = $guard::of('http');
        $cli = $guard::of('cli');

        self::assertSame('http', $http['transport'], 'the application\'s http.php');
        self::assertSame('cli', $cli['transport'], 'the application\'s cli.php');
        self::assertSame('the application', $http['marker'], 'the application\'s default.php');
        self::assertSame(
            HasherService::class,
            self::beanClass($http, HasherServiceInterface::class),
            'the framework\'s default.php',
        );
        self::assertSame('ampf\Router\HttpRouter', self::beanClass($http, 'Router'), 'the framework\'s http.php');
        self::assertSame('ampf\Router\CliRouter', self::beanClass($cli, 'Router'), 'the framework\'s cli.php');
        self::assertIsArray($http['doctrine']);
        self::assertIsArray($http['doctrine']['typeOverrides']);
        self::assertSame(UuidType::class, $http['doctrine']['typeOverrides']['guid'], 'the package\'s doctrine block');
        self::assertSame(
            ['custom' => 'text'],
            $http['doctrine']['mappingOverrides'],
            'the application\'s default.php replaces a block of the package\'s as a whole',
        );
    }

    public function testTheFilesBeforeTheApplicationsAreTheFrameworksTwoAndThePackagesOne(): void
    {
        $guard = new class('files before') extends AbstractFileGuard {
            /**
             * @return list<string>
             */
            public static function before(string $transport): array
            {
                return self::packageConfigurationFiles($transport);
            }

            protected static function projectRoot(): string
            {
                return __DIR__;
            }
        };
        $files = $guard::before('cli');

        self::assertCount(3, $files);
        self::assertStringEndsWith('/config/default.php', $files[0]);
        self::assertStringEndsWith('/config/cli.php', $files[1]);
        self::assertSame(dirname(__DIR__, 4) . '/config/default.php', $files[2]);
        self::assertSame(dirname($files[0]), dirname($files[1]), 'the framework\'s two files are in one directory');
        self::assertNotSame(dirname($files[0]), dirname($files[2]));
        self::assertFileExists($files[0]);
        self::assertFileExists($files[1]);
    }

    public function testNoProblemsIsAnAssertionAndProblemsFailWithAllOfThem(): void
    {
        $guard = new class('problems') extends AbstractFileGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            /**
             * @param list<string> $problems
             */
            public function check(array $problems): void
            {
                $this->assertNoProblems($problems);
            }
        };

        $guard->check([]);
        self::assertSame(1, $guard->numberOfAssertionsPerformed());

        try {
            $guard->check(['The first thing is wrong.', 'The second thing is wrong.']);
            self::fail('The guard passed.');
        } catch (AssertionFailedError $e) {
            self::assertSame(
                'The first thing is wrong.' . PHP_EOL . 'The second thing is wrong.',
                $e->getMessage(),
            );
        }

        self::assertSame(1, $guard->numberOfAssertionsPerformed(), 'a failure is no assertion made');
    }
}
