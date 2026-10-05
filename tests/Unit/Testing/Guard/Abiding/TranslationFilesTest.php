<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard\Abiding;

use ampf\Kit\Testing\Guard\TranslationFilesGuard;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * An application's small class that names its languages and points the guard at its translation files (here the
 * fixture's), run by PHPUnit as an application runs it. English is the base language: it comes first.
 */
#[CoversClass(TranslationFilesGuard::class)]
final class TranslationFilesTest extends TranslationFilesGuard
{
    protected static function projectRoot(): string
    {
        return dirname(__DIR__, 4) . '/Fixtures/TranslationFilesGuard/Abiding';
    }

    /**
     * @return list<string>
     */
    protected static function languages(): array
    {
        return ['en', 'de'];
    }
}
