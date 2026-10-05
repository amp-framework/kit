<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Helper;

use ampf\Kit\Helper\Text;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** What the rules for the texts a user types have in common. */
final class TextTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function texts(): iterable
    {
        yield 'nothing to cut' => ['Backup', 'Backup'];
        yield 'spaces around' => ["  Backup 2\t ", 'Backup 2'];
        yield 'lines around' => ["\r\nBackup\n", 'Backup'];
        yield 'spaces and lines inside stay' => ["Back  up\n2", "Back  up\n2"];
        yield 'a no-break space around' => ["\u{00A0}Backup\u{00A0}", 'Backup'];
        yield 'an ideographic space around' => ["\u{3000}映画\u{3000}", '映画'];
        yield 'nothing but spaces' => ["  \n ", ''];
        yield 'empty' => ['', ''];
        yield 'bytes that are no UTF-8 stay as they are' => ["\xFF\xFE", "\xFF\xFE"];
    }

    /** @return iterable<string, array{string, bool, bool}> a text, whether it is one line, whether it is lines */
    public static function characters(): iterable
    {
        yield 'a word' => ['Backup', true, true];
        yield 'nothing' => ['', true, true];
        yield 'any script' => ['映画 Größe 🎬', true, true];
        yield 'a line feed' => ["two\nlines", false, true];
        yield 'a line feed at the end' => ["one line\n", false, true];
        yield 'two line feeds in a row' => ["two\n\nlines", false, true];
        yield 'a carriage return' => ["two\rlines", false, false];
        yield 'a tab' => ["a\tb", false, false];
        yield 'a tab beside a line feed' => ["a\n\tb", false, false];
        yield 'a NUL' => ["a\0b", false, false];
        yield 'a delete character' => ["a\x7Fb", false, false];
        yield 'a control character of the second block' => ["a\u{0085}b", false, false];
        yield 'bytes that are no UTF-8' => ["a\xFFb", false, false];
        yield 'bytes that are no UTF-8 beside a line feed' => ["a\n\xFF", false, false];
    }

    #[DataProvider('texts')]
    public function testATextIsKeptWithoutTheWhiteSpaceAtItsEnds(string $text, string $kept): void
    {
        self::assertSame($kept, Text::trim($text));
    }

    #[DataProvider('characters')]
    public function testWhichCharactersATextMayHold(string $text, bool $oneLine, bool $lines): void
    {
        self::assertSame($oneLine, Text::isOneLine($text), 'one line');
        self::assertSame($lines, Text::isLines($text), 'lines');
    }
}
