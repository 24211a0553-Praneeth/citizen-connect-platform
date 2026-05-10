-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2026 at 10:30 AM
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
-- Database: `citizen_connect`
--

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `location` varchar(255) DEFAULT NULL,
  `priority` varchar(20) DEFAULT NULL,
  `proof` varchar(255) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `geo_lat` varchar(30) DEFAULT NULL,
  `geo_lng` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `title`, `description`, `department`, `image`, `status`, `location`, `priority`, `proof`, `proof_image`, `contact_phone`, `contact_email`, `username`, `geo_lat`, `geo_lng`) VALUES
(1, 'Power Issue', 'No electricity', 'electricity', '', 'In Progress', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'road issue', 'roads are un even', 'Roads', '', 'Pending', 'chakeriyal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Elcetricity issue', 'i am facing current shock', 'Electricity', 'electricity.jpeg', 'Pending', 'chakeriyal', 'Normal', NULL, NULL, '6302280782', 'battusunny82@gmail.com', 'battusunny82@gmail.com', NULL, NULL),
(4, 'Electricity issue', 'i have got the cuurent shock', 'Electricity', 'complaint_1775294809_69d0d9597b5ce.jpeg', 'Pending', 'chakeriyal', 'Normal', NULL, NULL, '6302280782', 'battusunny82@gmail.com', 'battusunny82@gmail.com', '', ''),
(5, 'Road issue', 'we are facing road problrms', 'Roads', 'complaint_1775992055_69db7cf76f9d0.jpg', 'Pending', 'NH161AA, Tuljarampet, Narsapur mandal, Hathnoora mandal, Medak, Telangana, India', 'Normal', NULL, NULL, '6302280782', 'battusunny82@gmail.com', 'battusunny82@gmail.com', '17.72489', '78.25395'),
(6, 'Roads issue', 'path holes', 'Roads', '', 'Pending', 'chakeriyal', 'High', NULL, NULL, '6302280782', '24211a0553@bvrit.ac.in', 'battusunny82@gmail.com', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `complaint_id` int(11) DEFAULT NULL,
  `rating` int(1) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `department` varchar(100) DEFAULT 'all',
  `target_user` varchar(100) DEFAULT NULL,
  `sent_by` varchar(100) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `message`, `department`, `target_user`, `sent_by`, `created_at`, `is_read`) VALUES
(1, 'Elcetricity issue came can be solved early', 'We arw not that much pf sufficient workers', 'electricity', NULL, 'admin', '2026-03-31 13:25:28', 0),
(2, 'Elcetricity issue came can be solved early', 'We arw not that much pf sufficient workers', 'electricity', NULL, 'admin', '2026-03-31 13:26:10', 0),
(3, 'Test Notification', 'This is a test notification from Citizen Connect.', 'all', NULL, 'system', '2026-03-31 16:14:01', 0),
(4, 'Ticket Status Update | ID: #3', 'Hello battusunny82@gmail.com,The status of your complaint #0003 has been updated.Title: Elcetricity issueNew Status: PENDINGOur team is currently addressing the reported issue.Thank you for using Citizen Connect.', 'all', NULL, 'system', '2026-03-31 17:40:48', 0),
(5, 'Password Reset OTP', 'Your Citizen Connect password reset OTP is: 820975. Do not share this with anyone.', 'all', '24211a0553@bvrit.ac.in', 'system', '2026-04-04 08:42:46', 0),
(6, 'Complaint Logged Successfully | ID: #4', 'Hello battusunny82@gmail.com,Your complaint has been successfully received by the Electricity department.Tracking ID: #4Title: Electricity issueStatus: PendingYou can track its progress on your dashboard.', 'Electricity', 'battusunny82@gmail.com', 'system', '2026-04-04 09:26:49', 0),
(7, 'Complaint Logged Successfully | ID: #5', 'Hello battusunny82@gmail.com,Your complaint has been successfully received by the Roads department.Tracking ID: #5Title: Road issueStatus: PendingYou can track its progress on your dashboard.', 'Roads', 'battusunny82@gmail.com', 'system', '2026-04-12 11:07:35', 0),
(8, 'Complaint Logged Successfully | ID: #6', 'Hello battusunny82@gmail.com,Your complaint has been successfully received by the Roads department.Tracking ID: #6Title: Roads issueStatus: PendingYou can track its progress on your dashboard.', 'Roads', 'battusunny82@gmail.com', 'system', '2026-04-12 18:44:55', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `department`, `email`, `phone`) VALUES
(1, 'admin', 'admin', 'admin', NULL, NULL, NULL),
(2, 'user', 'user', 'user', NULL, NULL, NULL),
(3, 'electricity', '1234', 'department', 'electricity', NULL, NULL),
(4, 'Sunny', '4912', 'user', NULL, NULL, NULL),
(5, 'Roads', '1234', 'department', 'Roads', NULL, NULL),
(6, 'Arjun', '1234', 'user', NULL, NULL, NULL),
(7, 'battusunny82@gmail.com', 'Sunny@4912', 'user', NULL, NULL, NULL),
(8, '24211a0553@bvrit.ac.in', 'Sunny@49212', 'user', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
