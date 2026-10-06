<?php

declare(strict_types=1);

namespace ampf\Kit\Doctrine\Type;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;

/**
 * DBAL's `datetime_immutable` with every value in UTC: any DateTimeInterface is written as its UTC time, and the value
 * itself stays as it is, in its own zone; a value read is a DateTimeImmutable in UTC, whatever time zone PHP has as its
 * default. The column is DBAL's DATETIME, which holds whole seconds: the fraction of a second of a value written is cut
 * off (not rounded), and a value read has none. The package puts it in place of `datetime_immutable`, which the ORM
 * chooses for a property or a parameter that is a DateTimeImmutable, and of `datetimetz_immutable`
 * (`doctrine.typeOverrides` in its config/default.php), so that no mapping has to remember it: DBAL 4.5's own
 * `datetime_utc_immutable` is a name of its own, which the ORM never chooses by itself.
 *
 * Unlike DBAL's type it also writes a mutable DateTime, and it reads the platform's format only: a text of another
 * format (an empty one, one with a fraction of a second or a zone, a word PHP would guess at) is refused, so the zone
 * of a value read is always UTC.
 */
class UtcDateTimeImmutableType extends DateTimeImmutableType
{
    /**
     * The UTC zone of every conversion, created once: a zone does not change, and every datetime of an entity is
     * converted.
     */
    protected static ?DateTimeZone $utc = null;

    protected static function utc(): DateTimeZone
    {
        return static::$utc ??= new DateTimeZone('UTC');
    }

    /**
     * The value's UTC time as text, to the second; the value is not changed.
     *
     * @throws InvalidType for a value that is neither null nor a DateTimeInterface
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof DateTimeInterface) {
            throw InvalidType::new($value, static::class, ['null', DateTimeInterface::class]);
        }

        return parent::convertToDatabaseValue(
            DateTimeImmutable::createFromInterface($value)->setTimezone(static::utc()),
            $platform,
        );
    }

    /**
     * The value read as a DateTimeImmutable in UTC; null and a DateTimeImmutable are taken as they are.
     *
     * @throws InvalidFormat for a text that is no datetime of the platform's format
     * @throws InvalidType for a value that is neither null, a text nor a DateTimeImmutable
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTimeImmutable
    {
        if ($value === null || $value instanceof DateTimeImmutable) {
            return $value;
        }

        if (!is_string($value)) {
            throw InvalidType::new($value, static::class, ['null', 'string', DateTimeImmutable::class]);
        }

        $format = $platform->getDateTimeFormatString();
        $converted = DateTimeImmutable::createFromFormat($format, $value, static::utc());

        if ($converted === false) {
            throw InvalidFormat::new($value, static::class, $format);
        }

        return $converted;
    }
}
