<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Repository\NoteRepo;
use Doctrine\ORM\Mapping as ORM;

/** An entity whose table has no name. */
#[ORM\Entity(repositoryClass: NoteRepo::class)]
#[ORM\Table(options: self::TABLE_OPTIONS)]
class NamelessTableEntity extends BaseEntity
{
}
