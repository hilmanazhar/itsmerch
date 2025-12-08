-- =============================================
-- Update Schema: Add Notifications System
-- Run this after update_schema_reviews.sql
-- =============================================

-- Table structure for notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('order_status','payment','promo','review','system') NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_notifications` (`user_id`, `is_read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample notifications for testing (optional, remove in production)
-- INSERT INTO notifications (user_id, type, title, message, link) VALUES
-- (1, 'promo', 'Promo Akhir Tahun!', 'Diskon 20% untuk semua merchandise ITS. Gunakan kode: AKHIRTAHUN2024', 'catalog.html'),
-- (1, 'system', 'Selamat Datang!', 'Terima kasih telah bergabung dengan myITS Merchandise.', NULL);
