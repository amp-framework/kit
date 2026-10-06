<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Doctrine\Type;

use ampf\Kit\Doctrine\Type\UtcDateTimeImmutableType;
use ampf\Testing\ExpectsExactMessage;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDB1010Platform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * DBAL's `datetime_immutable` with the database in UTC, on MariaDB's platform: any datetime is written as its UTC time
 * (whole seconds, the way a DATETIME column keeps them) and a value read is a DateTimeImmutable in UTC, whatever time
 * zone PHP has as its default.
 */
final class UtcDateTimeImmutableTypeTest extends TestCase
{
    use ExpectsExactMessage;

    private UtcDateTimeImmutableType $type;

    private AbstractPlatform $platform;

    private string $defaultTimeZone;

    /** @return iterable<string, array{mixed}> */
    public static function nothing(): iterable
    {
        yield 'null' => [null];
    }

    /** @return iterable<string, array{mixed}> a value that is read as the very same one */
    public static function valuesReadAsTheyAre(): iterable
    {
        yield 'nothing' => [null];
        yield 'a DateTimeImmutable in another zone' => [self::at('2026-07-01 14:30:00', 'Europe/Berlin')];
        yield 'a DateTimeImmutable in UTC' => [self::at('2026-07-01 12:30:00', 'UTC')];
    }

    /** @return iterable<string, array{DateTimeImmutable, string}> a value in some zone, and the text the column gets */
    public static function valuesInOtherZones(): iterable
    {
        yield 'summer time in Berlin' => [self::at('2026-07-01 14:30:00', 'Europe/Berlin'), '2026-07-01 12:30:00'];
        yield 'winter time in Berlin' => [self::at('2026-01-15 09:15:30', 'Europe/Berlin'), '2026-01-15 08:15:30'];
        yield 'an offset of half an hour' => [self::at('2026-07-01 14:30:00', 'Asia/Kolkata'), '2026-07-01 09:00:00'];
        yield 'an offset in place of a zone' => [self::at('2026-07-01 14:30:00', '-05:00'), '2026-07-01 19:30:00'];
        yield 'the year before, in UTC' => [self::at('2026-01-01 00:30:00', 'Europe/Berlin'), '2025-12-31 23:30:00'];
        yield 'the year after, in UTC' => [
            self::at('2026-12-31 20:30:00', 'America/New_York'),
            '2027-01-01 01:30:00',
        ];
        yield 'UTC itself' => [self::at('2026-07-01 12:30:00', 'UTC'), '2026-07-01 12:30:00'];
        yield 'the last second before the clocks skip an hour' => [
            self::at('2026-03-29 01:59:59', 'Europe/Berlin'),
            '2026-03-29 00:59:59',
        ];
        yield 'the first second after the clocks skipped it' => [
            self::at('2026-03-29 03:00:00', 'Europe/Berlin'),
            '2026-03-29 01:00:00',
        ];
    }

    /** @return iterable<string, array{string, string}> the time in UTC, and the abbreviation of the zone in Berlin */
    public static function theHourThatHappensTwice(): iterable
    {
        yield 'the first time, on summer time' => ['2026-10-25 00:30:00', 'CEST'];
        yield 'the second time, on winter time' => ['2026-10-25 01:30:00', 'CET'];
    }

    /** @return iterable<string, array{string, string}> PHP's default time zone, and a text the column holds */
    public static function defaultTimeZonesAndTexts(): iterable
    {
        yield 'UTC' => ['UTC', '2026-07-01 12:30:00'];
        yield 'a time of day the clocks skip in Berlin' => ['Europe/Berlin', '2026-03-29 02:30:00'];
        yield 'a time of day that Berlin has twice' => ['Europe/Berlin', '2026-10-25 02:30:00'];
        yield 'a time of day the clocks skip in New York' => ['America/New_York', '2026-03-08 02:30:00'];
        yield 'a zone ahead of UTC by half a day' => ['Pacific/Auckland', '2026-07-01 12:30:00'];
    }

