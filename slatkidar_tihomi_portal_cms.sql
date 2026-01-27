-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 26, 2026 at 09:58 AM
-- Server version: 10.6.24-MariaDB-cll-lve
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `slatkidar_tihomi_portal_cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 06:12:35'),
(2, 1, 'theme_propose', 'theme', 1, NULL, '92.242.240.96', '2026-01-14 06:14:55'),
(3, 1, 'user_delete', 'user', 3, NULL, '92.242.240.96', '2026-01-14 06:16:01'),
(4, 1, 'user_delete', 'user', 2, NULL, '92.242.240.96', '2026-01-14 06:16:04'),
(5, 1, 'user_delete', 'user', 4, NULL, '92.242.240.96', '2026-01-14 06:16:07'),
(6, 1, 'user_create', 'user', 5, NULL, '92.242.240.96', '2026-01-14 06:17:11'),
(7, 1, 'user_create', 'user', 6, NULL, '92.242.240.96', '2026-01-14 06:18:22'),
(8, 1, 'logout', 'user', 1, NULL, '92.242.240.96', '2026-01-14 06:18:41'),
(9, 6, 'login', 'user', 6, NULL, '92.242.240.96', '2026-01-14 06:18:55'),
(10, 6, 'logout', 'user', 6, NULL, '92.242.240.96', '2026-01-14 06:19:08'),
(11, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 06:19:19'),
(12, 1, 'user_update', 'user', 6, NULL, '92.242.240.96', '2026-01-14 06:19:39'),
(13, 1, 'user_update', 'user', 6, NULL, '92.242.240.96', '2026-01-14 06:19:48'),
(14, 1, 'logout', 'user', 1, NULL, '92.242.240.96', '2026-01-14 06:20:07'),
(15, 6, 'login', 'user', 6, NULL, '92.242.240.96', '2026-01-14 06:20:14'),
(16, 6, 'logout', 'user', 6, NULL, '92.242.240.96', '2026-01-14 06:20:24'),
(17, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 06:20:32'),
(18, 1, 'user_update', 'user', 5, NULL, '92.242.240.96', '2026-01-14 06:21:14'),
(19, 1, 'user_create', 'user', 7, NULL, '92.242.240.96', '2026-01-14 06:22:05'),
(20, 1, 'user_create', 'user', 8, NULL, '92.242.240.96', '2026-01-14 06:23:09'),
(21, 1, 'user_create', 'user', 9, NULL, '92.242.240.96', '2026-01-14 06:23:47'),
(22, 1, 'user_create', 'user', 10, NULL, '92.242.240.96', '2026-01-14 06:24:29'),
(23, 1, 'theme_reject', 'theme', 1, NULL, '92.242.240.96', '2026-01-14 06:33:36'),
(24, 1, 'shift_add', 'event', 1, NULL, '92.242.240.96', '2026-01-14 06:52:47'),
(25, 1, 'shift_add', 'event', 2, NULL, '92.242.240.96', '2026-01-14 06:53:00'),
(26, 5, 'login', 'user', 5, NULL, '93.137.117.191', '2026-01-14 06:53:01'),
(27, 1, 'shift_add', 'event', 3, NULL, '92.242.240.96', '2026-01-14 06:53:14'),
(28, 1, 'shift_add', 'event', 4, NULL, '92.242.240.96', '2026-01-14 06:53:37'),
(29, 1, 'shift_add', 'event', 5, NULL, '92.242.240.96', '2026-01-14 06:54:02'),
(30, 1, 'shift_add', 'event', 6, NULL, '92.242.240.96', '2026-01-14 06:54:18'),
(31, 1, 'user_update', 'user', 8, NULL, '92.242.240.96', '2026-01-14 06:54:40'),
(32, 5, 'task_create', 'task', 1, NULL, '93.137.117.191', '2026-01-14 06:56:02'),
(33, 1, 'login', 'user', 1, NULL, '104.28.130.20', '2026-01-14 06:58:15'),
(34, 1, 'event_create', 'event', 7, NULL, '92.242.240.96', '2026-01-14 07:09:18'),
(35, 1, 'event_create', 'event', 8, NULL, '92.242.240.96', '2026-01-14 07:15:26'),
(36, 1, 'login', 'user', 1, NULL, '109.227.13.111', '2026-01-14 07:22:06'),
(37, 1, 'login', 'user', 1, NULL, '104.28.130.20', '2026-01-14 07:58:35'),
(38, 1, 'logout', 'user', 1, NULL, '92.242.240.96', '2026-01-14 08:00:43'),
(39, 10, 'login', 'user', 10, NULL, '92.242.240.96', '2026-01-14 08:00:50'),
(40, 10, 'task_update', 'task', 1, NULL, '92.242.240.96', '2026-01-14 08:06:29'),
(41, 1, 'task_update', 'task', 1, NULL, '104.28.130.20', '2026-01-14 08:11:51'),
(42, 10, 'task_update', 'task', 1, NULL, '92.242.240.96', '2026-01-14 08:12:21'),
(43, 1, 'logout', 'user', 1, NULL, '104.28.130.20', '2026-01-14 08:15:47'),
(44, 10, 'login', 'user', 10, NULL, '104.28.130.20', '2026-01-14 08:15:58'),
(45, 5, 'login', 'user', 5, NULL, '93.137.117.191', '2026-01-14 08:34:14'),
(46, 5, 'task_update', 'task', 1, NULL, '93.137.117.191', '2026-01-14 08:35:08'),
(47, 5, 'task_update', 'task', 1, NULL, '93.137.117.191', '2026-01-14 08:35:13'),
(48, 5, 'task_update', 'task', 1, NULL, '93.137.117.191', '2026-01-14 08:35:36'),
(49, 5, 'task_update', 'task', 1, NULL, '93.137.117.191', '2026-01-14 08:35:41'),
(50, 5, 'task_update', 'task', 1, NULL, '93.137.117.191', '2026-01-14 08:35:48'),
(51, 1, 'login', 'user', 1, NULL, '109.227.13.111', '2026-01-14 09:21:58'),
(52, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 09:36:23'),
(53, 1, 'shift_add', 'event', 9, NULL, '92.242.240.96', '2026-01-14 09:43:23'),
(54, 1, 'shift_add', 'event', 10, NULL, '92.242.240.96', '2026-01-14 09:43:59'),
(55, 1, 'shift_add', 'event', 11, NULL, '92.242.240.96', '2026-01-14 09:44:09'),
(56, 1, 'shift_add', 'event', 12, NULL, '92.242.240.96', '2026-01-14 09:45:19'),
(57, 1, 'shift_add', 'event', 13, NULL, '92.242.240.96', '2026-01-14 09:45:29'),
(58, 1, 'shift_add', 'event', 14, NULL, '92.242.240.96', '2026-01-14 09:45:50'),
(59, 1, 'shift_add', 'event', 15, NULL, '92.242.240.96', '2026-01-14 09:46:06'),
(60, 1, 'shift_add', 'event', 16, NULL, '92.242.240.96', '2026-01-14 09:46:37'),
(61, 1, 'shift_add', 'event', 17, NULL, '92.242.240.96', '2026-01-14 09:46:51'),
(62, 1, 'logout', 'user', 1, NULL, '92.242.240.96', '2026-01-14 09:47:50'),
(63, 10, 'login', 'user', 10, NULL, '92.242.240.96', '2026-01-14 09:47:58'),
(64, 10, 'logout', 'user', 10, NULL, '92.242.240.96', '2026-01-14 09:50:37'),
(65, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 09:50:40'),
(66, 1, 'user_create', 'user', 11, NULL, '92.242.240.96', '2026-01-14 09:51:29'),
(67, 1, 'user_create', 'user', 12, NULL, '92.242.240.96', '2026-01-14 09:52:03'),
(68, 1, 'logout', 'user', 1, NULL, '92.242.240.96', '2026-01-14 10:57:18'),
(69, 5, 'login', 'user', 5, NULL, '92.242.240.96', '2026-01-14 10:57:24'),
(70, 5, 'shift_add', 'event', 18, NULL, '92.242.240.96', '2026-01-14 11:01:17'),
(71, 5, 'shift_add', 'event', 19, NULL, '92.242.240.96', '2026-01-14 11:01:37'),
(72, 5, 'shift_add', 'event', 20, NULL, '92.242.240.96', '2026-01-14 11:01:54'),
(73, 5, 'shift_add', 'event', 21, NULL, '92.242.240.96', '2026-01-14 11:02:12'),
(74, 5, 'shift_add', 'event', 22, NULL, '92.242.240.96', '2026-01-14 11:02:25'),
(75, 5, 'shift_add', 'event', 23, NULL, '92.242.240.96', '2026-01-14 11:02:35'),
(76, 5, 'shift_add', 'event', 24, NULL, '92.242.240.96', '2026-01-14 11:02:48'),
(77, 5, 'shift_add', 'event', 25, NULL, '92.242.240.96', '2026-01-14 11:03:01'),
(78, 5, 'shift_add', 'event', 26, NULL, '92.242.240.96', '2026-01-14 11:03:36'),
(79, 5, 'shift_add', 'event', 27, NULL, '92.242.240.96', '2026-01-14 11:03:47'),
(80, 5, 'shift_add', 'event', 28, NULL, '92.242.240.96', '2026-01-14 11:03:58'),
(81, 5, 'shift_add', 'event', 29, NULL, '92.242.240.96', '2026-01-14 11:04:13'),
(82, 5, 'shift_add', 'event', 30, NULL, '92.242.240.96', '2026-01-14 11:04:23'),
(83, 5, 'shift_add', 'event', 31, NULL, '92.242.240.96', '2026-01-14 11:04:36'),
(84, 5, 'shift_add', 'event', 32, NULL, '92.242.240.96', '2026-01-14 11:04:50'),
(85, 5, 'shift_add', 'event', 33, NULL, '92.242.240.96', '2026-01-14 11:05:02'),
(86, 5, 'shift_add', 'event', 34, NULL, '92.242.240.96', '2026-01-14 11:05:14'),
(87, 5, 'shift_add', 'event', 35, NULL, '92.242.240.96', '2026-01-14 11:05:24'),
(88, 5, 'shift_add', 'event', 36, NULL, '92.242.240.96', '2026-01-14 11:05:37'),
(89, 5, 'shift_add', 'event', 37, NULL, '92.242.240.96', '2026-01-14 11:05:50'),
(90, 5, 'shift_add', 'event', 38, NULL, '92.242.240.96', '2026-01-14 11:06:01'),
(91, 5, 'shift_add', 'event', 39, NULL, '92.242.240.96', '2026-01-14 11:06:12'),
(92, 5, 'shift_add', 'event', 40, NULL, '92.242.240.96', '2026-01-14 11:06:25'),
(93, 5, 'shift_add', 'event', 41, NULL, '92.242.240.96', '2026-01-14 11:06:37'),
(94, 5, 'shift_add', 'event', 42, NULL, '92.242.240.96', '2026-01-14 11:06:51'),
(95, 5, 'shift_add', 'event', 43, NULL, '92.242.240.96', '2026-01-14 11:07:02'),
(96, 5, 'shift_add', 'event', 44, NULL, '92.242.240.96', '2026-01-14 11:07:13'),
(97, 5, 'shift_add', 'event', 45, NULL, '92.242.240.96', '2026-01-14 11:07:24'),
(98, 5, 'shift_add', 'event', 46, NULL, '92.242.240.96', '2026-01-14 11:07:40'),
(99, 5, 'shift_add', 'event', 47, NULL, '92.242.240.96', '2026-01-14 11:07:50'),
(100, 5, 'shift_add', 'event', 48, NULL, '92.242.240.96', '2026-01-14 11:08:02'),
(101, 5, 'shift_add', 'event', 49, NULL, '92.242.240.96', '2026-01-14 11:08:16'),
(102, 5, 'shift_add', 'event', 50, NULL, '92.242.240.96', '2026-01-14 11:08:30'),
(103, 5, 'shift_add', 'event', 51, NULL, '92.242.240.96', '2026-01-14 11:08:42'),
(104, 5, 'shift_add', 'event', 52, NULL, '92.242.240.96', '2026-01-14 11:08:57'),
(105, 5, 'shift_add', 'event', 53, NULL, '92.242.240.96', '2026-01-14 11:09:08'),
(106, 5, 'shift_add', 'event', 54, NULL, '92.242.240.96', '2026-01-14 11:09:19'),
(107, 5, 'shift_add', 'event', 55, NULL, '92.242.240.96', '2026-01-14 11:09:39'),
(108, 5, 'logout', 'user', 5, NULL, '92.242.240.96', '2026-01-14 11:12:40'),
(109, 10, 'login', 'user', 10, NULL, '92.242.240.96', '2026-01-14 11:12:46'),
(110, 10, 'logout', 'user', 10, NULL, '92.242.240.96', '2026-01-14 11:12:50'),
(111, 5, 'login', 'user', 5, NULL, '104.28.130.19', '2026-01-14 11:13:08'),
(112, 5, 'login', 'user', 5, NULL, '92.242.240.96', '2026-01-14 11:14:16'),
(113, 5, 'logout', 'user', 5, NULL, '92.242.240.96', '2026-01-14 11:14:41'),
(114, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 11:14:43'),
(115, 1, 'user_create', 'user', 13, NULL, '92.242.240.96', '2026-01-14 11:15:27'),
(116, 1, 'event_create', 'event', 56, NULL, '92.242.240.96', '2026-01-14 11:16:26'),
(117, 1, 'event_create', 'event', 57, NULL, '92.242.240.96', '2026-01-14 11:17:17'),
(118, 1, 'event_create', 'event', 58, NULL, '92.242.240.96', '2026-01-14 11:18:05'),
(119, 1, 'event_create', 'event', 59, NULL, '92.242.240.96', '2026-01-14 11:18:55'),
(120, 1, 'event_create', 'event', 60, NULL, '92.242.240.96', '2026-01-14 11:19:46'),
(121, 1, 'event_create', 'event', 61, NULL, '92.242.240.96', '2026-01-14 11:20:35'),
(122, 1, 'event_create', 'event', 62, NULL, '92.242.240.96', '2026-01-14 11:21:13'),
(123, 1, 'event_create', 'event', 63, NULL, '92.242.240.96', '2026-01-14 11:22:07'),
(124, 1, 'event_create', 'event', 64, NULL, '92.242.240.96', '2026-01-14 11:23:02'),
(125, 1, 'logout', 'user', 1, NULL, '92.242.240.96', '2026-01-14 11:24:12'),
(126, 10, 'login', 'user', 10, NULL, '92.242.240.96', '2026-01-14 11:24:19'),
(127, 10, 'logout', 'user', 10, NULL, '92.242.240.96', '2026-01-14 11:29:36'),
(128, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 11:29:38'),
(129, 1, 'event_create', 'event', 65, NULL, '92.242.240.96', '2026-01-14 11:39:48'),
(130, 1, 'event_delete', 'event', 65, NULL, '92.242.240.96', '2026-01-14 11:40:19'),
(131, 5, 'login', 'user', 5, NULL, '172.225.189.241', '2026-01-14 11:41:01'),
(132, 5, 'login', 'user', 5, NULL, '172.225.189.245', '2026-01-14 12:30:03'),
(133, 5, 'login', 'user', 5, NULL, '172.225.96.192', '2026-01-14 13:18:24'),
(134, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 14:09:51'),
(135, 5, 'login', 'user', 5, NULL, '104.28.139.144', '2026-01-14 14:20:12'),
(136, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 15:10:00'),
(137, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 15:10:12'),
(138, 5, 'login', 'user', 5, NULL, '92.242.240.96', '2026-01-14 15:10:19'),
(139, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-14 15:11:01'),
(140, 5, 'login', 'user', 5, NULL, '93.137.117.191', '2026-01-14 15:49:25'),
(141, 5, 'login', 'user', 5, NULL, '172.225.189.245', '2026-01-14 18:46:47'),
(142, 5, 'login', 'user', 5, NULL, '93.137.117.191', '2026-01-14 19:10:08'),
(143, 1, 'login', 'user', 1, NULL, '104.28.139.143', '2026-01-15 03:16:25'),
(144, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-15 08:52:45'),
(145, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 08:54:25'),
(146, 1, 'ai_text_process', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 08:54:56'),
(147, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 09:10:21'),
(148, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 09:10:45'),
(149, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 09:11:28'),
(150, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 09:12:00'),
(151, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 09:14:56'),
(152, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 09:18:12'),
(153, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 09:19:00'),
(154, 1, 'logout', 'user', 1, NULL, '92.242.240.96', '2026-01-15 09:21:31'),
(155, 5, 'login', 'user', 5, NULL, '92.242.240.96', '2026-01-15 09:21:44'),
(156, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-15 12:24:21'),
(157, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-15 15:42:27'),
(158, 5, 'login', 'user', 5, NULL, '140.248.36.133', '2026-01-15 16:05:36'),
(159, 5, 'file_upload', 'attachment', 1, NULL, '140.248.36.133', '2026-01-15 16:07:07'),
(160, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-16 07:36:53'),
(161, 5, 'login', 'user', 5, NULL, '92.242.240.96', '2026-01-18 16:22:34'),
(162, 5, 'audio_transcribe', 'ai', NULL, NULL, '92.242.240.96', '2026-01-18 16:36:32'),
(163, 5, 'audio_transcribe', 'ai', NULL, NULL, '92.242.240.96', '2026-01-18 16:49:20'),
(164, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-19 09:53:13'),
(165, 5, 'login', 'user', 5, NULL, '93.137.125.220', '2026-01-19 19:16:42'),
(166, 5, 'shift_add', 'event', 66, NULL, '93.137.125.220', '2026-01-19 19:17:06'),
(167, 5, 'shift_add', 'event', 67, NULL, '93.137.125.220', '2026-01-19 19:17:20'),
(168, 5, 'shift_add', 'event', 68, NULL, '93.137.125.220', '2026-01-19 19:17:32'),
(169, 5, 'shift_add', 'event', 69, NULL, '93.137.125.220', '2026-01-19 19:18:04'),
(170, 5, 'shift_add', 'event', 70, NULL, '93.137.125.220', '2026-01-19 19:18:15'),
(171, 5, 'shift_add', 'event', 71, NULL, '93.137.125.220', '2026-01-19 19:18:29'),
(172, 5, 'shift_add', 'event', 72, NULL, '93.137.125.220', '2026-01-19 19:18:53'),
(173, 5, 'shift_add', 'event', 73, NULL, '93.137.125.220', '2026-01-19 19:19:05'),
(174, 5, 'shift_add', 'event', 74, NULL, '93.137.125.220', '2026-01-19 19:20:02'),
(175, 5, 'shift_add', 'event', 75, NULL, '93.137.125.220', '2026-01-19 19:20:34'),
(176, 5, 'shift_add', 'event', 76, NULL, '93.137.125.220', '2026-01-19 19:21:03'),
(177, 5, 'shift_add', 'event', 77, NULL, '93.137.125.220', '2026-01-19 19:21:15'),
(178, 5, 'shift_add', 'event', 78, NULL, '93.137.125.220', '2026-01-19 19:39:15'),
(179, 5, 'shift_add', 'event', 79, NULL, '93.137.125.220', '2026-01-19 19:39:36'),
(180, 5, 'shift_add', 'event', 80, NULL, '93.137.125.220', '2026-01-19 19:39:53'),
(181, 5, 'shift_add', 'event', 81, NULL, '93.137.125.220', '2026-01-19 19:40:32'),
(182, 5, 'shift_add', 'event', 82, NULL, '93.137.125.220', '2026-01-19 19:41:09'),
(183, 5, 'shift_add', 'event', 83, NULL, '93.137.125.220', '2026-01-19 19:41:21'),
(184, 5, 'shift_add', 'event', 84, NULL, '93.137.125.220', '2026-01-19 19:41:57'),
(185, 5, 'shift_add', 'event', 85, NULL, '93.137.125.220', '2026-01-19 19:42:08'),
(186, 5, 'shift_add', 'event', 86, NULL, '93.137.125.220', '2026-01-19 19:42:19'),
(187, 5, 'event_create', 'event', 87, NULL, '93.137.125.220', '2026-01-19 19:43:57'),
(188, 5, 'event_update', 'event', 87, NULL, '93.137.125.220', '2026-01-19 19:44:21'),
(189, 5, 'login', 'user', 5, NULL, '93.137.52.112', '2026-01-23 17:03:17'),
(190, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-24 05:15:23'),
(191, 5, 'login', 'user', 5, NULL, '93.137.122.68', '2026-01-25 08:39:31'),
(192, 5, 'audio_transcribe', 'ai', NULL, NULL, '93.137.122.68', '2026-01-25 08:40:01'),
(193, 5, 'ai_text_process', 'ai', NULL, NULL, '92.242.240.96', '2026-01-25 09:07:12'),
(194, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-26 05:58:45'),
(195, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-26 07:26:43'),
(196, 5, 'login', 'user', 5, NULL, '92.242.240.96', '2026-01-26 08:23:21'),
(197, 5, 'audio_transcribe', 'ai', NULL, NULL, '92.242.240.96', '2026-01-26 08:23:53'),
(198, 5, 'ai_text_process', 'ai', NULL, NULL, '92.242.240.96', '2026-01-26 08:25:00'),
(199, 5, 'audio_transcribe', 'ai', NULL, NULL, '92.242.240.96', '2026-01-26 08:39:33'),
(200, 5, 'ai_text_process', 'ai', NULL, NULL, '92.242.240.96', '2026-01-26 08:40:41'),
(201, 5, 'audio_transcribe', 'ai', NULL, NULL, '92.242.240.96', '2026-01-26 08:42:05'),
(202, 5, 'audio_transcribe', 'ai', NULL, NULL, '92.242.240.96', '2026-01-26 08:42:43'),
(203, 5, 'ai_text_process', 'ai', NULL, NULL, '92.242.240.96', '2026-01-26 08:44:35'),
(204, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-26 08:56:59');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `all_day` tinyint(1) DEFAULT 0,
  `shift_type` enum('urednik','novinar','fotograf','web','dezurni') DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `event_type` enum('press','sport','kultura','politika','drustvo','dezurstvo','ostalo') DEFAULT 'ostalo',
  `importance` enum('normal','important','must_cover') DEFAULT 'normal',
  `created_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `skip_coverage` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `location`, `event_date`, `event_time`, `all_day`, `shift_type`, `end_time`, `event_type`, `importance`, `created_by`, `notes`, `skip_coverage`, `created_at`, `updated_at`) VALUES
