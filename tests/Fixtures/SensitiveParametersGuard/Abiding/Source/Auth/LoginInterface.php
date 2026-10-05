<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Abiding\Source\Auth;

use SensitiveParameter;

/** An interface with a method whose password is marked. */
interface LoginInterface
{
    public function login(string $username, #[SensitiveParameter] string $password): bool;
}
