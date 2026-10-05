<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionParameter;
use SensitiveParameter;

/**
 * A parameter that carries a password (or its hash) is marked `#[SensitiveParameter]`: a stack trace then shows an
 * object in its place, whatever the php.ini says about the arguments of a trace, so that what was typed is never written
 * to a log by a failure that happens below the call. Every parameter of every method of the source whose name has the
 * word `password` in it, whatever the case, is held to it; an application names more words (a token, a key) in the small
 * class of its tests that extends the guard. A source with no such parameter has nothing that is unmarked, which is a pass,
 * reported as the data set "no parameter is named for a secret", and no test that was skipped:
 *
 *     protected static function sensitiveNames(): array { return [...parent::sensitiveNames(), 'token']; }
 */
abstract class SensitiveParametersGuard extends AbstractSourceGuard
{
    /**
     * Each parameter whose name says that it carries a secret, in the methods that the class of its file declares itself.
     *
     * @return iterable<string, array{string, string, string}> the type, the method and the parameter's name
     */
    public static function sensitiveParameters(): iterable
    {
        foreach (static::sourceTypes(static::sourceDirectory(), '.php') as $file => $type) {
            $methods = array_filter(
                static::reflect($type, $file)->getMethods(),
                static fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $type,
            );

            foreach ($methods as $method) {
                foreach ($method->getParameters() as $parameter) {
                    if (self::carriesASecret($parameter->getName())) {
                        $name = $parameter->getName();

                        yield $type . '::' . $method->getName() . '($' . $name . ')' => [$type, $method->getName(), $name];
                    }
                }
            }
        }
    }

    /**
     * The parameters to check, and when the source has none the one data set that says so: its test passes, since nothing
     * is unmarked, and the run reports it instead of skipping a test whose data is empty.
     *
     * @return iterable<string, array{string|null, string|null, string|null}> the type, the method and the parameter's name
     */
    public static function parametersToCheck(): iterable
    {
        $found = false;

        foreach (static::sensitiveParameters() as $description => $parameter) {
            $found = true;

            yield $description => $parameter;
        }

        if (!$found) {
            yield 'no parameter is named for a secret' => [null, null, null];
        }
    }

    /**
     * The words that make a parameter one that carries a secret, when its name has one of them in it whatever the case.
     *
     * @return list<string>
     */
    protected static function sensitiveNames(): array
    {
        return ['password'];
    }

    private static function carriesASecret(string $name): bool
    {
        return array_any(
            static::sensitiveNames(),
            static fn (string $word): bool => stripos($name, $word) !== false,
        );
    }

    #[DataProvider('parametersToCheck')]
    public function testTheParameterIsMarkedSensitive(?string $type, ?string $method, ?string $name): void
    {
        if ($type === null) {
            // The data set of a source with no parameter named for a secret, which has none unmarked
            $this->addToAssertionCount(1);

            return;
        }
        assert($method !== null && $name !== null);
        $parameter = array_find(
            new ReflectionMethod($type, $method)->getParameters(),
            static fn (ReflectionParameter $candidate): bool => $candidate->getName() === $name,
        );
        assert($parameter instanceof ReflectionParameter);
        $problems = [];

        if ($parameter->getAttributes(SensitiveParameter::class) === []) {
            $problems[] = $type . '::' . $method . '($' . $name . ') is not marked #[SensitiveParameter]: a stack trace'
                . ' would show what was typed.';
        }

        $this->assertNoProblems($problems);
    }
}
