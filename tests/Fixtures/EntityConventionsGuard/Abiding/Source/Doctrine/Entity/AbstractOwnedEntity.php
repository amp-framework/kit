<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Abiding\Source\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * What entities have in common: a mapped superclass has no table and no repository of its own.
 */
#[ORM\MappedSuperclass]
abstract class AbstractOwnedEntity extends BaseEntity
{
    #[ORM\Column(type: Types::STRING, length: 100)]
    protected string $owner;
}
