<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Integration\Testing\Guard\Abiding;

use ampf\Kit\Testing\Guard\MigrationsGuard;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * An application's small class that points the guard at its project (here the fixture application), run by PHPUnit as an
 * application runs it: it names nothing but the project root.
 */
#[CoversClass(MigrationsGuard::class)]
final class MigrationsTest extends MigrationsGuard
{
    protected static function projectRoot(): string
    {
        return dirname(__DIR__, 4) . '/Fixtures/App';
    }
}
