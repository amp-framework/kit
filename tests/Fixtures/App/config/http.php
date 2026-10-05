<?php

declare(strict_types=1);

use ampf\Kit\Tests\Fixtures\App\Controller\Http\NotesController;

/*
 * The fixture application's web: one page, which asks the database. ampf's own routes and beans are the rest.
 */
return [
    'routes' => [
        'notes' => ['pattern' => 'notes', 'controller' => 'NotesController'],
    ],

    'beans' => [
        'NotesController' => ['class' => NotesController::class],
    ],
];
