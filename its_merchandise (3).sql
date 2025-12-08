-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 03:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `its_merchandise`
--

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image_url`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Pakaian', 'pakaian', 'Koleksi pakaian resmi ITS', NULL, 1, 1, '2025-12-08 10:33:59'),
(2, 'Aksesoris', 'aksesoris', 'Aksesoris dan merchandise ITS', NULL, 1, 2, '2025-12-08 10:33:59'),
(3, 'Alat Tulis', 'alat-tulis', 'Perlengkapan alat tulis ITS', NULL, 1, 3, '2025-12-08 10:33:59');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `type`, `value`, `min_purchase`, `max_discount`, `usage_limit`, `used_count`, `valid_from`, `valid_until`, `description`, `is_active`, `created_at`) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 50000.00, 20000.00, 100, 1, '2024-01-01 00:00:00', '2025-12-31 23:59:59', 'Diskon 10% untuk pembelian pertama', 1, '2025-12-08 10:33:59'),
(2, 'HEMAT20', 'percentage', 20.00, 100000.00, 50000.00, 50, 0, '2024-01-01 00:00:00', '2025-12-31 23:59:59', 'Diskon 20% minimal pembelian Rp100.000', 1, '2025-12-08 10:33:59'),
(3, 'GRATIS15K', 'fixed', 15000.00, 75000.00, NULL, 200, 0, '2024-01-01 00:00:00', '2025-12-31 23:59:59', 'Potongan Rp15.000 minimal belanja Rp75.000', 1, '2025-12-08 10:33:59'),
(4, 'DISKON50', 'percentage', 50.00, 1.00, NULL, NULL, 1, '2025-12-07 17:34:00', '2025-12-24 17:34:00', '', 1, '2025-12-08 10:35:00'),
(5, 'GRATIS', 'percentage', 100.00, 0.00, NULL, NULL, 1, NULL, NULL, '', 1, '2025-12-08 10:36:55'),
(6, 'DISKON99', 'percentage', 99.00, 1.00, NULL, NULL, 1, NULL, NULL, '', 1, '2025-12-08 13:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usages`
--

