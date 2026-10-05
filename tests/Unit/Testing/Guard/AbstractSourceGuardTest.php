<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractSourceGuard;
use ampf\Kit\Tests\Fixtures\AbstractSourceGuard\Project\Source\Alpha;
use ampf\Kit\Tests\Fixtures\AbstractSourceGuard\Project\Source\Service\Beta;
use ampf\Kit\Tests\Fixtures\AbstractSourceGuard\Project\Source\Service\BetaInterface;
use ampf\Kit\Tests\Fixtures\AbstractSourceGuard\Project\Source\Support\Gamma;
use ampf\Kit\Tests\Fixtures\AbstractSourceGuard\Project\Source\Support\Mode;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * What the guards that read an application's classes have in common, over a small source tree
 * (tests/Fixtures/AbstractSourceGuard): the type each file's path names (PSR-4) and the reflection of a type that
 * must be there. A guard is a TestCase, which takes its name: PHP-CS-Fixer writes `new class('name')`, PSR-12
 * `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractSourceGuard::class)]
final class AbstractSourceGuardTest extends TestCase
{
    private const string NAMESPACE = 'ampf\Kit\Tests\Fixtures\AbstractSourceGuard\Project\Source';

    public function testEachFileBelowTheSourceDirectoryNamesItsType(): void
    {
        $guard = new class('types') extends AbstractSourceGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/AbstractSourceGuard/Project';
            }

            protected static function sourceDirectory(): string
            {
                return 'Source';
            }

            protected static function sourceNamespace(): string
            {
                return 'ampf\Kit\Tests\Fixtures\AbstractSourceGuard\Project\Source';
            }

            /**
             * @return array<string, string>
             */
            public static function typesOf(string $directory, string $suffix): array
            {
                return self::sourceTypes($directory, $suffix);
            }
        };

        self::assertSame(
            [
                'Source/Alpha.php' => self::NAMESPACE . '\Alpha',
                'Source/Misnamed.php' => self::NAMESPACE . '\Misnamed',
                'Source/Service/Beta.php' => self::NAMESPACE . '\Service\Beta',
                'Source/Service/BetaInterface.php' => self::NAMESPACE . '\Service\BetaInterface',
                'Source/Support/Gamma.php' => self::NAMESPACE . '\Support\Gamma',
                'Source/Support/Mode.php' => self::NAMESPACE . '\Support\Mode',
            ],
            $guard::typesOf('Source', '.php'),
        );
        self::assertSame(
            [
                'Source/Service/Beta.php' => self::NAMESPACE . '\Service\Beta',
                'Source/Service/BetaInterface.php' => self::NAMESPACE . '\Service\BetaInterface',
            ],
            $guard::typesOf('Source/Service', '.php'),
            'a directory below the source directory',
        );
        self::assertSame(
            ['Source/Service/BetaInterface.php' => self::NAMESPACE . '\Service\BetaInterface'],
            $guard::typesOf('Source', 'Interface.php'),
            'files that end in a suffix',
        );
    }

    public function testAClassAnInterfaceATraitAndAnEnumAreTypesThatAreThere(): void
    {
        $guard = new class('reflection') extends AbstractSourceGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            protected static function sourceNamespace(): string
            {
                return 'App';
            }

            /**
             * @return ReflectionClass<object>
             */
            public static function reflection(string $type, string $file): ReflectionClass
            {
                return self::reflect($type, $file);
            }
        };

        self::assertSame(Alpha::class, $guard::reflection(Alpha::class, 'Source/Alpha.php')->getName());
        self::assertSame(BetaInterface::class, $guard::reflection(BetaInterface::class, 'x')->getName());
        self::assertSame(Beta::class, $guard::reflection(Beta::class, 'x')->getName());
        self::assertTrue($guard::reflection(Gamma::class, 'x')->isTrait());
        self::assertTrue($guard::reflection(Mode::class, 'x')->isEnum());

        // The second look must not run the file again: it would declare its class twice, and that is a fatal error
        foreach ([1, 2] as $look) {
            try {
                $guard::reflection(self::NAMESPACE . '\Misnamed', 'Source/Misnamed.php');
                self::fail('The guard passed.');
            } catch (AssertionFailedError $e) {
                self::assertSame(
                    'The file Source/Misnamed.php does not declare ' . self::NAMESPACE . '\Misnamed, the type its path'
                    . ' names.',
                    $e->getMessage(),
                    'look ' . $look,
                );
            }
        }
    }
}
