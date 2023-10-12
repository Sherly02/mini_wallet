-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 12, 2023 at 06:21 PM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mini_wallet`
--

-- --------------------------------------------------------

--
-- Table structure for table `mt_deposit`
--

CREATE TABLE `mt_deposit` (
  `id` varchar(36) NOT NULL,
  `amount` int(11) NOT NULL,
  `reference_id` varchar(36) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `deposited_by` varchar(36) NOT NULL,
  `deposited_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `mt_deposit`
--

INSERT INTO `mt_deposit` (`id`, `amount`, `reference_id`, `status`, `deposited_by`, `deposited_at`) VALUES
('90564c74-567e-4c3b-ab0d-ae6022714f04', 300, '970fe886-9dae-48cf-862d-514db4a7e6c9', 1, '6bbf2c91-3aba-4263-96ad-655ed05af487', '2023-10-12 00:49:44'),
('b9bae804-3db2-4b84-84ae-f5649d4d0454', 10000, '3a033177-8d2f-4de5-b9ff-ce085928cf69', 1, '6bbf2c91-3aba-4263-96ad-655ed05af487', '2023-10-11 23:42:24'),
('cd3633d6-7e0f-477d-becb-141d4c990a41', 5000, '683ad0e2-cce6-4487-a0ed-ac2ef6fe2d22', 1, '6bbf2c91-3aba-4263-96ad-655ed05af487', '2023-10-12 00:28:30');

-- --------------------------------------------------------

--
-- Table structure for table `mt_withdraw`
--

CREATE TABLE `mt_withdraw` (
  `id` varchar(36) NOT NULL,
  `amount` int(11) NOT NULL,
  `reference_id` varchar(36) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `withdrawn_by` varchar(36) NOT NULL,
  `withdrawn_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `mt_withdraw`
--

INSERT INTO `mt_withdraw` (`id`, `amount`, `reference_id`, `status`, `withdrawn_by`, `withdrawn_at`) VALUES
('b26fa799-df92-4599-8e2d-7b1316ff29d6', 5000, '27225e03-ec28-4b54-b690-d6d0c5f7f537', 1, '6bbf2c91-3aba-4263-96ad-655ed05af487', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `m_account`
--

CREATE TABLE `m_account` (
  `id` varchar(36) NOT NULL,
  `owned_by` varchar(36) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `enabled_at` datetime DEFAULT NULL,
  `disabled_at` datetime DEFAULT NULL,
  `balance` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `m_account`
--

INSERT INTO `m_account` (`id`, `owned_by`, `status`, `enabled_at`, `disabled_at`, `balance`) VALUES
('2407cdf8-7bf9-4f99-b9af-f7efe1c6bb3c', 'ea0212d3-abd6-406f-8c67-868e814a2436', 0, NULL, '2023-10-11 17:57:00', 0),
('6bbf2c91-3aba-4263-96ad-655ed05af487', '64fcf1a1-629c-4f16-9237-976313a12f4c', 1, '2023-10-10 05:26:02', NULL, 10300);

-- --------------------------------------------------------

--
-- Table structure for table `trx_wallet`
--

CREATE TABLE `trx_wallet` (
  `transaction_id` varchar(36) NOT NULL,
  `type` enum('withdrawal','deposit') NOT NULL,
  `amount` int(11) NOT NULL,
  `action_by` varchar(36) NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `reference_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `trx_wallet`
--

INSERT INTO `trx_wallet` (`transaction_id`, `type`, `amount`, `action_by`, `transaction_date`, `reference_id`) VALUES
('3753b9d5-2476-48c4-9578-deecd8cd80a9', 'deposit', 300, '64fcf1a1-629c-4f16-9237-976313a12f4c', '2023-10-12 00:49:44', '970fe886-9dae-48cf-862d-514db4a7e6c9'),
('5edcfe43-2aeb-4b7e-bc3f-260517f82f73', 'withdrawal', 5000, '64fcf1a1-629c-4f16-9237-976313a12f4c', '2023-10-12 00:33:25', '27225e03-ec28-4b54-b690-d6d0c5f7f537'),
('99526ea5-7680-47ef-9565-5a0f89c8294a', 'deposit', 5000, '64fcf1a1-629c-4f16-9237-976313a12f4c', '2023-10-12 00:28:30', '683ad0e2-cce6-4487-a0ed-ac2ef6fe2d22'),
('fe2c0797-ae36-4ac2-86ab-9c301846b9ac', 'deposit', 10000, '64fcf1a1-629c-4f16-9237-976313a12f4c', '2023-10-11 23:42:24', '3a033177-8d2f-4de5-b9ff-ce085928cf69');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mt_deposit`
--
ALTER TABLE `mt_deposit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mt_withdraw`
--
ALTER TABLE `mt_withdraw`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_id` (`reference_id`);

--
-- Indexes for table `m_account`
--
ALTER TABLE `m_account`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `trx_wallet`
--
ALTER TABLE `trx_wallet`
  ADD PRIMARY KEY (`transaction_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
