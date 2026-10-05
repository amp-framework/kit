<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\SchemaGuard\InvalidMapping\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** An entity whose column is an integer and whose property is a text: the schema tool finds the mapping invalid. */
#[ORM\Entity]
#[ORM\Table(name: 'mismatches', options: self::TABLE_OPTIONS)]
class MismatchedEntity extends BaseEntity
{
    #[ORM\Column(type: Types::INTEGER)]
    private string $count = '0';

    public function getCount(): string
    {
        return $this->count;
    }
}
