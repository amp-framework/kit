<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

/**
 * The base of the guards over an application's translation files (`config/translations/<code>.php`, each returning the
 * texts by key): the languages that the application names, the first of them the base language that the others are
 * compared with, and the texts of a language by their keys. An application names the languages in the small class of
 * its tests:
 *
 *     protected static function languages(): array { return ['de', 'en']; }
 */
abstract class AbstractTranslationGuard extends AbstractFileGuard
{
    /**
     * Each language of the application.
     *
     * @return iterable<string, array{string}> the language's code
     */
    public static function translations(): iterable
    {
        foreach (static::languages() as $language) {
            yield $language => [$language];
        }
    }

    /**
     * The codes of the application's languages (`de`, `en`), the base language first.
     *
     * @return list<string>
     */
    abstract protected static function languages(): array;

    /** The language that the others are compared with: the first one. */
    protected static function baseLanguage(): string
    {
        $language = array_first(static::languages());
        assert(is_string($language));

        return $language;
    }

    /** The directory of the translation files, relative to the project root. */
    protected static function translationDirectory(): string
    {
        return 'config/translations';
    }

    /** The translation file of the language, from the project's root. */
    protected static function translationFile(string $language): string
    {
        return static::translationDirectory() . '/' . $language . '.php';
    }

    /**
     * The texts of the language by their keys, as its translation file returns them.
     *
     * @return array<string, string>
     */
    protected static function texts(string $language): array
    {
        $file = static::translationFile($language);
        $texts = (static fn (string $__file): mixed => require $__file)(static::existingFile($file));

        if (!is_array($texts)) {
            self::fail('The translation file ' . $file . ' does not return an array.');
        }
        $checked = [];

        foreach ($texts as $key => $text) {
            if (!is_string($key)) {
                self::fail('The translation file ' . $file . ' has the key ' . $key . ', which is no text.');
            }

            if (!is_string($text)) {
                self::fail('The translation file ' . $file . ' gives the key ' . $key . ' something that is no text.');
            }
            $checked[$key] = $text;
        }

        return $checked;
    }
}
