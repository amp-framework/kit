<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Repository\NoteRepo;
use Doctrine\ORM\Mapping as ORM;

/** An entity whose table is on another collation. */
#[ORM\Entity(repositoryClass: NoteRepo::class)]
#[ORM\Table(name: 'others', options: ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_general_ci'])]
class OtherCollationEntity extends BaseEntity
{
}
