-- =============================================
-- Update Schema: Add Coupons & Discounts System
-- Run this after update_schema_notifications.sql
-- =============================================

-- Table structure for coupons
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `used_count` int DEFAULT 0,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table to track coupon usage by users
CREATE TABLE IF NOT EXISTS `coupon_usages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coupon_user` (`coupon_id`, `user_id`),
  CONSTRAINT `fk_usage_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add coupon_id and discount columns to orders table
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `coupon_id` int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `discount_amount` decimal(10,2) DEFAULT 0;

-- Insert sample coupons for testing
INSERT INTO `coupons` (`code`, `type`, `value`, `min_purchase`, `max_discount`, `usage_limit`, `valid_until`, `description`) VALUES
('WELCOME10', 'percentage', 10, 50000, 20000, 100, DATE_ADD(NOW(), INTERVAL 30 DAY), 'Diskon 10% untuk pengguna baru'),
('ITSMERCH20', 'percentage', 20, 100000, 50000, 50, DATE_ADD(NOW(), INTERVAL 30 DAY), 'Diskon 20% untuk pembelian min 100rb'),
('GRATIS15K', 'fixed', 15000, 75000, NULL, 30, DATE_ADD(NOW(), INTERVAL 14 DAY), 'Potongan langsung 15rb')
ON DUPLICATE KEY UPDATE `id`=`id`;
