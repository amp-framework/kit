<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

/**
 * The code and the base language agree on the keys. Every string in the code and the templates that is written like a key
 * of a group the base language has must be a key of it (a typo shows its key in the page instead of its text), and every
 * key of the base language is written somewhere (a text nobody shows is dead). A key is named in the code as a string in
 * single quotes, never assembled, so that this can see it. An application extends the guard in one small class that names its project root
 * and its languages (AbstractTranslationGuard).
 */
abstract class TranslationKeyUsageGuard extends AbstractTranslationGuard
{
    /**
     * The directories, from the project's root, whose PHP files name keys: the source and the templates.
     *
     * @return list<string>
     */
    protected static function scannedDirectories(): array
    {
        return [static::sourceDirectory(), 'views'];
    }

    /**
     * Every string in single quotes that is written like a key (dotted groups of lowercase words, digits and hyphens) in the
     * PHP files of the scanned directories, with the files that write it, once each, in the order they are met.
     *
     * @return array<string, list<string>>
     */
    protected static function namedStrings(): array
    {
        $named = [];

        foreach (static::scannedDirectories() as $directory) {
            foreach (static::filesUnder($directory, '.php') as $file) {
                preg_match_all(
                    "/'([a-z0-9]+(?:-[a-z0-9]+)*(?:\\.[a-z0-9]+(?:-[a-z0-9]+)*)+)'/",
                    static::contentsOf($file),
                    $matches,
                );

                foreach (array_unique($matches[1]) as $string) {
                    $named[$string][] = $file;
                }
            }
        }

        return $named;
    }

    public function testEveryKeyTheCodeNamesExists(): void
    {
        $base = static::translationFile(static::baseLanguage());
        $keys = array_keys(static::texts(static::baseLanguage()));
        $known = array_fill_keys($keys, true);
        $groups = array_fill_keys(array_map(static fn (string $key): string => explode('.', $key)[0], $keys), true);
        $problems = [];

        foreach (static::namedStrings() as $string => $files) {
            $group = explode('.', $string)[0];

            if (!isset($groups[$group]) || isset($known[$string])) {
                continue;
            }
            $where = implode(', ', $files);
            $problems[] = 'The string "' . $string . '" in ' . $where . ' is written like a key of the group ' . $group
                . ' but is no key of ' . $base . '.';
        }

        $this->assertNoProblems($problems);
    }

    public function testEveryKeyOfTheBaseLanguageIsUsed(): void
    {
        $base = static::translationFile(static::baseLanguage());
        $named = static::namedStrings();
        $problems = [];

        foreach (array_keys(static::texts(static::baseLanguage())) as $key) {
            if (!isset($named[$key])) {
                $problems[] = 'The key "' . $key . '" of ' . $base . ' is named nowhere in '
                    . implode(', ', static::scannedDirectories()) . '.';
            }
        }

        $this->assertNoProblems($problems);
    }
}
