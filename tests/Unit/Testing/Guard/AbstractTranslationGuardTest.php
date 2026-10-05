<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Kit\Testing\Guard\AbstractTranslationGuard;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * What the guards over an application's translation files have in common, over a small project
 * (tests/Fixtures/AbstractTranslationGuard): the languages that the application names, the first of them the base
 * language, the file of each and the texts in it by their keys, and the keys of the package's own texts. A guard is a
 * TestCase, which takes its name: PHP-CS-Fixer writes `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
#[CoversClass(AbstractTranslationGuard::class)]
final class AbstractTranslationGuardTest extends TestCase
{
    public function testTheTranslationsAreTheLanguagesInTheOrderTheApplicationNamesThem(): void
    {
        $guard = new class('translations') extends AbstractTranslationGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['de', 'en'];
            }
        };

        self::assertSame(['de' => ['de'], 'en' => ['en']], iterator_to_array($guard::translations()));
    }

    public function testTheFirstLanguageIsTheBaseAndTheTextsAreThoseOfTheFilesInConfigTranslationsUnlessTold(): void
    {
        $guard = new class('languages') extends AbstractTranslationGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractTranslationGuard/Project';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['de', 'en'];
            }

            /**
             * @return array<string, string>
             */
            public static function textsOf(string $language): array
            {
                return self::texts($language);
            }

            public static function base(): string
            {
                return self::baseLanguage();
            }

            public static function fileOf(string $language): string
            {
                return self::translationFile($language);
            }
        };

        self::assertSame('de', $guard::base());
        self::assertSame('config/translations/de.php', $guard::fileOf('de'));
        self::assertSame('config/translations/en.php', $guard::fileOf('en'));
        self::assertSame(['a.one' => 'Eins', 'b.two' => 'Zwei %s'], $guard::textsOf('de'));
        self::assertSame(['a.one' => 'One', 'b.two' => 'Two %s', 'c.three' => 'Three'], $guard::textsOf('en'));
    }

    public function testAnApplicationNamesItsBaseLanguageAndItsDirectory(): void
    {
        $guard = new class('base') extends AbstractTranslationGuard {
            public static function base(): string
            {
                return self::baseLanguage();
            }

            public static function fileOf(string $language): string
            {
                return self::translationFile($language);
            }

            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['de', 'en'];
            }

            protected static function baseLanguage(): string
            {
                return 'en';
            }

            protected static function translationDirectory(): string
            {
                return 'translations';
            }
        };

        self::assertSame('en', $guard::base());
        self::assertSame('translations/de.php', $guard::fileOf('de'));
    }

    public function testAFileThatIsNotThereOrNotTextsByKeyFailsAndSaysWhich(): void
    {
        $guard = new class('broken') extends AbstractTranslationGuard {
            /**
             * @return array<string, string>
             */
            public static function textsOf(string $language): array
            {
                return self::texts($language);
            }

            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractTranslationGuard/Broken';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['notarray'];
            }
        };

        foreach (
            [
                'missing' => 'The project has no file config/translations/missing.php.',
                'notarray' => 'The translation file config/translations/notarray.php does not return an array.',
                'intkey' => 'The translation file config/translations/intkey.php has the key 0, which is no text.',
                'nottext' => 'The translation file config/translations/nottext.php gives the key c.d something that is no'
                    . ' text.',
            ] as $language => $message
        ) {
            try {
                $guard::textsOf($language);
                self::fail('The guard passed.');
            } catch (AssertionFailedError $e) {
                self::assertSame($message, $e->getMessage());
            }
        }
    }
}
