<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Abiding\Source\Doctrine\Entity\Archive;

use ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Abiding\Source\Doctrine\Entity\AbstractOwnedEntity;
use ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Abiding\Source\Doctrine\Repository\NoteRepo;
use Doctrine\ORM\Mapping as ORM;

/** An entity in a directory below the entities', whose parent is a mapped superclass. */
#[ORM\Entity(repositoryClass: NoteRepo::class)]
#[ORM\Table(name: 'tags', options: self::TABLE_OPTIONS)]
class TagEntity extends AbstractOwnedEntity
{
}
