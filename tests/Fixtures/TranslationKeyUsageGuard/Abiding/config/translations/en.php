<?php

declare(strict_types=1);


// The texts of an application whose code names every one, and those of the package, which its own code names
$texts = [
    'app.count' => '{count, plural, one {# note} other {# notes}}',
    'app.greeting' => 'Hello',
    'app.note' => 'A note',
    'app.title' => 'Notes',
];
ksort($texts, SORT_STRING);

return $texts;
