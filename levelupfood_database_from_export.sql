-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2025 at 09:56 AM (Based on provided dump header)
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
-- Database: `levelupfood_db`
--
DROP DATABASE IF EXISTS `levelupfood_db`;
CREATE DATABASE IF NOT EXISTS `levelupfood_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `levelupfood_db`;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Category name',
  `description` text DEFAULT NULL COMMENT 'Optional category description',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores food item categories';

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Starters', 'Delicious appetizers to begin your meal.', '2025-04-25 11:24:15', '2025-04-25 11:24:15'),
(2, 'Soups', 'Warm and comforting soups.', '2025-04-25 11:24:15', '2025-04-25 11:24:15'),
(3, 'Main Course - Indian', 'Authentic Indian main dishes.', '2025-04-25 11:24:15', '2025-04-25 11:24:15'),
(4, 'Main Course - Continental', 'Classic Continental main dishes.', '2025-04-25 11:24:15', '2025-04-25 11:24:15'),
(5, 'Noodles & Rice', 'Flavorful noodle and rice preparations.', '2025-04-25 11:24:15', '2025-04-25 11:24:15'),
(6, 'Salads', 'Fresh and healthy salads.', '2025-04-25 11:24:15', '2025-04-25 11:24:15'),
(7, 'Desserts', 'Sweet treats to end your meal.', '2025-04-25 11:24:15', '2025-04-25 11:24:15'),
(9, 'Beverages', 'Refreshing drinks.', '2025-04-25 12:07:17', '2025-04-25 12:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag indicating if message has been read by admin (1=Yes, 0=No)',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores messages from the contact form';

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `is_read`, `submitted_at`) VALUES
(1, 'John Doe', 'john.doe@email.com', 'This is a test message. Great website!', 1, '2025-04-25 11:24:15');
-- Note: If you had other messages submitted, they would appear here in a real export.

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL COMMENT 'Foreign key linking to categories table',
  `name` varchar(255) NOT NULL COMMENT 'Name of the food item',
  `description` text DEFAULT NULL COMMENT 'Description of the food item',
  `price` decimal(10,2) NOT NULL COMMENT 'Price of the item',
  `image_url` varchar(255) DEFAULT NULL COMMENT 'URL or path to the item image',
  `is_available` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Flag to indicate if item is currently offered (1=Yes, 0=No)',
  `is_special` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag for special/featured items (1=Yes, 0=No)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores individual food/menu items';

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_available`, `is_special`, `created_at`, `updated_at`) VALUES
(1, 1, 'Veg Spring Rolls', 'Crispy rolls filled with fresh vegetables.', 120.00, 'Images/menu/item_680b866c0e3107.09882406.png', 1, 0, '2025-04-25 11:24:15', '2025-04-25 12:56:12'),
(2, 2, 'Tomato Soup', 'Classic creamy tomato soup.', 90.00, 'Images/menu/item_680b85f0aed4a3.46331476.png', 1, 0, '2025-04-25 11:24:15', '2025-04-25 12:54:08'),
(3, 3, 'Paneer Butter Masala', 'Soft paneer cubes in a rich creamy tomato gravy.', 250.00, 'Images/menu/item_680b83c87675b4.37693769.png', 1, 1, '2025-04-25 11:24:15', '2025-04-25 12:44:56'),
(4, 3, 'Kathiyawadi Thali', 'A complete meal with various Gujarati dishes.', 300.00, 'Images/menu/item_680b8025d205d8.51016132.jpg', 1, 0, '2025-04-25 11:24:15', '2025-04-25 12:29:25'),
(5, 5, 'Veg Hakka Noodles', 'Stir-fried noodles with vegetables.', 180.00, 'Images/menu/item_680b837d90ad32.93051502.jpg', 1, 0, '2025-04-25 11:24:15', '2025-04-25 12:43:41'),
(6, 6, 'Greek Salad', 'Fresh cucumbers, tomatoes, olives, and feta cheese.', 150.00, 'Images/menu/item_680b85a9649f58.65527304.png', 1, 0, '2025-04-25 11:24:15', '2025-04-25 12:52:57'),
(7, 7, 'Chocolate Brownie', 'Warm chocolate brownie with ice cream.', 160.00, 'Images/menu/item_680b7cf5b6daa4.39632851.jpg', 1, 1, '2025-04-25 11:24:15', '2025-04-25 12:15:49');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'FK to users table',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Processing','Out for Delivery','Delivered','Cancelled','Failed') NOT NULL DEFAULT 'Pending',
  `shipping_name` varchar(255) NOT NULL,
  `shipping_address_line1` varchar(255) NOT NULL,
  `shipping_address_line2` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_postal_code` varchar(20) NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'COD' COMMENT 'e.g., COD, Stripe, PayPal',
  `payment_status` enum('Pending','Completed','Failed') NOT NULL DEFAULT 'Pending',
  `transaction_id` varchar(255) DEFAULT NULL COMMENT 'Optional: ID from payment gateway',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores customer order summary';

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `total_amount`, `status`, `shipping_name`, `shipping_address_line1`, `shipping_address_line2`, `shipping_city`, `shipping_postal_code`, `shipping_phone`, `payment_method`, `payment_status`, `transaction_id`, `updated_at`) VALUES
(1, 1, '2025-04-26 05:16:13', 550.00, 'Out for Delivery', 'Aastha kacchhi', 'qwertyuiop', 'lkjhgfdsa', 'Rajkot', '360004', '7418529634', 'COD', 'Pending', NULL, '2025-04-26 07:26:15'); -- Reflects the data shown in phpMyAdmin screenshot
-- Note: payment_status is still Pending in the export, matching the user view screenshot issue.

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL COMMENT 'FK to orders table',
  `menu_item_id` int(11) DEFAULT NULL COMMENT 'FK to menu_items table (allow NULL if item deleted)',
  `item_name_at_order` varchar(255) NOT NULL COMMENT 'Store name in case menu item changes/deleted',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_at_order` decimal(10,2) NOT NULL COMMENT 'Price of single item when ordered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores individual items within an order';

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `item_name_at_order`, `quantity`, `price_at_order`) VALUES
(1, 1, 4, 'Kathiyawadi Thali', 1, 300.00),
(2, 1, 3, 'Paneer Butter Masala', 1, 250.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Stores hashed password',
  `role` enum('user','admin') NOT NULL DEFAULT 'user' COMMENT 'User role for potential admin features',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores user account information';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'User Name', 'test@example.com', '$2y$10$.5pTf34fTOtWgwCiCFyNMeWlP9rVe3l5fLcva/nKapL/4M0Om2PKa', 'user', '2025-04-25 11:24:15', '2025-04-26 07:49:11'), -- Password 123456
(2, 'Admin User', 'admin@example.com', '$2y$10$Pg7ptMvSv8l/si3KAhLwUOwa0sape7u9l1Ug2Y0Z6Y67oywjqyA2y', 'admin', '2025-04-25 11:24:15', '2025-04-26 07:49:29'); -- Password 123456

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_category_name` (`name`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_menu_item_name` (`name`),
  ADD KEY `idx_is_available` (`is_available`),
  ADD KEY `idx_is_special` (`is_special`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_user_id` (`user_id`),
  ADD KEY `idx_order_status` (`status`),
  ADD KEY `idx_order_date` (`order_date`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order_id` (`order_id`),
  ADD KEY `idx_order_items_menu_item_id` (`menu_item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE; -- Note: Export shows ON UPDATE CASCADE, default ON DELETE is RESTRICT/NO ACTION

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;