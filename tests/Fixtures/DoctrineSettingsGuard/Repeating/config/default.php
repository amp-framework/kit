<?php

declare(strict_types=1);

use ampf\Doctrine\Type\UTCDateTimeType;
use ampf\Kit\Doctrine\Type\UuidType;

// An application that sets both blocks itself: every entry of the framework's and the package's, and its own beside them
return [
    'doctrine' => [
        'typeOverrides' => [
            'datetime' => UTCDateTimeType::class,
            'datetimetz' => UTCDateTimeType::class,
            'guid' => UuidType::class,
            'money' => 'App\Doctrine\Type\MoneyType',
        ],
        'mappingOverrides' => [
            'enum' => 'string',
            'uuid' => 'guid',
            'money' => 'integer',
        ],
    ],
];
