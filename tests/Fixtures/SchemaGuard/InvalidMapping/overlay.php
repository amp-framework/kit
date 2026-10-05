<?php

declare(strict_types=1);

use ampf\Bootstrap\DoctrineConfiguration;

/*
 * The fixture application with one more entity whose mapping the schema tool finds invalid: the application's own
 * entities and the one beside them.
 */
return [
    'doctrine' => [
        'configuration' => DoctrineConfiguration::create([
            dirname(__DIR__, 2) . '/App/Doctrine/Entity',
            __DIR__ . '/Entity',
        ]),
    ],
];
