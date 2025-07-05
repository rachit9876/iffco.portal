-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 05, 2025 at 08:10 PM
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
(6, 28, 'VC', 'uploads/25CHE1/project.zip', 'uploads/25CHE1/report.pdf', 'Completed', '2025-07-05 17:36:23');

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
  `referral_path` varchar(255) DEFAULT NULL,
  `toggle_status` enum('ON','OFF') NOT NULL DEFAULT 'OFF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `roll_no`, `department`, `batch`, `contact_info`, `role`, `status`, `created_at`, `college`, `program`, `semester`, `duration`, `noc_path`, `referral_type`, `referral_path`, `toggle_status`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$i8qZYMQpnCArs9SgudeOp..Svvhx2c2N5kek.hAo8mywmfaO4gcg6', NULL, NULL, NULL, NULL, 'admin', 'approved', '2025-06-22 06:18:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ON'),
(28, 'Student User', 'student@example.com', '$2y$10$PcvkNdVdkf1Mdl/lUICoLOFoQPVmEbbIZ6CL.li1BDJcEfSL.pXLa', '25CHE1', 'Chemical', '2025', '90909090909', 'user', 'approved', '2025-07-04 03:27:13', 'SRM', 'B.Sc.', 'I', '1 Month', 'uploads/25CHE1/noc.pdf', 'Organization', 'uploads/25CHE1/referral.pdf', 'OFF');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
