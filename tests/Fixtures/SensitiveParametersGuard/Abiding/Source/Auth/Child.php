<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Abiding\Source\Auth;

/** A class that inherits the methods of its parent and declares none that has a password. */
final class Child extends Login
{
    public function name(string $username, string $note): string
    {
        return $username . $note;
    }
}
