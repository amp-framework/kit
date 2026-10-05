<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Abiding\Source\Auth;

use SensitiveParameter;

/** A trait with a parameter named for a password in capitals. */
trait Remembers
{
    public function remember(#[SensitiveParameter] string $PASSWORD_HASH): string
    {
        return $PASSWORD_HASH;
    }
}
