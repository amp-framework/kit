<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard\Abiding;

use ampf\Kit\Testing\Guard\DoctrineSettingsGuard;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * An application's small class that points the guard at its configuration (here the fixture's, which sets both blocks
 * and repeats every entry of the framework's and the package's), run by PHPUnit as an application runs it.
 */
#[CoversClass(DoctrineSettingsGuard::class)]
final class DoctrineSettingsTest extends DoctrineSettingsGuard
{
    protected static function projectRoot(): string
    {
        return dirname(__DIR__, 4) . '/Fixtures/DoctrineSettingsGuard/Repeating';
    }
}
