<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\MigrationsGuard\Stubborn\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** A migration whose way back does not undo what it made. */
final class Version20270101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Abandoned';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE abandoned (id UUID NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci`',
        );
    }

    public function down(Schema $schema): void
    {
        // The way back leaves the table where it is
    }
}
