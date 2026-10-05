<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** The fixture application's shelves and their notes. */
final class Version20261004000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Shelves and notes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE shelves (id UUID NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY (id))'
            . ' DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci`',
        );
        $this->addSql(
            'CREATE TABLE notes (id UUID NOT NULL, text VARCHAR(200) NOT NULL, shelf_id UUID NOT NULL,'
            . ' INDEX IDX_11BA68C7C12FBC0 (shelf_id), PRIMARY KEY (id))'
            . ' DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci`',
        );
        $this->addSql(
            'ALTER TABLE notes ADD CONSTRAINT FK_11BA68C7C12FBC0 FOREIGN KEY (shelf_id) REFERENCES shelves (id)'
            . ' ON DELETE CASCADE',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notes DROP FOREIGN KEY FK_11BA68C7C12FBC0');
        $this->addSql('DROP TABLE notes');
        $this->addSql('DROP TABLE shelves');
    }
}
