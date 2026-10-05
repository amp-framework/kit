<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Kit\Testing\Guard\AbstractTemplateGuard;
use ampf\Kit\Testing\Guard\TemplateMarkupGuard;
use ampf\Kit\Tests\Support\GuardFailures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The guard over templates that need no inline script, no style and no event handler, and whose error alerts take the
 * focus but where the login's does not (tests/Fixtures/TemplateMarkupGuard/Abiding), and templates that do and do not
 * (Breaking); and what the guard counts as an offence and as an error alert. A guard is a TestCase, which takes its
 * name: PHP-CS-Fixer writes `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
#[CoversClass(AbstractTemplateGuard::class)]
#[CoversClass(TemplateMarkupGuard::class)]
final class TemplateMarkupGuardTest extends TestCase
{
    private const string FOCUS = 'role="alert" tabindex="-1" autofocus>';

    /** @return iterable<string, array{string, list<string>}> markup, and the offences that the guard finds in it */
    public static function offences(): iterable
    {
        yield 'markup that is fine' => ['<p class="a">Text</p><a href="/b">B</a>', []];
        yield 'a script of its own' => ['<script>run()</script>', ['an inline script']];
        yield 'a script of its own, in capitals' => ['<SCRIPT>run()</SCRIPT>', ['an inline script']];
        yield 'a script with attributes and no source' => ['<script type="module" async>run()</script>', ['an inline script']];
        yield 'a script from the site' => ['<script src="/js/app.js"></script>', []];
        yield 'a script from the site, with attributes' => ['<script type="module" defer src="/js/app.js"></script>', []];
        yield 'a script from the site, over lines' => ["<script\n    type=\"module\"\n    src=\"/js/app.js\"\n></script>", []];
        yield 'a script whose source is only a data attribute' => ['<script data-src="/js/app.js">run()</script>', ['an inline script']];
        yield 'a style element' => ['<style>p {}</style>', ['a style element']];
        yield 'a style element, in capitals' => ['<STYLE>p {}</STYLE>', ['a style element']];
        yield 'a style attribute' => ['<p style="color: red">Text</p>', ['an inline style']];
        yield 'a style attribute, spaced and in capitals' => ['<p STYLE = "color: red">Text</p>', ['an inline style']];
        yield 'a style attribute over lines' => ["<p\nstyle=\"color: red\">Text</p>", ['an inline style']];
        yield 'an attribute that ends in style' => ['<p data-style="red">Text</p>', []];
        yield 'a class that is called style' => ['<p class="style">Text</p>', []];
        yield 'an event handler' => ['<a onclick="run()">Text</a>', ['an event handler attribute']];
        yield 'an event handler, spaced and in capitals' => ['<a ONMOUSEOVER = "run()">Text</a>', ['an event handler attribute']];
        yield 'an event handler over lines' => ["<a\nonclick='run()'>Text</a>", ['an event handler attribute']];
        yield 'an attribute that ends in an event' => ['<a data-onclick="run()">Text</a>', []];
        yield 'an attribute that starts with on and has no value' => ['<input once>', []];
        yield 'every offence, in the order of the list' => [
            '<a onclick="run()" style="x">Text</a><style>p {}</style><script>run()</script>',
            ['an inline script', 'a style element', 'an inline style', 'an event handler attribute'],
        ];
        yield 'one offence twice' => ['<script>a()</script><script>b()</script>', ['an inline script']];
        yield 'markup that PHP prints' => [
            "<?= '<script>run()</script><style>p {}</style>' ?><p <?= 'style=\"x\" onclick=\"y\"' ?>></p>",
            [],
        ];
        yield 'a block of PHP' => ["<?php\n// <script> onclick= style=\n\$x = '<style>';\n?><p>Text</p>", []];
        yield 'markup next to PHP' => ['<?= $a ?><script>run()</script><?php foo(); ?>', ['an inline script']];
    }

    /** @return iterable<string, array{string, string, list<string>}> markup, how an error alert must end, and the alerts that do not */
    public static function alerts(): iterable
    {
        $bare = '<p class="alert alert--error" role="alert">';
        $focused = '<div class="alert alert--error" role="alert" tabindex="-1" autofocus>';

        yield 'no alert' => ['<p class="text">Text</p>', self::FOCUS, []];
        yield 'an alert that takes the focus' => ["{$focused}Text</div>", self::FOCUS, []];
        yield 'an alert that does not' => ["{$bare}Text</p>", self::FOCUS, [$bare]];
        yield 'an alert that has a tabindex and no autofocus' => [
            '<p class="alert alert--error" role="alert" tabindex="-1">Text</p>',
            self::FOCUS,
            ['<p class="alert alert--error" role="alert" tabindex="-1">'],
        ];
        yield 'an alert with the words in another order' => [
            '<p class="alert alert--error" tabindex="-1" autofocus role="alert">Text</p>',
            self::FOCUS,
            ['<p class="alert alert--error" tabindex="-1" autofocus role="alert">'],
        ];
        yield 'alerts in the order of the document' => [
            "{$bare}A</p>{$focused}B</div><div class=\"alert alert--error\" role=\"alert\">C</div>",
            self::FOCUS,
            [$bare, '<div class="alert alert--error" role="alert">'],
        ];
        yield 'an alert that is no error' => ['<p class="alert alert--info" role="alert">Text</p>', self::FOCUS, []];
        yield 'an error alert that is not a paragraph or a division' => [
            '<span class="alert alert--error" role="alert">Text</span>',
            self::FOCUS,
            ['<span class="alert alert--error" role="alert">'],
        ];
        yield 'an error alert of any element' => [
            '<section class="alert alert--error" role="alert">A</section><li class="alert alert--error">B</li>',
            self::FOCUS,
            ['<section class="alert alert--error" role="alert">', '<li class="alert alert--error">'],
        ];
        yield 'an alert with other classes' => [
            '<p class="alert alert--error big" role="alert">Text</p>',
            self::FOCUS,
            ['<p class="alert alert--error big" role="alert">'],
        ];
        yield 'an alert with the classes in another order' => [
            '<p class="alert--error alert" role="alert">Text</p>',
            self::FOCUS,
            ['<p class="alert--error alert" role="alert">'],
        ];
        yield 'an alert with the error class alone, among others' => [
            '<p class="big alert--error small" role="alert">Text</p>',
            self::FOCUS,
            ['<p class="big alert--error small" role="alert">'],
        ];
        yield 'an alert with other classes that takes the focus' => [
            '<p class="big alert--error alert" role="alert" tabindex="-1" autofocus>Text</p>',
            self::FOCUS,
            [],
        ];
        yield 'an alert with its classes over lines and spaced' => [
            "<p class = \"alert\n    alert--error\"\n role=\"alert\">Text</p>",
            self::FOCUS,
            ["<p class = \"alert\n    alert--error\"\n role=\"alert\">"],
        ];
        yield 'an alert in single quotes' => [
            "<p class='alert alert--error' role='alert'>Text</p>",
            self::FOCUS,
            ["<p class='alert alert--error' role='alert'>"],
        ];
        yield 'an alert with the class in no quotes' => [
            '<p class=alert--error role=alert>Text</p>',
            self::FOCUS,
            ['<p class=alert--error role=alert>'],
        ];
        yield 'an alert in capitals' => [
            '<P CLASS="alert alert--error" ROLE="alert">Text</P>',
            self::FOCUS,
            ['<P CLASS="alert alert--error" ROLE="alert">'],
        ];
        yield 'an alert with a class that PHP prints beside the error class' => [
            '<p class="alert alert--error <?= $x ?>" role="alert">Text</p>',
            self::FOCUS,
            ['<p class="alert alert--error " role="alert">'],
        ];
        yield 'an attribute that ends in class' => ['<p data-class="alert--error" role="alert">Text</p>', self::FOCUS, []];
        yield 'a class that only holds the error class' => [
            '<p class="alert--errors" role="alert">A</p><p class="no-alert--error" role="alert">B</p>'
            . '<p class="alert--error-big" role="alert">C</p>',
            self::FOCUS,
            [],
        ];
        yield 'a closing tag and a tag without a class' => ['<p>Text</p><br><div id="alert--error">Text</div>', self::FOCUS, []];
        yield 'an alert with attributes before its class' => [
            '<p id="a" class="alert alert--error" role="alert">Text</p>',
            self::FOCUS,
            ['<p id="a" class="alert alert--error" role="alert">'],
        ];
        yield 'an alert with PHP in its tag' => [
            '<p class="alert alert--error" role="alert" tabindex="-1" autofocus <?= $x ?>>Text</p>',
            self::FOCUS,
            ['<p class="alert alert--error" role="alert" tabindex="-1" autofocus >'],
        ];
        yield 'an alert with PHP that gives the words' => [
            '<p class="alert alert--error" role="alert" tabindex="-1" autofocus><?= $x ?></p>',
            self::FOCUS,
            [],
        ];
        yield 'an alert that PHP prints' => ["<?= '{$bare}' ?>", self::FOCUS, []];
        yield 'an alert that leaves the focus where it is' => ["{$bare}Text</p>", 'role="alert">', []];
        yield 'an alert that takes the focus where it should not' => [
            "{$focused}Text</div>",
            'role="alert">',
            [$focused],
        ];
    }

    /** The guard over the templates that keep the rules, and the login that leaves the focus to its field. */
    private static function abiding(): TemplateMarkupGuard
    {
        return new class('abiding') extends TemplateMarkupGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TemplateMarkupGuard/Abiding';
            }

            /**
             * @return list<string>
             */
            protected static function templatesWithoutAlertFocus(): array
            {
                return ['views/http/login.html.php'];
            }
        };
    }

    /** The guard over the templates that break them. */
    private static function breaking(): TemplateMarkupGuard
    {
        return new class('breaking') extends TemplateMarkupGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TemplateMarkupGuard/Breaking';
            }

            /**
             * @return list<string>
             */
            protected static function templatesWithoutAlertFocus(): array
            {
                return ['views/http/login.html.php'];
            }
        };
    }

    /**
     * @param list<string> $offences
     */
    #[DataProvider('offences')]
    public function testAnOffenceIsWhatTheContentSecurityPolicyRefusesInTheMarkupOfATemplate(
        string $template,
        array $offences,
    ): void {
        $guard = new class('offences') extends TemplateMarkupGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            /**
             * @return list<string>
             */
            public static function offencesOf(string $template): array
            {
                return self::offencesIn($template);
            }
        };

        self::assertSame($offences, $guard::offencesOf($template));
    }

    /**
     * @param list<string> $tags
     */
    #[DataProvider('alerts')]
    public function testAnErrorAlertOfTheTemplateMustEndAsItIsTold(string $template, string $ending, array $tags): void
    {
        $guard = new class('alerts') extends TemplateMarkupGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            /**
             * @return list<string>
             */
            public static function alertsOf(string $template, string $ending): array
            {
                return self::alertsNotEndingIn($template, $ending);
            }
        };

        self::assertSame($tags, $guard::alertsOf($template, $ending));
    }

    public function testTheClassOfAnErrorAlertIsTheApplicationsToName(): void
    {
        $default = new class('default') extends TemplateMarkupGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            public static function className(): string
            {
                return self::errorAlertClass();
            }
        };
        $guard2 = new class('classes') extends TemplateMarkupGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            protected static function errorAlertClass(): string
            {
                return "notice  error \n";
            }

            /**
             * @return list<string>
             */
            public static function alertsOf(string $template): array
            {
                return self::alertsNotEndingIn($template, 'role="alert">');
            }
        };
        $guard = new class('class') extends TemplateMarkupGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            protected static function errorAlertClass(): string
            {
                return 'notice.error';
            }

            /**
             * @return list<string>
             */
            public static function alertsOf(string $template): array
            {
                return self::alertsNotEndingIn($template, 'role="alert">');
            }
        };

        self::assertSame('alert alert--error', $default::className());
        self::assertSame(
            ['<p class="notice.error" role="alert" autofocus>', '<p class="other notice.error" role="alert" autofocus>'],
            $guard::alertsOf(
                '<p class="alert alert--error" role="alert" autofocus><p class="noticeXerror" role="alert" autofocus>'
                . '<p class="notice.error" role="alert" autofocus><p class="other notice.error" role="alert" autofocus>',
            ),
            'The class is a text, not a pattern.',
        );
        self::assertSame(
            ['<p class="notice error" role="alert" autofocus>', '<p class="error notice" role="alert" autofocus>'],
            $guard2::alertsOf(
                '<p class="notice error" role="alert" autofocus><p class="notice" role="alert" autofocus>'
                . '<p class="error notice" role="alert" autofocus>',
            ),
            'Of several classes it is the last that makes an alert an error.',
        );
    }

    public function testAnApplicationThatListsNoClassForAnErrorAlertIsToldSoAndNotLeftWithAGuardThatFindsNothing(): void
    {
        $guard = new class('none') extends TemplateMarkupGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            protected static function errorAlertClass(): string
            {
                return " \n";
            }

            /**
             * @return list<string>
             */
            public static function alertsOf(string $template): array
            {
                return self::alertsNotEndingIn($template, 'role="alert">');
            }
        };

        self::assertSame(
            'errorAlertClass() of ' . $guard::class . ' lists no class: an error alert has none to be found by.',
            GuardFailures::message($guard::alertsOf(...), '<p class="alert alert--error" role="alert">'),
        );
    }

    public function testTemplatesWithoutAnInlineScriptStyleOrHandlerPass(): void
    {
        $guard = self::abiding();
        $test = $guard->testTheTemplateHasNoInlineScriptOrStyleAndNoEventHandler(...);

        self::assertSame(
            ['login.html.php', 'page.html.php', 'partials/alert.html.php'],
            array_keys(iterator_to_array($guard::templates())),
        );
        self::assertSame([], GuardFailures::of($test, $guard::templates()));
        self::assertSame(3, $guard->numberOfAssertionsPerformed());
    }

    public function testATemplateWithAnInlineScriptStyleOrHandlerFailsAndSaysWhat(): void
    {
        $guard = self::breaking();
        $test = $guard->testTheTemplateHasNoInlineScriptOrStyleAndNoEventHandler(...);

        self::assertSame(
            [
                'inline.html.php' => 'The template views/http/inline.html.php has an inline script, which the Content'
                    . ' Security Policy refuses.' . PHP_EOL
                    . 'The template views/http/inline.html.php has a style element, which the Content Security Policy'
                    . ' refuses.' . PHP_EOL
                    . 'The template views/http/inline.html.php has an inline style, which the Content Security Policy'
                    . ' refuses.' . PHP_EOL
                    . 'The template views/http/inline.html.php has an event handler attribute, which the Content Security'
                    . ' Policy refuses.',
            ],
            GuardFailures::of($test, $guard::templates()),
        );
    }

    public function testErrorAlertsThatTakeTheFocusExceptWhereThePageSaysOtherwisePass(): void
    {
        $guard = self::abiding();
        $test = $guard->testAnErrorThatTheTemplateShowsTakesTheFocusWhenThePageLoads(...);

        self::assertSame([], GuardFailures::of($test, $guard::templates()));
        self::assertSame(3, $guard->numberOfAssertionsPerformed());
    }

    public function testAnErrorAlertThatDoesNotTakeTheFocusFailsAndSoDoesOneWhereThePageSaysItShouldNot(): void
    {
        $guard = self::breaking();

        self::assertSame(
            [
                'alerts.html.php' => 'The error alert <p class="alert alert--error" role="alert"> of the template'
                    . ' views/http/alerts.html.php must end in ' . self::FOCUS . ', so that it takes the focus when the'
                    . ' page loads.' . PHP_EOL
                    . 'The error alert <div class="alert alert--error" role="alert" tabindex="-1"> of the template'
                    . ' views/http/alerts.html.php must end in ' . self::FOCUS . ', so that it takes the focus when the'
                    . ' page loads.' . PHP_EOL
                    . 'The error alert <section class="alert--error alert" role="alert"> of the template'
                    . ' views/http/alerts.html.php must end in ' . self::FOCUS . ', so that it takes the focus when the'
                    . ' page loads.' . PHP_EOL
                    . 'The error alert <p class=\'alert alert--error big\' role="alert" tabindex="-1"> of the template'
                    . ' views/http/alerts.html.php must end in ' . self::FOCUS . ', so that it takes the focus when the'
                    . ' page loads.',
                'login.html.php' => 'The error alert <p class="alert alert--error" role="alert" tabindex="-1" autofocus> of'
                    . ' the template views/http/login.html.php must end in role="alert">, since the page puts the focus'
                    . ' elsewhere.',
            ],
            GuardFailures::of(
                $guard->testAnErrorThatTheTemplateShowsTakesTheFocusWhenThePageLoads(...),
                $guard::templates(),
            ),
        );
    }

    public function testNoTemplateIsExemptFromTheFocusUnlessTheApplicationNamesIt(): void
    {
        $guard = new class('default') extends TemplateMarkupGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TemplateMarkupGuard/Breaking';
            }

            /**
             * @return list<string>
             */
            public static function exempt(): array
            {
                return self::templatesWithoutAlertFocus();
            }
        };

        self::assertSame([], $guard::exempt());
        self::assertNull(
            GuardFailures::message(
                $guard->testAnErrorThatTheTemplateShowsTakesTheFocusWhenThePageLoads(...),
                'views/http/login.html.php',
            ),
            'the alert of the login takes the focus, which is what every template must do unless it is told not to',
        );
    }
}
