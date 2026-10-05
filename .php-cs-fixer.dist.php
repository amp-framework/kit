<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

// The rules are ampf's, as the PHPCS standard is (phpcs.xml.dist): both formatters come from the framework, so they
// agree. The package names its own files and cache.
$config = require __DIR__ . '/vendor/amp-framework/ampf/.php-cs-fixer.dist.php';

if (!$config instanceof Config) {
    throw new RuntimeException('vendor/amp-framework/ampf/.php-cs-fixer.dist.php returned no PHP-CS-Fixer configuration.');
}

return $config
    ->setCacheFile(__DIR__ . '/cache/php-cs-fixer.cache')
    ->setFinder(
        Finder::create()
            ->in([__DIR__ . '/src', __DIR__ . '/config', __DIR__ . '/tests'])
            // The fixtures of the guards (tests/Fixtures/<Guard>) are the applications that a guard judges, the bad ones
            // on purpose: nothing formats them
            ->notPath('#^Fixtures/\w+Guard/#')
            ->append([__FILE__]),
    )
;
