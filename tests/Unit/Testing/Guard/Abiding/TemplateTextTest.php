<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard\Abiding;

use ampf\Kit\Testing\Guard\TemplateTextGuard;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * An application's small class that points the guard at its templates (here the fixture's), run by PHPUnit as an
 * application runs it: the data providers, the attributes and the names of the tests are the ones PHPUnit reads. The
 * wordmark's pieces are the literals its templates may say.
 */
#[CoversClass(TemplateTextGuard::class)]
final class TemplateTextTest extends TemplateTextGuard
{
    protected static function projectRoot(): string
    {
        return dirname(__DIR__, 4) . '/Fixtures/TemplateTextGuard/Abiding';
    }

    /**
     * @return list<string>
     */
    protected static function allowedLiterals(): array
    {
        return [...parent::allowedLiterals(), 'Note', 'Book'];
    }
}
