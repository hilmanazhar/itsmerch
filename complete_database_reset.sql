-- =============================================
-- COMPLETE DATABASE RESET SCRIPT
-- Cleans everything and inserts fresh sample data
-- Run this in phpMyAdmin or MySQL CLI
-- =============================================

-- Drop database and recreate
DROP DATABASE IF EXISTS `its_merchandise`;
CREATE DATABASE `its_merchandise` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `its_merchandise`;

-- =============================================
-- CORE TABLES
-- =============================================

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `province_id` varchar(10) DEFAULT NULL,
  `city_id` varchar(10) DEFAULT NULL,
  `address_detail` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Categories table
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Products table
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `has_variants` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- VARIANT TABLES
-- =============================================

-- Variant options (sizes, colors, etc.)
CREATE TABLE `variant_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('size','color') NOT NULL,
  `value` varchar(100) NOT NULL,
  `display_value` varchar(100) NOT NULL,
  `sort_order` int DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_type_value` (`type`, `value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Product variants
CREATE TABLE `product_variants` (
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

-- =============================================
-- ORDER TABLES
-- =============================================

-- User addresses
CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL DEFAULT 'Home',
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `destination_id` varchar(20) DEFAULT NULL,
  `destination_label` varchar(255) DEFAULT NULL,
  `address_detail` text NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Orders table
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Belum Bayar',
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `shipping_address` text DEFAULT NULL,
  `shipping_courier` varchar(50) DEFAULT NULL,
  `shipping_service` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('unpaid','paid','failed') DEFAULT 'unpaid',
  `snap_token` varchar(255) DEFAULT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Order details
CREATE TABLE `order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `variant_info` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cart table
CREATE TABLE `carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`user_id`, `product_id`, `variant_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- ADDITIONAL FEATURES
-- =============================================

-- Reviews table
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_review` (`product_id`, `user_id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Wishlist table
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`user_id`, `product_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Coupons table
CREATE TABLE `coupons` (
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
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Coupon Usages table (Moved here for FK constraint)
CREATE TABLE `coupon_usages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `coupon_usages_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notifications table
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- INSERT SAMPLE DATA
-- =============================================

-- Users (password: password)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`) VALUES
(1, 'Admin ITS', 'admin@its.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '081234567890'),
(2, 'Hilman User', 'hilman@its.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '089876543210'),
(3, 'John Doe', 'john@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '081111222333');

-- Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `is_active`, `sort_order`) VALUES
(1, 'Pakaian', 'pakaian', 'Koleksi pakaian resmi ITS', 1, 1),
(2, 'Aksesoris', 'aksesoris', 'Aksesoris dan merchandise ITS', 1, 2),
(3, 'Alat Tulis', 'alat-tulis', 'Perlengkapan alat tulis ITS', 1, 3);

-- Variant Options - Sizes
INSERT INTO `variant_options` (`id`, `type`, `value`, `display_value`, `sort_order`) VALUES
(1, 'size', 'S', 'S', 1),
(2, 'size', 'M', 'M', 2),
(3, 'size', 'L', 'L', 3),
(4, 'size', 'XL', 'XL', 4),
(5, 'size', 'XXL', 'XXL', 5);

-- Variant Options - Colors
INSERT INTO `variant_options` (`id`, `type`, `value`, `display_value`, `sort_order`) VALUES
(6, 'color', 'Hitam', 'Hitam', 1),
(7, 'color', 'Putih', 'Putih', 2),
(8, 'color', 'Navy', 'Navy', 3),
(9, 'color', 'Abu-abu', 'Abu-abu', 4),
(10, 'color', 'Merah', 'Merah', 5);

-- Products
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `stock`, `image_url`, `has_variants`) VALUES
(1, 1, 'ITS Hoodie Premium', 'Hoodie premium dengan bordir logo ITS. Bahan fleece tebal, lembut dan hangat.', 185000.00, 100, 'https://placehold.co/400x400/1a1a2e/9b59b6?text=ITS+Hoodie', 1),
(2, 1, 'ITS T-Shirt Classic', 'Kaos katun combed 30s dengan sablon berkualitas tinggi logo ITS.', 95000.00, 200, 'https://placehold.co/400x400/16213e/4db5e6?text=ITS+T-Shirt', 1),
(3, 1, 'ITS Polo Shirt', 'Polo shirt resmi dengan emblem ITS di dada.', 125000.00, 75, 'https://placehold.co/400x400/0f3460/e94560?text=ITS+Polo', 1),
(4, 2, 'ITS Tumbler Stainless', 'Tumbler stainless steel 500ml dengan logo ITS.', 85000.00, 50, 'https://placehold.co/400x400/1e1e2e/ffffff?text=ITS+Tumbler', 0),
(5, 2, 'ITS Tote Bag', 'Tote bag kanvas tebal dengan desain eksklusif ITS.', 65000.00, 100, 'https://placehold.co/400x400/2d4059/ea5455?text=ITS+Tote+Bag', 0),
(6, 2, 'ITS Lanyard', 'Lanyard premium dengan kait metal.', 25000.00, 200, 'https://placehold.co/400x400/007bc0/ffffff?text=ITS+Lanyard', 0),
(7, 3, 'ITS Notebook A5', 'Notebook hardcover A5 dengan 100 halaman.', 35000.00, 150, 'https://placehold.co/400x400/333333/ffffff?text=ITS+Notebook', 0),
(8, 3, 'ITS Pen Set', 'Set 3 pulpen dengan logo ITS.', 45000.00, 100, 'https://placehold.co/400x400/4a4a4a/ffd93d?text=ITS+Pen+Set', 0),
(9, 1, 'ITS Jacket Varsity', 'Jaket varsity dengan kombinasi warna khas ITS.', 275000.00, 50, 'https://placehold.co/400x400/1a1a2e/c0392b?text=ITS+Jacket', 1),
(10, 2, 'ITS Cap', 'Topi baseball dengan logo ITS bordir.', 55000.00, 100, '', 0);

-- Product Variants for Hoodie (Product 1)
INSERT INTO `product_variants` (`product_id`, `size_option_id`, `color_option_id`, `stock`, `price_adjustment`) VALUES
(1, 1, 6, 10, 0),   -- S, Hitam
(1, 1, 8, 10, 0),   -- S, Navy
(1, 2, 6, 15, 0),   -- M, Hitam
(1, 2, 8, 15, 0),   -- M, Navy
(1, 3, 6, 20, 0),   -- L, Hitam
(1, 3, 8, 18, 0),   -- L, Navy
(1, 4, 6, 12, 0),   -- XL, Hitam
(1, 4, 8, 10, 0),   -- XL, Navy
(1, 5, 6, 5, 10000), -- XXL, Hitam (price +10k)
(1, 5, 8, 5, 10000); -- XXL, Navy (price +10k)

-- Product Variants for T-Shirt (Product 2)
INSERT INTO `product_variants` (`product_id`, `size_option_id`, `color_option_id`, `stock`, `price_adjustment`) VALUES
(2, 1, 6, 20, 0),   -- S, Hitam
(2, 1, 7, 20, 0),   -- S, Putih
(2, 2, 6, 30, 0),   -- M, Hitam
(2, 2, 7, 30, 0),   -- M, Putih
(2, 3, 6, 25, 0),   -- L, Hitam
(2, 3, 7, 25, 0),   -- L, Putih
(2, 4, 6, 15, 0),   -- XL, Hitam
(2, 4, 7, 15, 0),   -- XL, Putih
(2, 5, 6, 10, 5000), -- XXL, Hitam (price +5k)
(2, 5, 7, 10, 5000); -- XXL, Putih (price +5k)

-- Product Variants for Polo (Product 3)
INSERT INTO `product_variants` (`product_id`, `size_option_id`, `color_option_id`, `stock`, `price_adjustment`) VALUES
(3, 2, 7, 15, 0),   -- M, Putih
(3, 2, 8, 15, 0),   -- M, Navy
(3, 3, 7, 20, 0),   -- L, Putih
(3, 3, 8, 20, 0),   -- L, Navy
(3, 4, 7, 10, 0),   -- XL, Putih
(3, 4, 8, 10, 0);   -- XL, Navy

-- Product Variants for Jacket (Product 9)
INSERT INTO `product_variants` (`product_id`, `size_option_id`, `color_option_id`, `stock`, `price_adjustment`) VALUES
(9, 2, 6, 10, 0),   -- M, Hitam
(9, 3, 6, 15, 0),   -- L, Hitam
(9, 4, 6, 10, 0),   -- XL, Hitam
(9, 5, 6, 5, 15000); -- XXL, Hitam (price +15k)

-- Sample Address for User 2
INSERT INTO `user_addresses` (`user_id`, `label`, `recipient_name`, `phone`, `destination_id`, `destination_label`, `address_detail`, `is_primary`) VALUES
(2, 'Rumah', 'Hilman', '089876543210', '444', 'Surabaya', 'Jl. Keputih Tegal Timur No. 100, Keputih, Sukolilo', 1),
(2, 'Kampus', 'Hilman - ITS', '089876543210', '444', 'Surabaya', 'Gedung Rektorat ITS, Kampus ITS Sukolilo', 0);

-- Sample Coupons
INSERT INTO `coupons` (`code`, `type`, `value`, `min_purchase`, `max_discount`, `usage_limit`, `valid_from`, `valid_until`, `description`, `is_active`) VALUES
('WELCOME10', 'percentage', 10, 50000, 20000, 100, '2024-01-01 00:00:00', '2025-12-31 23:59:59', 'Diskon 10% untuk pembelian pertama', 1),
('HEMAT20', 'percentage', 20, 100000, 50000, 50, '2024-01-01 00:00:00', '2025-12-31 23:59:59', 'Diskon 20% minimal pembelian Rp100.000', 1),
('GRATIS15K', 'fixed', 15000, 75000, NULL, 200, '2024-01-01 00:00:00', '2025-12-31 23:59:59', 'Potongan Rp15.000 minimal belanja Rp75.000', 1);

-- =============================================
-- DONE! Database is now reset with fresh data
-- =============================================

-- Login credentials:
-- Admin: admin@its.ac.id / password
-- User: hilman@its.ac.id / password