CREATE TABLE `coupon_usages` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupon_usages`
--

INSERT INTO `coupon_usages` (`id`, `coupon_id`, `user_id`, `order_id`, `discount_amount`, `created_at`) VALUES
(1, 4, 2, 1, 550000.00, '2025-12-08 10:35:23'),
(2, 5, 2, 4, 5860000.00, '2025-12-08 10:37:54'),
(3, 1, 2, 5, 20000.00, '2025-12-08 10:46:02'),
(4, 6, 2, 6, 594000.00, '2025-12-08 13:45:33');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `address_id`, `total_amount`, `status`, `shipping_cost`, `shipping_address`, `shipping_courier`, `shipping_service`, `tracking_number`, `payment_method`, `payment_status`, `snap_token`, `coupon_code`, `discount_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 1110500.00, 'cancelled', 10500.00, '', '', NULL, NULL, 'midtrans', 'unpaid', '8d1daeb1-0627-470e-ba30-224b18b801d9', NULL, 0.00, NULL, '2025-12-08 10:35:22', '2025-12-08 10:36:07'),
(4, 2, NULL, 5882000.00, 'completed', 22000.00, 'Hilman (089876543210) - Jl. Keputih Tegal Timur No. 100, Keputih, Sukolilo', '', NULL, '123425364789', 'midtrans', 'paid', '039d489a-7889-435a-8ded-8957c0171a74', NULL, 0.00, NULL, '2025-12-08 10:37:53', '2025-12-08 10:38:47'),
(5, 2, NULL, 7173000.00, 'completed', 43000.00, 'Hilman - ITS (089876543210) - Gedung Rektorat ITS, Kampus ITS Sukolilo', '', NULL, '2134567890', 'midtrans', 'paid', '6903c09f-4771-4b63-b017-81c0a573ff6a', 'WELCOME10', 20000.00, NULL, '2025-12-08 10:46:01', '2025-12-08 10:46:41'),
(6, 2, NULL, 49000.00, 'completed', 43000.00, 'Hilman (089876543210) - Jl. Keputih Tegal Timur No. 100, Keputih, Sukolilo', '', NULL, '1234224315rgf', 'midtrans', 'paid', 'a8135a59-c8f5-44f9-b0e7-cd55147b3f7c', 'DISKON99', 594000.00, NULL, '2025-12-08 13:45:32', '2025-12-08 13:46:16');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `variant_info` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `variant_id`, `variant_info`, `quantity`, `price`) VALUES
(1, 1, 9, NULL, NULL, 4, 275000.00),
(6, 4, 8, NULL, NULL, 8, 45000.00),
(7, 4, 9, NULL, NULL, 20, 275000.00),
(8, 5, 9, NULL, NULL, 26, 275000.00),
(9, 6, 11, NULL, NULL, 6, 100000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `has_variants` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `stock`, `image_url`, `has_variants`, `is_active`, `created_at`) VALUES
(1, 1, 'ITS Hoodie Premium', 'Hoodie premium dengan bordir logo ITS. Bahan fleece tebal, lembut dan hangat.', 185000.00, 100, 'https://placehold.co/400x400/1a1a2e/9b59b6?text=ITS+Hoodie', 1, 1, '2025-12-08 10:33:59'),
(2, 1, 'ITS T-Shirt Classic', 'Kaos katun combed 30s dengan sablon berkualitas tinggi logo ITS.', 95000.00, 200, 'https://placehold.co/400x400/16213e/4db5e6?text=ITS+T-Shirt', 1, 1, '2025-12-08 10:33:59'),
(3, 1, 'ITS Polo Shirt', 'Polo shirt resmi dengan emblem ITS di dada.', 125000.00, 75, 'https://placehold.co/400x400/0f3460/e94560?text=ITS+Polo', 1, 1, '2025-12-08 10:33:59'),
(4, 2, 'ITS Tumbler Stainless', 'Tumbler stainless steel 500ml dengan logo ITS.', 85000.00, 50, 'https://placehold.co/400x400/1e1e2e/ffffff?text=ITS+Tumbler', 0, 1, '2025-12-08 10:33:59'),
(5, 2, 'ITS Tote Bag', 'Tote bag kanvas tebal dengan desain eksklusif ITS.', 65000.00, 100, 'https://placehold.co/400x400/2d4059/ea5455?text=ITS+Tote+Bag', 0, 1, '2025-12-08 10:33:59'),
(6, 2, 'ITS Lanyard', 'Lanyard premium dengan kait metal.', 25000.00, 200, 'https://placehold.co/400x400/007bc0/ffffff?text=ITS+Lanyard', 0, 1, '2025-12-08 10:33:59'),
(7, 3, 'ITS Notebook A5', 'Notebook hardcover A5 dengan 100 halaman.', 35000.00, 150, 'https://placehold.co/400x400/333333/ffffff?text=ITS+Notebook', 0, 1, '2025-12-08 10:33:59'),
(8, 3, 'ITS Pen Set', 'Set 3 pulpen dengan logo ITS.', 45000.00, 92, 'https://placehold.co/400x400/4a4a4a/ffd93d?text=ITS+Pen+Set', 0, 1, '2025-12-08 10:33:59'),
(9, 1, 'ITS Jacket Varsity', 'Jaket varsity dengan kombinasi warna khas ITS.', 275000.00, 4, 'https://placehold.co/400x400/1a1a2e/c0392b?text=ITS+Jacket', 1, 1, '2025-12-08 10:33:59'),
(10, 2, 'ITS Cap', 'Topi baseball dengan logo ITS bordir.', 55000.00, 100, '', 0, 1, '2025-12-08 10:33:59'),
(11, NULL, 'baju', 'efjndbw', 100000.00, 94, 'assets/images/products/prod_1765191110_6936adc6b2c97.png', 1, 1, '2025-12-08 10:51:50');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `size_option_id` int(11) DEFAULT NULL,
  `color_option_id` int(11) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `sku`, `size_option_id`, `color_option_id`, `stock`, `price_adjustment`, `is_active`, `created_at`) VALUES
