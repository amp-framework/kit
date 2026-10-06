<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Integration;

use ampf\Kit\Doctrine\Type\UtcDateTimeImmutableType;
use ampf\Kit\Tests\Fixtures\App\Doctrine\Entity\NoteEntity;
use ampf\Kit\Tests\Fixtures\App\Doctrine\Entity\ShelfEntity;
use ampf\Kit\Tests\Support\FixtureApplicationTestCase;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 * The immutable datetimes of an application on the package, through the entity manager on the disposable MariaDB: a
 * `Types::DATETIME_IMMUTABLE` column of an entity (the fixture application's notes have one) holds the UTC time of a
 * value in any zone, and the value read back is the same moment in UTC, whatever time zone PHP has as its default.
 */
final class ImmutableDatetimesInUtcTest extends FixtureApplicationTestCase
{
    private string $defaultTimeZone;

    public function testAValueInAnotherZoneIsStoredAsItsUtcTimeAndReadBackAsTheSameMomentInUtc(): void
    {
        // PHP's default zone is neither the value's nor UTC: the database's time must not depend on it
        date_default_timezone_set('America/New_York');
        $written = new DateTimeImmutable('2026-07-01 14:30:15', new DateTimeZone('Europe/Berlin'));
        $shelf = new ShelfEntity('Kitchen');
        $note = new NoteEntity($shelf, 'Milk', $written);
        $this->em->persist($shelf);
        $this->em->persist($note);
        $this->em->flush();
        $this->em->clear();

        self::assertSame(['2026-07-01 12:30:15'], $this->dbTexts('SELECT written_at FROM notes'));

        $read = $this->em->find(NoteEntity::class, $note->getId());
        self::assertInstanceOf(NoteEntity::class, $read);
        $writtenAt = $read->getWrittenAt();
        self::assertInstanceOf(DateTimeImmutable::class, $writtenAt);
        self::assertSame($written->getTimestamp(), $writtenAt->getTimestamp());
        self::assertSame('2026-07-01T12:30:15+00:00', $writtenAt->format(DATE_ATOM));
        self::assertSame('UTC', $writtenAt->getTimezone()->getName());
    }

    public function testNoValueIsStoredAsNullAndReadBackAsNone(): void
    {
        $shelf = new ShelfEntity('Kitchen');
        $note = new NoteEntity($shelf, 'Milk');
        $this->em->persist($shelf);
        $this->em->persist($note);
        $this->em->flush();
        $this->em->clear();

        self::assertSame(['1'], $this->dbTexts('SELECT written_at IS NULL FROM notes'));

        $read = $this->em->find(NoteEntity::class, $note->getId());
        self::assertInstanceOf(NoteEntity::class, $read);
        self::assertNull($read->getWrittenAt());
    }

    public function testBothNamesOfAnImmutableDatetimeAreTheTypeOfThePackage(): void
    {
        self::assertInstanceOf(UtcDateTimeImmutableType::class, Type::getType(Types::DATETIME_IMMUTABLE));
        self::assertInstanceOf(UtcDateTimeImmutableType::class, Type::getType(Types::DATETIMETZ_IMMUTABLE));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultTimeZone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->defaultTimeZone);

        parent::tearDown();
    }
}
