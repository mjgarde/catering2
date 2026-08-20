-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 20, 2026 at 03:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `catering`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(2, 'admin', '$2y$10$os3Ya/c5GNSqcbPG.5Q6JOWpWFy0lUP1ENo3EQebdoqNUfxbi8n9.');

-- --------------------------------------------------------

--
-- Table structure for table `booking_items`
--

CREATE TABLE `booking_items` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_items`
--

INSERT INTO `booking_items` (`id`, `booking_id`, `equipment_id`, `package_id`, `quantity`, `price`) VALUES
(16, 14, 31, NULL, 2, 10.00),
(17, 15, 31, NULL, 1, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`) VALUES
(62, 'Utensils and Dining Ware'),
(63, 'Food Service Equipment'),
(64, 'Furniture and Setup'),
(65, 'Cooking Equipment'),
(66, 'Event and DÃ©cor'),
(67, 'Rental Accessories');

-- --------------------------------------------------------

--
-- Table structure for table `customer_booking`
--

CREATE TABLE `customer_booking` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(500) NOT NULL,
  `email` varchar(500) DEFAULT NULL,
  `phone` varchar(500) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `borrow_date` datetime NOT NULL,
  `return_date` datetime NOT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('Pending','Borrowed','Returned') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actual_return_date` datetime DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `damage_fee` decimal(10,2) DEFAULT 0.00,
  `damage_notes` text DEFAULT NULL,
  `damaged_items` text DEFAULT NULL,
  `sms_reminder_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_booking`
--

INSERT INTO `customer_booking` (`id`, `customer_name`, `email`, `phone`, `address`, `borrow_date`, `return_date`, `total_amount`, `status`, `created_at`, `actual_return_date`, `fine_amount`, `damage_fee`, `damage_notes`, `damaged_items`, `sms_reminder_sent`) VALUES
(14, 'Juan Delacruz', 'm@m.c', '09099292922', 'Banga, South Cotabato', '2026-02-15 22:22:00', '2027-02-15 03:03:00', 20.00, 'Returned', '2026-08-20 00:34:06', '2026-08-20 02:52:37', 0.00, 0.00, '', NULL, 0),
(15, 'mick', 'd@g.c', '09090902109', 'Banga, South Cotabato', '2005-02-15 11:11:00', '2027-02-15 22:22:00', 10.00, 'Returned', '2026-08-20 00:55:03', '2026-08-20 02:56:11', 0.00, 0.00, '', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `customer_info`
--

CREATE TABLE `customer_info` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `birthday` date NOT NULL,
  `contact_number` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_info`
--

INSERT INTO `customer_info` (`id`, `fullname`, `address`, `gender`, `birthday`, `contact_number`, `username`, `password_hash`, `created_at`) VALUES
(1, 'BarEErpInHWR/ZW0B8fWXlBJcjlWL01HemEwODd2S2NkU29mV3c9PQ==', 'Db5pU0ALTVsnm+zB1MANK0U0dHUwbVBOOG9VUWFkRkRoRGtzOTJzYU5WMkVqNEROV05pRmI0WlBidVU9', 'Male', '2005-02-15', 'S8olQ7y66NytkKHY3e9eNkhYSk14SEFLRzV0bEZPbkc1UDg1ZEE9PQ==', 'juan1', '$2y$10$8nGZyReAma0jHmx2Nuu1rO.3VclloSe6esXQe7sljacFRV2/FV15.', '2026-08-19 23:21:29'),
(2, 'mick', 'Banga, South Cotabato', 'Male', '2005-02-15', '09090902109', 'admin', '$2y$10$Vstgs8AnHk4pUeF.YS2c1uOGsNonlGTTIEtoxiSGLceS2JMFAwaEC', '2026-08-20 00:54:08');

-- --------------------------------------------------------

--
-- Table structure for table `equipments`
--

CREATE TABLE `equipments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipments`
--

