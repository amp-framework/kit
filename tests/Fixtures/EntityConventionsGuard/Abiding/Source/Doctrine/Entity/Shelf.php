<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Abiding\Source\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Abiding\Source\Doctrine\Repository\NoteRepo;
use Doctrine\ORM\Mapping as ORM;

/**
 * An entity whose file is not named after the entity suffix, which the mapping takes all the same.
 */
#[ORM\Entity(repositoryClass: NoteRepo::class)]
#[ORM\Table(name: 'shelves', options: self::TABLE_OPTIONS)]
class Shelf extends BaseEntity
{
    #[ORM\ManyToOne(targetEntity: NoteEntity::class)]
    private ?NoteEntity $pinned = null;
}
