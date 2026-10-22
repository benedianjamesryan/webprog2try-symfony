<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261011090500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Use the product photo instead of the placeholder fan icon for the first catalog item.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE products SET image = 'assets/products/fc1-cooler.jpg' WHERE id = 1 AND image = 'assets/products/fc1-cooler.svg'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE products SET image = 'assets/products/fc1-cooler.svg' WHERE id = 1 AND image = 'assets/products/fc1-cooler.jpg'");
    }
}
