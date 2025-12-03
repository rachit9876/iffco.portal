-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 04:04 PM
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
-- Database: `iffco`
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

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `user_id`, `certificate_path`, `qr_code_path`, `issue_date`) VALUES
(20, 28, '../user/uploads/25CS1/certificate.html', NULL, '2025-12-02');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `is_active`) VALUES
(1, 'Chemical', 'CHE', 1),
(2, 'Mechanical', 'ME', 1),
(3, 'Electrical', 'EE', 1),
(4, 'Instrumentation', 'INST', 1),
(5, 'CS', 'CS', 1),
(6, 'IT', 'IT', 1),
(7, 'Civil', 'CE', 1),
(8, 'Finance', 'FIN', 1),
(9, 'MBA', 'MBA', 1),
(10, 'HR', 'HR', 1),
(11, 'Other', 'OTR', 1);

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `is_active`) VALUES
(1, 'ITI', 1),
(2, 'Diploma', 1),
(3, 'B.Sc.', 1),
(4, 'M.Sc.', 1),
(5, 'B.Tech.', 1),
(6, 'M.Tech.', 1),
(7, 'BBA', 1),
(8, 'MBA', 1),
(9, 'BA', 1),
(10, 'MA', 1),
(11, 'PhD', 1),
(12, 'Other', 1);

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
  `department_id` int(11) DEFAULT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `college` varchar(255) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `noc_path` varchar(255) DEFAULT NULL,
  `referral_type` varchar(50) DEFAULT NULL,
  `referral_path` varchar(255) DEFAULT NULL,
  `github_id` varchar(255) DEFAULT NULL,
  `github_email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `roll_no`, `department_id`, `batch`, `contact_info`, `role`, `status`, `created_at`, `college`, `program_id`, `semester`, `duration`, `noc_path`, `referral_type`, `referral_path`, `github_id`, `github_email`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$i8qZYMQpnCArs9SgudeOp..Svvhx2c2N5kek.hAo8mywmfaO4gcg6', NULL, NULL, NULL, NULL, 'admin', 'approved', '2025-06-22 06:18:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 'Rachit Pandey', 'student@example.com', '$2y$10$GzbuYhtSdyllRai1fInZ0uHTkhGqTMJiy/H9ajnAMOolTFHW2Sr32', '25CS1', 5, '2025', '5556668881', 'user', 'approved', '2025-12-02 03:19:36', 'KIIT', 5, 'VIII', '1 Month', 'uploads/25CS1/noc.pdf', 'Employee', 'uploads/25CS1/referral.pdf', NULL, NULL);

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
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `github_id` (`github_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
