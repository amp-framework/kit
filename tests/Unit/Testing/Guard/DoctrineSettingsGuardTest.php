<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Kit\Testing\Guard\DoctrineSettingsGuard;
use ampf\Kit\Tests\Support\GuardFailures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The guard over applications that leave the `doctrine` block to the framework and the package
 * (tests/Fixtures/DoctrineSettingsGuard/Abiding), that set both blocks themselves and keep every entry of theirs
 * (Repeating), and that replace them without some (Breaking: the web loses both entries of the database types, the
 * command line has another class for the UUID and has not the immutable datetimes). A guard is a TestCase, which takes
 * its name: PHP-CS-Fixer writes `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
#[CoversClass(DoctrineSettingsGuard::class)]
final class DoctrineSettingsGuardTest extends TestCase
{
    /** The guard over the application that leaves the blocks alone. */
    private static function abiding(): DoctrineSettingsGuard
    {
        return new class('abiding') extends DoctrineSettingsGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/DoctrineSettingsGuard/Abiding';
            }
        };
    }

    /** The guard over the application that sets the blocks and repeats the entries. */
    private static function repeating(): DoctrineSettingsGuard
    {
        return new class('repeating') extends DoctrineSettingsGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/DoctrineSettingsGuard/Repeating';
            }
        };
    }

    /** The guard over the application that replaces the blocks and loses entries. */
    private static function breaking(): DoctrineSettingsGuard
    {
        return new class('breaking') extends DoctrineSettingsGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/DoctrineSettingsGuard/Breaking';
            }
        };
    }

    public function testTheBlocksOfTheWebAndOfTheCommandLineAreChecked(): void
    {
        self::assertSame(
            [
                'http typeOverrides' => ['http', 'typeOverrides'],
                'http mappingOverrides' => ['http', 'mappingOverrides'],
                'cli typeOverrides' => ['cli', 'typeOverrides'],
                'cli mappingOverrides' => ['cli', 'mappingOverrides'],
            ],
            iterator_to_array(self::abiding()::overrides()),
        );
    }

    public function testAnApplicationThatLeavesTheBlocksToTheFrameworkAndThePackagePasses(): void
    {
        $guard = self::abiding();
        $test = $guard->testTheOverridesKeepEveryEntryOfTheFrameworkAndThePackage(...);

        self::assertSame([], GuardFailures::of($test, $guard::overrides()));
        self::assertSame(4, $guard->numberOfAssertionsPerformed());
    }

    public function testAnApplicationThatRepeatsTheirEntriesBesideItsOwnPasses(): void
    {
        $guard = self::repeating();
        $test = $guard->testTheOverridesKeepEveryEntryOfTheFrameworkAndThePackage(...);

        self::assertSame([], GuardFailures::of($test, $guard::overrides()));
    }

    public function testBlocksThatLoseOrChangeAnEntryFail(): void
    {
        $guard = self::breaking();
        $test = $guard->testTheOverridesKeepEveryEntryOfTheFrameworkAndThePackage(...);

        self::assertSame(
            [
                'http mappingOverrides' => 'The doctrine.mappingOverrides of the http configuration do not keep "enum" =>'
                    . ' string, which the framework or the package sets: an application that sets the block repeats their'
                    . ' entries beside its own.' . PHP_EOL
                    . 'The doctrine.mappingOverrides of the http configuration do not keep "uuid" => guid, which the'
                    . ' framework or the package sets: an application that sets the block repeats their entries beside'
                    . ' its own.',
                'cli typeOverrides' => 'The doctrine.typeOverrides of the cli configuration do not keep'
                    . ' "datetime_immutable" => ampf\Kit\Doctrine\Type\UtcDateTimeImmutableType, which the framework or'
                    . ' the package sets: an application that sets the block repeats their entries beside its own.' . PHP_EOL
                    . 'The doctrine.typeOverrides of the cli configuration do not keep "datetimetz_immutable" =>'
                    . ' ampf\Kit\Doctrine\Type\UtcDateTimeImmutableType, which the framework or the package sets: an'
                    . ' application that sets the block repeats their entries beside its own.' . PHP_EOL
                    . 'The doctrine.typeOverrides of the cli configuration do not keep "guid" =>'
                    . ' ampf\Kit\Doctrine\Type\UuidType, which the framework or the package sets: an application that sets'
                    . ' the block repeats their entries beside its own.',
            ],
            GuardFailures::of($test, $guard::overrides()),
        );
    }

    public function testAnApplicationWithoutACommandLineNamesItsTransports(): void
    {
        $guard = new class('web only') extends DoctrineSettingsGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/DoctrineSettingsGuard/Breaking';
            }

            /**
             * @return list<string>
             */
            protected static function transports(): array
            {
                return ['http'];
            }
        };

        self::assertSame(
            ['http typeOverrides' => ['http', 'typeOverrides'], 'http mappingOverrides' => ['http', 'mappingOverrides']],
            iterator_to_array($guard::overrides()),
        );
    }
}
