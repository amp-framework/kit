<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Integration;

use ampf\Kit\Tests\Support\FixtureApplicationTestCase;
use RuntimeException;

/**
 * An application may tighten the pattern of the databases its tests may empty, to the names of its own test stack: a
 * database it does not take is refused before anything is changed in it, with a message that names the pattern and the
 * database — even the package's own disposable one, which the default pattern takes.
 */
final class DisposableDatabasePatternTest extends FixtureApplicationTestCase
{
    private ?RuntimeException $refusal = null;

    public function testADatabaseThePatternDoesNotTakeIsRefused(): void
    {
        $database = self::dbText($this->em->getConnection()->fetchOne('SELECT DATABASE()'));

        self::assertSame(
            'The integration tests empty every table, so they run only against a disposable database whose name matches'
            . ' /^staging_test$/D, not against \'' . $database . '\'.',
            $this->refusal?->getMessage(),
        );
    }

    /** Only the staging machine's test database. */
    protected function disposableDatabasePattern(): string
    {
        return '/^staging_test$/D';
    }

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (RuntimeException $refusal) {
            $this->refusal = $refusal;
        }
    }
}
