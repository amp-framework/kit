<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\TranslationKeyUsageGuard\Abiding\Controller;

/** A controller that names keys of the translation files, and strings that only look like keys. */
final class PageController
{
    /** @return list<string> */
    public function keys(): array
    {
        return ['app.title', 'app.count', 'app.title'];
    }

    /** @return list<string> strings that are written like keys of a group that no translation file has */
    public function files(): array
    {
        return ['composer.json', 'image.png', 'jquery.min.js', "app.double"];
    }
}
