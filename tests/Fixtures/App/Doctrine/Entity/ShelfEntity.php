<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\App\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** A shelf: the fixture application's data, which holds its notes. */
#[ORM\Entity]
#[ORM\Table(name: 'shelves', options: self::TABLE_OPTIONS)]
class ShelfEntity extends BaseEntity
{
    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $name;

    public function __construct(string $name)
    {
        parent::__construct();

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
