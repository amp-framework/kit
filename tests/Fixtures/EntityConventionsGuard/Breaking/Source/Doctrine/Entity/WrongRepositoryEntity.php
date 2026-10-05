<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Repository\PlainRepo;
use Doctrine\ORM\Mapping as ORM;

/** An entity whose repository is none of ampf's. */
#[ORM\Entity(repositoryClass: PlainRepo::class)]
#[ORM\Table(name: 'wrong_repositories', options: self::TABLE_OPTIONS)]
class WrongRepositoryEntity extends BaseEntity
{
}
