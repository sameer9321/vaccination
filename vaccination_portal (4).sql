-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 02:08 PM
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
-- Database: `vaccination_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `child_id` int(11) DEFAULT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `vaccine_name` varchar(100) DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `child_id`, `hospital_id`, `vaccine_name`, `booking_date`, `status`, `created_at`) VALUES
(4, 6, 8, 'polio', '2026-02-03', 'Completed', '2026-02-03 12:39:43'),
(6, 9, 10, 'polio', '2026-02-04', 'Pending', '2026-02-03 13:50:48'),
(7, 7, 11, 'polio', '2026-02-05', 'Pending', '2026-02-04 15:26:17'),
(8, 5, 11, 'polio', '2026-02-05', 'Pending', '2026-02-04 15:27:01'),
(9, 10, 13, 'polio', '2026-02-09', 'Pending', '2026-02-09 11:30:43'),
(10, 6, 13, 'polio', '2026-02-10', 'Pending', '2026-02-09 11:34:45'),
(11, 11, 10, 'polio', '2026-02-10', 'Pending', '2026-02-10 12:02:37'),
(12, 11, 16, 'polio', '2026-02-10', 'Pending', '2026-02-10 12:04:54'),
(14, 12, 17, 'polio', '2026-02-10', 'Pending', '2026-02-10 12:20:20'),
(15, 13, 17, 'polio', '2026-02-11', 'Pending', '2026-02-10 12:26:28');

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

CREATE TABLE `children` (
  `child_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `child_name` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `vaccination_status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`child_id`, `parent_id`, `child_name`, `birth_date`, `vaccination_status`) VALUES
(4, 1, 'sameer', '2008-03-11', 'Pending'),
(5, 1, 'Shayan', '2008-03-03', 'Up to date'),
(6, 1, 'Aaliyan', '2010-05-15', 'Completed'),
(7, 1, 'Huzaifa', '2006-05-05', 'Up to date'),
(9, 1, 'Fazal', '2003-05-05', 'Completed'),
(10, 1, 'Maaz', '2006-02-18', 'Pending'),
(11, 2, 'Shayan', '2007-03-03', 'Pending'),
(12, 2, 'Aaliyan', '2008-02-03', 'Pending'),
(13, 3, 'Aaliyan', '2006-02-10', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `hospitals`
--

CREATE TABLE `hospitals` (
  `id` int(11) NOT NULL,
  `hospital_name` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospitals`
--

INSERT INTO `hospitals` (`id`, `hospital_name`, `address`, `phone`, `email`) VALUES
(16, 'Jinnah Hospital', '', '', 'jinnah@gmail.com'),
(17, 'Index Hospital', '', '', 'index@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `hospital_bookings`
--

CREATE TABLE `hospital_bookings` (
  `booking_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `hospital_name` varchar(150) NOT NULL,
  `vaccine_date` date NOT NULL,
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hospital_requests`
--

CREATE TABLE `hospital_requests` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `requested_hospital` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital_requests`
--

INSERT INTO `hospital_requests` (`id`, `parent_id`, `child_id`, `hospital_id`, `requested_hospital`, `status`, `created_at`) VALUES
(1, 1, 4, NULL, 'Aptech Mental Hospital', 'Approved', '2026-02-02 14:21:23'),
(2, 1, 6, NULL, 'City hospital', 'Approved', '2026-02-03 12:49:21'),
(3, 1, 9, NULL, 'City hospital', 'Approved', '2026-02-03 13:52:03'),
(4, 1, 6, NULL, 'City hospital', 'Approved', '2026-02-03 17:16:15'),
(5, 2, 11, NULL, 'City hospital', 'Pending', '2026-02-10 12:02:47'),
(6, 3, 13, NULL, 'Index Hospital', 'Pending', '2026-02-10 12:26:52');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `parent_id` int(11) NOT NULL,
  `parent_name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`parent_id`, `parent_name`, `address`, `phone`, `email`, `password`) VALUES
(1, 'Ahmed', NULL, NULL, 'sameerqadeer167@gmail.com', ''),
(2, 'Ahmed', NULL, NULL, 'ahmed@gmail.com', ''),
(3, 'Huzaifa', NULL, NULL, 'huzaifa@gmail.com', '');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `vaccine_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `parent_name`, `message`, `status`, `vaccine_id`) VALUES
(1, 'Sameer', 'Need vaccine appointment', 'approved', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','parent','hospital') NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'user.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `profile_pic`) VALUES
(17, 'Ahmed', 'ahmed@gmail.com', '$2y$10$wNlC2Aj2DUjUShl3fHzMM.pbTpe4yVvVTX8na/XvWEKACgQDJDQAy', 'parent', 'user.png'),
(18, 'Jinnah Hospital', 'jinnah@gmail.com', '$2y$10$iSqXOP/vjSs5DGl7KEJT.OfoFUxihSbZTOEKViAxQDEK8k6JZaLbi', 'hospital', 'user.png'),
(19, 'admin', 'admin@gmail.com', '$2y$10$IDUfaoMys021.bJiVVO.h.XEgpYNOQMJYNZJLHNrv1i.b7n0xBx4S', 'admin', 'user.png'),
(20, 'Index Hospital', 'index@gmail.com', '$2y$10$BQSnzR0a0Axqo0zHcKRyluu7rJGuQg2Uw6.cmNW6iI74qmYjvN.LK', 'hospital', 'user.png'),
(21, 'Huzaifa', 'huzaifa@gmail.com', '$2y$10$U8HMD9w3w/ChKqUx7SjE9.NnX83ohYrCWqCgMPX53rdgopDnRbrRG', 'parent', 'user.png');

-- --------------------------------------------------------

--
-- Table structure for table `vaccinations`
--

CREATE TABLE `vaccinations` (
  `vaccination_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `date_taken` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE `vaccines` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccines`
--

INSERT INTO `vaccines` (`id`, `name`, `stock`) VALUES
(3, 'Aptech injection', 50),
(4, 'snakes candy', 20),
(5, 'polio injection', 50),
(7, 'Polio Vaccine', 100),
(8, 'MMR Vaccine', 50),
(9, 'Hepatitis B Vaccine', 80),
(10, 'developer injection', 50);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`child_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hospital_bookings`
--
ALTER TABLE `hospital_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `hospital_requests`
--
ALTER TABLE `hospital_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`parent_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vaccine_id` (`vaccine_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD PRIMARY KEY (`vaccination_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `child_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `hospitals`
--
ALTER TABLE `hospitals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `hospital_bookings`
--
ALTER TABLE `hospital_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hospital_requests`
--
ALTER TABLE `hospital_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `vaccinations`
--
ALTER TABLE `vaccinations`
  MODIFY `vaccination_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vaccines`
--
ALTER TABLE `vaccines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `children`
--
ALTER TABLE `children`
  ADD CONSTRAINT `children_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`parent_id`) ON DELETE CASCADE;

--
-- Constraints for table `hospital_bookings`
--
ALTER TABLE `hospital_bookings`
  ADD CONSTRAINT `hospital_bookings_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`child_id`) ON DELETE CASCADE;

--
-- Constraints for table `hospital_requests`
--
ALTER TABLE `hospital_requests`
  ADD CONSTRAINT `fk_req_child` FOREIGN KEY (`child_id`) REFERENCES `children` (`child_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_req_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`parent_id`) ON DELETE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_vaccine_id` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`);

--
-- Constraints for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD CONSTRAINT `vaccinations_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`child_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
