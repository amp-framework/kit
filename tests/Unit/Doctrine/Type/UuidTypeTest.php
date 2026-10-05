<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Doctrine\Type;

use ampf\Kit\Doctrine\Type\UuidType;
use Doctrine\DBAL\Platforms\MariaDB1010Platform;
use Doctrine\DBAL\Platforms\MariaDB120300Platform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use PHPUnit\Framework\TestCase;

/** DBAL's `guid` as MariaDB's own UUID column. */
final class UuidTypeTest extends TestCase
{
    public function testMariaDbGetsItsOwnUuidColumn(): void
    {
        self::assertSame('UUID', new UuidType()->getSQLDeclaration([], new MariaDB120300Platform()));
        self::assertSame('UUID', new UuidType()->getSQLDeclaration([], new MariaDB1010Platform()));
    }

    public function testAnotherPlatformKeepsDbalsDeclaration(): void
    {
        self::assertSame('CHAR(36)', new UuidType()->getSQLDeclaration([], new SQLitePlatform()));
        self::assertSame('CHAR(36)', new UuidType()->getSQLDeclaration([], new MySQL80Platform()));
    }

    public function testTheValueIsTheTextOfTheId(): void
    {
        $id = '0198c5f2-7b3a-7d21-b7ad-3f1c2a9e4d10';
        $platform = new MariaDB120300Platform();

        self::assertSame($id, new UuidType()->convertToDatabaseValue($id, $platform));
        self::assertSame($id, new UuidType()->convertToPHPValue($id, $platform));
        self::assertNull(new UuidType()->convertToPHPValue(null, $platform));
    }
}
