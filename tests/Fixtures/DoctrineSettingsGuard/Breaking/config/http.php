<?php

declare(strict_types=1);

// The web replaces the database types that are read as DBAL's, and loses both entries of the framework's and the package's
return [
    'doctrine' => [
        'mappingOverrides' => [],
    ],
];
