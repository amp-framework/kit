<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\MigrationsGuard\Undescribed\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** A migration that says nothing of what it does. */
final class Version20270101000000 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql('SELECT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SELECT 1');
    }
}
