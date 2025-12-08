-- =============================================
-- Update Schema: Add Product Variants (Size/Color)
-- Run this after update_schema_product_images.sql
-- =============================================

-- Table for variant types (e.g., Size, Color)
CREATE TABLE IF NOT EXISTS `variant_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default variant types
INSERT INTO `variant_types` (`name`, `display_name`) VALUES
('size', 'Ukuran'),
('color', 'Warna')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Table for variant options (e.g., S, M, L, XL for Size)
CREATE TABLE IF NOT EXISTS `variant_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variant_type_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  `display_value` varchar(100) NOT NULL,
  `sort_order` int DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_variant_type` (`variant_type_id`),
  CONSTRAINT `fk_option_type` FOREIGN KEY (`variant_type_id`) REFERENCES `variant_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default size options
INSERT INTO `variant_options` (`variant_type_id`, `value`, `display_value`, `sort_order`) VALUES
((SELECT id FROM variant_types WHERE name = 'size'), 'S', 'S', 1),
((SELECT id FROM variant_types WHERE name = 'size'), 'M', 'M', 2),
((SELECT id FROM variant_types WHERE name = 'size'), 'L', 'L', 3),
((SELECT id FROM variant_types WHERE name = 'size'), 'XL', 'XL', 4),
((SELECT id FROM variant_types WHERE name = 'size'), 'XXL', 'XXL', 5)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert default color options
INSERT INTO `variant_options` (`variant_type_id`, `value`, `display_value`, `sort_order`) VALUES
((SELECT id FROM variant_types WHERE name = 'color'), 'black', 'Hitam', 1),
((SELECT id FROM variant_types WHERE name = 'color'), 'white', 'Putih', 2),
((SELECT id FROM variant_types WHERE name = 'color'), 'navy', 'Navy', 3),
((SELECT id FROM variant_types WHERE name = 'color'), 'gray', 'Abu-abu', 4),
((SELECT id FROM variant_types WHERE name = 'color'), 'red', 'Merah', 5),
((SELECT id FROM variant_types WHERE name = 'color'), 'blue', 'Biru', 6)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Table for product variants (links products to variant options with stock/price)
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `size_option_id` int(11) DEFAULT NULL,
  `color_option_id` int(11) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `price_adjustment` decimal(10,2) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_variant` (`product_id`, `size_option_id`, `color_option_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_variant_size` FOREIGN KEY (`size_option_id`) REFERENCES `variant_options`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_variant_color` FOREIGN KEY (`color_option_id`) REFERENCES `variant_options`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add has_variants column to products table
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `has_variants` tinyint(1) NOT NULL DEFAULT 0;

-- NOTE: If you have an order_items table, run this manually:
-- ALTER TABLE `order_items` 
-- ADD COLUMN IF NOT EXISTS `variant_id` int(11) DEFAULT NULL,
-- ADD COLUMN IF NOT EXISTS `variant_info` varchar(255) DEFAULT NULL;
