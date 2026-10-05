<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Kit\Testing\Guard\AbstractSourceGuard;
use ampf\Kit\Testing\Guard\SensitiveParametersGuard;
use ampf\Kit\Tests\Support\GuardFailures;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The guard over classes whose parameters named for a password are all marked `#[SensitiveParameter]`, in a class, an
 * interface, a trait and an enum (tests/Fixtures/SensitiveParametersGuard/Abiding), over a class where some are not
 * (Breaking), and over a file that declares another class than its path names (Misnamed). A guard is a TestCase, which
 * takes its name: PHP-CS-Fixer writes `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
#[CoversClass(AbstractSourceGuard::class)]
#[CoversClass(SensitiveParametersGuard::class)]
final class SensitiveParametersGuardTest extends TestCase
{
    private const string ABIDING = 'ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Abiding\Source';

    private const string BREAKING = 'ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Breaking\Source';

    /** The guard over the classes that mark every password. */
    private static function abiding(): SensitiveParametersGuard
    {
        return new class('abiding') extends SensitiveParametersGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/SensitiveParametersGuard/Abiding';
            }

            protected static function sourceDirectory(): string
            {
                return 'Source';
            }

            protected static function sourceNamespace(): string
            {
                return 'ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Abiding\Source';
            }
        };
    }

    /** The guard over a source that has no parameter named for a secret. */
    private static function plain(): SensitiveParametersGuard
    {
        return new class('plain') extends SensitiveParametersGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/SensitiveParametersGuard/Plain';
            }

            protected static function sourceDirectory(): string
            {
                return 'Source';
            }

            protected static function sourceNamespace(): string
            {
                return 'ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Plain\Source';
            }
        };
    }

    /** The guard over the class that does not. */
    private static function breaking(): SensitiveParametersGuard
    {
        return new class('breaking') extends SensitiveParametersGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/SensitiveParametersGuard/Breaking';
            }

            protected static function sourceDirectory(): string
            {
                return 'Source';
            }

            protected static function sourceNamespace(): string
            {
                return 'ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Breaking\Source';
            }
        };
    }

    public function testEveryParameterNamedForAPasswordIsListedWithTheTypeThatDeclaresItsMethod(): void
    {
        $login = self::ABIDING . '\Auth\Login';

        self::assertSame(
            [
                $login . '::__construct($adminPassword)' => [$login, '__construct', 'adminPassword'],
                $login . '::login($password)' => [$login, 'login', 'password'],
                $login . '::change($currentPassword)' => [$login, 'change', 'currentPassword'],
                $login . '::change($newPassword)' => [$login, 'change', 'newPassword'],
                self::ABIDING . '\Auth\LoginInterface::login($password)' => [
                    self::ABIDING . '\Auth\LoginInterface',
                    'login',
                    'password',
                ],
                self::ABIDING . '\Auth\Remembers::remember($PASSWORD_HASH)' => [
                    self::ABIDING . '\Auth\Remembers',
                    'remember',
                    'PASSWORD_HASH',
                ],
                self::ABIDING . '\Support\Mode::with($password)' => [self::ABIDING . '\Support\Mode', 'with', 'password'],
            ],
            iterator_to_array(self::abiding()::sensitiveParameters()),
        );
    }

    public function testAnApplicationNamesTheWordsThatMarkAParameterAsASecret(): void
    {
        $guard = new class('tokens') extends SensitiveParametersGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/SensitiveParametersGuard/Abiding';
            }

            protected static function sourceDirectory(): string
            {
                return 'Source';
            }

            protected static function sourceNamespace(): string
            {
                return 'ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Abiding\Source';
            }

            /**
             * @return list<string>
             */
            protected static function sensitiveNames(): array
            {
                return ['TOKEN', 'hash'];
            }
        };
        $login = self::ABIDING . '\Auth\Login';

        self::assertSame(
            [
                $login . '::connect($apiToken)' => [$login, 'connect', 'apiToken'],
                self::ABIDING . '\Auth\Remembers::remember($PASSWORD_HASH)' => [
                    self::ABIDING . '\Auth\Remembers',
                    'remember',
                    'PASSWORD_HASH',
                ],
            ],
            iterator_to_array($guard::sensitiveParameters()),
        );
    }

    public function testThePasswordIsTheWordThatMakesAParameterASecretUnlessTold(): void
    {
        $guard = new class('words') extends SensitiveParametersGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            protected static function sourceNamespace(): string
            {
                return 'App';
            }

            /**
             * @return list<string>
             */
            public static function words(): array
            {
                return self::sensitiveNames();
            }
        };

        self::assertSame(['password'], $guard::words());
    }

    public function testParametersThatAreMarkedPass(): void
    {
        $guard = self::abiding();
        $test = $guard->testTheParameterIsMarkedSensitive(...);

        self::assertSame([], GuardFailures::of($test, $guard::parametersToCheck()));
        self::assertSame(7, $guard->numberOfAssertionsPerformed());
    }

    public function testAnApplicationWithParametersNamedForASecretChecksThemAndNothingElse(): void
    {
        $guard = self::abiding();

        self::assertSame(
            iterator_to_array($guard::sensitiveParameters()),
            iterator_to_array($guard::parametersToCheck()),
        );
    }

    /** Nothing to be unmarked is a pass that says so, and no test that was skipped, or that was never run. */
    public function testAnApplicationWithoutAParameterNamedForASecretPassesWithADataSetThatSaysSo(): void
    {
        $guard = self::plain();

        self::assertSame([], iterator_to_array($guard::sensitiveParameters()));
        self::assertSame(
            ['no parameter is named for a secret' => [null, null, null]],
            iterator_to_array($guard::parametersToCheck()),
        );
        self::assertSame(
            [],
            GuardFailures::of($guard->testTheParameterIsMarkedSensitive(...), $guard::parametersToCheck()),
        );
        self::assertSame(1, $guard->numberOfAssertionsPerformed());
    }

    public function testParametersThatAreNotMarkedFailAndSayWhere(): void
    {
        $guard = self::breaking();
        $unmarked = self::BREAKING . '\Auth\Unmarked';
        $reason = ' is not marked #[SensitiveParameter]: a stack trace would show what was typed.';

        self::assertSame(
            [
                $unmarked . '::__construct($passwordHash)' => $unmarked . '::__construct($passwordHash)' . $reason,
                $unmarked . '::login($password)' => $unmarked . '::login($password)' . $reason,
                $unmarked . '::change($newPassword)' => $unmarked . '::change($newPassword)' . $reason,
            ],
            GuardFailures::of($guard->testTheParameterIsMarkedSensitive(...), $guard::sensitiveParameters()),
        );
    }

    public function testAFileThatDeclaresAnotherClassThanItsPathNamesFailsTheListing(): void
    {
        $guard = new class('misnamed') extends SensitiveParametersGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/SensitiveParametersGuard/Misnamed';
            }

            protected static function sourceDirectory(): string
            {
                return 'Source';
            }

            protected static function sourceNamespace(): string
            {
                return 'ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Misnamed\Source';
            }
        };

        try {
            iterator_to_array($guard::sensitiveParameters());
            self::fail('The guard passed.');
        } catch (AssertionFailedError $e) {
            self::assertSame(
                'The file Source/Auth/Mismatch.php does not declare '
                . 'ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Misnamed\Source\Auth\Mismatch, the type its path names.',
                $e->getMessage(),
            );
        }
    }
}
