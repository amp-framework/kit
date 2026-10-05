<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Support;

use PHPUnit\Framework\AssertionFailedError;

/**
 * What a guard's test says when it fails, for the tests of the guards: the message of the failure, whole.
 */
final class GuardFailures
{
    /**
     * The message that the test fails with for the arguments; null when it passes.
     *
     * @param callable $test the guard's test, which gets the arguments
     */
    public static function message(callable $test, mixed ...$arguments): ?string
    {
        try {
            $test(...$arguments);
        } catch (AssertionFailedError $failure) {
            return $failure->getMessage();
        }

        return null;
    }

    /**
     * The messages that the test fails with, by the name of the data set that makes it fail: the data sets that pass are
     * not there.
     *
     * @param callable $test the guard's test, which gets the arguments of each data set
     * @param iterable<int|string, array<mixed>> $dataSets
     *
     * @return array<int|string, string>
     */
    public static function of(callable $test, iterable $dataSets): array
    {
        $failures = [];

        foreach ($dataSets as $name => $arguments) {
            $message = self::message($test, ...$arguments);

            if ($message !== null) {
                $failures[$name] = $message;
            }
        }

        return $failures;
    }
}
