<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\MigrationsGuard\BrokenDown\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

/** A migration whose way back fails. */
final class Version20270101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cannot be undone';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE heavy (id UUID NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci`',
        );
    }

    public function down(Schema $schema): void
    {
        throw new RuntimeException('there is no way back');
    }
}
