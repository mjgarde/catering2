-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 13, 2026 at 01:01 AM
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
(17, 15, 31, NULL, 1, 10.00),
(18, 16, 31, NULL, 1, 10.00),
(19, 17, 31, NULL, 2, 10.00),
(20, 18, NULL, 8, 1, 2200.00),
(21, 19, 34, NULL, 1, 12.00),
(22, 20, 31, NULL, 1, 10.00),
(23, 21, NULL, 6, 1, 2000.00),
(24, 22, 31, NULL, 1, 10.00),
(25, 23, 31, NULL, 1, 10.00),
(26, 24, 31, NULL, 1, 10.00),
(27, 25, 31, NULL, 1, 10.00),
(28, 26, 30, NULL, 2, 20.00),
(29, 26, 31, NULL, 2, 10.00),
(30, 26, 34, NULL, 1, 12.00),
(31, 26, 33, NULL, 2, 21.00),
(32, 26, 29, NULL, 5, 50.00),
(33, 26, 32, NULL, 3, 40.00),
(34, 27, 30, NULL, 1, 20.00),
(35, 28, 31, NULL, 1, 10.00),
(36, 29, NULL, 8, 1, 2200.00),
(37, 29, NULL, 3, 1, 2500.00);

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
(66, 'Event and Decor'),
(67, 'Rental Accessories');

-- --------------------------------------------------------

--
-- Table structure for table `customer_booking`
--

CREATE TABLE `customer_booking` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
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

