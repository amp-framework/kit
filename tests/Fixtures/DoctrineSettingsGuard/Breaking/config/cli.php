<?php

declare(strict_types=1);

use ampf\Doctrine\Type\UTCDateTimeType;

// The command line replaces the types: the UUID is another class, and the immutable datetimes are not there
return [
    'doctrine' => [
        'typeOverrides' => [
            'datetime' => UTCDateTimeType::class,
            'datetimetz' => UTCDateTimeType::class,
            'guid' => 'Elsewhere\Doctrine\UuidType',
        ],
    ],
];
