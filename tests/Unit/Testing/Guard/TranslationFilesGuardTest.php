<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Kit\Testing\Guard\AbstractTranslationGuard;
use ampf\Kit\Testing\Guard\TranslationFilesGuard;
use ampf\Kit\Tests\Support\GuardFailures;
use IntlException;
use MessageFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The guard over translation files that hold together (tests/Fixtures/TranslationFilesGuard/Abiding), and over seven languages that each break one rule (Breaking: German
 * lacks a key and has another, French has keys that are not sorted and one that is no key, Italian takes other
 * arguments, Spanish has texts that are not safe to print, Dutch an ICU message that is none, Polish an ICU message
 * with another argument, and a file of no language); and what the guard counts as an argument and as unsafe. A guard
 * is a TestCase, which takes its name: PHP-CS-Fixer writes `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
#[CoversClass(AbstractTranslationGuard::class)]
#[CoversClass(TranslationFilesGuard::class)]
final class TranslationFilesGuardTest extends TestCase
{
    private const string DIRECTORY = 'config/translations/';

    /** @return iterable<string, array{string, list<int>}> a text, and the numbers of the arguments that it takes */
    public static function arguments(): iterable
    {
        yield 'nothing' => ['', []];
        yield 'a text without arguments' => ['Hello, world', []];
        yield 'a string' => ['Hello, %s!', [1]];
        yield 'a number' => ['%d items', [1]];
        yield 'two strings' => ['%s and %s', [1, 2]];
        yield 'numbered arguments' => ['%1$s and %2$s', [1, 2]];
        yield 'numbered arguments in another order' => ['%2$s and %1$s', [1, 2]];
        yield 'numbered and plain arguments count apart' => ['%1$s and %s', [1, 1]];
        yield 'a plain argument after a numbered one' => ['%2$s and %s', [1, 2]];
        yield 'a number that is more than one digit' => ['%12$s', [12]];
        yield 'a percent sign' => ['100%% sure', []];
        yield 'a percent sign and an argument' => ['100%% of %s', [1]];
        yield 'a percent sign before a letter' => ['%%s', []];
        yield 'a conversion that is none of the two' => ['%x', []];
    }

    /** @return iterable<string, array{string, list<string>}> a text, and the names of the arguments of the ICU message */
    public static function messageArguments(): iterable
    {
        yield 'nothing' => ['', []];
        yield 'a text without braces' => ['Hello, world', []];
        yield 'an argument' => ['Hello, {name}!', ['name']];
        yield 'an argument with spaces' => ['Hello, { name }!', ['name']];
        yield 'a plural' => ['{count, plural, one {# item} other {# items}}', ['count']];
        yield 'arguments, sorted' => ['{b} and {a}', ['a', 'b']];
        yield 'an argument twice' => ['{a} and {a}', ['a']];
        yield 'an argument inside a plural' => ['{count, plural, one {# {name}} other {# {name}s}}', ['count', 'name']];
        yield 'a name that starts with an underscore' => ['{_x}', ['_x']];
        yield 'a number' => ['{0}', []];
        yield 'a brace that is not closed' => ['{ name', []];
    }

    /** @return iterable<string, array{string, string, list<string>}> a key, its text, and what is wrong with it */
    public static function printable(): iterable
    {
        yield 'a text that is fine' => ['a.text', 'Some <strong>strong</strong> and <em>weak</em> words &amp; more &#8212; &mdash;', []];
        yield 'a double quote' => ['a.text', 'Say "hello"', ['has a double quote: write &quot; or a typographic one.']];
        yield 'an ampersand' => ['a.text', 'Tom & Jerry', ['has an ampersand that is no entity: write &amp;.']];
        yield 'an ampersand before an entity' => ['a.text', 'Tom && Jerry &amp; Co', ['has an ampersand that is no entity: write &amp;.']];
        yield 'an entity without its semicolon' => ['a.text', 'Tom &amp Jerry', ['has an ampersand that is no entity: write &amp;.']];
        yield 'entities' => ['a.text', '&lt; &#60; &#x3C; &Auml;', []];
        yield 'markup' => ['a.text', 'Some <b>bold</b> words', ['has markup other than <strong> and <em>.']];
        yield 'a closing tag only' => ['a.text', 'Some words</p>', ['has markup other than <strong> and <em>.']];
        yield 'an angle bracket' => ['a.text', 'a > b', ['has markup other than <strong> and <em>.']];
        yield 'a title that is plain' => ['a.title', 'Plain title', []];
        yield 'a title with an entity' => ['a.title', 'Tom &amp; Jerry', ['is a title: plain text, no markup and no entity.']];
        yield 'a title with markup' => ['a.title', 'The <em>best</em>', ['is a title: plain text, no markup and no entity.']];
        yield 'a title with an angle bracket' => ['a.title', 'a > b', [
            'has markup other than <strong> and <em>.',
            'is a title: plain text, no markup and no entity.',
        ]];
        yield 'a key that ends in title without a dot' => ['a.subtitle', 'Tom &amp; Jerry', []];
        yield 'a key that has title inside' => ['a.title.more', 'Tom &amp; Jerry', []];
        yield 'everything at once' => ['a.title', 'Say "<b>x</b>" & more', [
            'has a double quote: write &quot; or a typographic one.',
            'has an ampersand that is no entity: write &amp;.',
            'has markup other than <strong> and <em>.',
            'is a title: plain text, no markup and no entity.',
        ]];
    }

    /** @return iterable<string, array{string, string, bool}> a language, a text, and whether the text is an ICU message that fails */
    public static function icuMessages(): iterable
    {
        yield 'a text without braces' => ['en', 'Just words', false];
        yield 'a brace that closes nothing' => ['en', 'a } b', false];
        yield 'a text that is no UTF-8' => ['en', "caf\xe9", true];
        yield 'a message' => ['en', 'Hello, {name}!', false];
        yield 'a plural' => ['en', '{count, plural, one {# item} other {# items}}', false];
        yield 'a plural in a language with more forms' => ['pl', '{count, plural, one {# a} few {# b} other {# c}}', false];
        yield 'a message that is not closed' => ['en', '{count, plural, one {# item}', true];
        yield 'a brace that opens nothing' => ['en', 'a { b', true];
        yield 'a plural without its other' => ['en', '{count, plural, one {# item}}', true];
    }

    /** The guard over the languages that keep the rules. */
    private static function abiding(): TranslationFilesGuard
    {
        return new class('abiding') extends TranslationFilesGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TranslationFilesGuard/Abiding';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['en', 'de'];
            }
        };
    }

    /** The guard over the languages that break them, English the base. */
    private static function breaking(): TranslationFilesGuard
    {
        return new class('breaking') extends TranslationFilesGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TranslationFilesGuard/Breaking';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['en', 'de', 'fr', 'it', 'es', 'nl', 'pl'];
            }
        };
    }

    /**
     * @param list<int> $numbers
     */
    #[DataProvider('arguments')]
    public function testAnArgumentIsAPlaceholderOfSprintfThatIsAStringOrANumber(string $text, array $numbers): void
    {
        $guard = new class('arguments') extends TranslationFilesGuard {
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

            /**
             * @return list<int>
             */
            public static function numbersOf(string $text): array
            {
                return self::argumentsOf($text);
            }
        };

        self::assertSame($numbers, $guard::numbersOf($text));
    }

    /**
     * @param list<string> $names
     */
    #[DataProvider('messageArguments')]
    public function testAMessageArgumentIsAName(string $text, array $names): void
    {
        $guard = new class('message arguments') extends TranslationFilesGuard {
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

            /**
             * @return list<string>
             */
            public static function namesOf(string $text): array
            {
                return self::messageArgumentsOf($text);
            }
        };

        self::assertSame($names, $guard::namesOf($text));
    }

    /**
     * @param list<string> $problems
     */
    #[DataProvider('printable')]
    public function testATextIsSafeToPrintWhenItHasNoQuoteNoBareAmpersandAndNoMarkupButTwoTags(
        string $key,
        string $text,
        array $problems,
    ): void {
        $guard = new class('printable') extends TranslationFilesGuard {
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

            /**
             * @return list<string>
             */
            public static function problemsOf(string $key, string $text): array
            {
                return self::printProblems($key, $text);
            }
        };

        self::assertSame($problems, $guard::problemsOf($key, $text));
    }

    #[DataProvider('icuMessages')]
    public function testATextFailsWhenIcuCannotReadItAsAMessageOfItsLanguage(
        string $language,
        string $text,
        bool $fails,
    ): void {
        $guard = new class('icu') extends TranslationFilesGuard {
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

            public static function errorOf(string $language, string $text): ?string
            {
                return self::icuError($language, $text);
            }
        };

        if (!$fails) {
            self::assertNull($guard::errorOf($language, $text));

            return;
        }

        try {
            new MessageFormatter($language, $text);
            self::fail('ICU accepted the message.');
        } catch (IntlException $exception) {
            self::assertSame($exception->getMessage(), $guard::errorOf($language, $text));
            self::assertNotSame('', $exception->getMessage());
        }
    }

    public function testLanguagesThatKeepTheRulesPass(): void
    {
        $guard = self::abiding();
        $tests = [
            $guard->testTheKeysAreNamedInDottedGroupsAndSorted(...),
            $guard->testALanguageHasExactlyTheKeysOfTheBaseLanguage(...),
            $guard->testALanguageTakesTheArgumentsTheBaseLanguageTakes(...),
            $guard->testTheTextsAreSafeToPrintAsTheyAre(...),
            $guard->testATextIsAnIcuMessageThatItsLanguageCanFormat(...),
            $guard->testALanguageTakesTheMessageArgumentsTheBaseLanguageTakes(...),
        ];

        foreach ($tests as $test) {
            self::assertSame([], GuardFailures::of($test, $guard::translations()));
        }

        $guard->testEveryLanguageHasAFileAndEveryFileALanguage();

        self::assertSame(13, $guard->numberOfAssertionsPerformed());
    }

    public function testALanguageWithoutAFileAndAFileWithoutALanguageFail(): void
    {
        $guard = new class('files') extends TranslationFilesGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TranslationFilesGuard/Breaking';
            }

            /**
             * @return list<string>
             */
            protected static function languages(): array
            {
                return ['en', 'de', 'fr', 'it', 'es', 'nl', 'pl', 'gr'];
            }
        };

        self::assertSame(
            'The language gr has no translation file ' . self::DIRECTORY . 'gr.php.' . PHP_EOL
            . 'The translation file ' . self::DIRECTORY . 'xx.php belongs to no language: name its code in languages() or'
            . ' delete the file.',
            GuardFailures::message($guard->testEveryLanguageHasAFileAndEveryFileALanguage(...)),
        );
    }

    public function testKeysThatAreNotDottedGroupsOfWordsOrNotSortedFail(): void
    {
        $guard = self::breaking();
        $test = $guard->testTheKeysAreNamedInDottedGroupsAndSorted(...);

        self::assertSame(
            [
                'fr' => 'The key "Bad_Key" of ' . self::DIRECTORY . 'fr.php is not written in dotted groups of lowercase'
                    . ' words, digits and hyphens (such as account.password-hint).' . PHP_EOL
                    . 'The keys of ' . self::DIRECTORY . 'fr.php are not sorted: "b.msg" stands where "Bad_Key" belongs.',
            ],
            GuardFailures::of($test, $guard::translations()),
        );
    }

    public function testALanguageThatLacksOrAddsAKeyOfTheBaseLanguageFails(): void
    {
        $guard = self::breaking();
        $test = $guard->testALanguageHasExactlyTheKeysOfTheBaseLanguage(...);

        self::assertSame(
            [
                'de' => 'The translation file ' . self::DIRECTORY . 'de.php lacks the key "c.title" of ' . self::DIRECTORY
                    . 'en.php.' . PHP_EOL
                    . 'The translation file ' . self::DIRECTORY . 'de.php has the key "z.extra", which ' . self::DIRECTORY
                    . 'en.php does not have.',
                'fr' => 'The translation file ' . self::DIRECTORY . 'fr.php has the key "Bad_Key", which ' . self::DIRECTORY
                    . 'en.php does not have.',
            ],
            GuardFailures::of($test, $guard::translations()),
        );
    }

    public function testATextThatTakesOtherArgumentsThanTheBaseLanguagesFails(): void
    {
        $guard = self::breaking();
        $test = $guard->testALanguageTakesTheArgumentsTheBaseLanguageTakes(...);

        self::assertSame(
            [
                'it' => 'The text a.one of ' . self::DIRECTORY . 'it.php takes other arguments than the same text of '
                    . self::DIRECTORY . 'en.php.',
            ],
            GuardFailures::of($test, $guard::translations()),
        );
    }

    public function testTextsThatAreNotSafeToPrintFail(): void
    {
        $guard = self::breaking();
        $test = $guard->testTheTextsAreSafeToPrintAsTheyAre(...);

        self::assertSame(
            [
                'es' => 'The text a.one of ' . self::DIRECTORY . 'es.php has a double quote: write &quot; or a typographic'
                    . ' one.' . PHP_EOL
                    . 'The text a.one of ' . self::DIRECTORY . 'es.php has an ampersand that is no entity: write &amp;.'
                    . PHP_EOL
                    . 'The text a.one of ' . self::DIRECTORY . 'es.php has markup other than <strong> and <em>.' . PHP_EOL
                    . 'The text c.title of ' . self::DIRECTORY . 'es.php is a title: plain text, no markup and no entity.',
            ],
            GuardFailures::of($test, $guard::translations()),
        );
    }

    public function testATextWithBracesThatIsNoIcuMessageFailsWithWhatIcuSays(): void
    {
        $guard = self::breaking();
        $test = $guard->testATextIsAnIcuMessageThatItsLanguageCanFormat(...);

        try {
            new MessageFormatter('nl', '{count, plural, one {# ding}');
            self::fail('ICU accepted the message.');
        } catch (IntlException $exception) {
            $reason = $exception->getMessage();
        }

        self::assertNotSame('', $reason);
        self::assertSame(
            ['nl' => 'The text b.msg of ' . self::DIRECTORY . 'nl.php is no ICU message: ' . $reason],
            GuardFailures::of($test, $guard::translations()),
        );
    }

    public function testAnIcuMessageThatTakesOtherArgumentsThanTheBaseLanguagesFails(): void
    {
        $guard = self::breaking();
        $test = $guard->testALanguageTakesTheMessageArgumentsTheBaseLanguageTakes(...);

        self::assertSame(
            [
                'pl' => 'The text b.msg of ' . self::DIRECTORY . 'pl.php takes other message arguments than the same text of '
                    . self::DIRECTORY . 'en.php.',
            ],
            GuardFailures::of($test, $guard::translations()),
        );
    }
}
