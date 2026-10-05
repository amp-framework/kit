<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Abiding\Source\Auth;

use SensitiveParameter;

/** A class whose parameters named for a password are all marked, one of them a promoted property. */
class Login
{
    public function __construct(
        #[SensitiveParameter]
        private readonly string $adminPassword,
    ) {
    }

    public function login(string $username, #[SensitiveParameter] string $password): bool
    {
        return $username !== '' && $password === $this->adminPassword;
    }

    public function change(#[SensitiveParameter] string $currentPassword, #[SensitiveParameter] string $newPassword): bool
    {
        return $currentPassword !== $newPassword;
    }

    public function connect(string $username, string $apiToken): string
    {
        return $username . $apiToken;
    }
}
