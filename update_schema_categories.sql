-- =============================================
-- Update Schema: Add Categories
-- Run this after final_schema.sql
-- =============================================

-- Table structure for categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add category_id column to products
ALTER TABLE `products` ADD COLUMN `category_id` int(11) DEFAULT NULL;
ALTER TABLE `products` ADD CONSTRAINT `fk_product_category` 
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL;

-- Insert default categories
INSERT INTO `categories` (`name`, `slug`, `icon`, `description`, `sort_order`) VALUES
('Apparel', 'apparel', 'bi-person-arms-up', 'Kaos, hoodie, jaket, dan pakaian lainnya', 1),
('Accessories', 'accessories', 'bi-bag', 'Topi, tas, lanyard, dan aksesoris lainnya', 2),
('Stationery', 'stationery', 'bi-pencil', 'Buku, pensil, pulpen, dan alat tulis lainnya', 3),
('Drinkware', 'drinkware', 'bi-cup-hot', 'Tumbler, mug, dan botol minum', 4),
('Collectibles', 'collectibles', 'bi-star', 'Stiker, pin, gantungan kunci, dan koleksi lainnya', 5);

-- Update existing products with categories
UPDATE `products` SET `category_id` = 1 WHERE `name` LIKE '%Hoodie%' OR `name` LIKE '%T-Shirt%';
UPDATE `products` SET `category_id` = 4 WHERE `name` LIKE '%Tumbler%';
UPDATE `products` SET `category_id` = 5 WHERE `name` LIKE '%Sticker%';
UPDATE `products` SET `category_id` = 2 WHERE `name` LIKE '%Lanyard%';
UPDATE `products` SET `category_id` = 3 WHERE `name` LIKE '%Notebook%';
