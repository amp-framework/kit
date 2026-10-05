<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Kit\Testing\Guard\AbstractTranslationGuard;
use ampf\Kit\Testing\Guard\TranslationKeyUsageGuard;
use ampf\Kit\Tests\Support\GuardFailures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The guard over an application whose code and templates name every key of its base language and no key that it lacks,
 * (tests/Fixtures/TranslationKeyUsageGuard/Abiding), and one that names a key with a typo and has two keys that nothing
 * names (Breaking). A guard is a TestCase, which takes its name:
 * PHP-CS-Fixer writes `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
#[CoversClass(AbstractTranslationGuard::class)]
#[CoversClass(TranslationKeyUsageGuard::class)]
final class TranslationKeyUsageGuardTest extends TestCase
{
    private const string BASE = 'config/translations/en.php';

    /** The guard over the application that names its keys right. */
    private static function abiding(): TranslationKeyUsageGuard
    {
        return new class('abiding') extends TranslationKeyUsageGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TranslationKeyUsageGuard/Abiding';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['en'];
            }
        };
    }

    /** The guard over the application that does not. */
    private static function breaking(): TranslationKeyUsageGuard
    {
        return new class('breaking') extends TranslationKeyUsageGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TranslationKeyUsageGuard/Breaking';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['en'];
            }
        };
    }

    public function testEveryStringWrittenLikeAKeyIsListedWithTheFilesThatWriteItOnce(): void
    {
        $guard = new class('named') extends TranslationKeyUsageGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TranslationKeyUsageGuard/Abiding';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['en'];
            }

            /**
             * @return array<string, list<string>>
             */
            public static function named(): array
            {
                return self::namedStrings();
            }

            /**
             * @return list<string>
             */
            public static function scanned(): array
            {
                return self::scannedDirectories();
            }
        };
        $page = 'src/Controller/PageController.php';

        self::assertSame(['src', 'views'], $guard::scanned());
        self::assertSame(
            [
                'app.title' => [$page],
                'app.count' => [$page],
                'composer.json' => [$page],
                'image.png' => [$page],
                'jquery.min.js' => [$page],
                'app.greeting' => ['views/http/home.html.php'],
                'app.note' => ['views/http/home.html.php'],
            ],
            $guard::named(),
        );
    }

    public function testAKeyThatTheFilesOfTwoDirectoriesNameIsListedWithBoth(): void
    {
        $guard = new class('two') extends TranslationKeyUsageGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TranslationKeyUsageGuard/Breaking';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['en'];
            }

            /**
             * @return array<string, list<string>>
             */
            public static function named(): array
            {
                return self::namedStrings();
            }
        };

        self::assertSame(
            [
                'app.greeting' => ['src/Service/Mailer.php', 'views/http/page.html.php'],
                'app.titel' => ['src/Service/Mailer.php', 'views/http/page.html.php'],
            ],
            $guard::named(),
        );
    }

    public function testAnApplicationWhoseCodeNamesEveryKeyAndNoOtherPasses(): void
    {
        $guard = self::abiding();

        $guard->testEveryKeyTheCodeNamesExists();
        $guard->testEveryKeyOfTheBaseLanguageIsUsed();

        self::assertSame(2, $guard->numberOfAssertionsPerformed());
    }

    public function testAKeyWithATypoFails(): void
    {
        self::assertSame(
            'The string "app.titel" in src/Service/Mailer.php, views/http/page.html.php is written like a key of the group'
            . ' app but is no key of ' . self::BASE . '.',
            GuardFailures::message(self::breaking()->testEveryKeyTheCodeNamesExists(...)),
        );
    }

    public function testKeysThatNothingNamesFail(): void
    {
        self::assertSame(
            'The key "app.title" of ' . self::BASE . ' is named nowhere in src, views.' . PHP_EOL
            . 'The key "app.unused" of ' . self::BASE . ' is named nowhere in src, views.',
            GuardFailures::message(self::breaking()->testEveryKeyOfTheBaseLanguageIsUsed(...)),
        );
    }

    public function testAnApplicationNamesTheDirectoriesItsCodeIsIn(): void
    {
        $guard = new class('views only') extends TranslationKeyUsageGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TranslationKeyUsageGuard/Abiding';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['en'];
            }

            /**
             * @return list<string>
             */
            protected static function scannedDirectories(): array
            {
                return ['views'];
            }
        };

        self::assertSame(
            'The key "app.count" of ' . self::BASE . ' is named nowhere in views.' . PHP_EOL
            . 'The key "app.title" of ' . self::BASE . ' is named nowhere in views.',
            GuardFailures::message($guard->testEveryKeyOfTheBaseLanguageIsUsed(...)),
        );
    }

    public function testTheSourceDirectoryIsTheOneTheApplicationNames(): void
    {
        $guard = new class('source') extends TranslationKeyUsageGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['en'];
            }

            protected static function sourceDirectory(): string
            {
                return 'app';
            }

            /**
             * @return list<string>
             */
            public static function scanned(): array
            {
                return self::scannedDirectories();
            }
        };

        self::assertSame(['app', 'views'], $guard::scanned());
    }
}
