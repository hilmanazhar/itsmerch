-- =============================================
-- Update Schema: Add Reviews & Ratings
-- Run this after update_schema_wishlist.sql
-- =============================================

-- Table structure for product reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product_review` (`user_id`, `product_id`),
  KEY `idx_product_reviews` (`product_id`),
  CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add average_rating column to products table for caching
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `average_rating` decimal(2,1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `review_count` int DEFAULT 0;

-- Trigger to update product rating after insert
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `update_product_rating_insert` 
AFTER INSERT ON `reviews`
FOR EACH ROW
BEGIN
    UPDATE products 
    SET average_rating = (SELECT AVG(rating) FROM reviews WHERE product_id = NEW.product_id),
        review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = NEW.product_id)
    WHERE id = NEW.product_id;
END//
DELIMITER ;

-- Trigger to update product rating after update
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `update_product_rating_update` 
AFTER UPDATE ON `reviews`
FOR EACH ROW
BEGIN
    UPDATE products 
    SET average_rating = (SELECT AVG(rating) FROM reviews WHERE product_id = NEW.product_id),
        review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = NEW.product_id)
    WHERE id = NEW.product_id;
END//
DELIMITER ;

-- Trigger to update product rating after delete
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `update_product_rating_delete` 
AFTER DELETE ON `reviews`
FOR EACH ROW
BEGIN
    UPDATE products 
    SET average_rating = COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = OLD.product_id), 0),
        review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = OLD.product_id)
    WHERE id = OLD.product_id;
END//
DELIMITER ;
