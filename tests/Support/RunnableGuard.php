<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Support;

/**
 * What the double of a guard that boots an application offers the test of the guard (RunsAsAGuard implements it).
 */
interface RunnableGuard
{
    /**
     * The files of the configuration from now on end with this one: it replaces the beans and blocks it names; of the web's
     * configuration only, or of the command line's only, when the transport is given.
     */
    public function overlaid(string $file, ?string $transport = null): static;

    /** A setting that makes the double what it is: the double's own code reads it. */
    public function with(string $name, string $value): static;

    /**
     * The guard's test with its arguments, between a set up and a tear down.
     *
     * @param list<mixed> $arguments
     */
    public function runAsAGuard(string $test, array $arguments = []): void;

    /** The message of the failure of the guard's test with the arguments; null when it passes. */
    public function failureOf(string $test, mixed ...$arguments): ?string;

    /**
     * The messages of the failures of the guard's test, by the name of the data set that fails.
     *
     * @param iterable<int|string, array<mixed>> $dataSets
     *
     * @return array<int|string, string>
     */
    public function failuresOf(string $test, iterable $dataSets): array;
}
