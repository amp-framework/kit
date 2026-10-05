<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Abiding\Source\Support;

use SensitiveParameter;

/** An enum with a method whose password is marked. */
enum Mode: string
{
    case On = 'on';

    public function with(#[SensitiveParameter] string $password): string
    {
        return $this->value . $password;
    }
}
