<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022135000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product visibility for hiding and restoring catalog items.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products ADD is_visible TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP is_visible');
    }
}