    /** @return iterable<string, array{mixed, string}> a value that is no datetime, and how DBAL says it is refused */
    public static function valuesThatAreNoDatetimes(): iterable
    {
        $expected = ' to type ' . UtcDateTimeImmutableType::class
            . '. Expected one of the following types: null, DateTimeInterface.';

        yield 'a text' => ['2026-07-01 12:30:00', 'Could not convert PHP value \'2026-07-01 12:30:00\'' . $expected];
        yield 'a number' => [1_782_909_000, 'Could not convert PHP value 1782909000' . $expected];
        yield 'a boolean' => [false, 'Could not convert PHP value false' . $expected];
        yield 'an array' => [[], 'Could not convert PHP value of type array' . $expected];
        yield 'an object' => [new stdClass(), 'Could not convert PHP value of type stdClass' . $expected];
    }

    /** @return iterable<string, array{string}> a text that is no datetime of MariaDB's format */
    public static function textsThatAreNoDatetimesOfTheDatabase(): iterable
    {
        yield 'words' => ['not a datetime'];
        yield 'nothing' => [''];
        yield 'a date only' => ['2026-07-01'];
        yield 'no seconds' => ['2026-07-01 12:30'];
        yield 'the date and the time on ISO 8601\'s terms' => ['2026-07-01T12:30:00'];
        yield 'a fraction of a second, which the column does not have' => ['2026-07-01 12:30:00.123456'];
        yield 'a time zone, which the column does not have' => ['2026-07-01 12:30:00+02:00'];
        yield 'a word PHP knows' => ['tomorrow'];
    }

    /** @return iterable<string, array{mixed, string}> a value that is neither a text nor a datetime, and the message */
    public static function valuesThatAreNoTextAndNoDateTimeImmutable(): iterable
    {
        $expected = ' to type ' . UtcDateTimeImmutableType::class
            . '. Expected one of the following types: null, string, DateTimeImmutable.';

        yield 'a number' => [1_782_909_000, 'Could not convert PHP value 1782909000' . $expected];
        yield 'a mutable DateTime' => [
            new DateTime('2026-07-01 12:30:00'),
            'Could not convert PHP value of type DateTime' . $expected,
        ];
        yield 'an array' => [['2026-07-01 12:30:00'], 'Could not convert PHP value of type array' . $expected];
    }

    private static function at(string $time, string $zone): DateTimeImmutable
    {
        return new DateTimeImmutable($time, new DateTimeZone($zone));
    }

    public function testTheColumnIsMariaDbsDatetimeWhichHoldsWholeSeconds(): void
    {
        self::assertSame('DATETIME', $this->type->getSQLDeclaration([], $this->platform));
    }

    #[DataProvider('valuesInOtherZones')]
    public function testAValueInAnotherZoneIsWrittenAsItsUtcTime(DateTimeImmutable $value, string $text): void
    {
        self::assertSame($text, $this->type->convertToDatabaseValue($value, $this->platform));
    }

    #[DataProvider('theHourThatHappensTwice')]
    public function testTheTimeOfDayThatHappensTwiceWhenTheClocksGoBackIsWrittenAsTheMomentItWas(
        string $utc,
        string $abbreviation,
    ): void {
        $inBerlin = new DateTimeImmutable($utc . ' UTC')->setTimezone(new DateTimeZone('Europe/Berlin'));

        self::assertSame(
            '2026-10-25 02:30:00 ' . $abbreviation,
            $inBerlin->format('Y-m-d H:i:s T'),
            'It is half past two on the wall both times.',
        );
        self::assertSame($utc, $this->type->convertToDatabaseValue($inBerlin, $this->platform));
    }

    public function testAMutableDateTimeIsWrittenAsItsUtcTimeToo(): void
    {
        $value = new DateTime('2026-07-01 14:30:00', new DateTimeZone('Europe/Berlin'));

        self::assertSame('2026-07-01 12:30:00', $this->type->convertToDatabaseValue($value, $this->platform));
    }

