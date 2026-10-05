<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\SensitiveParametersGuard\Plain\Source\Notes;

/** A class with no parameter that is named for a secret: the source of an application that handles no password. */
final class Shelf
{
    public function __construct(private readonly string $title)
    {
    }

    public function put(string $note, int $position): string
    {
        return $this->title . $position . $note;
    }
}
