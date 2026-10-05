<?php

declare(strict_types=1);

namespace ampf\Kit\Testing;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use SensitiveParameter;

/**
 * The driver whose connections count the SELECT statements they are given.
 *
 * @phpstan-import-type Params from \Doctrine\DBAL\DriverManager
 */
class SelectCountingDriver extends AbstractDriverMiddleware
{
    /** @param Params $params */
    public function connect(#[SensitiveParameter]
    array $params, ): Connection
    {
        return new SelectCountingConnection(parent::connect($params));
    }
}