(1, 1, NULL, 1, 6, 10, 0.00, 1, '2025-12-08 10:33:59'),
(2, 1, NULL, 1, 8, 10, 0.00, 1, '2025-12-08 10:33:59'),
(3, 1, NULL, 2, 6, 15, 0.00, 1, '2025-12-08 10:33:59'),
(4, 1, NULL, 2, 8, 15, 0.00, 1, '2025-12-08 10:33:59'),
(5, 1, NULL, 3, 6, 20, 0.00, 1, '2025-12-08 10:33:59'),
(6, 1, NULL, 3, 8, 18, 0.00, 1, '2025-12-08 10:33:59'),
(7, 1, NULL, 4, 6, 12, 0.00, 1, '2025-12-08 10:33:59'),
(8, 1, NULL, 4, 8, 10, 0.00, 1, '2025-12-08 10:33:59'),
(9, 1, NULL, 5, 6, 5, 10000.00, 1, '2025-12-08 10:33:59'),
(10, 1, NULL, 5, 8, 5, 10000.00, 1, '2025-12-08 10:33:59'),
(11, 2, NULL, 1, 6, 20, 0.00, 1, '2025-12-08 10:33:59'),
(12, 2, NULL, 1, 7, 20, 0.00, 1, '2025-12-08 10:33:59'),
(13, 2, NULL, 2, 6, 30, 0.00, 1, '2025-12-08 10:33:59'),
(14, 2, NULL, 2, 7, 30, 0.00, 1, '2025-12-08 10:33:59'),
(15, 2, NULL, 3, 6, 25, 0.00, 1, '2025-12-08 10:33:59'),
(16, 2, NULL, 3, 7, 25, 0.00, 1, '2025-12-08 10:33:59'),
(17, 2, NULL, 4, 6, 15, 0.00, 1, '2025-12-08 10:33:59'),
(18, 2, NULL, 4, 7, 15, 0.00, 1, '2025-12-08 10:33:59'),
(19, 2, NULL, 5, 6, 10, 5000.00, 1, '2025-12-08 10:33:59'),
(20, 2, NULL, 5, 7, 10, 5000.00, 1, '2025-12-08 10:33:59'),
(21, 3, NULL, 2, 7, 15, 0.00, 1, '2025-12-08 10:33:59'),
(22, 3, NULL, 2, 8, 15, 0.00, 1, '2025-12-08 10:33:59'),
(23, 3, NULL, 3, 7, 20, 0.00, 1, '2025-12-08 10:33:59'),
(24, 3, NULL, 3, 8, 20, 0.00, 1, '2025-12-08 10:33:59'),
(25, 3, NULL, 4, 7, 10, 0.00, 1, '2025-12-08 10:33:59'),
(26, 3, NULL, 4, 8, 10, 0.00, 1, '2025-12-08 10:33:59'),
(27, 9, NULL, 2, 6, 10, 0.00, 1, '2025-12-08 10:33:59'),
(28, 9, NULL, 3, 6, 15, 0.00, 1, '2025-12-08 10:33:59'),
(29, 9, NULL, 4, 6, 10, 0.00, 1, '2025-12-08 10:33:59'),
(30, 9, NULL, 5, 6, 5, 15000.00, 1, '2025-12-08 10:33:59'),
(31, 11, '', 2, 6, 10, 0.00, 1, '2025-12-08 10:54:07'),
(32, 11, '', 2, 7, 10, 0.00, 1, '2025-12-08 10:54:07');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `province_id` varchar(10) DEFAULT NULL,
  `city_id` varchar(10) DEFAULT NULL,
  `address_detail` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `province_id`, `city_id`, `address_detail`, `created_at`) VALUES
(1, 'Admin ITS', 'admin@its.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '081234567890', NULL, NULL, NULL, '2025-12-08 10:33:59'),
(2, 'Hilman User', 'hilman@its.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '089876543210', NULL, NULL, NULL, '2025-12-08 10:33:59'),
(3, 'John Doe', 'john@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '081111222333', NULL, NULL, NULL, '2025-12-08 10:33:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL DEFAULT 'Home',
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `destination_id` varchar(20) DEFAULT NULL,
  `destination_label` varchar(255) DEFAULT NULL,
  `address_detail` text NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `label`, `recipient_name`, `phone`, `destination_id`, `destination_label`, `address_detail`, `is_primary`, `created_at`) VALUES
(1, 2, 'Rumah', 'Hilman', '089876543210', '444', 'Surabaya', 'Jl. Keputih Tegal Timur No. 100, Keputih, Sukolilo', 1, '2025-12-08 10:33:59'),
(2, 2, 'Kampus', 'Hilman - ITS', '089876543210', '444', 'Surabaya', 'Gedung Rektorat ITS, Kampus ITS Sukolilo', 0, '2025-12-08 10:33:59');

-- --------------------------------------------------------

--
-- Table structure for table `variant_options`
--

CREATE TABLE `variant_options` (
  `id` int(11) NOT NULL,
  `type` enum('size','color') NOT NULL,
  `value` varchar(100) NOT NULL,
  `display_value` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `variant_options`
--

INSERT INTO `variant_options` (`id`, `type`, `value`, `display_value`, `sort_order`) VALUES
(1, 'size', 'S', 'S', 1),
(2, 'size', 'M', 'M', 2),
(3, 'size', 'L', 'L', 3),
(4, 'size', 'XL', 'XL', 4),
(5, 'size', 'XXL', 'XXL', 5),
(6, 'color', 'Hitam', 'Hitam', 1),
(7, 'color', 'Putih', 'Putih', 2),
(8, 'color', 'Navy', 'Navy', 3),
(9, 'color', 'Abu-abu', 'Abu-abu', 4),
(10, 'color', 'Merah', 'Merah', 5);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`,`variant_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_variant` (`product_id`,`size_option_id`,`color_option_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `fk_variant_size` (`size_option_id`),
  ADD KEY `fk_variant_color` (`color_option_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`product_id`,`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `variant_options`
--
ALTER TABLE `variant_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_type_value` (`type`,`value`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `variant_options`
--
ALTER TABLE `variant_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD CONSTRAINT `coupon_usages_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variant_color` FOREIGN KEY (`color_option_id`) REFERENCES `variant_options` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_variant_size` FOREIGN KEY (`size_option_id`) REFERENCES `variant_options` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
