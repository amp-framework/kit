<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Support;

use Throwable;

/**
 * For the double of a guard that boots an application (an anonymous subclass of the guard in the test of the guard):
 * runs one of the guard's tests the way PHPUnit does, set up, the test, tear down, so that the test of the guard can say
 * how it failed; keeps the few settings that make the double what it is (a TestCase has a constructor of its name only);
 * and puts a configuration file of the test's own after the application's, where a bean or a block that the application
 * configures is replaced by a bad one (tests/Fixtures/<Guard>/<Variant>/overlay.php).
 */
trait RunsAsAGuard
{
    private ?string $overlay = null;

    private ?string $overlayTransport = null;

    /**
     * @var array<string, string>
     */
    private array $doubleSettings = [];

    public function overlaid(string $file, ?string $transport = null): static
    {
        $this->overlay = $file;
        $this->overlayTransport = $transport;

        return $this;
    }

    public function with(string $name, string $value): static
    {
        $this->doubleSettings[$name] = $value;

        return $this;
    }

    /**
     * The guard's test with its arguments, between a set up and a tear down. The failure of the test is the one that is
     * thrown, or else the tear down's: the first of them, as PHPUnit reports.
     *
     * @param list<mixed> $arguments
     */
    public function runAsAGuard(string $test, array $arguments = []): void
    {
        $failure = null;
        $this->setUp();

        try {
            $this->{$test}(...$arguments);
        } catch (Throwable $thrown) {
            $failure = $thrown;
        }

        try {
            $this->tearDown();
        } catch (Throwable $thrown) {
            $failure ??= $thrown;
        }

        if ($failure !== null) {
            throw $failure;
        }
    }

    public function failureOf(string $test, mixed ...$arguments): ?string
    {
        return GuardFailures::message(fn () => $this->runAsAGuard($test, array_values($arguments)));
    }

    /**
     * @param iterable<int|string, array<mixed>> $dataSets
     *
     * @return array<int|string, string>
     */
    public function failuresOf(string $test, iterable $dataSets): array
    {
        return GuardFailures::of(
            fn (mixed ...$arguments) => $this->runAsAGuard($test, array_values($arguments)),
            $dataSets,
        );
    }

    /** A setting of the double (`with()`); the double's own code reads it. */
    protected function setting(string $name): string
    {
        return $this->doubleSettings[$name];
    }

    /**
     * @return list<string>
     */
    protected function configurationFiles(string $transport): array
    {
        $files = parent::configurationFiles($transport);

        return $this->overlay === null || ($this->overlayTransport !== null && $this->overlayTransport !== $transport)
            ? $files
            : [...$files, $this->overlay];
    }
}
