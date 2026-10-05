<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Content Security Policy runs scripts and styles from the site only, so a template must not need an inline script,
 * an inline style or an event handler attribute: the browser would refuse it and nothing would say why. The security
 * headers' own test holds the policy and the pages it renders; here every template is held to it, also the ones that no
 * request renders in a test. And an error that a template shows takes the focus when the page loads (WCAG 3.3.1), so
 * that a person who has sent a form is at the problem and not at the top of the page, whatever the page's length: an
 * error alert (any element whose `class` attribute lists the application's class for it, among others and in any order)
 * ends in `role="alert" tabindex="-1" autofocus>`, except in the templates whose page puts the focus on a field, where it
 * ends in `role="alert">`. What PHP prints is not the template's own markup.
 */
abstract class TemplateMarkupGuard extends AbstractTemplateGuard
{
    /** What a template must not contain: what it would be, as a message says it, and the pattern that finds it. */
    protected const array FORBIDDEN = [
        'an inline script' => '/<script(?![^>]*\ssrc=)/i',
        'a style element' => '/<style/i',
        'an inline style' => '/\sstyle\s*=/i',
        'an event handler attribute' => '/\son[a-z]+\s*=/i',
    ];

    /** How the tag of an error alert ends when it takes the focus. */
    private const string TAKES_THE_FOCUS = 'role="alert" tabindex="-1" autofocus>';

    /** How the tag of an error alert ends where the page puts the focus on a field. */
    private const string LEAVES_THE_FOCUS = 'role="alert">';

    /**
     * The classes of an error alert in the application's templates, as its `class` attribute lists them: the last of them
     * (`alert--error`) is the one that makes an element an error alert, wherever the attribute lists it and whatever else
     * the attribute lists.
     */
    protected static function errorAlertClass(): string
    {
        return 'alert alert--error';
    }

    /**
     * The templates, from the project's root, whose page puts the focus on a field (the login's password, say): their
     * error alerts leave it there.
     *
     * @return list<string>
     */
    protected static function templatesWithoutAlertFocus(): array
    {
        return [];
    }

    /**
     * What the template has that the Content Security Policy refuses, each kind once, in the order of FORBIDDEN.
     *
     * @return list<string>
     */
    protected static function offencesIn(string $template): array
    {
        $markup = static::withoutPhp($template);
        $offences = [];

        foreach (static::FORBIDDEN as $offence => $pattern) {
            assert(is_string($offence) && is_string($pattern));

            if (preg_match($pattern, $markup) === 1) {
                $offences[] = $offence;
            }
        }

        return $offences;
    }

    /**
     * The opening tags of the template's error alerts that do not end as told, in the order of the document: the tags of
     * the elements whose `class` attribute lists the class that makes an error alert.
     *
     * @return list<string>
     */
    protected static function alertsNotEndingIn(string $template, string $ending): array
    {
        preg_match_all('/\S+/', static::errorAlertClass(), $classes);
        $class = array_last($classes[0]);

        if ($class === null) {
            self::fail(
                'errorAlertClass() of ' . static::class . ' lists no class: an error alert has none to be found by.',
            );
        }
        preg_match_all('/<[a-z][^>]*>/i', static::withoutPhp($template), $tags);

        return array_values(array_filter(
            $tags[0],
            static fn (string $tag): bool => self::listsTheClass($tag, $class) && !str_contains($tag, $ending),
        ));
    }

    /** Whether the `class` attribute of the opening tag lists the class, which is one of its words. */
    private static function listsTheClass(string $tag, string $class): bool
    {
        // The value is in the one group of three that matched; a tag without a class attribute gives no groups at all
        preg_match('/\sclass\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/i', $tag, $attribute);
        preg_match_all('/\S+/', implode('', array_slice($attribute, 1)), $words);

        return in_array($class, $words[0], true);
    }

    #[DataProvider('templates', true, true)]
    public function testTheTemplateHasNoInlineScriptOrStyleAndNoEventHandler(string $file): void
    {
        $problems = [];

        foreach (static::offencesIn(static::contentsOf($file)) as $offence) {
            $problems[] = 'The template ' . $file . ' has ' . $offence . ', which the Content Security Policy refuses.';
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('templates', true, true)]
    public function testAnErrorThatTheTemplateShowsTakesTheFocusWhenThePageLoads(string $file): void
    {
        $takesTheFocus = !in_array($file, static::templatesWithoutAlertFocus(), true);
        $ending = $takesTheFocus
            ? self::TAKES_THE_FOCUS
            : self::LEAVES_THE_FOCUS;
        $reason = $takesTheFocus
            ? 'so that it takes the focus when the page loads'
            : 'since the page puts the focus elsewhere';
        $problems = [];

        foreach (static::alertsNotEndingIn(static::contentsOf($file), $ending) as $tag) {
            $problems[] = 'The error alert ' . $tag . ' of the template ' . $file . ' must end in ' . $ending . ', '
                . $reason . '.';
        }

        $this->assertNoProblems($problems);
    }
}
