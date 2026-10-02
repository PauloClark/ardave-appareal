-- Ardave Apparel MySQL schema
-- Run this file in phpMyAdmin or `mysql` to create the database and tables

CREATE DATABASE IF NOT EXISTS `ardave_apparel` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ardave_apparel`;

-- Users (authentication)
CREATE TABLE IF NOT EXISTS `users` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`email` VARCHAR(255) NOT NULL UNIQUE,
	`password` VARCHAR(255) NOT NULL,
	`name` VARCHAR(150) DEFAULT NULL,
	`phone` VARCHAR(30) DEFAULT NULL,
	`role` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
	`is_verified` TINYINT(1) NOT NULL DEFAULT 0,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customers (profile / shipping information)
CREATE TABLE IF NOT EXISTS `customers` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT UNSIGNED DEFAULT NULL,
	`name` VARCHAR(150) NOT NULL,
	`email` VARCHAR(255) NOT NULL,
	`phone` VARCHAR(30) DEFAULT NULL,
	`address` TEXT DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`user_id`),
	CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products
CREATE TABLE IF NOT EXISTS `products` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`sku` VARCHAR(100) DEFAULT NULL,
	`name` VARCHAR(255) NOT NULL,
	`description` TEXT DEFAULT NULL,
	`price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
	`stock` INT NOT NULL DEFAULT 0,
	`category` VARCHAR(100) DEFAULT NULL,
	`image` VARCHAR(255) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `idx_products_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Size options
CREATE TABLE IF NOT EXISTS `sizes` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`size_label` VARCHAR(10) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`size_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Carts (persistent carts; user_id can be null for guest carts)
CREATE TABLE IF NOT EXISTS `carts` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT UNSIGNED DEFAULT NULL,
	`session_token` VARCHAR(128) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`user_id`),
	CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart items
CREATE TABLE IF NOT EXISTS `cart_items` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`cart_id` INT UNSIGNED NOT NULL,
	`product_id` INT UNSIGNED NOT NULL,
	`size` VARCHAR(10) DEFAULT NULL,
	`quantity` INT NOT NULL DEFAULT 1,
	`price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`cart_id`),
	INDEX (`product_id`),
	CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts`(`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders
CREATE TABLE IF NOT EXISTS `orders` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_number` VARCHAR(50) NOT NULL UNIQUE,
	`customer_id` INT UNSIGNED NOT NULL,
	`status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
	`total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
	`shipping_address` TEXT DEFAULT NULL,
	`phone` VARCHAR(30) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`customer_id`),
	CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items
CREATE TABLE IF NOT EXISTS `order_items` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_id` INT UNSIGNED NOT NULL,
	`product_id` INT UNSIGNED DEFAULT NULL,
	`product_name` VARCHAR(255) NOT NULL,
	`size` VARCHAR(10) DEFAULT NULL,
	`quantity` INT NOT NULL DEFAULT 1,
	`unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`order_id`),
	INDEX (`product_id`),
	CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments
CREATE TABLE IF NOT EXISTS `payments` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_id` INT UNSIGNED NOT NULL,
	`amount` DECIMAL(12,2) NOT NULL,
	`method` VARCHAR(50) NOT NULL,
	`status` ENUM('pending','verified','failed','refunded') NOT NULL DEFAULT 'pending',
	`transaction_ref` VARCHAR(255) DEFAULT NULL,
	`receipt_image` VARCHAR(255) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`order_id`),
	CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transactions (detailed ledger)
CREATE TABLE IF NOT EXISTS `transactions` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`payment_id` INT UNSIGNED DEFAULT NULL,
	`type` VARCHAR(50) DEFAULT NULL,
	`amount` DECIMAL(12,2) DEFAULT 0.00,
	`notes` TEXT DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`payment_id`),
	CONSTRAINT `fk_transactions_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`customer_id` INT UNSIGNED DEFAULT NULL,
	`type` VARCHAR(100) DEFAULT NULL,
	`message` TEXT DEFAULT NULL,
	`is_read` TINYINT(1) NOT NULL DEFAULT 0,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`customer_id`),
	CONSTRAINT `fk_notifications_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tracking statuses for orders
CREATE TABLE IF NOT EXISTS `tracking` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_id` INT UNSIGNED NOT NULL,
	`status` VARCHAR(100) NOT NULL,
	`note` TEXT DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`order_id`),
	CONSTRAINT `fk_tracking_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uploaded designs and logos
CREATE TABLE IF NOT EXISTS `uploaded_designs` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_item_id` INT UNSIGNED DEFAULT NULL,
	`file_path` VARCHAR(255) NOT NULL,
	`type` VARCHAR(50) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`order_item_id`),
	CONSTRAINT `fk_uploaded_designs_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed sizes
INSERT IGNORE INTO `sizes` (`size_label`) VALUES
('XS'),('S'),('M'),('L'),('XL'),('2XL'),('3XL'),('4XL'),('5XL'),('6XL');

-- Seed products (the 4 mandatory product types)
INSERT INTO `products` (`sku`,`name`,`description`,`price`,`stock`,`category`,`image`) VALUES
('JER-001','Jersey Sublimation','Team sublimation jersey, breathable fabric',350.00,100,'Apparel','assets/images/jersey.jpg'),
('TSH-001','T-shirt Sublimation','Classic sublimation t-shirt',380.00,200,'Apparel','assets/images/tshirt.jpg'),
('DRY-001','Dry Fit Shirt','Moisture-wicking dry fit shirt',320.00,150,'Performance','assets/images/dryfit.jpg'),
('HOD-001','Warmer Hoodie','Warm and comfortable hoodie',650.00,80,'Apparel','assets/images/hoodie.jpg')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Useful indexes (create manually in phpMyAdmin if needed)
-- Index creation statements with IF NOT EXISTS are removed for phpMyAdmin compatibility.

-- End of schema

-- OTP codes (persistent)
CREATE TABLE IF NOT EXISTS `otp_codes` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT UNSIGNED DEFAULT NULL,
	`email` VARCHAR(255) NOT NULL,
	`code` VARCHAR(10) NOT NULL,
	`expires_at` DATETIME NOT NULL,
	`used` TINYINT(1) NOT NULL DEFAULT 0,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`user_id`),
	CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT UNSIGNED DEFAULT NULL,
	`email` VARCHAR(255) NOT NULL,
	`token` VARCHAR(128) NOT NULL,
	`expires_at` DATETIME NOT NULL,
	`used` TINYINT(1) NOT NULL DEFAULT 0,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX (`user_id`),
	INDEX (`token`),
	CONSTRAINT `fk_pwdreset_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Additional columns needed by admin pages
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `is_enabled` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `sizes` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `colors` VARCHAR(255) DEFAULT NULL;

-- Inventory tracking table
CREATE TABLE IF NOT EXISTS `inventory` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `change_qty` INT NOT NULL DEFAULT 0,
    `reason` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX (`product_id`),
    CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site settings table
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

