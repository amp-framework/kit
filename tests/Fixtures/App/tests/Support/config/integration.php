<?php

declare(strict_types=1);

use ampf\Bootstrap\DoctrineConfiguration;
use Pdo\Mysql;

/*
 * The fixture application's configuration for its tests, in place of config/local.php: the last file an
 * IntegrationTestCase over it boots. No secret, nothing of this machine: the database is the disposable MariaDB of
 * docker/compose.test.yml, whose credentials are throw-away values that exist nowhere else.
 */
$host = getenv('AMPF_KIT_TEST_DB_HOST');
// Mutation testing runs several test processes at once, and Infection numbers them in TEST_TOKEN (1, 2, …): each gets a
// database of its own (docker/test-database-init.sh), or one would empty the tables another is working with
$token = getenv('TEST_TOKEN');

return [
    'doctrine' => [
        'configuration' => DoctrineConfiguration::create([dirname(__DIR__, 3) . '/Doctrine/Entity']),
        'connectionParams' => [
            'driver' => 'pdo_mysql',
            'host' => $host === false || $host === '' ? 'database' : $host,
            'port' => 3306,
            'user' => 'ampf_kit_test',
            'password' => 'disposable-test-only',
            'dbname' => $token === false || $token === '' ? 'ampf_kit_test' : 'ampf_kit_test_' . (int)$token,
            'charset' => 'utf8mb4',
            'driverOptions' => [
                Mysql::ATTR_INIT_COMMAND => "SET time_zone = 'UTC';",
            ],
        ],
    ],
    'errors' => [
        'display' => false,
    ],
];
