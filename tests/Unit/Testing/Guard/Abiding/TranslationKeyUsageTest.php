<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard\Abiding;

use ampf\Kit\Testing\Guard\TranslationKeyUsageGuard;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * An application's small class that names its languages and points the guard at its code and templates (here the
 * fixture's), run by PHPUnit as an application runs it.
 */
#[CoversClass(TranslationKeyUsageGuard::class)]
final class TranslationKeyUsageTest extends TranslationKeyUsageGuard
{
    protected static function projectRoot(): string
    {
        return dirname(__DIR__, 4) . '/Fixtures/TranslationKeyUsageGuard/Abiding';
    }

    /**
     * @return list<string>
     */
    protected static function languages(): array
    {
        return ['en'];
    }
}
