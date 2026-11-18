-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 18, 2026 at 04:28 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `threadline`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Women', 'women', 'Considered layers, dresses and denim for every day.'),
(2, 'Men', 'men', 'Relaxed shirting, knitwear and trousers in natural fibres.'),
(3, 'Accessories', 'accessories', 'Bags, hats and small things that finish the look.'),
(4, 'Footwear', 'footwear', 'Low-key shoes made for long walks and slow Sundays.');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `name`, `email`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1, 'Hannah Lee', 'hannah@example.com', 'Sizing on the wrap dress', 'Hi! I am usually a size 10 and wondering whether to take S or M in the midi wrap dress. Thank you!', 0, '2026-11-08 07:20:00'),
(2, 'Daniel Tan', 'daniel@example.com', 'Restock request', 'Could you let me know when the wool blend coat comes back in size L? Happy to be added to a waitlist.', 1, '2026-11-05 10:02:00');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `reference` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'card',
  `status` enum('pending','paid','shipped','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_reference` (`reference`),
  KEY `idx_orders_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `reference`, `full_name`, `email`, `phone`, `address`, `city`, `postcode`, `subtotal`, `shipping`, `total`, `payment_method`, `status`, `created_at`) VALUES
(1, 2, 'TL-100241', 'Alex Rivera', 'alex@example.com', '+60 12-345 6789', '42A Jalan Bangsar', 'Kuala Lumpur', '59100', 138.00, 0.00, 138.00, 'card', 'completed', '2026-10-12 06:24:00'),
(2, 2, 'TL-100387', 'Alex Rivera', 'alex@example.com', '+60 12-345 6789', '42A Jalan Bangsar', 'Kuala Lumpur', '59100', 87.00, 7.95, 94.95, 'card', 'shipped', '2026-11-02 12:40:00'),
(3, 3, 'TL-100412', 'Priya Nair', 'priya@example.com', '+60 17-888 2211', '7 Lorong Gurney', 'Kuala Lumpur', '50900', 187.00, 0.00, 187.00, 'card', 'paid', '2026-11-06 05:05:00'),
(4, 3, 'TL-100430', 'Priya Nair', 'priya@example.com', '+60 17-888 2211', '7 Lorong Gurney', 'Kuala Lumpur', '50900', 189.00, 0.00, 189.00, 'cash', 'pending', '2026-11-09 14:12:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED DEFAULT NULL,
  `product_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'One Size',
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_items_order` (`order_id`),
  KEY `fk_items_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `size`, `unit_price`, `quantity`) VALUES
