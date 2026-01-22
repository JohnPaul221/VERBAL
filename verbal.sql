-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 22, 2026 at 02:50 PM
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
-- Database: `verbal`
--

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `grade` varchar(20) NOT NULL,
  `section` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `fullname`, `username`, `grade`, `section`, `password`, `created_at`) VALUES
(9, 'Lorraine O. Gonowon', 'li li', '6', 'Sunflower', 'lili33454', '2025-10-14 05:50:25'),
(10, 'Allaine Luzy B. Borromeo', 'Luzy', '6', 'Sunflower', 'Luzy1123234', '2025-10-14 06:10:40'),
(11, 'James Ashraf M. Besada', 'Ashraf', '6', 'Sunflower', '123456789', '2025-10-14 06:23:51'),
(12, 'Rap David S. Coronel', 'Dave', '6', 'Sunflower', 'Dave112742342', '2025-10-14 06:34:54'),
(13, 'paul', 'jp', '1', 'Diamond', 'paul22323332', '2025-10-15 13:14:32');

-- --------------------------------------------------------

--
-- Table structure for table `student_progress`
--

CREATE TABLE `student_progress` (
  `student_id` int(11) NOT NULL,
  `difficulty` varchar(20) NOT NULL DEFAULT 'beginner',
  `word_index` int(11) NOT NULL DEFAULT 0,
  `words_attempted` int(11) NOT NULL DEFAULT 0,
  `words_correct` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_progress`
--

INSERT INTO `student_progress` (`student_id`, `difficulty`, `word_index`, `words_attempted`, `words_correct`, `last_updated`) VALUES
(9, 'intermediate', 0, 2, 0, '2025-10-14 06:03:10'),
(10, 'beginner', 2, 6, 2, '2025-10-15 15:15:06'),
(11, 'beginner', 1, 1, 1, '2025-10-25 23:08:37'),
(12, 'beginner', 5, 5, 5, '2025-10-14 06:41:11'),
(13, 'beginner', 1, 1, 1, '2025-10-15 14:11:45');

-- --------------------------------------------------------

--
-- Table structure for table `student_ratings`
--

CREATE TABLE `student_ratings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `word` varchar(100) NOT NULL,
  `score` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_ratings`
--

INSERT INTO `student_ratings` (`id`, `student_id`, `username`, `word`, `score`, `created_at`) VALUES
(109, 10, 'Luzy', 'ring', 5, '2025-10-15 15:13:57'),
(110, 10, 'Luzy', 'bus', 5, '2025-10-15 15:14:03'),
(111, 10, 'Luzy', 'door', 2, '2025-10-15 15:14:08'),
(112, 10, 'Luzy', 'door', 2, '2025-10-15 15:14:15'),
(113, 10, 'Luzy', 'door', 2, '2025-10-15 15:14:21'),
(114, 10, 'Luzy', 'door', 2, '2025-10-15 15:14:27'),
(115, 10, 'Luzy', 'new', 0, '2025-10-15 15:14:34'),
(116, 10, 'Luzy', 'door', 2, '2025-10-15 15:14:41'),
(117, 10, 'Luzy', 'hat', 2, '2025-10-15 15:14:48'),
(118, 10, 'Luzy', 'hat', 2, '2025-10-15 15:14:53'),
(119, 10, 'Luzy', 'hat', 2, '2025-10-15 15:14:58'),
(120, 10, 'Luzy', 'hat', 2, '2025-10-15 15:15:06'),
(121, 11, 'Ashraf', 'dog', 2, '2025-10-15 15:58:01'),
(122, 11, 'Ashraf', 'book', 0, '2025-10-15 15:59:32'),
(123, 11, 'Ashraf', 'hat', 2, '2025-10-15 16:00:01'),
(124, 11, 'Ashraf', 'low', 0, '2025-10-15 16:00:18'),
(125, 11, 'Ashraf', 'big', 5, '2025-10-25 23:08:37'),
(126, 11, 'Ashraf', 'ball', 1, '2025-10-26 02:22:10'),
(127, 11, 'Ashraf', 'car', 2, '2025-10-26 02:23:06'),
(129, 11, 'Ashraf', 'box', 2, '2025-10-26 04:35:44'),
(134, 11, 'Ashraf', 'cup', 5, '2025-10-26 05:39:50'),
(135, 11, 'Ashraf', 'tree', 2, '2025-10-26 05:44:22'),
(136, 11, 'Ashraf', 'tree', 2, '2025-10-26 05:44:36'),
(137, 11, 'Ashraf', 'eat', 0, '2025-10-26 05:44:43'),
(138, 11, 'Ashraf', 'run', 5, '2025-10-26 05:45:03'),
(139, 11, 'Ashraf', 'cup', 5, '2025-10-26 05:55:06'),
(140, 11, 'Ashraf', 'ball', 1, '2025-10-26 05:55:39'),
(141, 11, 'Ashraf', 'ball', 0, '2025-10-26 05:55:48'),
(142, 11, 'Ashraf', 'cry', 0, '2025-10-26 05:56:00'),
(143, 11, 'Ashraf', 'cry', 0, '2025-10-26 05:56:10'),
(144, 11, 'Ashraf', 'pencil', 0, '2025-10-26 05:56:57'),
(145, 11, 'Ashraf', 'pencil', 3, '2025-10-26 05:57:03'),
(146, 11, 'Ashraf', 'orange', 0, '2025-10-26 05:57:23'),
(147, 11, 'Ashraf', 'orange', 0, '2025-10-26 05:57:29'),
(148, 11, 'Ashraf', 'orange', 0, '2025-10-26 05:57:40'),
(149, 11, 'Ashraf', 'ball', 1, '2025-10-26 05:57:52'),
(150, 11, 'Ashraf', 'door', 0, '2025-10-26 05:58:02'),
(151, 11, 'Ashraf', 'pen', 2, '2025-10-26 08:09:41'),
(152, 11, 'Ashraf', 'pen', 2, '2025-10-26 08:11:40'),
(153, 11, 'Ashraf', 'pen', 0, '2025-10-26 08:11:51'),
(154, 11, 'Ashraf', 'hat', 2, '2025-10-26 08:18:36'),
(155, 11, 'Ashraf', 'ring', 5, '2025-10-26 08:18:51'),
(156, 11, 'Ashraf', 'ring', 5, '2025-10-28 12:01:43'),
(157, 11, 'Ashraf', 'bed', 2, '2025-10-28 12:06:56'),
(158, 11, 'Ashraf', 'pig', 5, '2025-10-28 12:07:07'),
(159, 11, 'Ashraf', 'leg', 5, '2025-10-28 12:07:32'),
(160, 11, 'Ashraf', 'cake', 0, '2025-10-28 12:07:42'),
(161, 11, 'Ashraf', 'cake', 0, '2025-10-28 12:08:01'),
(162, 11, 'Ashraf', 'low', 0, '2025-10-28 12:08:10'),
(163, 11, 'Ashraf', 'fly', 0, '2025-10-28 13:47:30'),
(164, 11, 'Ashraf', 'bed', 0, '2025-10-28 13:54:02'),
(165, 11, 'Ashraf', 'bed', 0, '2025-10-28 13:54:14'),
(166, 11, 'Ashraf', 'bed', 0, '2025-10-28 13:54:25'),
(167, 11, 'Ashraf', 'dog', 2, '2025-10-31 11:46:07'),
(168, 11, 'Ashraf', 'wet', 2, '2025-10-31 11:46:38'),
(169, 11, 'Ashraf', 'cry', 0, '2025-10-31 11:47:01'),
(170, 11, 'Ashraf', 'cry', 0, '2025-10-31 11:47:06'),
(171, 11, 'Ashraf', 'cup', 5, '2025-10-31 11:47:13'),
(172, 11, 'Ashraf', 'bus', 5, '2025-10-31 11:56:25'),
(173, 11, 'Ashraf', 'fish', 5, '2025-10-31 12:04:02'),
(174, 11, 'Ashraf', 'new', 0, '2025-10-31 12:04:48'),
(175, 11, 'Ashraf', 'door', 2, '2025-10-31 12:04:52'),
(176, 11, 'Ashraf', 'run', 5, '2025-10-31 12:04:59'),
(177, 11, 'Ashraf', 'cat', 0, '2025-12-16 12:35:26'),
(178, 11, 'Ashraf', 'fly', 0, '2026-01-14 03:21:00'),
(179, 11, 'Ashraf', 'fish', 0, '2026-01-19 03:24:31'),
(180, 11, 'Ashraf', 'fish', 5, '2026-01-19 03:24:41'),
(181, 11, 'Ashraf', 'bus', 5, '2026-01-19 03:34:00'),
(182, 11, 'Ashraf', 'door', 2, '2026-01-19 03:34:12'),
(183, 11, 'Ashraf', 'door', 2, '2026-01-19 03:34:20'),
(184, 11, 'Ashraf', 'new', 0, '2026-01-19 03:35:48'),
(185, 11, 'Ashraf', 'toy', 0, '2026-01-19 03:35:55'),
(186, 11, 'Ashraf', 'road', 0, '2026-01-19 04:26:48'),
(187, 11, 'Ashraf', 'pan', 5, '2026-01-19 13:21:39'),
(188, 11, 'Ashraf', 'mat', 2, '2026-01-19 13:21:49'),
(189, 11, 'Ashraf', 'mat', 2, '2026-01-19 13:21:52'),
(190, 11, 'Ashraf', 'bib', 1, '2026-01-19 13:22:03'),
(191, 11, 'Ashraf', 'mad', 5, '2026-01-19 13:22:15'),
(192, 11, 'Ashraf', 'sad', 5, '2026-01-19 13:29:39'),
(193, 11, 'Ashraf', 'dog', 2, '2026-01-19 13:54:37'),
(194, 11, 'Ashraf', 'dog', 2, '2026-01-19 13:55:31'),
(195, 11, 'Ashraf', 'mop', 2, '2026-01-19 13:55:38'),
(196, 11, 'Ashraf', 'sun', 5, '2026-01-19 13:55:45'),
(197, 11, 'Ashraf', 'bus', 5, '2026-01-19 14:00:32'),
(198, 11, 'Ashraf', 'cat', 0, '2026-01-21 05:46:49'),
(199, 11, 'Ashraf', 'cat', 5, '2026-01-21 06:05:16'),
(200, 11, 'Ashraf', 'sun', 5, '2026-01-21 06:17:34'),
(201, 11, 'Ashraf', 'pig', 5, '2026-01-21 06:18:06'),
(202, 11, 'Ashraf', 'bus', 0, '2026-01-21 06:30:07'),
(203, 11, 'Ashraf', 'dog', 0, '2026-01-21 06:35:53'),
(204, 11, 'Ashraf', 'sun', 0, '2026-01-21 06:36:16'),
(205, 11, 'Ashraf', 'sun', 5, '2026-01-21 06:36:23'),
(206, 11, 'Ashraf', 'sun', 0, '2026-01-21 06:43:59');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `grade` varchar(20) NOT NULL,
  `section` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `fullname`, `username`, `grade`, `section`, `password`, `created_at`) VALUES
(13, 'elma', 'elms', '6', 'Sunflower', '123456789', '2025-10-14 05:48:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `student_progress`
--
ALTER TABLE `student_progress`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `student_ratings`
--
ALTER TABLE `student_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `student_ratings`
--
ALTER TABLE `student_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `student_ratings`
--
ALTER TABLE `student_ratings`
  ADD CONSTRAINT `student_ratings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
