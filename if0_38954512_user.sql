-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql103.infinityfree.com
-- Generation Time: Jun 28, 2025 at 08:19 AM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38954512_user`
--

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `certificate_path` varchar(255) NOT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `issue_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `report_path` varchar(255) DEFAULT NULL,
  `status` enum('Not started','Completed') NOT NULL DEFAULT 'Not started',
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `user_id`, `project_name`, `file_path`, `report_path`, `status`, `submission_date`) VALUES
(3, 14, 'EMS', 'uploads/25CS1/project.zip', 'uploads/25CS1/report.pdf', 'Completed', '2025-06-26 06:11:01'),
(4, 23, 'EMS', 'uploads/25INST1/project.zip', 'uploads/25INST1/report.pdf', 'Completed', '2025-06-26 06:58:06'),
(5, 25, 'IFFCO E-Procurement Portal', 'uploads/25CS3/project.zip', 'uploads/25CS3/report.pdf', 'Completed', '2025-06-27 17:16:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `roll_no` varchar(50) DEFAULT NULL,
  `department` enum('Chemical','Mechanical','Electrical','Instrumentation','CS','IT','Civil','Finance','MBA','HR','Other') DEFAULT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `college` varchar(255) DEFAULT NULL,
  `program` varchar(50) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `noc_path` varchar(255) DEFAULT NULL,
  `referral_type` varchar(50) DEFAULT NULL,
  `referral_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `roll_no`, `department`, `batch`, `contact_info`, `role`, `status`, `created_at`, `college`, `program`, `semester`, `duration`, `noc_path`, `referral_type`, `referral_path`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$i8qZYMQpnCArs9SgudeOp..Svvhx2c2N5kek.hAo8mywmfaO4gcg6', NULL, NULL, NULL, NULL, 'admin', 'approved', '2025-06-22 06:18:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'Student User', 'student@example.com', '$2y$10$4qQsKUUG.nu9N48QEl6ze.FBp0nUJWdOESNiS3eX1qOl5afHdWlLK', '25CS1', 'CS', '2025', '90909090909', 'user', 'approved', '2025-06-24 14:42:35', 'AMITY', NULL, 'III', NULL, 'uploads/25CS1/noc_685ab95b92cc6_New Text Document.pdf', 'University', 'uploads/25CS1/ref_685ab95b92cca_New Text Document.pdf'),
(18, 'Sumit', 's@s.com', '$2y$10$5d1qjayXQpLa.KZU/K12CeJ4CpV2jjRbYnegjaWFmWCeDHb46gHCy', '25CS2', 'CS', '2025', 'N/A', 'user', 'approved', '2025-06-24 16:31:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'Rachit', 'r@r.com', '$2y$10$UjjM5/mQInHEIrYaGC78FeNWS6naqD/hC7p3UV/6pZH5Tc7rkA6uy', '25INST1', 'Instrumentation', '2025', '90909090909', 'user', 'approved', '2025-06-24 19:16:49', 'KIIT', 'B.Sc.', 'IX', '1 Month', 'uploads/25INST1/noc.pdf', 'Employee', 'uploads/25INST1/referral.pdf'),
(24, 'Surya Mani Pandey', 'smpandey@iffco.in', '$2y$10$05ZIgU16vItxwEhPNZV6Y.QT5ykOnkp0v9BDV3Jd6Xe6eIDoi8qy6', '25CHE1', 'Chemical', '2025', '90909090909', 'user', 'approved', '2025-06-24 19:18:43', 'AMITY', 'PhD', 'IX', '2 Month', 'uploads/25CHE1/noc.pdf', 'University', 'uploads/25CHE1/referral.pdf'),
(25, 'Rachit Pandey', 'rachit@gmail.com', '$2y$10$otT/6OxvxW24Utp.wlhbSeYM7DF9oESoCjejaq4JzUUvaCnkgW7QS', '25CS3', 'CS', '2025', '9616425263', 'user', 'approved', '2025-06-27 16:59:22', 'KIIT', 'B.Tech.', 'VIII', '2 Month', 'uploads/25CS3/noc.pdf', 'University', 'uploads/25CS3/referral.pdf');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `roll_no` (`roll_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