(1, '☀️ Jutarnja smjena - Sabina Pušec', NULL, NULL, '2026-01-14', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 06:52:47', '2026-01-14 06:52:47'),
(2, '🌤️ Popodnevna smjena - Jakov Tuković', NULL, NULL, '2026-01-14', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 06:53:00', '2026-01-14 06:53:00'),
(3, '🌙 Večernja smjena - Ivan Kovačić', NULL, NULL, '2026-01-14', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 06:53:14', '2026-01-14 06:53:14'),
(4, '☀️ Jutarnja smjena - Marta Čaržavec', NULL, NULL, '2026-01-15', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 06:53:37', '2026-01-14 06:53:37'),
(5, '🌤️ Popodnevna smjena - Patrik', NULL, NULL, '2026-01-15', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 06:54:02', '2026-01-14 06:54:02'),
(6, '🌙 Večernja smjena - Jakov Tuković', NULL, NULL, '2026-01-15', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 06:54:18', '2026-01-14 06:54:18'),
(7, 'Tuhelj bicikli', '', 'Tuhelj', '2026-01-14', '08:00:00', 0, NULL, NULL, 'ostalo', 'normal', 1, '', 0, '2026-01-14 07:09:18', '2026-01-14 07:09:18'),
(8, 'Klanjec bicikli i proračun', '', 'Klanjec', '2026-01-14', '09:30:00', 0, NULL, NULL, 'politika', 'normal', 1, '', 0, '2026-01-14 07:15:26', '2026-01-14 07:15:26'),
(9, '☀️ Jutarnja smjena - Sabina Pušec', NULL, NULL, '2026-01-16', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 09:43:23', '2026-01-14 09:43:23'),
(10, '🌤️ Popodnevna smjena - Marta Čaržavec', NULL, NULL, '2026-01-16', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 09:43:59', '2026-01-14 09:43:59'),
(11, '🌙 Večernja smjena - Ivan Kovačić', NULL, NULL, '2026-01-16', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 09:44:09', '2026-01-14 09:44:09'),
(12, '☀️ Jutarnja smjena - Patrik Jazbec', NULL, NULL, '2026-01-17', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 09:45:19', '2026-01-14 09:45:19'),
(13, '🌤️ Popodnevna smjena - Jakov Tuković', NULL, NULL, '2026-01-17', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 09:45:29', '2026-01-14 09:45:29'),
(14, '☀️ Jutarnja smjena - Ivan Kovačić', NULL, NULL, '2026-01-18', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 09:45:50', '2026-01-14 09:45:50'),
(15, '🌤️ Popodnevna smjena - Elvis Lacković', NULL, NULL, '2026-01-18', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 09:46:06', '2026-01-14 09:46:06'),
(16, '🌙 Večernja smjena - Ivan Kovačić', NULL, NULL, '2026-01-17', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 09:46:37', '2026-01-14 09:46:37'),
(17, '🌙 Večernja smjena - Patrik Jazbec', NULL, NULL, '2026-01-18', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 1, NULL, 0, '2026-01-14 09:46:51', '2026-01-14 09:46:51'),
(18, '☀️ Jutarnja smjena - Elvis Lacković', NULL, NULL, '2026-01-01', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:01:17', '2026-01-14 11:01:17'),
(19, '🌙 Večernja smjena - Sabina Pušec', NULL, NULL, '2026-01-01', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:01:37', '2026-01-14 11:01:37'),
(20, '☀️ Jutarnja smjena - Marta Čaržavec', NULL, NULL, '2026-01-02', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:01:54', '2026-01-14 11:01:54'),
(21, '🌤️ Popodnevna smjena - Patrik Jazbec', NULL, NULL, '2026-01-02', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:02:12', '2026-01-14 11:02:12'),
(22, '🌙 Večernja smjena - Ivan Kovačić', NULL, NULL, '2026-01-02', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:02:25', '2026-01-14 11:02:25'),
(23, '☀️ Jutarnja smjena - Jakov Tuković', NULL, NULL, '2026-01-03', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:02:35', '2026-01-14 11:02:35'),
(24, '🌤️ Popodnevna smjena - Sabina Pušec', NULL, NULL, '2026-01-03', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:02:48', '2026-01-14 11:02:48'),
(25, '🌙 Večernja smjena - Patrik Jazbec', NULL, NULL, '2026-01-03', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:03:01', '2026-01-14 11:03:01'),
(26, '☀️ Jutarnja smjena - Ivan Kovačić', NULL, NULL, '2026-01-04', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:03:36', '2026-01-14 11:03:36'),
(27, '🌤️ Popodnevna smjena - Patrik Jazbec', NULL, NULL, '2026-01-04', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:03:47', '2026-01-14 11:03:47'),
(28, '🌙 Večernja smjena - Jakov Tuković', NULL, NULL, '2026-01-04', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:03:58', '2026-01-14 11:03:58'),
(29, '☀️ Jutarnja smjena - Marta Čaržavec', NULL, NULL, '2026-01-05', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:04:13', '2026-01-14 11:04:13'),
(30, '🌤️ Popodnevna smjena - Jakov Tuković', NULL, NULL, '2026-01-05', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:04:23', '2026-01-14 11:04:23'),
(31, '🌙 Večernja smjena - Sabina Pušec', NULL, NULL, '2026-01-05', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:04:36', '2026-01-14 11:04:36'),
(32, '☀️ Jutarnja smjena - Elvis Lacković', NULL, NULL, '2026-01-06', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:04:50', '2026-01-14 11:04:50'),
(33, '🌤️ Popodnevna smjena - Patrik Jazbec', NULL, NULL, '2026-01-06', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:05:02', '2026-01-14 11:05:02'),
(34, '🌙 Večernja smjena - Ivan Kovačić', NULL, NULL, '2026-01-06', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:05:14', '2026-01-14 11:05:14'),
(35, '☀️ Jutarnja smjena - Ivan Kovačić', NULL, NULL, '2026-01-07', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:05:24', '2026-01-14 11:05:24'),
(36, '🌤️ Popodnevna smjena - Sabina Pušec', NULL, NULL, '2026-01-07', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:05:37', '2026-01-14 11:05:37'),
(37, '🌙 Večernja smjena - Patrik Jazbec', NULL, NULL, '2026-01-07', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:05:50', '2026-01-14 11:05:50'),
(38, '☀️ Jutarnja smjena - Patrik Jazbec', NULL, NULL, '2026-01-08', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:06:01', '2026-01-14 11:06:01'),
(39, '🌤️ Popodnevna smjena - Marta Čaržavec', NULL, NULL, '2026-01-08', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:06:12', '2026-01-14 11:06:12'),
(40, '🌙 Večernja smjena - Jakov Tuković', NULL, NULL, '2026-01-08', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:06:25', '2026-01-14 11:06:25'),
(41, '☀️ Jutarnja smjena - Ivan Kovačić', NULL, NULL, '2026-01-09', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:06:37', '2026-01-14 11:06:37'),
(42, '🌤️ Popodnevna smjena - Jakov Tuković', NULL, NULL, '2026-01-09', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:06:51', '2026-01-14 11:06:51'),
(43, '🌙 Večernja smjena - Marta Čaržavec', NULL, NULL, '2026-01-09', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:07:02', '2026-01-14 11:07:02'),
(44, '☀️ Jutarnja smjena - Marta Čaržavec', NULL, NULL, '2026-01-10', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:07:13', '2026-01-14 11:07:13'),
(45, '🌤️ Popodnevna smjena - Elvis Lacković', NULL, NULL, '2026-01-10', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:07:24', '2026-01-14 11:07:24'),
(46, '🌙 Večernja smjena - Ivan Kovačić', NULL, NULL, '2026-01-10', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:07:40', '2026-01-14 11:07:40'),
(47, '☀️ Jutarnja smjena - Sabina Pušec', NULL, NULL, '2026-01-11', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:07:50', '2026-01-14 11:07:50'),
(48, '🌤️ Popodnevna smjena - Patrik Jazbec', NULL, NULL, '2026-01-11', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:08:02', '2026-01-14 11:08:02'),
(49, '🌙 Večernja smjena - Jakov Tuković', NULL, NULL, '2026-01-11', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:08:16', '2026-01-14 11:08:16'),
(50, '☀️ Jutarnja smjena - Elvis Lacković', NULL, NULL, '2026-01-12', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:08:30', '2026-01-14 11:08:30'),
(51, '🌤️ Popodnevna smjena - Patrik Jazbec', NULL, NULL, '2026-01-12', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:08:42', '2026-01-14 11:08:42'),
(52, '🌙 Večernja smjena - Jakov Tuković', NULL, NULL, '2026-01-12', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:08:57', '2026-01-14 11:08:57'),
(53, '☀️ Jutarnja smjena - Ivan Kovačić', NULL, NULL, '2026-01-13', '06:00:00', 0, NULL, '14:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:09:08', '2026-01-14 11:09:08'),
(54, '🌤️ Popodnevna smjena - Marta Čaržavec', NULL, NULL, '2026-01-13', '14:00:00', 0, NULL, '22:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:09:19', '2026-01-14 11:09:19'),
(55, '🌙 Večernja smjena - Sabina Pušec', NULL, NULL, '2026-01-13', '22:00:00', 0, NULL, '06:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-14 11:09:39', '2026-01-14 11:09:39'),
(56, 'Bračak UPMC', '', 'Bračak', '2026-01-07', '11:30:00', 0, NULL, NULL, 'press', 'normal', 1, '', 0, '2026-01-14 11:16:26', '2026-01-14 11:16:26'),
(57, 'Zlatar Bistrica', '', 'Zlatar Bistrica', '2026-01-08', '09:00:00', 0, NULL, NULL, 'politika', 'normal', 1, '', 0, '2026-01-14 11:17:17', '2026-01-14 11:17:17'),
(58, 'Turistički rezultati', '', 'Tuheljske', '2026-01-09', '09:00:00', 0, NULL, NULL, 'press', 'normal', 1, '', 0, '2026-01-14 11:18:05', '2026-01-14 11:18:05'),
(59, 'Čikin breg', '', 'Zabok', '2026-01-13', '13:00:00', 0, NULL, NULL, 'drustvo', 'normal', 1, '', 0, '2026-01-14 11:18:55', '2026-01-14 11:18:55'),
(60, 'Teren Oroslavje', '', 'Oroslavje', '2026-01-15', '09:30:00', 0, NULL, NULL, 'ostalo', 'normal', 1, '', 0, '2026-01-14 11:19:46', '2026-01-14 11:19:46'),
(61, 'Župan Crveni Križ', '', 'Krapina', '2026-01-16', '09:00:00', 0, NULL, NULL, 'press', 'normal', 1, '', 0, '2026-01-14 11:20:35', '2026-01-14 11:20:35'),
(62, 'Dragovoljci NK rudar', '', 'Zabok', '2026-01-16', '16:00:00', 0, NULL, NULL, 'ostalo', 'normal', 1, '', 0, '2026-01-14 11:21:13', '2026-01-14 11:21:13'),
(63, 'Općine Bedex', '', '', '2026-01-14', '08:30:00', 0, NULL, NULL, 'press', 'normal', 1, '', 0, '2026-01-14 11:22:07', '2026-01-14 11:22:07'),
(64, 'Muzeji Krapina', '', '', '2026-01-14', '11:00:00', 0, NULL, NULL, 'press', 'normal', 1, '', 0, '2026-01-14 11:23:02', '2026-01-14 11:23:02'),
(66, '☀️ Jutarnja smjena - Elvis Lacković', NULL, NULL, '2026-01-19', '07:30:00', 0, NULL, '12:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:17:06', '2026-01-19 19:17:06'),
(67, '🌤️ Popodnevna smjena - Patrik Jazbec', NULL, NULL, '2026-01-19', '12:00:00', 0, NULL, '19:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:17:20', '2026-01-19 19:17:20'),
(68, '🌙 Večernja smjena - Ivan Kovačić', NULL, NULL, '2026-01-19', '19:30:00', 0, NULL, '07:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:17:32', '2026-01-19 19:17:32'),
(69, '☀️ Jutarnja smjena - Jakov Tuković', NULL, NULL, '2026-01-20', '07:30:00', 0, NULL, '12:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:18:04', '2026-01-19 19:18:04'),
(70, '🌤️ Popodnevna smjena - Marta Čaržavec', NULL, NULL, '2026-01-20', '12:00:00', 0, NULL, '19:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:18:15', '2026-01-19 19:18:15'),
(71, '🌙 Večernja smjena - Patrik Jazbec', NULL, NULL, '2026-01-20', '19:30:00', 0, NULL, '07:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:18:29', '2026-01-19 19:18:29'),
(72, '☀️ Jutarnja smjena - Marta Čaržavec', NULL, NULL, '2026-01-21', '07:30:00', 0, NULL, '12:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:18:53', '2026-01-19 19:18:53'),
(73, '🌤️ Popodnevna smjena - Ivan Kovačić', NULL, NULL, '2026-01-21', '12:00:00', 0, NULL, '19:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:19:05', '2026-01-19 19:19:05'),
(74, '🌙 Večernja smjena - Jakov Tuković', NULL, NULL, '2026-01-21', '19:30:00', 0, NULL, '07:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:20:02', '2026-01-19 19:20:02'),
(75, '☀️ Jutarnja smjena - Ivan Kovačić', NULL, NULL, '2026-01-22', '07:30:00', 0, NULL, '12:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:20:34', '2026-01-19 19:20:34'),
(76, '🌤️ Popodnevna smjena - Jakov Tuković', NULL, NULL, '2026-01-22', '12:00:00', 0, NULL, '19:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:21:03', '2026-01-19 19:21:03'),
(77, '🌙 Večernja smjena - Marta Čaržavec', NULL, NULL, '2026-01-22', '19:30:00', 0, NULL, '07:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:21:15', '2026-01-19 19:21:15'),
(78, '☀️ Jutarnja smjena - Sabina Pušec', NULL, NULL, '2026-01-23', '07:30:00', 0, NULL, '12:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:39:15', '2026-01-19 19:39:15'),
(79, '🌙 Večernja smjena - Patrik Jazbec', NULL, NULL, '2026-01-23', '19:30:00', 0, NULL, '07:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:39:36', '2026-01-19 19:39:36'),
(80, '🌤️ Popodnevna smjena - Ivan Kovačić', NULL, NULL, '2026-01-23', '12:00:00', 0, NULL, '19:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:39:53', '2026-01-19 19:39:53'),
(81, '☀️ Jutarnja smjena - Patrik Jazbec', NULL, NULL, '2026-01-24', '07:30:00', 0, NULL, '12:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:40:32', '2026-01-19 19:40:32'),
(82, '🌤️ Popodnevna smjena - Marta Čaržavec', NULL, NULL, '2026-01-24', '12:00:00', 0, NULL, '19:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:41:09', '2026-01-19 19:41:09'),
(83, '🌙 Večernja smjena - Ivan Kovačić', NULL, NULL, '2026-01-24', '19:30:00', 0, NULL, '07:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:41:21', '2026-01-19 19:41:21'),
(84, '☀️ Jutarnja smjena - Elvis Lacković', NULL, NULL, '2026-01-25', '07:30:00', 0, NULL, '12:00:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:41:57', '2026-01-19 19:41:57'),
(85, '🌤️ Popodnevna smjena - Sabina Pušec', NULL, NULL, '2026-01-25', '12:00:00', 0, NULL, '19:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:42:08', '2026-01-19 19:42:08'),
(86, '🌙 Večernja smjena - Jakov Tuković', NULL, NULL, '2026-01-25', '19:30:00', 0, NULL, '07:30:00', 'dezurstvo', 'normal', 5, NULL, 0, '2026-01-19 19:42:19', '2026-01-19 19:42:19'),
(87, 'Teren - Osnovna škola KRTOP', '', 'Krapinske Toplice', '2026-01-20', '08:45:00', 0, NULL, '09:55:00', 'ostalo', 'normal', 5, '', 0, '2026-01-19 19:43:57', '2026-01-19 19:43:57');

-- --------------------------------------------------------

--
-- Table structure for table `event_assignments`
--

CREATE TABLE `event_assignments` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('reporter','photographer','camera','backup') DEFAULT 'reporter',
  `confirmed` tinyint(1) DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_assignments`
--

INSERT INTO `event_assignments` (`id`, `event_id`, `user_id`, `role`, `confirmed`, `notes`, `assigned_by`, `created_at`) VALUES
(1, 1, 9, '', 0, NULL, NULL, '2026-01-14 06:52:47'),
(2, 2, 7, '', 0, NULL, NULL, '2026-01-14 06:53:00'),
(3, 3, 5, '', 0, NULL, NULL, '2026-01-14 06:53:14'),
(4, 4, 10, '', 0, NULL, NULL, '2026-01-14 06:53:37'),
(5, 5, 8, '', 0, NULL, NULL, '2026-01-14 06:54:02'),
(6, 6, 7, '', 0, NULL, NULL, '2026-01-14 06:54:18'),
(7, 7, 10, 'reporter', 0, '', 1, '2026-01-14 07:12:03'),
(8, 8, 10, 'reporter', 0, NULL, 1, '2026-01-14 07:15:26'),
(9, 9, 9, '', 0, NULL, NULL, '2026-01-14 09:43:23'),
(10, 10, 10, '', 0, NULL, NULL, '2026-01-14 09:43:59'),
(11, 11, 5, '', 0, NULL, NULL, '2026-01-14 09:44:09'),
(12, 12, 8, '', 0, NULL, NULL, '2026-01-14 09:45:19'),
(13, 13, 7, '', 0, NULL, NULL, '2026-01-14 09:45:29'),
(14, 14, 5, '', 0, NULL, NULL, '2026-01-14 09:45:50'),
(15, 15, 6, '', 0, NULL, NULL, '2026-01-14 09:46:06'),
(16, 16, 5, '', 0, NULL, NULL, '2026-01-14 09:46:37'),
(17, 17, 8, '', 0, NULL, NULL, '2026-01-14 09:46:51'),
(18, 18, 6, '', 0, NULL, NULL, '2026-01-14 11:01:17'),
(19, 19, 9, '', 0, NULL, NULL, '2026-01-14 11:01:37'),
(20, 20, 10, '', 0, NULL, NULL, '2026-01-14 11:01:54'),
(21, 21, 8, '', 0, NULL, NULL, '2026-01-14 11:02:12'),
(22, 22, 5, '', 0, NULL, NULL, '2026-01-14 11:02:25'),
(23, 23, 7, '', 0, NULL, NULL, '2026-01-14 11:02:35'),
(24, 24, 9, '', 0, NULL, NULL, '2026-01-14 11:02:48'),
(25, 25, 8, '', 0, NULL, NULL, '2026-01-14 11:03:01'),
(26, 26, 5, '', 0, NULL, NULL, '2026-01-14 11:03:36'),
(27, 27, 8, '', 0, NULL, NULL, '2026-01-14 11:03:47'),
(28, 28, 7, '', 0, NULL, NULL, '2026-01-14 11:03:58'),
(29, 29, 10, '', 0, NULL, NULL, '2026-01-14 11:04:13'),
(30, 30, 7, '', 0, NULL, NULL, '2026-01-14 11:04:23'),
(31, 31, 9, '', 0, NULL, NULL, '2026-01-14 11:04:36'),
(32, 32, 6, '', 0, NULL, NULL, '2026-01-14 11:04:50'),
(33, 33, 8, '', 0, NULL, NULL, '2026-01-14 11:05:02'),
(34, 34, 5, '', 0, NULL, NULL, '2026-01-14 11:05:14'),
(35, 35, 5, '', 0, NULL, NULL, '2026-01-14 11:05:24'),
(36, 36, 9, '', 0, NULL, NULL, '2026-01-14 11:05:37'),
(37, 37, 8, '', 0, NULL, NULL, '2026-01-14 11:05:50'),
(38, 38, 8, '', 0, NULL, NULL, '2026-01-14 11:06:01'),
(39, 39, 10, '', 0, NULL, NULL, '2026-01-14 11:06:12'),
(40, 40, 7, '', 0, NULL, NULL, '2026-01-14 11:06:25'),
(41, 41, 5, '', 0, NULL, NULL, '2026-01-14 11:06:37'),
(42, 42, 7, '', 0, NULL, NULL, '2026-01-14 11:06:51'),
(43, 43, 10, '', 0, NULL, NULL, '2026-01-14 11:07:02'),
(44, 44, 10, '', 0, NULL, NULL, '2026-01-14 11:07:13'),
(45, 45, 6, '', 0, NULL, NULL, '2026-01-14 11:07:24'),
(46, 46, 5, '', 0, NULL, NULL, '2026-01-14 11:07:40'),
(47, 47, 9, '', 0, NULL, NULL, '2026-01-14 11:07:50'),
(48, 48, 8, '', 0, NULL, NULL, '2026-01-14 11:08:02'),
(49, 49, 7, '', 0, NULL, NULL, '2026-01-14 11:08:16'),
(50, 50, 6, '', 0, NULL, NULL, '2026-01-14 11:08:30'),
(51, 51, 8, '', 0, NULL, NULL, '2026-01-14 11:08:42'),
(52, 52, 7, '', 0, NULL, NULL, '2026-01-14 11:08:57'),
(53, 53, 5, '', 0, NULL, NULL, '2026-01-14 11:09:08'),
(54, 54, 10, '', 0, NULL, NULL, '2026-01-14 11:09:19'),
(55, 55, 9, '', 0, NULL, NULL, '2026-01-14 11:09:39'),
(56, 56, 13, 'reporter', 0, NULL, 1, '2026-01-14 11:16:26'),
(57, 57, 9, 'reporter', 0, NULL, 1, '2026-01-14 11:17:17'),
(58, 58, 13, 'reporter', 0, NULL, 1, '2026-01-14 11:18:05'),
(59, 59, 13, 'reporter', 0, NULL, 1, '2026-01-14 11:18:55'),
(60, 60, 5, 'reporter', 0, NULL, 1, '2026-01-14 11:19:46'),
(61, 61, 13, 'reporter', 0, NULL, 1, '2026-01-14 11:20:35'),
(62, 62, 13, 'reporter', 0, NULL, 1, '2026-01-14 11:21:13'),
(63, 63, 13, 'reporter', 0, NULL, 1, '2026-01-14 11:22:07'),
(64, 64, 5, 'reporter', 0, NULL, 1, '2026-01-14 11:23:02'),
(65, 64, 11, 'reporter', 0, NULL, 1, '2026-01-14 11:23:02'),
(66, 66, 6, '', 0, NULL, NULL, '2026-01-19 19:17:06'),
(67, 67, 8, '', 0, NULL, NULL, '2026-01-19 19:17:20'),
(68, 68, 5, '', 0, NULL, NULL, '2026-01-19 19:17:32'),
(69, 69, 7, '', 0, NULL, NULL, '2026-01-19 19:18:04'),
(70, 70, 10, '', 0, NULL, NULL, '2026-01-19 19:18:15'),
(71, 71, 8, '', 0, NULL, NULL, '2026-01-19 19:18:29'),
(72, 72, 10, '', 0, NULL, NULL, '2026-01-19 19:18:53'),
(73, 73, 5, '', 0, NULL, NULL, '2026-01-19 19:19:05'),
(74, 74, 7, '', 0, NULL, NULL, '2026-01-19 19:20:02'),
(75, 75, 5, '', 0, NULL, NULL, '2026-01-19 19:20:34'),
(76, 76, 7, '', 0, NULL, NULL, '2026-01-19 19:21:03'),
(77, 77, 10, '', 0, NULL, NULL, '2026-01-19 19:21:15'),
(78, 78, 9, '', 0, NULL, NULL, '2026-01-19 19:39:15'),
(79, 79, 8, '', 0, NULL, NULL, '2026-01-19 19:39:36'),
(80, 80, 5, '', 0, NULL, NULL, '2026-01-19 19:39:53'),
(81, 81, 8, '', 0, NULL, NULL, '2026-01-19 19:40:32'),
(82, 82, 10, '', 0, NULL, NULL, '2026-01-19 19:41:09'),
(83, 83, 5, '', 0, NULL, NULL, '2026-01-19 19:41:21'),
(84, 84, 6, '', 0, NULL, NULL, '2026-01-19 19:41:57'),
(85, 85, 9, '', 0, NULL, NULL, '2026-01-19 19:42:08'),
(86, 86, 7, '', 0, NULL, NULL, '2026-01-19 19:42:19'),
(87, 87, 5, 'reporter', 0, NULL, 5, '2026-01-19 19:43:57');

-- --------------------------------------------------------

--
-- Table structure for table `photos`
--

CREATE TABLE `photos` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `mime_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `credit` varchar(255) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `photos`
--

INSERT INTO `photos` (`id`, `filename`, `original_name`, `filepath`, `thumbnail`, `mime_type`, `file_size`, `width`, `height`, `caption`, `credit`, `event_id`, `uploaded_by`, `created_at`) VALUES
(1, '2026-01-15_170707_317d29bd.jpeg', '746f19db-ed5e-4c7e-a1be-1b532f20d900.jpeg', 'uploads/attachments/2026/01/2026-01-15_170707_317d29bd.jpeg', 'uploads/attachments/2026/01/2026-01-15_170707_317d29bd_thumb.jpeg', 'image/jpeg', 642897, 2048, 1536, 'Stubica vlak naletio na', '', NULL, 5, '2026-01-15 16:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `portali`
--

CREATE TABLE `portali` (
  `id` int(11) NOT NULL,
  `naziv` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `rss_url` varchar(255) DEFAULT NULL,
  `aktivan` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portali`
--

INSERT INTO `portali` (`id`, `naziv`, `url`, `rss_url`, `aktivan`, `created_at`) VALUES
(1, 'Index.hr', 'https://www.index.hr', 'https://www.index.hr/rss', 1, '2026-01-26 07:27:51'),
(2, '24sata', 'https://www.24sata.hr', 'https://www.24sata.hr/feeds/aktualno.xml', 1, '2026-01-26 07:27:51'),
(3, 'Jutarnji list', 'https://www.jutarnji.hr', 'https://www.jutarnji.hr/feed', 1, '2026-01-26 07:27:51'),
(4, 'Večernji list', 'https://www.vecernji.hr', 'https://www.vecernji.hr/feeds/latest', 1, '2026-01-26 07:27:51'),
(5, 'Zagorje International', 'https://zagorje-international.hr', 'https://zagorje-international.hr/feed', 1, '2026-01-26 07:40:20'),
(6, 'Net.hr', 'https://net.hr', 'https://net.hr/feed', 1, '2026-01-26 07:40:20');

-- --------------------------------------------------------

--
-- Table structure for table `portal_najcitanije`
--

CREATE TABLE `portal_najcitanije` (
  `id` int(11) NOT NULL,
  `portal_id` int(11) NOT NULL,
  `pozicija` int(11) NOT NULL,
  `naslov` varchar(500) NOT NULL,
  `url` varchar(500) NOT NULL,
  `objavljeno_at` datetime DEFAULT NULL,
  `sadrzaj` text DEFAULT NULL,
  `dohvaceno_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portal_najcitanije`
--

INSERT INTO `portal_najcitanije` (`id`, `portal_id`, `pozicija`, `naslov`, `url`, `objavljeno_at`, `sadrzaj`, `dohvaceno_at`) VALUES
(1, 2, 1, 'VIDEO Kaos na Savskoj: Sudar kod Vjesnika, preusmjerili su tramvaje. Niz nesreća u Zagrebu', 'https://www.24sata.hr/news/foto-kaos-na-savskoj-sudar-kod-vjesnika-preusmjerili-su-tramvaje-niz-nesreca-u-zagrebu-1103570', '2026-01-26 07:41:00', 'Povećana je gustoća prometa i na zagrebačkoj obilaznici (A3), iizmeđu čvorova Zagreb zapad i Lučko u smjeru Lipovca, vozi se uz ograničenje brzine', '2026-01-26 08:27:57'),
(2, 2, 2, 'FOTO Samo ljubav u Malmöu: Pogledajte poljupce i zagrljaje hrvatskih rukometaša na Euru', 'https://www.24sata.hr/sport/foto-samo-ljubav-u-malmou-pogledajte-poljupce-i-zagrljaje-hrvatskih-rukometasa-na-euru-1103574', '2026-01-26 08:01:00', 'MALMÖ - Nakon trijumfa nad Švicarskom hrvatski rukometaši dali su si oduška i slavili u društvu ljepših polovica i članova obitelji. Pogledajte emotivne kadrove', '2026-01-26 08:27:57'),
(3, 2, 3, 'Savršeni Karlo i Petar tražit će ljubav među ove 24 djevojke', 'https://www.24sata.hr/show/savrseni-karlo-i-petar-trazit-ce-ljubav-medu-ove-24-djevojke-1103489', '2026-01-26 08:01:00', 'Ukupno 24 djevojke predstavit će se Karlu Godecu i Petru Rašiću te pokušati na prvi pogled privući njihovu pažnju. Prošlih sezona dečki su cijenili trud pri prvom susretu', '2026-01-26 08:27:57'),
(4, 2, 4, 'UŽIVO ICE usmrtio muškarca u Minneapolicsu. Izbili prosvjedi, grad zatražio Nacionalnu gardu', 'https://www.24sata.hr/news/uzivo-ice-usmrtio-muskarca-u-minneapolisu-izbili-prosvjedi-grad-zatrazio-nacionalnu-gardu-1103568', '2026-01-26 07:23:00', 'Ubojstvo 37-godišnjeg Alexa Jeffreyja Prettija, treći takav incident u manje od mjesec dana, izazvalo je kaos na ulicama i navelo gradonačelnika Jacoba Freyja da zatraži intervenciju Nacionalne garde', '2026-01-26 08:27:57'),
(5, 2, 5, 'FOTO Najmanje sedam mrtvih u SAD-u:  \'Uhvatila nas je arktička klopka, ovo je iznimno opasno\'', 'https://www.24sata.hr/news/foto-najmanje-sedam-mrtvih-u-sad-u-uhvatila-nas-je-arkticka-klopka-ovo-je-iznimno-opasno-1103563', '2026-01-26 06:19:00', 'Najmanje sedam ljudi je poginulo, a više od 800.000 kućanstava ostalo je bez električne energije nakon što je snažna i opasna zimska oluja zahvatila Sjedinjene Američke Države, uzrokujući masovne nestanke struje, prometni kolaps i zatvaranje škola i zračnih luka diljem zemlje.', '2026-01-26 08:27:57'),
(6, 2, 6, 'Slavne prijateljice zajedno: Evo što je objavila Victoria Beckham nakon Brooklynovih izjava...', 'https://www.24sata.hr/show/slavne-prijateljice-zajedno-evo-sto-je-objavila-victoria-beckham-nakon-brooklynovih-izjava-1103573', '2026-01-26 07:36:00', 'Victoria i David Beckham bili su među samo 30 gostiju pozvanih na raskošnu večeru Emme Bunton. Bilo je to njihovo prvo zajedničko pojavnosti otkako se oglasio Brooklyn Beckham...', '2026-01-26 08:27:57'),
(7, 2, 7, 'FOTO Sigurdsson nakon pobjede u ljubavnom klinču s ljepoticom!', 'https://www.24sata.hr/sport/foto-sigurdsson-nakon-pobjede-u-ljubavnom-klincu-s-ljepoticom-1103558', '2026-01-25 23:19:00', 'MALMO - Izbornik Dagur Sigurdsson nakon pobjede razmijenio je nježnosti s tajanstvenom plavušom, a fotograf Pixsella zabilježio je njihove intimne trenutke. Sigurdsson je prije godinu dana priznao kako je prekinuo s djevojkom nakon ranijeg razvoda braka, ali sad ponovno ljubi.', '2026-01-26 08:27:57'),
(8, 2, 8, 'FOTO Zavela \'Savršenog\' Tonija, a onda prošla pakao radi filera: Ovako danas izgleda Stankica', 'https://www.24sata.hr/show/foto-zavela-savrsenog-tonija-a-onda-prosla-pakao-radi-filera-ovako-danas-izgleda-stankica-1073004', '2026-01-26 05:55:00', 'Gledatelji su je upoznali u trećoj sezoni RTL-ova showa \'Gospodin Savršeni\', gdje je osvojila srce glavnog junaka Tonija Šćulca. No život Stankice Stojanović nakon realityja nije bio bajka', '2026-01-26 08:27:57'),
(9, 2, 9, 'FOTO Raskošni život najmlađeg sina Donalda Trumpa: Još kao dječaka mazali su ga kavijarom', 'https://www.24sata.hr/news/foto-raskosni-zivot-najmladeg-sina-donalda-trumpa-jos-kao-djecaka-mazali-su-ga-kavijarom-1102735', '2026-01-25 20:03:00', 'Barron je puno vremena provodio i kod bake i djeda iz Slovenije tako da tečno, uz engleski i francuski, priča i slovenski jezik...', '2026-01-26 08:27:57'),
(10, 2, 10, 'FOTO Poze koje izluđuju fanove: Svojom fleksibilnošću mjesečno zarađuje i do 130.000 eura', 'https://www.24sata.hr/lifestyle/foto-poze-koje-izluduju-fanove-svojom-fleksibilnoscu-mjesecno-zaraduje-i-do-130-000-eura-1102361', '2026-01-26 05:58:00', 'Miami Macy nije planirala karijeru u industriji sadržaja za odrasle, no priznaje da je odlučila iskoristiti svoju iznimnu fleksibilnost i pokazati je publici koja je za to spremna platiti', '2026-01-26 08:27:57'),
(11, 1, 1, 'Najveći poraz Osmanlija dogodio se u današnjoj Srbiji. U ovom ratu izgubili su sve', 'https://www.index.hr/vijesti/clanak/najveci-poraz-osmanlija-dogodio-se-u-danasnjoj-srbiji-u-ovom-ratu-izgubili-su-sve/2636342.aspx', '2026-01-26 08:15:29', 'VELIKI turski rat najvažniji je rat za srednju Europu ranog novog vijeka.', '2026-01-26 08:27:59'),
(12, 1, 2, 'Dua Lipa pokazala odvažne modne kombinacije u Cape Townu', 'https://www.index.hr/shopping/clanak/dua-lipa-pokazala-odvazne-modne-kombinacije-u-cape-townu/2753943.aspx', '2026-01-26 08:13:00', 'DUA LIPA je na putovanju u Cape Townu pokazala nekoliko upečatljivih modnih kombinacija, uključujući crvenu vojničku jaknu Ann Demeulemeester, slojevite košulje i masivne čizme.', '2026-01-26 08:27:59'),
(13, 1, 3, 'Terapeutkinja: Ako ste postigli šest stvari, uspješniji ste od prosječnog roditelja', 'https://www.index.hr/mame/clanak/terapeutkinja-ako-ste-postigli-sest-stvari-uspjesniji-ste-od-prosjecnog-roditelja/2753947.aspx', '2026-01-26 08:11:00', 'RODITELJSTVO ne mora značiti stalnu jurnjavu i iscrpljenost, tvrdi terapeutkinja koja objašnjava kako pronaći ravnotežu.', '2026-01-26 08:27:59'),
(14, 1, 4, 'VIDEO Modrić oduševio potezom. Nije riječ o asistenciji', 'https://www.index.hr/sport/clanak/video-modric-odusevio-potezom-nije-rijec-o-asistenciji/2753938.aspx', '2026-01-26 08:09:00', 'LUKA MODRIĆ (40) junak je derbija Rome i Milana (1:1).', '2026-01-26 08:27:59'),
(15, 1, 5, 'Ovaj astrološki tranzit nije viđen od vremena Američkog građanskog rata', 'https://www.index.hr/horoskop/clanak/ovaj-astroloski-tranzit-nije-vidjen-od-vremena-americkog-gradjanskog-rata/2753944.aspx', '2026-01-26 08:08:00', 'NEPTUN 26. siječnja ulazi u Ovna, započinjući 14-godišnji ciklus.', '2026-01-26 08:27:59'),
(16, 1, 6, 'Vučić na sjednici Vlade: \"Šta je bre ovo, ljudi?\"', 'https://www.index.hr/vijesti/clanak/vucic-na-sjednici-vlade-sta-je-bre-ovo-ljudi/2753937.aspx', '2026-01-26 08:05:00', 'SRPSKI predsjednik Aleksandar Vučić sudjelovao je jučer na tematskoj sjednici Vlade Srbije.', '2026-01-26 08:27:59'),
(17, 1, 7, 'Objasnila kako će se zvati ako uzme prezime zaručnika, ljudi umiru od smijeha', 'https://www.index.hr/magazin/clanak/objasnila-kako-ce-se-zvati-ako-uzme-prezime-zarucnika-ljudi-umiru-od-smijeha/2753942.aspx', '2026-01-26 08:02:00', 'HALEY Ivers iz Colorada pokrenula je raspravu na TikToku.', '2026-01-26 08:27:59'),
(18, 1, 8, 'UŽIVO Top transferi: Riješen najbizarniji transfer ovog roka. Ronaldo ide u mirovinu?', 'https://www.index.hr/sport/clanak/uzivo-top-transferi-rijesen-najbizarniji-transfer-ovog-roka-ronaldo-ide-u-mirovinu/2753881.aspx', '2026-01-26 08:01:00', 'NAJZANIMLJIVIJE vijesti i glasine s nogometnog tržišta u zimskom prijelaznom roku pratite uživo u tekstualnom prijenostu na Indexu.', '2026-01-26 08:27:59'),
(19, 1, 9, 'Bizarnost u Kamerunu. Smijenjeni izbornik prima plaću, a novi radi besplatno', 'https://www.index.hr/sport/clanak/bizarnost-u-kamerunu-smijenjeni-izbornik-prima-placu-a-novi-radi-besplatno/2753940.aspx', '2026-01-26 08:00:00', 'SHOW u afričkom nogometu.', '2026-01-26 08:27:59'),
(20, 1, 10, 'Agenti ICE-a ubili dvoje ljudi: Mogu li kazneno odgovarati?', 'https://www.index.hr/vijesti/clanak/agenti-icea-ubili-dvoje-ljudi-mogu-li-kazneno-odgovarati/2753935.aspx', '2026-01-26 07:56:00', 'AGENTI ICE-a ubili su dvoje američkih državljana u Minneapolisu. Službene verzije događaja suprotne su videosnimkama, a kazneni progon agenata otežan je zbog saveznog imuniteta.', '2026-01-26 08:27:59'),
(21, 4, 1, 'Mnogi ga gase čim sjednu u automobil: Koliko je zapravo koristan ovaj sustav u vozilima?', 'https://www.vecernji.hr/barkod/mnogi-ga-gase-cim-sjednu-u-automobil-koliko-je-zapravo-koristan-ovaj-sustav-u-vozilima-1927912', '2026-01-26 08:22:00', 'Podaci proizlaze iz testiranja provedenog 2023. godine na četiri različita vozila u tri standardizirana ciklusa:', '2026-01-26 08:28:04'),
(22, 4, 2, 'NATO gradi \'zonu smrti\' na granici s Rusijom: Evo kako bi trebala izgledati nova linija obrane', 'https://www.vecernji.hr/vijesti/nato-gradi-zonu-smrti-na-granici-s-rusijom-evo-kako-bi-trebala-izgledati-nova-linija-obrane-1927902', '2026-01-26 08:18:00', 'Ključni elementi nove linije odvraćanja uključuju autonomne zemaljske platforme i naoružane dronove, poluautonomna vozila, automatizirane sustave protuzračne i proturaketne obrane, višeslojnu mrežu senzora, fiksne i mobilne radare...', '2026-01-26 08:28:04'),
(23, 4, 3, 'Sigurdsson na ručku otpisao kapetana Hrvatske, a onda je ovaj na sastanku napravio nešto posebno', 'https://www.vecernji.hr/sport/sigurdsson-na-rucku-otpisao-kapetana-hrvatske-a-onda-je-ovaj-na-sastanku-napravio-nesto-posebno-1927904', '2026-01-26 08:15:00', 'U našoj momčadi nalazio se kapetan Ivan Martinović, koji je bio upitan zbog ozljede protiv Islanda i nije se znalo do zadnjeg trenutka hoće li biti spreman za povratak na parket', '2026-01-26 08:28:04'),
(24, 4, 4, 'FOTO Snježna mećava odnosi živote, milijuni ljudi bez struje, a najavljuju još 50 cm novog snijega', 'https://www.vecernji.hr/vijesti/foto-snjezna-mecava-odnosi-zivote-milijuni-ljudi-bez-struje-a-najavljuju-jos-50-cm-novog-snijega-1927911', '2026-01-26 08:14:00', 'Najnovija prognoza Nacionalne meteorološke službe za nedjelju na ponedjeljak ujutro predviđa obilan snijeg od doline rijeke Ohio do sjeveroistoka zemlje, do oko 50 centimetara u Novoj Engleskoj', '2026-01-26 08:28:04'),
(25, 4, 5, 'Lako gube živce: Ovi horoskopski znakovi su najgori vozači', 'https://www.vecernji.hr/lifestyle/lako-gube-zivce-ovi-horoskopski-znakovi-su-najgori-vozaci-1927382', '2026-01-26 08:00:00', '', '2026-01-26 08:28:04'),
(26, 4, 6, 'Sjajni Francuz godinu i pol bio je bez kluba, a sad bi mogao oboriti Messijev rekord', 'https://www.vecernji.hr/sport/sjajni-francuz-godinu-i-pol-bio-je-bez-kluba-a-sad-bi-mogao-oboriti-messijev-rekord-1927881', '2026-01-26 08:00:00', 'Nogometni put počeo je kao šestogodišnjak u lokalnom klubu Hayes and Yeading, a već nakon nekoliko mjeseci Arsenal ga je pozvao u svoju školu nogometa', '2026-01-26 08:28:04'),
(27, 4, 7, 'VIDEO Pogledajte trenutak kada je Jelena Rozga zapjevala uz uličnog svirača: Usred Rima čuo se ovaj veliki hit Magazina', 'https://www.vecernji.hr/showbiz/video-pogledajte-trenutak-kada-je-jelena-rozga-zapjevala-usred-rima-uz-ulicnog-sviraca-1927818', '2026-01-26 08:00:00', 'Jelena ovih dana boravi u Rimu, gdje je iznenadila prolaznike spontanim glazbenim trenutkom na ulici. U opuštenom izdanju, s osmijehom i bez velike pozornice, zapjevala je uz uličnog glazbenika pjesmu \"Sve bi seke ljubile mornare\", a kratki susret brzo je privukao pažnju prolaznika i njezinih pratitelja na društvenim mrežama', '2026-01-26 08:28:04'),
(28, 4, 8, 'Slučaj Picula: Zašto Hrvatska više nema što tražiti u beogradskom političkom cirkusu', 'https://www.vecernji.hr/vijesti/slucaj-picula-zasto-hrvatska-vise-nema-sto-traziti-u-beogradskom-politickom-cirkusu-1927838', '2026-01-26 07:53:00', 'Piculin slučaj ima i pozitivnu stranu: to nam je još jedna politička lekcija i novo podsjećanje koliko je dobra i pametna bila odluka da se oslobodimo Jugoslavije.', '2026-01-26 08:28:04'),
(29, 4, 9, 'Kaos kod Vjesnika: Sudarila se dva vozila, morali preusmjeriti tramvaje', 'https://www.vecernji.hr/zagreb/krs-i-lom-kod-vjesnika-sudarila-se-dva-vozila-morali-preusmjeriti-tramvaje-1927906', '2026-01-26 07:53:00', 'Linija 4 sada ide preko Ljubljanice, dok linije 14 i 17 od Cibone prometuju Vukovarskom do Heinzelove', '2026-01-26 08:28:04'),
(30, 4, 10, 'Hrvatski izbornik ponovno ljubi! U zagrljaju s novom djevojkom proslavio je pobjedu rukometaša', 'https://www.vecernji.hr/sport/hrvatski-izbornik-ponovno-ljubi-u-zagrljaju-s-novom-djevojkom-proslavio-je-pobjedu-rukometasa-1927894', '2026-01-26 07:20:00', 'Hrvatski izbornik Dagur Sigurdsson, inače islandski stručnjak, proslavio je novu pobjedu u Malmo Areni s poljupcima u zagrljaju nove djevojke', '2026-01-26 08:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `rss_items`
--

CREATE TABLE `rss_items` (
  `id` int(11) NOT NULL,
  `source_id` int(11) NOT NULL,
  `title` varchar(500) NOT NULL,
  `link` varchar(1000) NOT NULL,
  `description` text DEFAULT NULL,
  `pub_date` datetime DEFAULT NULL,
  `guid` varchar(500) DEFAULT NULL,
  `image` varchar(1000) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_starred` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rss_sources`
--

CREATE TABLE `rss_sources` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `url` varchar(500) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `last_fetch` datetime DEFAULT NULL,
  `fetch_interval` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rss_sources`
--

INSERT INTO `rss_sources` (`id`, `name`, `url`, `website`, `logo`, `category`, `active`, `last_fetch`, `fetch_interval`, `created_at`) VALUES
(1, 'Večernji list', 'https://www.vecernji.hr/feeds/latest', 'https://www.vecernji.hr', NULL, 'nacional', 1, NULL, 30, '2026-01-14 05:23:13'),
(2, '24sata', 'https://www.24sata.hr/feeds/news.xml', 'https://www.24sata.hr', NULL, 'nacional', 1, NULL, 30, '2026-01-14 05:23:13'),
(3, 'Index.hr', 'https://www.index.hr/rss', 'https://www.index.hr', NULL, 'nacional', 1, NULL, 30, '2026-01-14 05:23:13'),
(4, 'Jutarnji list', 'https://www.jutarnji.hr/feed', 'https://www.jutarnji.hr', NULL, 'nacional', 1, NULL, 30, '2026-01-14 05:23:13'),
(5, 'Net.hr', 'https://net.hr/feed', 'https://net.hr', NULL, 'nacional', 1, NULL, 30, '2026-01-14 05:23:13'),
(6, 'Dnevnik.hr', 'https://dnevnik.hr/assets/feed/articles/', 'https://dnevnik.hr', NULL, 'nacional', 1, NULL, 30, '2026-01-14 05:23:13'),
(7, 'RTL.hr', 'https://www.rtl.hr/feed/', 'https://www.rtl.hr', NULL, 'nacional', 1, NULL, 30, '2026-01-14 05:23:13'),
(8, 'HRT Vijesti', 'https://vijesti.hrt.hr/feed/all', 'https://vijesti.hrt.hr', NULL, 'nacional', 1, NULL, 30, '2026-01-14 05:23:13'),
(9, 'Telegram', 'https://www.telegram.hr/feed/', 'https://www.telegram.hr', NULL, 'nacional', 1, NULL, 30, '2026-01-14 05:23:13'),
(10, 'Sportske novosti', 'https://sportske.jutarnji.hr/feed', 'https://sportske.jutarnji.hr', NULL, 'sport', 1, NULL, 30, '2026-01-14 05:23:13');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_type` enum('morning','afternoon','full') NOT NULL,
  `notes` text DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('pending','in_progress','done','cancelled') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_to`, `created_by`, `priority`, `status`, `due_date`, `due_time`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'Muzeji  - press u Krapini', '', 5, 5, 'high', 'in_progress', '2026-01-14', '11:01:00', NULL, '2026-01-14 06:56:02', '2026-01-14 08:35:36');

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `themes`
--

CREATE TABLE `themes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('vijesti','sport','kultura','gospodarstvo','lifestyle','crna_kronika','politika','lokalno','ostalo') DEFAULT 'vijesti',
  `week_number` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `planned_date` date DEFAULT NULL,
  `status` enum('predlozeno','odobreno','u_izradi','zavrseno','odbijeno') DEFAULT 'predlozeno',
  `priority` enum('niska','normalna','visoka','hitno') DEFAULT 'normalna',
  `proposed_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rejection_reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `themes`
--

INSERT INTO `themes` (`id`, `title`, `description`, `category`, `week_number`, `year`, `planned_date`, `status`, `priority`, `proposed_by`, `approved_by`, `assigned_to`, `notes`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 'gripa hara', 'asdf', 'vijesti', 3, 2026, '2026-01-14', 'odbijeno', 'normalna', 1, 1, NULL, NULL, '', '2026-01-14 06:14:55', '2026-01-14 06:33:36');

-- --------------------------------------------------------

--
-- Table structure for table `theme_comments`
--

CREATE TABLE `theme_comments` (
  `id` int(11) NOT NULL,
  `theme_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcriptions`
--

CREATE TABLE `transcriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `audio_filename` varchar(255) DEFAULT NULL,
  `transcript` longtext DEFAULT NULL,
  `article` longtext DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('novinar','urednik','admin') NOT NULL DEFAULT 'novinar',
  `avatar` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `avatar`, `active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$7lBLDiPS4ueIGjyT/HicWO15lSXxIGt/DPgYHcHaYzAeSXcHOzTgO', 'Administrator', 'admin@portal.hr', NULL, 'admin', NULL, 1, '2026-01-26 09:56:59', '2026-01-14 05:23:13', '2026-01-26 08:56:59'),
(5, 'ivek', '$2y$10$I9ov1VTU8MKSs4s8V0ciy.kUSFY.lvgpWmtoPwPDJBUaq/.woKcxK', 'Ivan Kovačić', 'ivek.privat@gmail.com', '', 'urednik', NULL, 1, '2026-01-26 09:23:21', '2026-01-14 06:17:11', '2026-01-26 08:23:21'),
(6, 'elvis', '$2y$10$qgQBI0WAY8g.mflUcShobu6K6ZMAYkT4R1sh3x3cNMiDqT8Zv.IqS', 'Elvis Lacković', 'lackovicelvis@gmail.com', '', 'novinar', NULL, 1, '2026-01-14 07:20:14', '2026-01-14 06:18:22', '2026-01-14 06:20:14'),
(7, 'jakov', '$2y$10$4dB5j8Ie9RzMDu15wa.oq.grctjR9bVT46/NpHx4RWol3mXF2BEmW', 'Jakov Tuković', 'jakov.tukovic@gmail.com', '', 'novinar', NULL, 1, NULL, '2026-01-14 06:22:05', '2026-01-14 06:22:05'),
(8, 'patrik', '$2y$10$bYbie08.1jXsYwqRd5xHk.3rl7wX2nEB0q1kQlGv/KbgYu.Mq87yC', 'Patrik Jazbec', 'patrikjazbec25@gmail.com', '', 'novinar', NULL, 1, NULL, '2026-01-14 06:23:09', '2026-01-14 06:54:40'),
(9, 'sabina', '$2y$10$pmFsUe9KOZaUCXt6ucBPMOXdalpHi28kWRP8ysHcygDSyL8pesRm6', 'Sabina Pušec', 'sabina.sviben@gmail.com', '', 'novinar', NULL, 1, NULL, '2026-01-14 06:23:47', '2026-01-14 06:23:47'),
(10, 'marta', '$2y$10$/YAQrSK6aZEWp3B.te2SL.XXEYQbWn2lqkCpex9wA8owfAJ3VLQ.S', 'Marta Čaržavec', 'marta.carzavec@gmail.com', '', 'novinar', NULL, 1, '2026-01-14 12:24:19', '2026-01-14 06:24:29', '2026-01-14 11:24:19'),
(11, 'krisitna', '$2y$10$3fey7sZ45IPahkBkxA.Q5.cs/PW6csCi6C9V341uRKEmKeGE3lj/G', 'Kristina Halapir', 'kristina.zagorskilist@gmail.com', '', 'novinar', NULL, 1, NULL, '2026-01-14 09:51:29', '2026-01-14 09:51:29'),
(12, 'anamarija', '$2y$10$0m/uAbGj4MqSjucVC35/MePYxV7GC5w3SGF6U2u9HQBMSe8wmCJyS', 'Anamarija Očko', 'anamarija@zagorski-list.net', '', 'novinar', NULL, 1, NULL, '2026-01-14 09:52:03', '2026-01-14 09:52:03'),
(13, 'rikard', '$2y$10$vwmPHF0qm/dZ.3APqn1iiu73EMBu.Dr2ikOnDlZuAZoOcogqNj5se', 'Rikard Jadan', 'rikard.jadan@gmail.com', '', 'novinar', NULL, 1, NULL, '2026-01-14 11:15:27', '2026-01-14 11:15:27');

-- --------------------------------------------------------

--
-- Table structure for table `zl_articles`
--

CREATE TABLE `zl_articles` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `title` varchar(500) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `author` varchar(200) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `page_number` int(11) DEFAULT NULL,
  `status` enum('draft','review','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zl_article_images`
--

CREATE TABLE `zl_article_images` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zl_issues`
--

CREATE TABLE `zl_issues` (
  `id` int(11) NOT NULL,
  `issue_number` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zl_sections`
--

CREATE TABLE `zl_sections` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

--
-- Dumping data for table `zl_sections`
--

INSERT INTO `zl_sections` (`id`, `name`, `slug`, `sort_order`) VALUES
(1, 'Naslovnica', 'naslovnica', 1),
(2, 'Aktualno', 'aktualno', 2),
(3, 'Politika', 'politika', 3),
(4, 'Gospodarstvo', 'gospodarstvo', 4),
(5, 'Kultura', 'kultura', 5),
(6, 'Sport', 'sport', 6),
(7, 'Crna kronika', 'crna-kronika', 7),
(8, 'Zabava', 'zabava', 8);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_date` (`event_date`),
  ADD KEY `idx_type` (`event_type`);

--
-- Indexes for table `event_assignments`
--
ALTER TABLE `event_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_uploader` (`uploaded_by`);

--
-- Indexes for table `portali`
--
ALTER TABLE `portali`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `portal_najcitanije`
--
ALTER TABLE `portal_najcitanije`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_portal_datum` (`portal_id`,`dohvaceno_at`);

--
-- Indexes for table `rss_items`
--
ALTER TABLE `rss_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_guid` (`source_id`,`guid`(255)),
  ADD KEY `idx_source` (`source_id`),
  ADD KEY `idx_date` (`pub_date`),
  ADD KEY `idx_starred` (`is_starred`);

--
-- Indexes for table `rss_sources`
--
ALTER TABLE `rss_sources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_shift` (`user_id`,`shift_date`,`shift_type`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_date` (`shift_date`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_assigned` (`assigned_to`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due` (`due_date`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposed_by` (`proposed_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_week` (`year`,`week_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `theme_comments`
--
ALTER TABLE `theme_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `theme_id` (`theme_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transcriptions`
--
ALTER TABLE `transcriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `zl_articles`
--
ALTER TABLE `zl_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `zl_article_images`
--
ALTER TABLE `zl_article_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`);

--
-- Indexes for table `zl_issues`
--
ALTER TABLE `zl_issues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `issue_number` (`issue_number`);

--
-- Indexes for table `zl_sections`
--
ALTER TABLE `zl_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `event_assignments`
--
ALTER TABLE `event_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `photos`
--
ALTER TABLE `photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `portali`
--
ALTER TABLE `portali`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `portal_najcitanije`
--
ALTER TABLE `portal_najcitanije`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `rss_items`
--
ALTER TABLE `rss_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rss_sources`
--
ALTER TABLE `rss_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `themes`
--
ALTER TABLE `themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `theme_comments`
--
ALTER TABLE `theme_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transcriptions`
--
ALTER TABLE `transcriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `zl_articles`
--
ALTER TABLE `zl_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zl_article_images`
--
ALTER TABLE `zl_article_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zl_issues`
--
ALTER TABLE `zl_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zl_sections`
--
ALTER TABLE `zl_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_assignments`
--
ALTER TABLE `event_assignments`
  ADD CONSTRAINT `event_assignments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `photos`
--
ALTER TABLE `photos`
  ADD CONSTRAINT `photos_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `photos_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `portal_najcitanije`
--
ALTER TABLE `portal_najcitanije`
  ADD CONSTRAINT `portal_najcitanije_ibfk_1` FOREIGN KEY (`portal_id`) REFERENCES `portali` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rss_items`
--
ALTER TABLE `rss_items`
  ADD CONSTRAINT `rss_items_ibfk_1` FOREIGN KEY (`source_id`) REFERENCES `rss_sources` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shifts`
--
ALTER TABLE `shifts`
  ADD CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shifts_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `themes`
--
ALTER TABLE `themes`
  ADD CONSTRAINT `themes_ibfk_1` FOREIGN KEY (`proposed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `themes_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `themes_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `theme_comments`
--
ALTER TABLE `theme_comments`
  ADD CONSTRAINT `theme_comments_ibfk_1` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `theme_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transcriptions`
--
ALTER TABLE `transcriptions`
  ADD CONSTRAINT `transcriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `zl_articles`
--
ALTER TABLE `zl_articles`
  ADD CONSTRAINT `zl_articles_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `zl_issues` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `zl_articles_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `zl_sections` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `zl_article_images`
--
ALTER TABLE `zl_article_images`
  ADD CONSTRAINT `zl_article_images_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `zl_articles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
