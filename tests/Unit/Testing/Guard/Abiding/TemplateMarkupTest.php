<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard\Abiding;

use ampf\Kit\Testing\Guard\TemplateMarkupGuard;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * An application's small class that points the guard at its templates (here the fixture's), run by PHPUnit as an
 * application runs it. The login's page puts the focus on its field, so its error alert does not take it.
 */
#[CoversClass(TemplateMarkupGuard::class)]
final class TemplateMarkupTest extends TemplateMarkupGuard
{
    protected static function projectRoot(): string
    {
        return dirname(__DIR__, 4) . '/Fixtures/TemplateMarkupGuard/Abiding';
    }

    /**
     * @return list<string>
     */
    protected static function templatesWithoutAlertFocus(): array
    {
        return ['views/http/login.html.php'];
    }
}
