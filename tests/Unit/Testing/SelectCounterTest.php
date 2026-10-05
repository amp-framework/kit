<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing;

use ampf\Kit\Testing\SelectCounter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** What counts as a SELECT: a read, whatever white space or parentheses stand in front of it, and nothing else. */
final class SelectCounterTest extends TestCase
{
    /** @return iterable<string, array{string, int}> a statement and how many SELECTs it adds */
    public static function statements(): iterable
    {
        yield 'a select' => ['SELECT 1', 1];
        yield 'in small letters' => ['select 1', 1];
        yield 'in mixed letters' => ['SeLeCt 1', 1];
        yield 'after white space' => ["  \n\tSELECT 1", 1];
        yield 'in parentheses, as a union begins' => ['(SELECT 1) UNION (SELECT 2)', 1];
        yield 'in many parentheses' => ['((SELECT 1))', 1];
        yield 'with a line feed after it' => ["SELECT\n1", 1];
        yield 'with nothing after it' => ['SELECT', 1];
        yield 'an insert from a select is a write' => ['INSERT INTO notes SELECT * FROM old_notes', 0];
        yield 'an update' => ['UPDATE notes SET text = 1', 0];
        yield 'a show' => ['SHOW TABLES', 0];
        yield 'a set with a select in it' => ['SET @a = (SELECT 1)', 0];
        yield 'a word that only begins like one' => ['SELECTED 1', 0];
        yield 'a text in front of it' => ['x SELECT 1', 0];
        yield 'nothing' => ['', 0];
    }

    #[DataProvider('statements')]
    public function testOnlyAReadIsCounted(string $sql, int $added): void
    {
        $before = SelectCounter::total();

        SelectCounter::note($sql);

        self::assertSame($before + $added, SelectCounter::total());
    }
}
