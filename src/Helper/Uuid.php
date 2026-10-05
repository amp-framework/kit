<?php

declare(strict_types=1);

namespace ampf\Kit\Helper;

use InvalidArgumentException;

/**
 * The ids of every entity: UUIDs of version 7 (RFC 9562), assigned by the application when an object is created. The
 * first 48 bits are the time in milliseconds, so ids sort roughly by creation and a table's index grows at its end;
 * the rest is random. An id is written as the database returns it: lowercase, in groups of 8-4-4-4-12.
 */
class Uuid
{
    /** A UUID's text, as a pattern for a route or a check (any version of RFC 9562). */
    public const string PATTERN = '[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';

    /** A new id for the current time. */
    public static function generate(): string
    {
        return self::v7((int)(microtime(true) * 1000), random_bytes(10));
    }

    /**
     * An id of version 7: the time, then the version and the variant set into the random bytes.
     *
     * @param int $milliseconds the Unix time in milliseconds, which fits 48 bits
     * @param string $random ten bytes
     *
     * @throws InvalidArgumentException for a time that does not fit 48 bits and for other than ten bytes
     */
    public static function v7(int $milliseconds, string $random): string
    {
        if ($milliseconds < 0 || $milliseconds >= 2 ** 48) {
            throw new InvalidArgumentException('A UUID\'s time is 48 bits of milliseconds, not ' . $milliseconds . '.');
        }

        if (strlen($random) !== 10) {
            throw new InvalidArgumentException('A UUID takes ten random bytes, not ' . strlen($random) . '.');
        }

        $bytes = substr(pack('J', $milliseconds), 2) . $random;
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }

    /** Whether the text is a UUID in the form of this class. */
    public static function isValid(string $text): bool
    {
        return preg_match('/^' . self::PATTERN . '$/D', $text) === 1;
    }
}
