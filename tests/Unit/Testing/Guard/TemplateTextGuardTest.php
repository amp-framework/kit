<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Kit\Testing\Guard\AbstractTemplateGuard;
use ampf\Kit\Testing\Guard\TemplateTextGuard;
use ampf\Kit\Tests\Support\GuardFailures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The guard over templates that hold markup and PHP and no text of their own, the pieces of a wordmark among the
 * literals they may say (tests/Fixtures/TemplateTextGuard/Abiding), templates with texts of their own (Breaking) and
 * another directory (Elsewhere); and what the guard counts as a text. A guard is a TestCase, which takes its name:
 * PHP-CS-Fixer writes `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
#[CoversClass(AbstractTemplateGuard::class)]
#[CoversClass(TemplateTextGuard::class)]
final class TemplateTextGuardTest extends TestCase
{
    /** @return iterable<string, array{string, list<string>}> a template, and the texts that the guard finds in it */
    public static function templates(): iterable
    {
        yield 'a text' => ['<p>Hello</p>', ['Hello']];
        yield 'a text, trimmed' => ["<p>\n    Hello there  \n</p>", ['Hello there']];
        yield 'two texts, in order' => ['<div><ul><li>One</li><li>Two</li></ul></div>', ['One', 'Two']];
        yield 'the same text twice' => ['<p>A</p><p>B</p><p>A</p>', ['A', 'B', 'A']];
        yield 'the text next to PHP' => ['<p>Hello <?= $name ?></p>', ['Hello']];
        yield 'nothing but PHP' => ["<p><?= \$this->t('key') ?></p>", []];
        yield 'blocks of PHP' => ['<?php if ($x): ?><p><?= $a ?></p><?php endif; ?>', []];
        yield 'a block of PHP with a text in it' => ["<?php\n\$x = 'Some text';\n?>\n<p><?= \$x ?></p>", []];
        yield 'no markup at all' => ['', []];
        yield 'white space' => ["\n  \n", []];
        yield 'the middle dot' => ['<p><?= $a ?> · <?= $b ?></p>', []];
        yield 'the middle dot as an entity' => ['<p><?= $a ?> &middot; <?= $b ?></p>', []];
        yield 'a bar' => ['<p><?= $a ?> | <?= $b ?></p>', []];
        yield 'a slash' => ['<p><?= $a ?> / <?= $b ?></p>', []];
        yield 'an asterisk' => ['<p><?= $a ?> *</p>', []];
        yield 'two marks are a text of their own' => ['<p>· |</p>', ['· |']];
        yield 'a comment' => ['<!-- Some words --><p><?= $a ?></p>', []];
        yield 'an alt' => ['<img src="/a.png" alt="A photo">', ['A photo']];
        yield 'a title' => ['<a href="/a" title="Go there"><?= $a ?></a>', ['Go there']];
        yield 'an aria-label' => ['<button aria-label="Close"></button>', ['Close']];
        yield 'an aria-description' => ['<button aria-description="Closes the dialog"></button>', ['Closes the dialog']];
        yield 'an aria-placeholder' => ['<div role="textbox" aria-placeholder="Search here"></div>', ['Search here']];
        yield 'an aria-roledescription' => ['<div aria-roledescription="slide"></div>', ['slide']];
        yield 'an aria-valuetext' => ['<div role="slider" aria-valuetext="Half full"></div>', ['Half full']];
        yield 'a label of a group and of an option' => [
            '<select><optgroup label="Fuel"><option label="Diesel" value="<?= $id ?>"><?= $a ?></option></optgroup></select>',
            ['Fuel', 'Diesel'],
        ];
        yield 'a placeholder' => ['<input placeholder="Search">', ['Search']];
        yield 'a value' => ['<input type="submit" value="Save">', ['Save']];
        yield 'an attribute with PHP in it' => ['<img alt="<?= $a ?>" title="<?= $b ?>" value="<?= $c ?>">', []];
        yield 'an attribute with a text beside PHP' => ['<img alt="Photo of <?= $a ?>">', ['Photo of']];
        yield 'attributes that are no texts' => [
            '<a href="/Some/Where" class="Big Blue" id="Main" name="Name" type="Submit" data-label="Label" lang="en">'
            . '<?= $a ?></a>',
            [],
        ];
        yield 'the attributes of an element, then its content' => [
            '<a title="Go" href="/a">Text<b title="Deep">More</b></a>',
            ['Go', 'Text', 'Deep', 'More'],
        ];
        yield 'the description of the page' => ['<meta name="description" content="About this page">', ['About this page']];
        yield 'the description, written by PHP' => ['<meta name="description" content="<?= $a ?>">', []];
        yield 'another meta tag' => ['<meta name="viewport" content="width=device-width">', []];
        yield 'a charset' => ['<meta charset="utf-8">', []];
        yield 'a content that is no meta tag\'s' => ['<div name="description" content="Words"><?= $a ?></div>', []];
        yield 'the title of a document' => [
            '<!DOCTYPE html><html><head><title>Own title</title></head><body><?= $a ?></body></html>',
            ['Own title'],
        ];
        yield 'a document that PHP writes whole' => ['<?= $page ?>', []];
        yield 'a text inside a template element' => [
            '<ul><template id="row"><li>Wird hochgeladen</li></template></ul>',
            ['Wird hochgeladen'],
        ];
        yield 'the texts before, inside and after a template, in the order of the document' => [
            '<p>Before</p><template><i>Inside</i></template><p>After</p>',
            ['Before', 'Inside', 'After'],
        ];
        yield 'an attribute of the template element itself' => ['<template title="Own"><?= $a ?></template>', ['Own']];
        yield 'PHP inside a template' => ['<template><li title="<?= $a ?>"><?= $b ?></li></template>', []];
        yield 'a template inside a template' => [
            '<template><p>Outer</p><template><p>Deep</p></template></template>',
            ['Outer', 'Deep'],
        ];
        yield 'a row of a table in a template keeps its attributes' => [
            '<table><template id="line"><tr title="Row"><td aria-label="Cell">Cell text</td></tr></template></table>',
            ['Row', 'Cell', 'Cell text'],
        ];
        yield 'an attribute of an element inside a template' => [
            '<template><button aria-label="Close"><?= $a ?></button></template>',
            ['Close'],
        ];
        yield 'an echo that is not closed' => ['<p>Hello</p><?= $a', ['Hello']];
        yield 'a block that is not closed' => ['<p>Hello</p><?php foo();', ['Hello']];
    }

    /** The guard over templates that say nothing themselves but the pieces of a wordmark. */
    private static function abiding(): TemplateTextGuard
    {
        return new class('abiding') extends TemplateTextGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TemplateTextGuard/Abiding';
            }

            /**
             * @return list<string>
             */
            protected static function allowedLiterals(): array
            {
                return [...parent::allowedLiterals(), 'Note', 'Book'];
            }
        };
    }

    /** The guard over templates that do. */
    private static function breaking(): TemplateTextGuard
    {
        return new class('breaking') extends TemplateTextGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TemplateTextGuard/Breaking';
            }
        };
    }

    /**
     * @param list<string> $texts
     */
    #[DataProvider('templates')]
    public function testATextIsWhatIsLeftOfTheMarkupWhenPhpAndTheAllowedMarksAreTaken(
        string $template,
        array $texts,
    ): void {
        $guard = new class('texts') extends TemplateTextGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            /**
             * @return list<string>
             */
            public static function textsOf(string $template): array
            {
                return self::ownTexts($template);
            }
        };

        self::assertSame($texts, $guard::textsOf($template));
    }

    public function testTheLiteralsATemplateMaySayAreTheMarksThatMeanTheSameInEveryLanguageUnlessTold(): void
    {
        $guard = new class('allowed') extends TemplateTextGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            /**
             * @return list<string>
             */
            public static function allowed(): array
            {
                return self::allowedLiterals();
            }
        };

        self::assertSame(['·', '|', '/', '*'], $guard::allowed());
    }

    public function testTheLiteralsAndTheAttributesAreTheApplicationsToChange(): void
    {
        $guard = new class('hooks') extends TemplateTextGuard {
            protected const array TEXT_ATTRIBUTES = ['data-label'];

            protected const array TEXT_META = ['keywords'];

            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            /**
             * @return list<string>
             */
            protected static function allowedLiterals(): array
            {
                return ['Note', 'Book'];
            }

            /**
             * @return list<string>
             */
            public static function textsOf(string $template): array
            {
                return self::ownTexts($template);
            }
        };

        self::assertSame(
            ['Notebook', 'Label'],
            $guard::textsOf(
                '<p>Note</p><p>Book</p><p>Notebook</p><a data-label="Label" title="Not a text here" alt="Nor here">'
                . '<?= $a ?></a>',
            ),
        );
        self::assertSame(
            ['A, b, c'],
            $guard::textsOf(
                '<meta name="keywords" content="A, b, c"><meta name="description" content="Not a text here">',
            ),
        );
    }

    public function testTemplatesWithoutTextsOfTheirOwnPass(): void
    {
        $guard = self::abiding();
        $test = $guard->testTheTemplateSaysNothingItself(...);

        self::assertSame(
            ['home.html.php', 'layout.html.php', 'partials/field.html.php'],
            array_keys(iterator_to_array($guard::templates())),
        );
        self::assertSame([], GuardFailures::of($test, $guard::templates()));
        self::assertSame(3, $guard->numberOfAssertionsPerformed());
    }

    public function testTemplatesWithTextsOfTheirOwnFailAndNameThem(): void
    {
        $guard = self::breaking();

        self::assertSame(
            [
                'own-text.html.php' => 'The template views/http/own-text.html.php has texts of its own: "Hello", "Sign in".'
                    . ' Make them keys of the translation files.',
                'partials/own-attribute.html.php' => 'The template views/http/partials/own-attribute.html.php has texts of'
                    . ' its own: "Search", "A photo". Make them keys of the translation files.',
                'partials/own-label.html.php' => 'The template views/http/partials/own-label.html.php has texts of its own:'
                    . ' "Fuel", "Diesel", "Search here", "Slide", "Half full". Make them keys of the translation files.',
                'partials/own-template.html.php' => 'The template views/http/partials/own-template.html.php has texts of its'
                    . ' own: "Wird hochgeladen", "Row", "Cell", "Cell text", "Deep". Make them keys of the translation files.',
            ],
            GuardFailures::of($guard->testTheTemplateSaysNothingItself(...), $guard::templates()),
        );
    }

    public function testATemplateInAnotherDirectoryIsOneTheApplicationNames(): void
    {
        $guard = new class('elsewhere') extends TemplateTextGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/TemplateTextGuard/Elsewhere';
            }

            protected static function templateDirectory(): string
            {
                return 'templates';
            }
        };

        self::assertSame(
            [
                'page.html.php' => 'The template templates/page.html.php has texts of its own: "Own text". Make them keys'
                    . ' of the translation files.',
            ],
            GuardFailures::of($guard->testTheTemplateSaysNothingItself(...), $guard::templates()),
        );
    }
}