    public function testTheValueThatIsWrittenIsLeftAsItIs(): void
    {
        $berlin = new DateTimeZone('Europe/Berlin');
        $immutable = new DateTimeImmutable('2026-07-01 14:30:00', $berlin);
        $mutable = new DateTime('2026-07-01 14:30:00', $berlin);

        $this->type->convertToDatabaseValue($immutable, $this->platform);
        $this->type->convertToDatabaseValue($mutable, $this->platform);

        self::assertSame('2026-07-01 14:30:00 Europe/Berlin', $immutable->format('Y-m-d H:i:s e'));
        self::assertSame('2026-07-01 14:30:00 Europe/Berlin', $mutable->format('Y-m-d H:i:s e'));
    }

    public function testTheFractionOfASecondIsCutOffNotRounded(): void
    {
        $value = new DateTimeImmutable('2026-10-06 12:00:00.987654', new DateTimeZone('Europe/Berlin'));

        self::assertSame('2026-10-06 10:00:00', $this->type->convertToDatabaseValue($value, $this->platform));
    }

    #[DataProvider('nothing')]
    public function testNothingIsWrittenAsNull(mixed $nothing): void
    {
        self::assertNull($this->type->convertToDatabaseValue($nothing, $this->platform));
    }

    #[DataProvider('valuesThatAreNoDatetimes')]
    public function testAValueThatIsNoDatetimeIsNotWritten(mixed $value, string $message): void
    {
        $this->expectException(InvalidType::class);
        $this->expectExceptionMessageExactly($message);

        $this->type->convertToDatabaseValue($value, $this->platform);
    }

    public function testAValueReadIsADateTimeImmutableInUtcWithoutAFraction(): void
    {
        $value = $this->type->convertToPHPValue('2026-07-01 12:30:00', $this->platform);

        self::assertSame('2026-07-01 12:30:00.000000 UTC', $value->format('Y-m-d H:i:s.u e'));
    }

    #[DataProvider('defaultTimeZonesAndTexts')]
    public function testAValueReadIsInUtcWhateverTheDefaultTimeZoneOfPhpIs(string $zone, string $text): void
    {
        date_default_timezone_set($zone);

        $value = $this->type->convertToPHPValue($text, $this->platform);

        self::assertSame($text . ' UTC', $value->format('Y-m-d H:i:s e'));
    }

    #[DataProvider('valuesReadAsTheyAre')]
    public function testNothingAndAValueThatIsADateTimeImmutableAlreadyAreTakenAsTheyAre(mixed $value): void
    {
        self::assertSame($value, $this->type->convertToPHPValue($value, $this->platform));
    }

    #[DataProvider('textsThatAreNoDatetimesOfTheDatabase')]
    public function testATextThatIsNoDatetimeOfTheDatabaseIsNotRead(string $text): void
    {
        $this->expectException(InvalidFormat::class);
        $this->expectExceptionMessageExactly(
            'Could not convert database value "' . $text . '" to Doctrine Type ' . UtcDateTimeImmutableType::class
            . '. Expected format "Y-m-d H:i:s".',
        );

        $this->type->convertToPHPValue($text, $this->platform);
    }

    #[DataProvider('valuesThatAreNoTextAndNoDateTimeImmutable')]
    public function testAValueThatIsNeitherATextNorADateTimeImmutableIsNotRead(mixed $value, string $message): void
    {
        $this->expectException(InvalidType::class);
        $this->expectExceptionMessageExactly($message);

        $this->type->convertToPHPValue($value, $this->platform);
    }

    public function testOneUtcZoneServesEveryConversion(): void
    {
        $type = new class extends UtcDateTimeImmutableType {
            public static function zone(): DateTimeZone
            {
                return self::utc();
            }
        };

        self::assertSame('UTC', $type::zone()->getName());
        self::assertSame($type::zone(), $type::zone());
    }

    protected function setUp(): void
    {
        $this->defaultTimeZone = date_default_timezone_get();
        $this->type = new UtcDateTimeImmutableType();
        $this->platform = new MariaDB1010Platform();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->defaultTimeZone);
    }
}
