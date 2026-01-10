-- --------------------------------------------------------
-- Database: `ecommerce_db`
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `ecommerce_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ecommerce_db`;

-- 1. USERS TABLE
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `profile_image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`name`, `email`, `password`, `role`, `profile_image_url`) VALUES
('Admin User', 'admin@example.com', '$2y$10$w4y.pY2j.jP1cE5nFTbTj.gS/fO0h2Q0wJ2o3E1c.gG3z8r9C6pWa', 'admin', 'uploads/profiles/admin_pic.jpg'),
('Customer John', 'john.doe@example.com', '$2y$10$vH/5B5Stc2e/A6x5q1R.q.yVnI.2w.h8n/U5d3B9K.x2p0a/L4Q1G', 'customer', NULL);

-- 2. CATEGORIES TABLE
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `products_per_page` int(11) DEFAULT 8,
  `default_sort_order` varchar(50) DEFAULT 'newest',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`id`, `name`, `description`, `image_url`, `is_active`, `products_per_page`, `default_sort_order`) VALUES
(1, 'Food & Drinks', 'Snacks and beverages for the event.', 'uploads/categories/food.jpg', 1, 8, 'newest'),
(2, 'Merchandise', 'School branded shirts and souvenirs.', 'uploads/categories/merch.jpg', 1, 8, 'price_desc'),
(3, 'Stationery', 'Useful items for students.', 'uploads/categories/stationery.jpg', 1, 9, 'name_asc');

-- 3. PRODUCTS TABLE
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `average_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_product_category` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ORDERS TABLE (Contains all project features)
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shipping_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `grand_total` decimal(10,2) NOT NULL,
  `discount_code` varchar(100) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_charge` decimal(10,2) DEFAULT 0.00,
  `shipping_address` text,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cod',
  `transaction_code` varchar(100) DEFAULT NULL,
  `status` enum('Pending Payment','Payment Submitted','Payment Verified','Payment Rejected','Processing','Shipped','Completed','Cancelled') NOT NULL DEFAULT 'Payment Submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_transaction_code` (`transaction_code`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. ORDER_ITEMS TABLE
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_item` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. REVIEWS TABLE
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT 'Rating from 1 to 5',
  `review_text` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_review_per_order_item` (`order_item_id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. DISCOUNTS TABLE
DROP TABLE IF EXISTS `discounts`;
CREATE TABLE `discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `times_used` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. WISHLIST TABLE
DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product_unique` (`user_id`,`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. SALES LEDGER (Permanent Records)
DROP TABLE IF EXISTS `sales_ledger`;
CREATE TABLE `sales_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `price_per_item` decimal(10,2) NOT NULL,
  `transaction_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_product_unique` (`order_id`, `product_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. SITE SETTINGS TABLE
DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Settings for the Project
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES 
('shipping_charge', '5'),
('esewa_id', '98XXXXXXXX'),
('esewa_qr', 'uploads/default_qr.png'),
('hero_title', 'Annual Function 2025<br>Order from the Ground'),
('hero_description', 'Skip the lines! Order your favorite snacks and items and get them delivered to your spot.'),
('about_us_title', 'About Our Event Store'),
('about_us_content', '<p>Welcome to MyStore, your event-day companion.</p>'),
('privacy_policy_title', 'Your Privacy'),
('privacy_policy_content', '<p>We only use your GPS and phone number for delivery purposes.</p>');

--Alter queries 
ALTER TABLE categories 
ADD COLUMN allow_cod TINYINT(1) DEFAULT 1, 
ADD COLUMN allow_esewa TINYINT(1) DEFAULT 1;