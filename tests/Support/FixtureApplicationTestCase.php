<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Support;

use ampf\Kit\Testing\IntegrationTestCase;

/**
 * The package's integration tests: its IntegrationTestCase over the fixture application (tests/Fixtures/App), an
 * application on ampf and the package with a page for the logged in, the login, the logout and a table of its own.
 */
abstract class FixtureApplicationTestCase extends IntegrationTestCase
{
    protected static function projectRoot(): string
    {
        return dirname(__DIR__) . '/Fixtures/App';
    }
}
