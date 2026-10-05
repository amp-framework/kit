<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;

/** A class that is not mapped: no attribute says it is an entity or names its table. */
class NoAttributeEntity extends BaseEntity
{
}
