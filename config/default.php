<?php

declare(strict_types=1);

use ampf\Doctrine\Type\UTCDateTimeType;
use ampf\Kit\Doctrine\Type\UtcDateTimeImmutableType;
use ampf\Kit\Doctrine\Type\UuidType;

/*
 * The package's configuration, loaded by an application's entry points after ampf's own and before the application's
 * (config/default.php of the application, then config/http.php or config/cli.php, then this machine's config/local.php).
 * The files merge one level deep (ampf's README, "Configuration merge"): a later file replaces one bean, or one entry of
 * a block, as a whole.
 *
 * The database: `configuration` and `connectionParams` are the machine's (config/local.php). A block of this file
 * replaces the whole one of ampf's, so each repeats ampf's entries (DoctrineSettingsTest holds them): every datetime in
 * UTC, a database's enum read as a string; and the package adds its own: the immutable datetimes in UTC as well, and
 * `guid` as MariaDB's UUID, which is read back as one. `datetime_immutable` is what the ORM chooses for a
 * DateTimeImmutable; `datetimetz_immutable` is, on MariaDB, a column without a time zone, like `datetimetz`, which would
 * hold the time of whichever zone its value happens to have.
 */
return [
    'doctrine' => [
        'typeOverrides' => [
            'datetime' => UTCDateTimeType::class,
            'datetimetz' => UTCDateTimeType::class,
            'datetime_immutable' => UtcDateTimeImmutableType::class,
            'datetimetz_immutable' => UtcDateTimeImmutableType::class,
            'guid' => UuidType::class,
        ],
        'mappingOverrides' => [
            'enum' => 'string',
            'uuid' => 'guid',
        ],
    ],
];
