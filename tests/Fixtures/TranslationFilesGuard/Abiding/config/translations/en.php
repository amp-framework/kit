<?php

declare(strict_types=1);


// The texts of an application that keeps the rules and those of the package, sorted by their keys
$texts = [
    'app.bold' => 'Press <strong>Save</strong> &amp; <em>go</em>',
    'app.count' => '{count, plural, one {# note} other {# notes}}',
    'app.greeting' => 'Hello, %s!',
    'app.pair' => '%1$s and %2$s',
    'app.title' => 'Notes',
];
ksort($texts, SORT_STRING);

return $texts;
