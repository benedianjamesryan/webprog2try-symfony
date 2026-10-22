<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the FrostCore data model and seed its eight catalog products.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(8) NOT NULL DEFAULT 'customer',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE INDEX uniq_users_email (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE products (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            name VARCHAR(180) NOT NULL,
            category VARCHAR(80) NOT NULL,
            description LONGTEXT NOT NULL,
            price NUMERIC(10, 2) NOT NULL,
            image VARCHAR(255) NOT NULL,
            rating NUMERIC(2, 1) NOT NULL DEFAULT 0.0,
            stock INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE cart_items (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE INDEX uniq_cart_user_product (user_id, product_id),
            INDEX IDX_BEF48445A76ED395 (user_id),
            INDEX IDX_BEF484454584665A (product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_CART_ITEMS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_CART_ITEMS_PRODUCT FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE orders (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            order_number VARCHAR(40) NOT NULL,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(40) NOT NULL,
            address VARCHAR(255) NOT NULL,
            city VARCHAR(100) NOT NULL,
            province VARCHAR(100) NOT NULL,
            postal_code VARCHAR(20) NOT NULL,
            payment_method VARCHAR(5) NOT NULL,
            subtotal NUMERIC(10, 2) NOT NULL DEFAULT 0,
            shipping_fee NUMERIC(10, 2) NOT NULL DEFAULT 0,
            total NUMERIC(10, 2) NOT NULL DEFAULT 0,
            status VARCHAR(10) NOT NULL DEFAULT 'Pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE INDEX uniq_orders_order_number (order_number),
            INDEX IDX_E52FFDEEA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_ORDERS_USER FOREIGN KEY (user_id) REFERENCES users (id)');

        $this->addSql("CREATE TABLE order_items (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            order_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            product_name VARCHAR(180) NOT NULL,
            price NUMERIC(10, 2) NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            subtotal NUMERIC(10, 2) NOT NULL,
            INDEX IDX_62809DB08D9F6D38 (order_id),
            INDEX IDX_62809DB04584665A (product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_ORDER_ITEMS_ORDER FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_ORDER_ITEMS_PRODUCT FOREIGN KEY (product_id) REFERENCES products (id)');

        $this->addSql("CREATE TABLE reviews (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            rating SMALLINT UNSIGNED NOT NULL,
            review_text VARCHAR(1000) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE INDEX uniq_reviews_user_product (user_id, product_id),
            INDEX IDX_6970EB0FA76ED395 (user_id),
            INDEX IDX_6970EB0F4584665A (product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_REVIEWS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_REVIEWS_PRODUCT FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO products (id, name, category, description, price, image, rating, stock) VALUES
            (1, 'FrostCore X1 ArcticClip', 'Phone Cooler', 'Compact semiconductor clip cooler built for long gaming sessions.', 999.00, 'assets/products/fc1-cooler.jpg', 4.8, 30),
            (2, 'ArcticStrike X2 RGB', 'Phone Cooler', 'Gaming phone cooler with RGB lighting and magnetic mounting.', 1499.00, 'assets/products/fc2-cooler.jpg', 4.9, 25),
            (3, 'CryoGrip X3 HyperChill', 'Phone Cooler', 'Premium magnetic cooler designed for serious mobile gamers.', 1799.00, 'assets/products/fc3-cooler.jpg', 4.8, 20),
            (4, 'GlacierStand L1', 'Laptop Cooler', 'Adjustable laptop cooling stand for long gaming sessions.', 2499.00, 'assets/products/fc4-cooler.jpg', 4.7, 20),
            (5, 'TitanFrost L2 Pro', 'Laptop Cooler', 'High-performance laptop cooler with dual turbo fans and RGB.', 3499.00, 'assets/products/fc5-cooler.png', 4.9, 15),
            (6, 'FrostFlow L3 IceForge', 'Laptop Cooler', 'Quad-fan laptop cooler designed for maximum airflow.', 3999.00, 'assets/products/fc6-cooler.jpg', 4.9, 12),
            (7, 'FrostCore DuoForge Pack', 'Bundle', 'Complete phone and laptop cooling bundle.', 4299.00, 'assets/products/fc7-cooler.jpg', 4.8, 10),
            (8, 'FrostCore Elite Arena Kit', 'Bundle', 'Premium cooling kit for competitive gamers.', 5499.00, 'assets/products/fc8-cooler.jpg', 4.9, 8)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reviews');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE cart_items');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE users');
    }
}
