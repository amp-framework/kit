<?php

declare(strict_types=1);

namespace ampf\Kit\Doctrine\Entity;

use ampf\Doctrine\Entity\AbstractEntity;
use ampf\Kit\Helper\Uuid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * What every entity has, the package's and the application's: a UUID that the application assigns when the object is
 * created, so that it is known before the object is flushed and no table relies on an auto-increment.
 */
#[ORM\MappedSuperclass]
abstract class BaseEntity extends AbstractEntity
{
    /**
     * Every table's character set and collation: utf8mb4_uca1400_ai_ci is MariaDB's current Unicode collation (UCA
     * 14.0, case- and accent-insensitive). No column declares its own, so a string compares, and is unique, the way
     * its table does.
     */
    public const array TABLE_OPTIONS = ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_uca1400_ai_ci'];

    #[ORM\Column(type: Types::GUID)]
    #[ORM\Id]
    private string $id;

    public function __construct()
    {
        $this->id = Uuid::generate();
    }

    public function getId(): string
    {
        return $this->id;
    }
}
