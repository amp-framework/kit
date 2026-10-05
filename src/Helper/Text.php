<?php

declare(strict_types=1);

namespace ampf\Kit\Helper;

/**
 * What the rules for the texts a user types have in common: how a text is kept (no white space at its ends) and what
 * characters it may hold. A text that is no UTF-8 holds the wrong characters.
 */
class Text
{
    /** The text as it is kept: without the white space (of any script) at its start and its end. */
    public static function trim(string $text): string
    {
        return preg_replace('/^\s+|\s+$/u', '', $text) ?? $text;
    }

    /** Whether the text is one line: no control character in it, so no line feed, no tab and no NUL. */
    public static function isOneLine(string $text): bool
    {
        return preg_match('/^[^\p{Cc}]*$/uD', $text) === 1;
    }

    /** Whether the text is lines of text: the line feed is its one control character. */
    public static function isLines(string $text): bool
    {
        return self::isOneLine(str_replace("\n", '', $text));
    }
}
