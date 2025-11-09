-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 05, 2025 at 09:11 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `depot`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `created_at`) VALUES
(1, 'admin@admin.com', 'admin@admin.com', '2025-11-03 14:25:22');

-- --------------------------------------------------------

--
-- Table structure for table `depot`
--

CREATE TABLE `depot` (
  `id` int NOT NULL,
  `nama_depot` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `depot`
--

INSERT INTO `depot` (`id`, `nama_depot`, `alamat`, `telepon`, `created_at`) VALUES
(1, 'Depot Kita A', 'adasasa', NULL, '2025-11-04 12:57:34');

-- --------------------------------------------------------

--
-- Table structure for table `galon`
--

CREATE TABLE `galon` (
  `id` int UNSIGNED NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `harga` int NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `stok` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `galon`
--

INSERT INTO `galon` (`id`, `nama`, `deskripsi`, `harga`, `foto`, `created_at`, `stok`) VALUES
(1, 'Galon 19L', 'aaaaaaaaaaa', 200000, '1762274031_download (6).jpg', '2025-11-04 16:33:51', 200000),
(2, 'Galon 10L', 'mmmmmmmmmmm', 15000, '1762274360_OIP (5).jpg', '2025-11-04 16:39:20', 99999),
(4, 'sadad', 'sada', 7777777, '1762275917_OIP (6).jpg', '2025-11-04 17:05:17', 77777);

-- --------------------------------------------------------

--
-- Table structure for table `metode_pembayaran`
--

CREATE TABLE `metode_pembayaran` (
  `id` int NOT NULL,
  `nama_metode` varchar(100) NOT NULL,
  `nomor_rekening` varchar(100) DEFAULT NULL,
  `atas_nama` varchar(100) DEFAULT NULL,
  `jenis` enum('transfer','cod') DEFAULT 'transfer',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tipe` varchar(20) DEFAULT 'transfer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `metode_pembayaran`
--

INSERT INTO `metode_pembayaran` (`id`, `nama_metode`, `nomor_rekening`, `atas_nama`, `jenis`, `created_at`, `tipe`) VALUES
(1, 'Dana', '081267348623', 'Dimas Anjay Mabar', 'transfer', '2025-11-04 12:33:16', 'transfer'),
(2, 'Seabank', '9999999999999', 'Dimas Anjay Mabar', 'transfer', '2025-11-04 13:06:08', 'transfer'),
(3, 'COD', '', '', 'transfer', '2025-11-04 17:53:49', 'cod');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `order_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `recipient_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `latitude` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `longitude` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `total` bigint NOT NULL,
  `status` enum('menunggu_pembayaran','menunggu_verifikasi','diproses','sedang_dikirim','selesai','dibatalkan') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'menunggu_pembayaran',
  `payment_method_id` int DEFAULT NULL,
  `payment_proof` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_code`, `quantity`, `recipient_name`, `phone`, `address`, `latitude`, `longitude`, `notes`, `total`, `status`, `payment_method_id`, `payment_proof`, `created_at`, `updated_at`, `payment_date`) VALUES
(32, 3, 'ORD1762285831', 1, 'asa', 'aa', 'aa', '-0.917504', '100.3749376', 'Produk: sadad (ID:4) - a', 7777777, 'diproses', 2, 'bukti_32_1762286328.jpeg', '2025-11-04 19:50:31', '2025-11-04 19:58:48', NULL),
(33, 3, 'ORD1762286384', 10, 'sayang', '081365905047', 'jambi', '-0.917504', '100.3749376', 'Produk: Galon 19L (ID:1) - ', 2000000, 'selesai', 1, 'bukti_33_1762286392.jpg', '2025-11-04 19:59:44', '2025-11-04 19:59:52', NULL),
(34, 3, 'ORD1762332247', 36, 'uudn', '081365905047', '', '-0.9396179533702272', '100.38993861902783', 'Produk: Galon 19L (ID:1) - ', 7200000, 'diproses', 3, NULL, '2025-11-05 08:44:07', '2025-11-05 08:44:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `email`, `password`, `created_at`, `address`) VALUES
(3, 'User Ganteng', '081365905047', 'user@example.com', 'user@example.com', '2025-11-03 14:34:28', 'padang'),
(4, 'udin', '81365905047', 'udin@mail.com', '$2y$10$20cssr49JcpIJ.sMHw15se0dh8f/fNdQREoSkk8GHy2dk9vD5jQKi', '2025-11-05 09:03:35', 'udin@mail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`);

--
-- Indexes for table `depot`
--
ALTER TABLE `depot`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `galon`
--
ALTER TABLE `galon`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `depot`
--
ALTER TABLE `depot`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `galon`
--
ALTER TABLE `galon`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
