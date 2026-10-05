<?php

declare(strict_types=1);

use ampf\Kit\Bootstrap\DoctrineConfiguration;

/*
 * The fixture application's configuration for both transports, as an application on the package has it: the migrations
 * of its one table.
 */
return [
    'migrations' => [
        'migrations_paths' => [
            'ampf\Kit\Tests\Fixtures\App\Migration' => dirname(__DIR__) . '/Migration',
        ],
        'table_storage' => ['table_name' => DoctrineConfiguration::MIGRATIONS_TABLE],
        'transactional' => false,
    ],
];
