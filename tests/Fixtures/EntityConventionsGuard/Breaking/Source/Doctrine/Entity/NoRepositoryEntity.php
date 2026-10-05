<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/** An entity without a repository. */
#[ORM\Entity]
#[ORM\Table(name: 'no_repositories', options: self::TABLE_OPTIONS)]
class NoRepositoryEntity extends BaseEntity
{
}
