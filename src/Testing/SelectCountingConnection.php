<?php

declare(strict_types=1);

namespace ampf\Kit\Testing;

use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

/** A connection that tells the SelectCounter of every statement it is asked to prepare or to run. */
class SelectCountingConnection extends AbstractConnectionMiddleware
{
    public function prepare(string $sql): Statement
    {
        SelectCounter::note($sql);

        return parent::prepare($sql);
    }

    public function query(string $sql): Result
    {
        SelectCounter::note($sql);

        return parent::query($sql);
    }
}
