<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\MigrationsGuard\Wiping\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** A migration that wipes the record of the migrations that ran: a second run would run them again. */
final class Version20270101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Forgets';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM doctrine_migration_versions');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SELECT 1');
    }
}
