<?php

declare(strict_types=1);


// The same keys in German, with the same arguments (in any order)
$texts = [
    'app.bold' => 'Drücke <strong>Speichern</strong> &amp; <em>los</em>',
    'app.count' => '{count, plural, one {# Notiz} other {# Notizen}}',
    'app.greeting' => 'Hallo, %s!',
    'app.pair' => '%2$s und %1$s',
    'app.title' => 'Notizen',
];
ksort($texts, SORT_STRING);

return $texts;
