-- =============================================
-- Update Schema: Add Multiple Product Images
-- Run this after update_schema_coupons.sql
-- =============================================

-- Table structure for product images
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_images` (`product_id`, `sort_order`),
  CONSTRAINT `fk_image_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrate existing product images to the new table
-- This will copy the current image_url from products table to product_images
INSERT INTO `product_images` (`product_id`, `image_url`, `is_primary`, `sort_order`)
SELECT p.`id`, p.`image_url`, 1, 0 FROM `products` p
WHERE p.`image_url` IS NOT NULL AND p.`image_url` != ''
ON DUPLICATE KEY UPDATE `product_id`=`product_id`;

-- Add some additional sample images for products (optional, for testing carousel)
-- Uncomment if needed
-- INSERT INTO `product_images` (`product_id`, `image_url`, `is_primary`, `sort_order`) VALUES
-- (1, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400', 0, 1),
-- (1, 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=400', 0, 2);
