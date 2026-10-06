<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use ampf\Bootstrap\ApplicationContext;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The application's `doctrine` block next to the framework's and the package's. The files merge one level deep: a nested
 * array that a file sets replaces the whole one of the files merged before it, so an application that sets
 * `typeOverrides` or `mappingOverrides` itself must still hold every entry that ampf's and the package's files set (every
 * datetime in UTC, the immutable ones too, `guid` as MariaDB's UUID, a UUID column read back as one, an enum read as a
 * string), beside its own.
 * The configuration is merged as the entry points do (AbstractFileGuard::configuration()), for each transport. An
 * application extends the guard in one small class that names its project root.
 */
abstract class DoctrineSettingsGuard extends AbstractFileGuard
{
    /** The blocks of `doctrine` that are replaced as a whole. */
    private const array BLOCKS = ['typeOverrides', 'mappingOverrides'];

    /**
     * Each block of each transport.
     *
     * @return iterable<string, array{string, string}> the transport and the block
     */
    public static function overrides(): iterable
    {
        foreach (static::transports() as $transport) {
            foreach (self::BLOCKS as $block) {
                yield $transport . ' ' . $block => [$transport, $block];
            }
        }
    }

    /**
     * The transports whose configuration is checked: an application without a command line has the web's only.
     *
     * @return list<string>
     */
    protected static function transports(): array
    {
        return ['http', 'cli'];
    }

    /**
     * The entries of a block of `doctrine`.
     *
     * @param array<string, mixed> $configuration
     *
     * @return array<mixed>
     */
    private static function overridesOf(array $configuration, string $block): array
    {
        $doctrine = $configuration['doctrine'];
        assert(is_array($doctrine) && is_array($doctrine[$block]));

        return $doctrine[$block];
    }

    #[DataProvider('overrides')]
    public function testTheOverridesKeepEveryEntryOfTheFrameworkAndThePackage(string $transport, string $block): void
    {
        $theirs = self::overridesOf(ApplicationContext::boot(static::packageConfigurationFiles($transport)), $block);
        $merged = self::overridesOf(static::configuration($transport), $block);
        $problems = [];

        foreach ($theirs as $name => $override) {
            assert(is_string($override));

            if (($merged[$name] ?? null) !== $override) {
                $problems[] = 'The doctrine.' . $block . ' of the ' . $transport . ' configuration do not keep "' . $name
                    . '" => ' . $override . ', which the framework or the package sets: an application that sets the block'
                    . ' repeats their entries beside its own.';
            }
        }

        $this->assertNoProblems($problems);
    }
}
