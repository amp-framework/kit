<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing;

use ampf\Kit\Testing\SelectCounter;
use ampf\Kit\Testing\SelectCountingMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use PHPUnit\Framework\TestCase;

/** Every connection of the wrapped driver counts the SELECTs it prepares and runs, and is the driver's own otherwise. */
final class SelectCountingMiddlewareTest extends TestCase
{
    public function testAConnectionCountsWhatItPreparesAndWhatItRunsAndAnswersAsTheOneItWraps(): void
    {
        $statement = self::createStub(Statement::class);
        $result = self::createStub(Result::class);
        $inner = self::createStub(Connection::class);
        $inner->method('prepare')->willReturn($statement);
        $inner->method('query')->willReturn($result);
        $driver = self::createStub(Driver::class);
        $driver->method('connect')->willReturn($inner);

        $connection = new SelectCountingMiddleware()->wrap($driver)->connect(['driver' => 'pdo_mysql']);
        $before = SelectCounter::total();

        self::assertSame($statement, $connection->prepare('SELECT 1'));
        self::assertSame($result, $connection->query('SELECT 2'));
        self::assertSame($statement, $connection->prepare('UPDATE notes SET text = 1'));
        self::assertSame($result, $connection->query('SHOW TABLES'));
        self::assertSame($before + 2, SelectCounter::total());
    }

    public function testTheParametersOfAConnectionReachTheWrappedDriver(): void
    {
        $parameters = ['driver' => 'pdo_mysql', 'host' => 'database'];
        $driver = self::createMock(Driver::class);
        $driver->expects(self::once())
            ->method('connect')
            ->with($parameters)
            ->willReturn(self::createStub(Connection::class))
        ;

        new SelectCountingMiddleware()->wrap($driver)->connect($parameters);
    }
}
