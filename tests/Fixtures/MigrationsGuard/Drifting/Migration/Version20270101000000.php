<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\MigrationsGuard\Drifting\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** A migration that leaves the schema different from the mapping: the columns of the notes and of the shelves are shorter. */
final class Version20270101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Shortens the text of the notes and the name of the shelves';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notes CHANGE text text VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE shelves CHANGE name name VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notes CHANGE text text VARCHAR(200) NOT NULL');
        $this->addSql('ALTER TABLE shelves CHANGE name name VARCHAR(100) NOT NULL');
    }
}