INSERT INTO `customer_booking` (`id`, `customer_id`, `customer_name`, `email`, `phone`, `address`, `borrow_date`, `return_date`, `total_amount`, `status`, `created_at`, `actual_return_date`, `fine_amount`, `damage_fee`, `damage_notes`, `damaged_items`, `sms_reminder_sent`) VALUES
(14, 1, 'Juan Delacruz', 'm@m.c', '09099292922', 'Banga, South Cotabato', '2026-02-15 22:22:00', '2027-02-15 03:03:00', 20.00, 'Returned', '2026-08-20 00:34:06', '2026-08-20 02:52:37', 0.00, 0.00, '', NULL, 0),
(15, 2, 'mick', 'd@g.c', '09090902109', 'Banga, South Cotabato', '2005-02-15 11:11:00', '2027-02-15 22:22:00', 10.00, 'Returned', '2026-08-20 00:55:03', '2026-08-20 02:56:11', 0.00, 0.00, '', NULL, 0),
(16, 3, 'Tan Tan', 'tantan@gmail.com', '09090909999', 'Banga, South Cotabato', '2029-02-15 22:02:00', '2030-02-01 11:01:00', 10.00, 'Returned', '2026-09-01 05:31:47', '2026-09-01 07:49:04', 0.00, 0.00, '', NULL, 0),
(17, 3, 'Tan Tan', 'g@h.c', '09090909999', 'Banga, South Cotabato', '2026-09-24 11:01:00', '2026-09-25 11:01:00', 20.00, 'Returned', '2026-09-01 05:48:38', '2026-09-01 08:12:44', 0.00, 0.00, '', NULL, 0),
(18, NULL, 'Tan Tan', 'tan@g.c', '09090909999', 'Banga, South Cotabato', '2026-09-02 11:01:00', '2026-09-04 11:01:00', 2200.00, 'Returned', '2026-09-01 06:06:22', '2026-09-01 08:12:50', 0.00, 0.00, '', NULL, 0),
(19, NULL, 'Tan Tan', 'tan@g.c', '09090909999', 'Banga, South Cotabato', '2027-02-15 11:11:00', '2028-02-15 11:11:00', 12.00, 'Returned', '2026-09-01 06:08:04', '2026-09-01 08:12:53', 0.00, 0.00, '', NULL, 0),
(20, 3, 'Tan Tan', 'j@v.c', '09090909999', 'Banga, South Cotabato', '2026-09-01 09:09:00', '2026-09-08 00:00:00', 10.00, 'Borrowed', '2026-09-01 06:13:26', NULL, 53500.00, 0.00, NULL, NULL, 0),
(21, 3, 'Tan Tan', 's@v.c', '09090909999', 'Banga, South Cotabato', '2026-09-08 11:01:00', '2026-09-10 11:11:00', 2000.00, 'Borrowed', '2026-09-01 06:14:40', NULL, 47600.00, 0.00, NULL, NULL, 0),
(22, 3, 'Tan Tan', 'tantan@gmail.com', '09090909999', 'Banga, South Cotabato', '2026-09-08 11:11:00', '2026-09-09 11:11:00', 10.00, 'Borrowed', '2026-09-03 02:02:41', NULL, 50000.00, 0.00, NULL, NULL, 0),
(23, 3, 'Tan Tan', 'h@v.c', '09090909999', 'Banga, South Cotabato', '2026-09-23 00:00:00', '2026-09-24 08:08:00', 10.00, 'Returned', '2026-09-03 02:33:49', '2026-09-03 04:37:58', 0.00, 200.00, '', '[{\"equipment_id\":31,\"equipment_name\":\"Plates\",\"quantity\":1}]', 0),
(24, 3, 'Tan Tan', 'o@h.c', '09090909999', 'Banga, South Cotabato', '2026-09-29 09:09:00', '2026-09-29 11:11:00', 10.00, 'Returned', '2026-09-03 02:36:25', '2026-09-03 04:37:21', 0.00, 0.00, '', NULL, 0),
(25, 3, 'Tan Tan', '', '09090909999', 'Banga, South Cotabato', '2026-09-03 11:11:00', '2026-09-03 17:00:00', 10.00, 'Borrowed', '2026-09-03 02:50:24', NULL, 63800.00, 0.00, NULL, NULL, 0),
(26, 3, 'Tan Tan', 'tantan@gmail.com', '09090909999', 'Banga, South Cotabato', '2026-09-21 10:00:00', '2026-09-23 10:00:00', 484.00, 'Returned', '2026-09-21 07:41:51', '2026-09-21 09:50:37', 0.00, 10.00, '', NULL, 0),
(27, 3, 'Tan Tan', '', '09090909999', 'Banga, South Cotabato', '2026-09-22 11:11:00', '2026-09-22 12:00:00', 20.00, 'Borrowed', '2026-09-21 08:02:31', NULL, 18700.00, 0.00, NULL, NULL, 0),
(28, 3, 'Tan Tan', 's@g.c', '09090909999', 'Banga, South Cotabato', '2026-01-16 01:01:00', '2026-09-23 01:01:00', 10.00, 'Pending', '2026-09-21 12:21:40', NULL, 0.00, 0.00, NULL, NULL, 0),
(29, 3, 'Tan Tan', 'mislang@gmail.com', '09090909999', 'Banga, South Cotabato', '2026-10-06 10:00:00', '2026-10-08 10:00:00', 4700.00, 'Pending', '2026-10-05 07:47:25', NULL, 0.00, 0.00, NULL, NULL, 0);

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
(2, 'mick', 'Banga, South Cotabato', 'Male', '2005-02-15', '09090902109', 'admin', '$2y$10$Vstgs8AnHk4pUeF.YS2c1uOGsNonlGTTIEtoxiSGLceS2JMFAwaEC', '2026-08-20 00:54:08'),
(3, 'Tan Tan', 'Banga, South Cotabato', 'Female', '2009-02-15', '09090909999', 'tantan', '$2y$10$7ZZH/UiDzpcnHUS1Dl..P.urLAbvmRurcFwRnqkSnixDg5xaBH9FC', '2026-09-01 05:30:40');

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
(29, 'Tables', '1761357204_Tate60inRndDiningTbl3QSSF23_3D_512x512.webp', 64, 50.00, 19, 16, '2025-10-25 01:53:24', '2026-09-21 07:50:37'),
(30, 'Plastic Chairs', '1761357361_RUBY1-APPLE-GREEN-FRONT-with-sticker-min-600x696.webp', 64, 20.00, 260, 241, '2025-10-25 01:56:01', '2026-09-27 21:47:12'),
(31, 'Desert Plates', '1761357436_1758710394_plates.jpg', 62, 10.00, 200, 161, '2025-10-25 01:57:16', '2026-09-27 21:24:04'),
(32, 'Sofas', '1761357491_1758712985_Sofas and Couches.png', 64, 40.00, 24, 5, '2025-10-25 01:58:11', '2026-09-21 07:50:37'),
(33, 'Wine Glass', '1761357556_1758712636_wine_glass.png', 62, 21.00, 34, 32, '2025-10-25 01:59:16', '2026-09-21 07:50:37'),
(34, 'Water Glass', '1761357577_1759635915_WATER GLASS.jpg', 62, 12.00, 63, 48, '2025-10-25 01:59:37', '2026-09-21 07:50:37'),
(36, 'Cocktail Glass', '1790514198_cock.jpeg', 62, 20.00, 24, 24, '2026-09-27 13:01:12', '2026-09-27 13:03:18'),
(37, 'Demitasse Glass', '1790514313_Bone-China-Royal-Albert-Night-Day.png', 62, 20.00, 21, 21, '2026-09-27 13:05:13', '2026-09-27 13:05:13'),
(38, 'Dinner Plte', '1790514414_images.jpeg', 62, 31.00, 180, 180, '2026-09-27 13:06:54', '2026-09-27 13:06:54'),
(39, 'Silver Spoon & Folk', '1790514745_images.jpeg', 62, 15.00, 109, 109, '2026-09-27 13:12:25', '2026-09-27 13:12:25'),
(40, 'Golden Spoon & Folk', '1790514865_images.jpeg', 62, 21.00, 50, 50, '2026-09-27 13:14:25', '2026-09-27 13:14:25'),
(41, 'Electric Deep Fryer', '1790515070_3521a6da0ab1946d3956d79842cf8170.jpg', 65, 400.00, 13, 13, '2026-09-27 13:17:50', '2026-09-27 13:17:50'),
(42, 'Double Tank Juice Dispenser', '1790515358_images.jpeg', 63, 540.00, 5, 5, '2026-09-27 13:22:38', '2026-09-27 13:22:38'),
(43, 'Food Warmer', '1790515431_images.jpeg', 65, 500.00, 21, 21, '2026-09-27 13:23:51', '2026-09-27 13:23:51'),
(44, 'Oven', '1790515560_images.jpeg', 65, 300.00, 10, 10, '2026-09-27 13:26:00', '2026-09-27 13:26:00'),
(45, 'Wedding Arch Stand', '1790515998_7-2ft-6-6ft-primehome-arbors-hd-vank-jjar-8-c3_600.png', 66, 400.00, 23, 23, '2026-09-27 13:33:18', '2026-09-27 13:33:18'),
(46, 'Event Tent', '1790516113_realistic-outdoor-tent_1284-21362.png', 66, 500.00, 6, 6, '2026-09-27 13:35:13', '2026-09-27 13:35:13'),
(47, 'Table Centerpiece Stand', '1790544363_IMG_7149-1000x1000.JPG', 66, 200.00, 5, 5, '2026-09-27 21:26:03', '2026-09-27 21:26:03'),
(48, 'Cake Stand', '1790544464_images.jpeg', 66, 250.00, 23, 23, '2026-09-27 21:27:44', '2026-09-27 21:27:44'),
(49, 'Flower Stand', '1790545066_cfst2309a_1200.jpg', 66, 300.00, 50, 50, '2026-09-27 21:37:46', '2026-09-27 21:37:46'),
(50, 'Red Carpet', '1790545736_s-l1200.jpg', 66, 500.00, 20, 20, '2026-09-27 21:48:56', '2026-09-27 21:48:56'),
(51, 'Banquet Table', '1790545874_images.jpeg', 64, 150.00, 40, 40, '2026-09-27 21:51:14', '2026-09-27 21:51:14'),
(52, 'Folding Table', '1790545959_images.jpeg', 64, 200.00, 20, 20, '2026-09-27 21:52:39', '2026-09-27 21:52:39'),
(53, 'Bar Stool', '1790546057_images.jpeg', 64, 150.00, 200, 200, '2026-09-27 21:54:17', '2026-09-27 21:54:17');

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
(2, 'Weeding Package', 3000.00, '2026-03-16 02:46:19'),
(3, 'Birthday Package', 2500.00, '2026-09-01 05:21:07'),
(4, 'Debut Package', 4000.00, '2026-09-01 05:21:07'),
(5, 'Corporate Event Package', 3500.00, '2026-09-01 05:21:07'),
(6, 'Christening/Baptismal Package', 2000.00, '2026-09-01 05:21:07'),
(7, 'Anniversary Package', 3000.00, '2026-09-01 05:21:07'),
(8, 'Graduation Package', 2200.00, '2026-09-01 05:21:07');

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
(10, 2, 31, 11),
(11, 3, 29, 5),
(12, 3, 30, 20),
(13, 3, 31, 20),
(14, 3, 34, 20),
(15, 4, 29, 8),
(16, 4, 30, 50),
(17, 4, 31, 50),
(18, 4, 32, 2),
(19, 4, 33, 50),
(20, 4, 34, 50),
(21, 5, 29, 10),
(22, 5, 30, 40),
(23, 5, 31, 40),
(24, 5, 34, 40),
(25, 6, 29, 4),
(26, 6, 30, 15),
(27, 6, 31, 15),
(28, 6, 34, 15),
(29, 7, 29, 6),
(30, 7, 30, 25),
(31, 7, 31, 25),
(32, 7, 32, 1),
(33, 7, 33, 25),
(34, 7, 34, 25),
(35, 8, 29, 5),
(36, 8, 30, 20),
(37, 8, 31, 20),
(38, 8, 34, 20);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_customer` (`customer_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `customer_booking`
--
ALTER TABLE `customer_booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `customer_info`
--
ALTER TABLE `customer_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `equipments`
--
ALTER TABLE `equipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

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
-- Constraints for table `customer_booking`
--
ALTER TABLE `customer_booking`
  ADD CONSTRAINT `fk_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_info` (`id`) ON DELETE SET NULL;

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