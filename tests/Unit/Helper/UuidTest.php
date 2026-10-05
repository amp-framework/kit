<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Helper;

use ampf\Kit\Helper\Uuid;
use ampf\Testing\ExpectsExactMessage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** The ids of every entity: UUIDs of version 7, built from the time and ten random bytes. */
final class UuidTest extends TestCase
{
    use ExpectsExactMessage;

    /** @return iterable<string, array{string, bool}> */
    public static function texts(): iterable
    {
        yield 'a version 7 id' => ['0198c5f2-7b3a-7d21-b7ad-3f1c2a9e4d10', true];
        yield 'a version 4 id' => ['3f2b8c1e-9d4a-4e6b-8a1c-5d7e9f0a2b3c', true];
        yield 'version 1, the lowest' => ['0198c5f2-7b3a-1d21-b7ad-3f1c2a9e4d10', true];
        yield 'version 8, the highest' => ['0198c5f2-7b3a-8d21-b7ad-3f1c2a9e4d10', true];
        yield 'version 9' => ['0198c5f2-7b3a-9d21-b7ad-3f1c2a9e4d10', false];
        yield 'upper case' => ['0198C5F2-7B3A-7D21-B7AD-3F1C2A9E4D10', false];
        yield 'no hyphens' => ['0198c5f27b3a7d21b7ad3f1c2a9e4d10', false];
        yield 'braces' => ['{0198c5f2-7b3a-7d21-b7ad-3f1c2a9e4d10}', false];
        yield 'something before' => ['x0198c5f2-7b3a-7d21-b7ad-3f1c2a9e4d10', false];
        yield 'a trailing line feed' => ["0198c5f2-7b3a-7d21-b7ad-3f1c2a9e4d10\n", false];
        yield 'a wrong variant' => ['0198c5f2-7b3a-7d21-c7ad-3f1c2a9e4d10', false];
        yield 'version 0' => ['0198c5f2-7b3a-0d21-b7ad-3f1c2a9e4d10', false];
        yield 'too short' => ['0198c5f2-7b3a-7d21-b7ad-3f1c2a9e4d1', false];
        yield 'not hex' => ['0198c5f2-7b3a-7d21-b7ad-3f1c2a9e4dzz', false];
        yield 'empty' => ['', false];
    }

    /** @return iterable<string, array{int}> */
    public static function randomLengths(): iterable
    {
        yield 'none' => [0];
        yield 'nine' => [9];
        yield 'eleven' => [11];
    }

    public function testAnIdIsBuiltFromTheTimeAndTheRandomBytes(): void
    {
        // An arbitrary time, and the random bytes 0x00 to 0x09
        $id = Uuid::v7(0x019A_1234_5678, "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09");

        // The time's 48 bits, the version 7 in the top of the next group, then the variant 10 in the top of the next
        self::assertSame('019a1234-5678-7001-8203-040506070809', $id);
    }

    public function testTheVersionAndTheVariantOverwriteWhateverTheRandomBytesHold(): void
    {
        $id = Uuid::v7(0, str_repeat("\xFF", 10));

        self::assertSame('00000000-0000-7fff-bfff-ffffffffffff', $id);
    }

    public function testTheLargestTimeFitsAndOneMoreDoesNot(): void
    {
        self::assertSame('ffffffff-ffff-7000-8000-000000000000', Uuid::v7(2 ** 48 - 1, str_repeat("\x00", 10)));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageExactly('A UUID\'s time is 48 bits of milliseconds, not 281474976710656.');

        Uuid::v7(2 ** 48, str_repeat("\x00", 10));
    }

    public function testTheEarliestTimeFitsAndOneLessDoesNot(): void
    {
        self::assertSame('00000000-0000-7000-8000-000000000000', Uuid::v7(0, str_repeat("\x00", 10)));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageExactly('A UUID\'s time is 48 bits of milliseconds, not -1.');

        Uuid::v7(-1, str_repeat("\x00", 10));
    }

    #[DataProvider('randomLengths')]
    public function testOtherThanTenRandomBytesAreRefused(int $length): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageExactly('A UUID takes ten random bytes, not ' . $length . '.');

        Uuid::v7(1, str_repeat("\x00", $length));
    }

    public function testAGeneratedIdIsValidAndCarriesTheCurrentTime(): void
    {
        $before = (int)(microtime(true) * 1000);
        $id = Uuid::generate();
        $after = (int)(microtime(true) * 1000);

        self::assertTrue(Uuid::isValid($id));
        $time = (int)hexdec(str_replace('-', '', substr($id, 0, 13)));
        self::assertGreaterThanOrEqual($before, $time);
        self::assertLessThanOrEqual($after, $time);
        self::assertSame('7', $id[14]);
    }

    public function testGeneratedIdsDiffer(): void
    {
        $ids = [];

        for ($i = 0; $i < 1000; $i++) {
            $ids[Uuid::generate()] = true;
        }

        self::assertCount(1000, $ids);
    }

    #[DataProvider('texts')]
    public function testTheTextOfAnIdIsRecognised(string $text, bool $valid): void
    {
        self::assertSame($valid, Uuid::isValid($text));
    }

    public function testThePatternIsWhatARouteCanUse(): void
    {
        self::assertSame(1, preg_match('/^notes\/(?P<id>' . Uuid::PATTERN . ')$/D', 'notes/' . Uuid::generate()));
        self::assertSame(0, preg_match('/^notes\/(?P<id>' . Uuid::PATTERN . ')$/D', 'notes/1'));
    }
}
