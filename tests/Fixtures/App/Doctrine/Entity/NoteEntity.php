<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\App\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A note on a shelf: the fixture application's data, which goes with its shelf (`ON DELETE CASCADE`), and the moment
 * it was written, when that is known: an immutable datetime, which the database holds in UTC.
 */
#[ORM\Entity]
#[ORM\Table(name: 'notes', options: self::TABLE_OPTIONS)]
class NoteEntity extends BaseEntity
{
    #[ORM\JoinColumn(name: 'shelf_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: ShelfEntity::class)]
    private ShelfEntity $shelf;

    #[ORM\Column(type: Types::STRING, length: 200)]
    private string $text;

    #[ORM\Column(name: 'written_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $writtenAt;

    public function __construct(ShelfEntity $shelf, string $text, ?DateTimeImmutable $writtenAt = null)
    {
        parent::__construct();

        $this->shelf = $shelf;
        $this->text = $text;
        $this->writtenAt = $writtenAt;
    }

    public function getShelf(): ShelfEntity
    {
        return $this->shelf;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getWrittenAt(): ?DateTimeImmutable
    {
        return $this->writtenAt;
    }
}
