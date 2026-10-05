<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Integration\Testing\Guard\Abiding;

use ampf\Kit\Testing\Guard\SchemaGuard;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * An application's small class that points the guard at its project (here the fixture application), run by PHPUnit as an
 * application runs it: it names nothing but the project root.
 */
#[CoversClass(SchemaGuard::class)]
final class SchemaTest extends SchemaGuard
{
    protected static function projectRoot(): string
    {
        return dirname(__DIR__, 4) . '/Fixtures/App';
    }
}
