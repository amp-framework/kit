<?php

declare(strict_types=1);

namespace ampf\Kit\Testing;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/** Puts the counting of SELECT statements into every connection that the configuration it is set on makes. */
class SelectCountingMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new SelectCountingDriver($driver);
    }
}
