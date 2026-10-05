<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use IntlException;
use MessageFormatter;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The translation files hold together: a file for every language and a language for every file, keys written in dotted
 * groups of lowercase words and sorted, the same keys in each (the first language is the base), the same arguments in
 * each text, texts that are safe to print as they are and that ICU can read as messages of their language (a plain text
 * is one). An application extends the guard in one small class that names its project root and its languages
 * (AbstractTranslationGuard).
 */
abstract class TranslationFilesGuard extends AbstractTranslationGuard
{
    /** What a key looks like: dotted groups of lowercase words, digits and hyphens. */
    protected const string KEY = '/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\.[a-z0-9]+(?:-[a-z0-9]+)*)+$/D';

    /**
     * The numbers of the arguments that a text takes, sorted: `%s` and `%d` count up on their own, `%2$s` names its
     * number, `%%` is a percent sign.
     *
     * @return list<int>
     */
    protected static function argumentsOf(string $text): array
    {
        preg_match_all('/%(?:(\d+)\$)?[sd]/', str_replace('%%', '', $text), $matches);
        $numbers = [];
        $next = 1;

        foreach ($matches[1] as $number) {
            if ($number === '') {
                $numbers[] = $next;
                $next++;
            } else {
                $numbers[] = (int)$number;
            }
        }
        sort($numbers);

        return $numbers;
    }

    /**
     * The names of the arguments of an ICU message (`{count, plural, …}`, `{name}`), sorted; none for a text that is no
     * message.
     *
     * @return list<string>
     */
    protected static function messageArgumentsOf(string $text): array
    {
        preg_match_all('/\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*[,}]/', $text, $matches);
        $names = array_unique($matches[1]);
        sort($names);

        return $names;
    }

    /**
     * What is wrong with a text that is printed as it is, in the words that continue "The text <key> of <file> …": a
     * double quote, an ampersand that is no entity, markup but `<strong>` and `<em>`, and, for a title (a key that ends in
     * `.title`), any markup or entity.
     *
     * @return list<string>
     */
    protected static function printProblems(string $key, string $text): array
    {
        $withoutTags = preg_replace('~</?(?:strong|em)>~', '', $text);
        assert(is_string($withoutTags));
        $problems = [];

        if (str_contains($text, '"')) {
            $problems[] = 'has a double quote: write &quot; or a typographic one.';
        }

        if (preg_match('/&(?!(?:[a-z][a-z0-9]*|#[0-9]+|#x[0-9a-f]+);)/i', $text) === 1) {
            $problems[] = 'has an ampersand that is no entity: write &amp;.';
        }

        if (preg_match('/[<>]/', $withoutTags) === 1) {
            $problems[] = 'has markup other than <strong> and <em>.';
        }

        if (str_ends_with($key, '.title') && preg_match('/[<>&]/', $text) === 1) {
            $problems[] = 'is a title: plain text, no markup and no entity.';
        }

        return $problems;
    }

    /**
     * What ICU says against a text as an ICU message of the language (a text without braces is one, and a plain one); null
     * for a text that ICU can read.
     */
    protected static function icuError(string $language, string $text): ?string
    {
        try {
            new MessageFormatter($language, $text);
        } catch (IntlException $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    public function testEveryLanguageHasAFileAndEveryFileALanguage(): void
    {
        $files = array_map(
            static fn (string $below): string => substr($below, 0, -strlen('.php')),
            array_keys(static::filesUnder(static::translationDirectory(), '.php')),
        );
        $problems = [];

        foreach (array_diff(static::languages(), $files) as $language) {
            $problems[] = 'The language ' . $language . ' has no translation file ' . static::translationFile($language)
                . '.';
        }

        foreach (array_diff($files, static::languages()) as $language) {
            $problems[] = 'The translation file ' . static::translationFile($language) . ' belongs to no language: name'
                . ' its code in languages() or delete the file.';
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('translations')]
    public function testTheKeysAreNamedInDottedGroupsAndSorted(string $language): void
    {
        $file = static::translationFile($language);
        $keys = array_keys(static::texts($language));
        $sorted = $keys;
        sort($sorted, SORT_STRING);
        $problems = [];

        foreach ($keys as $key) {
            if (preg_match(static::KEY, $key) !== 1) {
                $problems[] = 'The key "' . $key . '" of ' . $file . ' is not written in dotted groups of lowercase words,'
                    . ' digits and hyphens (such as account.password-hint).';
            }
        }
        $misplaced = array_key_first(array_diff_assoc($keys, $sorted));

        if ($misplaced !== null) {
            $problems[] = 'The keys of ' . $file . ' are not sorted: "' . $keys[$misplaced] . '" stands where "'
                . $sorted[$misplaced] . '" belongs.';
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('translations')]
    public function testALanguageHasExactlyTheKeysOfTheBaseLanguage(string $language): void
    {
        $file = static::translationFile($language);
        $base = static::translationFile(static::baseLanguage());
        $baseKeys = array_keys(static::texts(static::baseLanguage()));
        $keys = array_keys(static::texts($language));
        $problems = [];

        foreach (array_diff($baseKeys, $keys) as $key) {
            $problems[] = 'The translation file ' . $file . ' lacks the key "' . $key . '" of ' . $base . '.';
        }

        foreach (array_diff($keys, $baseKeys) as $key) {
            $problems[] = 'The translation file ' . $file . ' has the key "' . $key . '", which ' . $base
                . ' does not have.';
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('translations')]
    public function testALanguageTakesTheArgumentsTheBaseLanguageTakes(string $language): void
    {
        $file = static::translationFile($language);
        $base = static::translationFile(static::baseLanguage());
        $texts = static::texts($language);
        $problems = [];

        foreach (static::texts(static::baseLanguage()) as $key => $text) {
            if (isset($texts[$key]) && static::argumentsOf($text) !== static::argumentsOf($texts[$key])) {
                $problems[] = 'The text ' . $key . ' of ' . $file . ' takes other arguments than the same text of '
                    . $base . '.';
            }
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('translations')]
    public function testTheTextsAreSafeToPrintAsTheyAre(string $language): void
    {
        $file = static::translationFile($language);
        $problems = [];

        foreach (static::texts($language) as $key => $text) {
            foreach (static::printProblems($key, $text) as $problem) {
                $problems[] = 'The text ' . $key . ' of ' . $file . ' ' . $problem;
            }
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('translations')]
    public function testATextIsAnIcuMessageThatItsLanguageCanFormat(string $language): void
    {
        $file = static::translationFile($language);
        $problems = [];

        foreach (static::texts($language) as $key => $text) {
            $error = static::icuError($language, $text);

            if ($error !== null) {
                $problems[] = 'The text ' . $key . ' of ' . $file . ' is no ICU message: ' . $error;
            }
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('translations')]
    public function testALanguageTakesTheMessageArgumentsTheBaseLanguageTakes(string $language): void
    {
        $file = static::translationFile($language);
        $base = static::translationFile(static::baseLanguage());
        $texts = static::texts($language);
        $problems = [];

        foreach (static::texts(static::baseLanguage()) as $key => $text) {
            if (isset($texts[$key]) && static::messageArgumentsOf($text) !== static::messageArgumentsOf($texts[$key])) {
                $problems[] = 'The text ' . $key . ' of ' . $file . ' takes other message arguments than the same text of '
                    . $base . '.';
            }
        }

        $this->assertNoProblems($problems);
    }
}
