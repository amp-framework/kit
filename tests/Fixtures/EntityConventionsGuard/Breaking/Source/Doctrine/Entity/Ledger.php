<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** A mapped superclass whose file is not named after the entity suffix. */
#[ORM\MappedSuperclass]
abstract class Ledger extends BaseEntity
{
    #[ORM\Column(type: Types::STRING, length: 100)]
    protected string $owner;
}
