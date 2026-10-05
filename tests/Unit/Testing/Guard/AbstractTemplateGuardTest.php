<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Kit\Testing\Guard\AbstractTemplateGuard;
use ampf\Kit\Tests\Support\GuardFailures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What the guards over an application's templates have in common, over a small project
 * (tests/Fixtures/AbstractTemplateGuard): the web's templates, which are the files that end in `.html.php` below
 * `views/http` at any depth, and the template as markup without the PHP in it. A guard is a TestCase, which takes its
 * name: PHP-CS-Fixer writes `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
#[CoversClass(AbstractTemplateGuard::class)]
final class AbstractTemplateGuardTest extends TestCase
{
    /** @return iterable<string, array{string, string, string}> a template, what PHP prints, and the markup */
    public static function templatesWithPhp(): iterable
    {
        yield 'markup only' => ['<p>a</p>', '', '<p>a</p>'];
        yield 'an echo' => ['<p><?= $a ?></p>', '', '<p></p>'];
        yield 'an echo, marked' => ['<p><?= $a ?></p>', 'X', '<p>X</p>'];
        yield 'two echoes on a line' => ['<?= $a ?> and <?= $b ?>', 'X', 'X and X'];
        yield 'a block' => ['<?php $x = 1; ?><p>a</p>', 'X', '<p>a</p>'];
        yield 'a block, marked or not' => ['<?php $x = 1; ?>a<?= $x ?>', 'X', 'aX'];
        yield 'blocks around markup' => ['<p>a</p><?php if ($x): ?><b>b</b><?php endif; ?>', '', '<p>a</p><b>b</b>'];
        yield 'a block over lines' => ["<?php\ndeclare(strict_types=1);\n\n?>\n<p>a</p>", '', "\n<p>a</p>"];
        yield 'an echo over lines' => ["<p><?=\n    \$this->t('key')\n?></p>", 'X', '<p>X</p>'];
        yield 'an echo that is not closed' => ['<p>a</p><?= $x', 'X', '<p>a</p>X'];
        yield 'a block that is not closed' => ['<p>a</p><?php foo();', 'X', '<p>a</p>'];
        yield 'a block that is not closed, after an echo' => ['<?= $a ?><?php foo();', 'X', 'X'];
        yield 'the echo of a block' => ['<?php echo 1; ?>', 'X', ''];
    }

    #[DataProvider('templatesWithPhp')]
    public function testThePhpOfATemplateIsReplacedByWhatItPrintsForEchoesAndByNothingForBlocks(
        string $template,
        string $printed,
        string $markup,
    ): void {
        $guard = new class('markup') extends AbstractTemplateGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            public static function markup(string $template, string $printed): string
            {
                return self::withoutPhp($template, $printed);
            }

            public static function bare(string $template): string
            {
                return self::withoutPhp($template);
            }
        };

        self::assertSame($markup, $guard::markup($template, $printed));
        self::assertSame(str_replace($printed, '', $markup), $guard::bare($template), 'without marks, by default');
    }

    public function testTheTemplatesAreTheFilesBelowTheWebsDirectoryThatEndInHtmlPhp(): void
    {
        $guard = new class('templates') extends AbstractTemplateGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractTemplateGuard/Project';
            }
        };

        self::assertSame(
            [
                'about.html.php' => ['views/http/about.html.php'],
                'page.html.php' => ['views/http/page.html.php'],
                'partials/row.html.php' => ['views/http/partials/row.html.php'],
            ],
            iterator_to_array($guard::templates()),
        );
        self::assertNull(GuardFailures::message($guard->testThereIsATemplateToCheck(...)));
        self::assertSame(1, $guard->numberOfAssertionsPerformed());
    }

    public function testAnApplicationNamesTheDirectoryOfItsTemplates(): void
    {
        $guard = new class('partials') extends AbstractTemplateGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractTemplateGuard/Project';
            }

            protected static function templateDirectory(): string
            {
                return 'views/http/partials';
            }
        };

        self::assertSame(
            ['row.html.php' => ['views/http/partials/row.html.php']],
            iterator_to_array($guard::templates()),
        );
    }

    public function testADirectoryWithoutATemplateIsNothingToCheckAndSaysSo(): void
    {
        $guard = new class('empty') extends AbstractTemplateGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractTemplateGuard/Empty';
            }
        };

        self::assertSame([], iterator_to_array($guard::templates()));
        self::assertSame(
            'The directory views/http has no template (*.html.php): the guard has nothing to check.',
            GuardFailures::message($guard->testThereIsATemplateToCheck(...)),
        );
    }
}
