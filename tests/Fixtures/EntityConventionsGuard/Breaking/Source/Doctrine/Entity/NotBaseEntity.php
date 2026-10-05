<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Entity;

use ampf\Doctrine\Entity\AbstractEntity;
use ampf\Kit\Doctrine\Entity\BaseEntity;
use ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Repository\NoteRepo;
use Doctrine\ORM\Mapping as ORM;

/** An entity of ampf's base and not of the package's. */
#[ORM\Entity(repositoryClass: NoteRepo::class)]
#[ORM\Table(name: 'not_bases', options: BaseEntity::TABLE_OPTIONS)]
class NotBaseEntity extends AbstractEntity
{
}
