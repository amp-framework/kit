<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\TranslationKeyUsageGuard\Breaking\Service;

/** A service that names a key that is right, and one with a typo, twice. */
final class Mailer
{
    public function greeting(): string
    {
        return 'app.greeting';
    }

    public function subject(): string
    {
        return 'app.titel' . 'app.titel';
    }
}
