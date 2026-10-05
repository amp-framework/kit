<?php

declare(strict_types=1);

namespace ampf\Kit\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Types\GuidType;

/**
 * DBAL's `guid` as MariaDB's own `UUID` column (MariaDB 10.7 or later): 16 bytes, text in and out in the form of
 * `Helper\Uuid`, and a value that is no UUID is refused by the database. DBAL declares `guid` as CHAR(36) on MySQL
 * and MariaDB; this replaces that (`doctrine.typeOverrides` in the package's config/default.php), and
 * `doctrine.mappingOverrides` reads the column back as `guid`. On another platform it stays DBAL's.
 */
class UuidType extends GuidType
{
    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform instanceof MariaDBPlatform
            ? 'UUID'
            : parent::getSQLDeclaration($column, $platform);
    }
}
