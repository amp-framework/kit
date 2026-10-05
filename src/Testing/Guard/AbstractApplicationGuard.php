<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use ampf\Kit\Testing\IntegrationTestCase;

/**
 * The base of the guards that boot an application and use its database: the package's IntegrationTestCase (the project
 * root, which an application names in one small class of its tests; the real router; the disposable database) with what
 * the guards share. A failure says what is wrong and names the table or the file at fault, all the problems of a test at
 * once.
 */
abstract class AbstractApplicationGuard extends IntegrationTestCase
{
    use ReportsProblems;

    /**
     * The tables and the string columns of the database that are not on the collation of `BaseEntity::TABLE_OPTIONS`,
     * each as a sentence: the tables first, by name, then the columns, by table and name.
     *
     * @return list<string>
     */
    protected function collationProblems(): array
    {
        $connection = $this->em->getConnection();
        ['charset' => $charset, 'collation' => $collation] = BaseEntity::TABLE_OPTIONS;
        $problems = [];

        foreach (
            $connection->fetchAllAssociative(
                'SELECT table_name AS name, table_collation AS collation FROM information_schema.tables'
                . ' WHERE table_schema = DATABASE() AND table_collation <> ? ORDER BY table_name',
                [$collation],
            ) as $table
        ) {
            $problems[] = 'The table "' . self::dbText($table['name']) . '" is on the collation "'
                . self::dbText($table['collation']) . '", not on "' . $collation . '" (character set ' . $charset
                . '): put the table of its entity on BaseEntity::TABLE_OPTIONS.';
        }

        foreach (
            $connection->fetchAllAssociative(
                'SELECT table_name AS tbl, column_name AS col, collation_name AS collation'
                . ' FROM information_schema.columns WHERE table_schema = DATABASE() AND collation_name IS NOT NULL'
                . ' AND collation_name <> ? ORDER BY table_name, column_name',
                [$collation],
            ) as $column
        ) {
            $problems[] = 'The column "' . self::dbText($column['tbl']) . '.' . self::dbText($column['col'])
                . '" is on the collation "' . self::dbText($column['collation']) . '", not on "' . $collation
                . '": a column takes the collation of its table and declares none of its own.';
        }

        return $problems;
    }
}
