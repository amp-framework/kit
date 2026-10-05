<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit;

use ampf\Kit\Testing\Guard\SensitiveParametersGuard;

/**
 * A parameter of the package that carries a password (or its hash) is marked `#[SensitiveParameter]`: the guard that the
 * applications use, pointed at the package's own source. A new kind of secret is a word of the guard's list.
 */
final class SensitiveParametersTest extends SensitiveParametersGuard
{
    protected static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    protected static function sourceNamespace(): string
    {
        return 'ampf\Kit';
    }
}