(1, 1, 3, 'Midi Wrap Dress', 'M', 96.00, 1),
(2, 1, 6, 'Ribbed Knit Top', 'S', 42.00, 1),
(3, 2, 2, 'Oversized Fleece Hoodie', 'L', 59.00, 1),
(4, 2, 9, 'Organic Cotton Cap', 'One Size', 28.00, 1),
(5, 3, 4, 'Straight Leg Jeans', '28', 88.00, 1),
(6, 3, 8, 'Leather Tote Bag', 'One Size', 99.00, 1),
(7, 4, 5, 'Wool Blend Coat', 'M', 189.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `material` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `care` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sizes` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'One Size',
  `stock` int NOT NULL DEFAULT '0',
  `image` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `material`, `care`, `price`, `sale_price`, `sizes`, `stock`, `image`, `featured`, `created_at`) VALUES
(1, 2, 'Linen Camp Shirt', 'linen-camp-shirt', 'A warm-weather staple cut from breathable European linen. The camp collar sits flat, the body is relaxed and the buttons are corozo nut. Wear it open over a tee or buttoned for dinner.', '100% European linen', 'Cold machine wash. Line dry in shade. Warm iron.', 58.00, NULL, 'S,M,L,XL', 24, 'linen-shirt.jpg', 1, '2026-11-12 09:49:57'),
(2, 2, 'Oversized Fleece Hoodie', 'oversized-fleece-hoodie', 'Our best-selling hoodie, brushed on the inside and garment-dyed for a lived-in finish. Dropped shoulders and a generous hood make it the piece you reach for first.', '80% organic cotton, 20% recycled polyester', 'Machine wash cold, inside out. Tumble dry low.', 74.00, 59.00, 'S,M,L,XL,XXL', 18, 'oversized-hoodie.jpg', 1, '2026-11-12 09:49:57'),
(3, 1, 'Midi Wrap Dress', 'midi-wrap-dress', 'A fluid midi dress with a true wrap front and a self-tie waist. Cut on the bias so it moves with you, with a hem that finishes just below the knee.', 'Lenzing™ EcoVero™ viscose', 'Hand wash cold or delicate cycle. Dry flat.', 96.00, NULL, 'XS,S,M,L,XL', 12, 'wrap-dress.jpg', 1, '2026-11-12 09:49:57'),
(4, 1, 'Straight Leg Jeans', 'straight-leg-jeans', 'A high-rise, straight-leg jean in rigid organic denim that softens with wear. Full length, clean hem and a fit that works with boots or sneakers.', '100% organic cotton denim', 'Machine wash cold with similar colours. Do not tumble dry.', 88.00, NULL, '24,26,28,30,32', 30, 'straight-jeans.jpg', 1, '2026-11-12 09:49:57'),
(5, 1, 'Wool Blend Coat', 'wool-blend-coat', 'An easy, unstructured coat in a soft wool blend. Mid-calf length, dropped shoulders and deep pockets — designed to layer over everything.', '60% recycled wool, 40% polyester', 'Dry clean only. Steam to refresh.', 189.00, NULL, 'XS,S,M,L', 8, 'wool-coat.jpg', 1, '2026-11-12 09:49:57'),
(6, 1, 'Ribbed Knit Top', 'ribbed-knit-top', 'A fine-gauge ribbed top with a high neck and a slightly cropped body. Soft enough for layering, structured enough to wear on its own.', '95% organic cotton, 5% elastane', 'Machine wash cold. Reshape and dry flat.', 42.00, NULL, 'XS,S,M,L,XL', 40, 'ribbed-knit.jpg', 1, '2026-11-12 09:49:57'),
(7, 4, 'Canvas Low-Top Sneakers', 'canvas-low-top-sneakers', 'A simple canvas sneaker with a vulcanised rubber sole and a cushioned insole. Made in a factory we have worked with for six years.', 'Organic cotton canvas, natural rubber sole', 'Spot clean with a damp cloth. Air dry.', 79.00, NULL, '36,37,38,39,40,41,42,43,44', 16, 'canvas-sneakers.jpg', 1, '2026-11-12 09:49:57'),
(8, 3, 'Leather Tote Bag', 'leather-tote-bag', 'A everyday tote in vegetable-tanned leather that patinas beautifully. Fits a 14-inch laptop, a notebook and a water bottle with room to spare.', 'Vegetable-tanned full-grain leather', 'Wipe with a soft dry cloth. Condition twice a year.', 128.00, 99.00, 'One Size', 10, 'leather-tote.jpg', 1, '2026-11-12 09:49:57'),
(9, 3, 'Organic Cotton Cap', 'organic-cotton-cap', 'A six-panel cap in washed organic cotton with an adjustable brass buckle. Low profile, curved brim.', '100% organic cotton', 'Hand wash cold. Air dry.', 28.00, NULL, 'One Size', 50, 'cotton-cap.jpg', 0, '2026-11-12 09:49:57'),
(10, 2, 'Wide Leg Linen Trousers', 'wide-leg-linen-trousers', 'Pull-on linen trousers with a soft elasticated back and a wide, fluid leg. The quiet answer to warm days.', '100% washed linen', 'Machine wash cold. Line dry. Warm iron.', 82.00, NULL, 'S,M,L,XL', 22, 'linen-trousers.jpg', 0, '2026-11-12 09:49:57'),
(11, 1, 'Cropped Denim Jacket', 'cropped-denim-jacket', 'A boxy, cropped take on the classic trucker jacket in mid-wash rigid denim. Silver hardware and two chest pockets.', '100% organic cotton denim', 'Machine wash cold, inside out. Air dry.', 110.00, NULL, 'XS,S,M,L', 14, 'denim-jacket.jpg', 0, '2026-11-12 09:49:57'),
(12, 3, 'Ribbed Knit Beanie', 'ribbed-knit-beanie', 'A snug, double-layer beanie knitted from soft recycled wool. Folded brim, no logo.', '70% recycled wool, 30% nylon', 'Hand wash cold. Dry flat.', 26.00, NULL, 'One Size', 35, 'knit-beanie.jpg', 0, '2026-11-12 09:49:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('customer','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `phone`, `address`, `city`, `postcode`, `role`, `created_at`) VALUES
(1, 'Maya Chen', 'admin@threadline.test', '$2y$10$OQi.8vbxZd.ySiSFIKaCR.pVNcilbgXUZUmZWt96EJ6v2n0ytNK3W', '+60 3-2000 1234', '18 Jalan Tun Perak', 'Kuala Lumpur', '50050', 'admin', '2026-11-12 09:49:57'),
(2, 'Alex Rivera', 'alex@example.com', '$2y$10$HdGOjVY0cVM5WD.c5Xl9U.p1pzdOFTqh1J06w6ttlJNVYrH/rQyVu', '+60 12-345 6789', '42A Jalan Bangsar', 'Kuala Lumpur', '59100', 'customer', '2026-11-12 09:49:57'),
(3, 'Priya Nair', 'priya@example.com', '$2y$10$HdGOjVY0cVM5WD.c5Xl9U.p1pzdOFTqh1J06w6ttlJNVYrH/rQyVu', '+60 17-888 2211', '7 Lorong Gurney', 'Kuala Lumpur', '50900', 'customer', '2026-11-12 09:49:57');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
