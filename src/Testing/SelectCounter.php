<?php

declare(strict_types=1);

namespace ampf\Kit\Testing;

/**
 * How many SELECT statements every connection of the process has sent: the test's own and those of every scope a request
 * or a command runs in, which each have a session of their own with the database, so that the database's status for one
 * session counts only one of them. SelectCountingMiddleware counts; IntegrationTestCase::countSelects() reads.
 */
class SelectCounter
{
    private static int $selects = 0;

    /** What was sent so far. */
    public static function total(): int
    {
        return self::$selects;
    }

    /** Counts the statement when it is a SELECT: a read, whatever white space or parentheses stand in front of it. */
    public static function note(string $sql): void
    {
        if (preg_match('/^[\s(]*SELECT\b/i', $sql) === 1) {
            self::$selects++;
        }
    }
}
