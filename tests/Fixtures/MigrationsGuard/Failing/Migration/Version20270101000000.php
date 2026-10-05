<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\MigrationsGuard\Failing\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

/** A migration that cannot run. */
final class Version20270101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Breaks';
    }

    public function up(Schema $schema): void
    {
        throw new RuntimeException('the migration is broken');
    }

    public function down(Schema $schema): void
    {
        throw new RuntimeException('the migration is broken');
    }
}