INSERT INTO `equipments` (`id`, `name`, `photo`, `category_id`, `price`, `quantity`, `stock`, `created_at`, `updated_at`) VALUES
(29, 'Tables', '1761357204_Tate60inRndDiningTbl3QSSF23_3D_512x512.webp', 64, 50.00, 19, 20, '2025-10-25 01:53:24', '2025-11-01 01:20:23'),
(30, 'Chairs', '1761357361_RUBY1-APPLE-GREEN-FRONT-with-sticker-min-600x696.webp', 64, 20.00, 260, 257, '2025-10-25 01:56:01', '2026-03-16 04:39:32'),
(31, 'Plates', '1761357436_1758710394_plates.jpg', 62, 10.00, 200, 179, '2025-10-25 01:57:16', '2026-08-20 00:56:11'),
(32, 'Sofas', '1761357491_1758712985_Sofas and Couches.png', 64, 40.00, 24, 5, '2025-10-25 01:58:11', '2026-03-16 04:01:03'),
(33, 'Wine Glass', '1761357556_1758712636_wine_glass.png', 62, 21.00, 34, 32, '2025-10-25 01:59:16', '2026-03-16 04:01:03'),
(34, 'Water Glass', '1761357577_1759635915_WATER GLASS.jpg', 62, 12.00, 63, 63, '2025-10-25 01:59:37', '2026-03-16 04:39:32');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `package_name`, `price`, `created_at`) VALUES
(2, 'Weeding Package', 3000.00, '2026-03-16 02:46:19');

-- --------------------------------------------------------

--
-- Table structure for table `package_items`
--

CREATE TABLE `package_items` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_items`
--

INSERT INTO `package_items` (`id`, `package_id`, `equipment_id`, `quantity`) VALUES
(7, 2, 30, 1),
(8, 2, 33, 1),
(9, 2, 32, 10),
(10, 2, 31, 11);

-- --------------------------------------------------------

--
-- Table structure for table `staff_info`
--

CREATE TABLE `staff_info` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `firstname` varchar(500) NOT NULL,
  `lastname` varchar(500) NOT NULL,
  `age` smallint(5) UNSIGNED DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(500) DEFAULT NULL,
  `username` varchar(500) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_info`
--

INSERT INTO `staff_info` (`id`, `firstname`, `lastname`, `age`, `address`, `contact_number`, `username`, `password_hash`, `created_at`) VALUES
(4, 'iiXrp6eU8CsBx5qwo32ZlHYzWWtudm1zdGxLekVsS01nTVNEdnc9PQ==', 'ZNFFGE8GsQbLHA1LYtQ1um5jRHBqdjdtdGV3M1VaTUt4dUg4TGc9PQ==', 15, 'K5O3spGJNeGe2Ucxud5dJEZpS3d2U3FTdXZGY2M4YnNEYlRlT0NvMW9vbXJ6aTRJd3dRK1FEaTFkd3RpcW9kK1hlTzEvOHVEek83VmNlRGw=', 'U3vnW9hcALdAXnaKJ6eewkZpQ2FkWWpycmEyTU9vNjBDUWdnNWc9PQ==', 'juan', '$2y$10$Ek.wRKThW3EG65U7pRQwjeQW3Fc10jNJyihcRuZqovd73Dy99A3ha', '2026-03-16 11:41:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking` (`booking_id`),
  ADD KEY `fk_equipment_booking` (`equipment_id`),
  ADD KEY `fk_package_booking` (`package_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_booking`
--
ALTER TABLE `customer_booking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_info`
--
ALTER TABLE `customer_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `equipments`
--
ALTER TABLE `equipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_items`
--
ALTER TABLE `package_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_package` (`package_id`),
  ADD KEY `fk_equipment` (`equipment_id`);

--
-- Indexes for table `staff_info`
--
ALTER TABLE `staff_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `booking_items`
--
ALTER TABLE `booking_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `customer_booking`
--
ALTER TABLE `customer_booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `customer_info`
--
ALTER TABLE `customer_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `equipments`
--
ALTER TABLE `equipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `staff_info`
--
ALTER TABLE `staff_info`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD CONSTRAINT `fk_booking` FOREIGN KEY (`booking_id`) REFERENCES `customer_booking` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_equipment_booking` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_package_booking` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `equipments`
--
ALTER TABLE `equipments`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_items`
--
ALTER TABLE `package_items`
  ADD CONSTRAINT `fk_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;