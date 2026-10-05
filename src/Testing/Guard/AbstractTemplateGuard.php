<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

/**
 * The base of the guards over an application's templates: the web's templates, which are the files that end in
 * `.html.php` below `views/http` at any depth, and a template as markup, without the PHP in it. An application names
 * another directory in the small class of its tests when it keeps its templates elsewhere.
 */
abstract class AbstractTemplateGuard extends AbstractFileGuard
{
    /**
     * Every template, by its path below the template directory.
     *
     * @return iterable<string, array{string}> the template's path from the project's root
     */
    public static function templates(): iterable
    {
        foreach (static::filesUnder(static::templateDirectory(), '.html.php') as $below => $file) {
            yield $below => [$file];
        }
    }

    /** The directory of the web's templates, relative to the project root. */
    protected static function templateDirectory(): string
    {
        return 'views/http';
    }

    /**
     * The template without its PHP: an echo (`<?= … ?>`) gives way to what the caller says it prints, a block (`<?php … ?>`)
     * to nothing, and the last one of a file needs no closing tag.
     */
    protected static function withoutPhp(string $template, string $printed = ''): string
    {
        $markup = self::replaced('/<\?=.*?(?:\?>|\z)/s', $printed, $template);

        return self::replaced('/<\?php.*?(?:\?>|\z)/s', '', $markup);
    }

    private static function replaced(string $pattern, string $replacement, string $subject): string
    {
        $replaced = preg_replace($pattern, $replacement, $subject);
        assert(is_string($replaced));

        return $replaced;
    }

    public function testThereIsATemplateToCheck(): void
    {
        $problems = [];

        if (static::filesUnder(static::templateDirectory(), '.html.php') === []) {
            $problems[] = 'The directory ' . static::templateDirectory() . ' has no template (*.html.php): the guard has'
                . ' nothing to check.';
        }

        $this->assertNoProblems($problems);
    }
}
