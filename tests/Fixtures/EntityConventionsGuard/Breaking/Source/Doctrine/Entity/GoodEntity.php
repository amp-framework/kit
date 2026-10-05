<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Repository\NoteRepo;
use Doctrine\ORM\Mapping as ORM;

/** An entity that keeps the rules, to show that it is the others that fail. */
#[ORM\Entity(repositoryClass: NoteRepo::class)]
#[ORM\Table(name: 'goods', options: self::TABLE_OPTIONS)]
class GoodEntity extends BaseEntity
{
}
