<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\App\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** A note on a shelf: the fixture application's data, which goes with its shelf (`ON DELETE CASCADE`). */
#[ORM\Entity]
#[ORM\Table(name: 'notes', options: self::TABLE_OPTIONS)]
class NoteEntity extends BaseEntity
{
    #[ORM\JoinColumn(name: 'shelf_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: ShelfEntity::class)]
    private ShelfEntity $shelf;

    #[ORM\Column(type: Types::STRING, length: 200)]
    private string $text;

    public function __construct(ShelfEntity $shelf, string $text)
    {
        parent::__construct();

        $this->shelf = $shelf;
        $this->text = $text;
    }

    public function getShelf(): ShelfEntity
    {
        return $this->shelf;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
