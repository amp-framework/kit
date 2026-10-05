<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Breaking\Source\Auth;

use SensitiveParameter;

/** A class with parameters named for a password that nothing marks, among those that are marked. */
class Unmarked
{
    public function __construct(
        private readonly string $passwordHash,
        #[SensitiveParameter]
        private readonly string $adminPassword,
    ) {
    }

    public function login(string $username, string $password): bool
    {
        return $username !== '' && $password === $this->adminPassword && $this->passwordHash !== '';
    }

    public function change(#[SensitiveParameter] string $currentPassword, string $newPassword): bool
    {
        return $currentPassword !== $newPassword;
    }
}
