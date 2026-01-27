-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 26, 2026 at 09:27 AM
-- Server version: 11.4.9-MariaDB-ubu2204
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `deskcrm`
--
CREATE DATABASE IF NOT EXISTS `deskcrm` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `deskcrm`;

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
(157, 5, 'login', 'user', 5, NULL, '92.242.240.96', '2026-01-15 15:37:37'),
(158, 5, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-15 15:38:05'),
(159, 5, 'logout', 'user', 5, NULL, '92.242.240.96', '2026-01-15 15:38:37'),
(160, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-15 15:43:07'),
(161, 1, 'logout', 'user', 1, NULL, '92.242.240.96', '2026-01-16 11:42:28'),
(162, 5, 'login', 'user', 5, NULL, '92.242.240.96', '2026-01-16 11:42:35'),
(163, 5, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-16 12:14:05'),
(164, 5, 'logout', 'user', 5, NULL, '92.242.240.96', '2026-01-16 14:43:13'),
(165, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-16 14:44:43'),
(166, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-16 16:09:12'),
(167, 1, 'login', 'user', 1, NULL, '104.28.114.7', '2026-01-18 08:50:24'),
(168, 1, 'ai_image_generate', 'ai', NULL, NULL, '104.28.114.7', '2026-01-18 08:53:52'),
(169, 1, 'ai_image_generate', 'ai', NULL, NULL, '104.28.114.7', '2026-01-18 08:54:18'),
(170, 1, 'ai_image_generate', 'ai', NULL, NULL, '104.28.114.7', '2026-01-18 08:54:49'),
(171, 1, 'ai_image_generate', 'ai', NULL, NULL, '104.28.114.7', '2026-01-18 08:55:41'),
(172, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-18 09:57:29'),
(173, 5, 'login', 'user', 5, NULL, '104.28.130.19', '2026-01-18 12:14:54'),
(174, 5, 'logout', 'user', 5, NULL, '104.28.130.19', '2026-01-18 12:16:10'),
(175, 5, 'login', 'user', 5, NULL, '104.28.130.20', '2026-01-18 12:20:41'),
(176, 5, 'login', 'user', 5, NULL, '172.225.38.109', '2026-01-18 14:17:42'),
(177, 1, 'logout', 'user', 1, NULL, '92.242.240.96', '2026-01-19 09:54:21'),
(178, 1, 'login', 'user', 1, NULL, '92.242.240.96', '2026-01-19 09:54:29'),
(179, 1, 'audio_transcribe', 'ai', NULL, NULL, '92.242.240.96', '2026-01-19 09:58:18'),
(180, 1, 'ai_image_generate', 'ai', NULL, NULL, '92.242.240.96', '2026-01-19 10:33:01'),
(181, 1, 'login', 'user', 1, NULL, '104.28.130.19', '2026-01-20 04:47:48'),
(182, 1, 'login', 'user', 1, NULL, '104.23.162.44', '2026-01-26 08:18:56');

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
(64, 'Muzeji Krapina', '', '', '2026-01-14', '11:00:00', 0, NULL, NULL, 'press', 'normal', 1, '', 0, '2026-01-14 11:23:02', '2026-01-14 11:23:02');

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
(65, 64, 11, 'reporter', 0, NULL, 1, '2026-01-14 11:23:02');

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
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portali`
--

INSERT INTO `portali` (`id`, `naziv`, `url`, `rss_url`, `aktivan`, `created_at`) VALUES
(1, 'Index.hr', 'https://www.index.hr', 'https://www.index.hr/rss', 1, '2026-01-16 12:13:32'),
(2, '24sata', 'https://www.24sata.hr', 'https://www.24sata.hr/feeds/aktualno.xml', 1, '2026-01-16 12:13:32'),
(3, 'Jutarnji list', 'https://www.jutarnji.hr', 'https://www.jutarnji.hr/feed', 1, '2026-01-16 12:13:32'),
(4, 'Večernji list', 'https://www.vecernji.hr', 'https://www.vecernji.hr/feeds/latest', 1, '2026-01-16 12:13:32');

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
(1, 2, 1, 'Evo što dolazi s istoka: Ledeni val prijeti, opet će biti minus 10!', 'https://www.24sata.hr/news/evo-sto-dolazi-s-istoka-ledeni-val-prijeti-opet-ce-biti-minus-10-1101434', '2026-01-16 12:23:00', 'Dok će Zagreb i Osijek zahvatiti jaki minusi, Gospić će se naći u samom središtu hladnoće, a Split i Rijeka zadržat će blaže temperature, uz čestu buru', '2026-01-16 13:13:36'),
(2, 2, 2, 'UŽIVO Rijeka doznaje suparnika u play-offu Konferencijske lige', 'https://www.24sata.hr/sport/rijeka-danas-doznaje-protivnika-u-play-offu-konferencijske-lige-evo-gdje-gledati-izvlacenje-1101380', '2026-01-16 13:06:00', 'Kako je ždrijeb dirigiran, već se zna da će Rijeka danas izvući Omoniju ili Jagielloniju. Ciprani su natjecanje završili na 18 mjestu, a Poljaci na 17.', '2026-01-16 13:13:36'),
(3, 2, 3, 'Bivši model Christie Brinkley ima vrhunsku liniju u 72. godini: Pokazala je figuru u bikiniju', 'https://www.24sata.hr/show/bivsi-model-christie-brinkley-ima-vrhunsku-liniju-u-72-godini-pokazala-je-figuru-u-bikiniju-1101452', '2026-01-16 13:05:00', 'Christie je supermodel bila još sedamdesetih godina, a pojavila se na naslovnicama brojnih modnih časopisa. Nosila je revije poznatih dizajnera i proglašena jednom od najljepših žena svijeta', '2026-01-16 13:13:36'),
(4, 2, 4, 'Alarm na Rebru! Hitni prijem pred kolapsom: \'Ljudi su po hodnicima, radnici opterećeni...\'', 'https://www.24sata.hr/news/alarm-na-rebru-hitni-prijem-pred-kolapsom-ljudi-su-po-hodnicima-radnici-optereceni-1101473', '2026-01-16 13:08:00', 'Loša organizacija ugrožava pacijente i opterećuje osoblje. Sindikat traži hitne mjere, a bolnica tvrdi da radi na poboljšanjima...', '2026-01-16 13:13:36'),
(5, 2, 5, 'Pjesme za Doru skupile milijune pregleda: Ovo je najslušanija', 'https://www.24sata.hr/show/pjesme-za-doru-objavljene-su-prije-tjedan-dana-evo-koja-je-najslusanija-na-youtubeu-1101429', '2026-01-16 11:46:00', 'Veliko zanimanje publike potvrđuje i podatak kako je jedna od pjesama Dore 2026. prva na YouTube music trendingu, dok je još jedna među prvih pet pjesama', '2026-01-16 13:13:36'),
(6, 2, 6, 'Širi se snimka. Evo kako novi dinamovac pjeva Thompsona', 'https://www.24sata.hr/sport/siri-se-snimka-evo-kako-novi-dinamovac-pjeva-thompsona-1101444', '2026-01-16 11:37:00', 'Pjesmu \"Pukni, puško\" popularizirao je Mateo Kovačić, koji je na dočeku i proslavi nakon osvajanja svjetskog srebra zamolio publiku da je otpjeva zajedno s njim', '2026-01-16 13:13:36'),
(7, 2, 7, 'Svodnik iz Venezuele pokušao pobjeći. U Splitu organizirao lanac prostitucije  s pet žena', 'https://www.24sata.hr/news/svodnik-iz-venezuele-pokusao-pobjeci-u-splitu-organizirao-lanac-prostitucije-s-pet-zena-1101465', '2026-01-16 13:02:00', 'U službene prostorije dovedene su i žrtve kaznenog djela U sklopu kriminalističkog istraživanja pronađeno je i oduzeto  9.660 eura.', '2026-01-16 13:13:36'),
(8, 2, 8, 'Sutra je veliko finale \'The Voice Kids\': Glasajte za favorita i tako sudjelujte u humanitarnoj akciji', 'https://www.24sata.hr/show/sutra-je-veliko-finale-the-voice-kids-glasajte-za-favorita-i-tako-sudjelujte-u-humanitarnoj-akciji-1101439', '2026-01-16 12:38:00', 'Vaš poziv nije samo glas za vama najbolju ili najboljeg nego i način kako pomoći malim pacijentima Specijalne bolnice za kronične bolesti dječje dobi Gornja Bistra, koji trebaju medicinsku pomoć 24 sata dnevno', '2026-01-16 13:13:36'),
(9, 2, 9, 'FOTO \'Vatreni\' ju je zaprosio i dugo skrivao od javnosti: Čekao sam tri mjeseca da mi odgovori', 'https://www.24sata.hr/sport/foto-vatreni-ju-je-zaprosio-i-dugo-skrivao-od-javnosti-cekao-sam-tri-mjeseca-da-mi-odgovori-1101395', '2026-01-16 09:40:00', 'Nogometaš Kristijan Jakić zaručio se s vizažisticom Lucijom Jelić. Par je dugo skrivao vezu, a sada je planiraju okruniti brakom: \"Priznajem da sam u početku bio malo nervozan\", kazao je o vezi', '2026-01-16 13:13:36'),
(10, 2, 10, 'Čak sedam puta tražila ljubav: Rekorderku iz \'Ljubav je na selu\' danas mnogi ne bi prepoznali', 'https://www.24sata.hr/show/cak-sedam-puta-trazila-ljubav-rekorderku-iz-ljubav-je-na-selu-danas-mnogi-ne-bi-prepoznali-1101149', '2026-01-16 06:00:00', 'Ines Petrić gledali smo u showu \'Ljubav je na selu\', a posljednji je put bila na farmi Radoslava Biskupovića. Inače je frizerka i vizažistica s dugim iskustvom', '2026-01-16 13:13:36'),
(11, 1, 1, 'UŽIVO Rijeka doznaje protivnika u play-offu Konferencijske lige', 'https://www.index.hr/sport/clanak/uzivo-rijeka-doznaje-protivnika-u-playoffu-konferencijske-lige/2750659.aspx', '2026-01-16 13:00:00', 'UTAKMICE play-off runde igrat će se 19. i 26. veljače.', '2026-01-16 13:13:38'),
(12, 1, 2, 'Firma najpoznatijeg hrvatskog uvoznika luksuzne hrane pred stečajem', 'https://www.index.hr/vijesti/clanak/firma-najpoznatijeg-hrvatskog-uvoznika-luksuzne-hrane-pred-stecajem/2750665.aspx', '2026-01-16 12:58:00', 'TRGOVAČKI sud u Zagrebu zaprimio je prijedlog za otvaranje stečaja Selekcije MM, tvrtke Marija Mendeka, najpoznatijeg hrvatskog uvoznika luksuzne hrane i vrhunskih vina.', '2026-01-16 13:13:38'),
(13, 1, 3, '\"Bio je to masakr\": Robina nije preživjela krvavu noć u Iranu', 'https://www.index.hr/vijesti/clanak/bio-je-to-masakr-robina-nije-prezivjela-krvavu-noc-u-iranu/2750617.aspx', '2026-01-16 12:58:00', 'NAKON gašenja interneta 8. siječnja, iranske snage sigurnosti pokrenule su nasilno gušenje prosvjeda. U masakru diljem zemlje ubijene su stotine, a među žrtvama je i studentica Robina Aminian.', '2026-01-16 13:13:38'),
(14, 1, 4, 'UŽIVO Top transferi: Preokret i razočaranje nakon Džekinog sastanka u Zagrebu', 'https://www.index.hr/sport/clanak/uzivo-top-transferi-preokret-i-razocaranje-nakon-dzekinog-sastanka-u-zagrebu/2750491.aspx', '2026-01-16 12:58:00', 'NAJZANIMLJIVIJE vijesti i glasine u zimskom prijelaznom roku na nogometnom tržištu pratite uživo u tekstualnom prijenosu na Indexu.', '2026-01-16 13:13:38'),
(15, 1, 5, 'UŽIVO HR transferi: Hajduk želi igrača koji je jučer zabio Dinamu?', 'https://www.index.hr/sport/clanak/uzivo-hr-transferi-hajduk-zeli-igraca-koji-je-jucer-zabio-dinamu/2748943.aspx', '2026-01-16 12:56:00', 'NAJZANIMLJIVIJE vijesti s hrvatskog nogometnog tržišta pratite uživo na Indexu.', '2026-01-16 13:13:38'),
(16, 1, 6, 'Kava duplo skuplja, ulje gotovo isto: Kako su se promijenile cijene u 10 godina', 'https://www.index.hr/vijesti/clanak/kava-duplo-skuplja-ulje-gotovo-isto-kako-su-se-promijenile-cijene-u-10-godina/2750546.aspx', '2026-01-16 12:53:00', 'USPOREDILI smo kataloške cijene prehrambenih proizvoda iz siječnja 2015. i siječnja 2025., iz čega je jasno da poskupljenje nije jednako zahvatilio sve proizvode. Neke su stvari jako poskupjele, dok su druge ostale gotovo na istoj razini - ili su čak pojeftinile.', '2026-01-16 13:13:38'),
(17, 1, 7, 'Vijetnam gradi prvu tvornicu čipova', 'https://www.index.hr/vijesti/clanak/vijetnam-gradi-prvu-tvornicu-cipova/2750666.aspx', '2026-01-16 12:52:00', 'DRŽAVNA telekomunikacijska kompanija Viettel krenula je s gradnjom prvog pogona za proizvodnju čipova u Vijetnamu, u okviru šireg plana Hanoia o uspostavi vlastitog sustava proizvodnje poluvodiča.', '2026-01-16 13:13:38'),
(18, 1, 8, 'Mostovac: Nedostaje obvezan registar agresora', 'https://www.index.hr/vijesti/clanak/mostovac-nedostaje-obvezan-registar-agresora/2750662.aspx', '2026-01-16 12:49:00', 'SABORSKI zastupnici danas su pozdravili Vladin prijedlog da se izmjeni Zakon o civilnim stradalnicima iz Domovinskog rata i unaprijedi sustav skrbi o njima.', '2026-01-16 13:13:38'),
(19, 1, 9, 'Pierre-Gabrielu propao jedan transfer, a otvorila se prilika za drugi?', 'https://www.index.hr/sport/clanak/pierregabrielu-propao-jedan-transfer-a-otvorila-se-prilika-za-drugi/2750658.aspx', '2026-01-16 12:49:00', 'BRANIČ Dinama je na odlasku.', '2026-01-16 13:13:38'),
(20, 1, 10, 'VIDEO Vozili smo se taksijima po Zagrebu. Nismo imali niti jedno neugodno iskustvo', 'https://www.index.hr/vijesti/clanak/video-vozili-smo-se-taksijima-po-zagrebu-nismo-imali-niti-jedno-neugodno-iskustvo/2727151.aspx', '2026-01-16 12:48:00', 'JESU li taksi vožnje lutrija, funkcioniraju po principu \"na koga naiđeš\", ili je razlika između domaćih i stranih taksista u Zagrebu stvarno toliko velika?', '2026-01-16 13:13:38'),
(21, 3, 1, 'Policija u Plaškom pronašla kućni pogon za proizvodnju marihuane: Uhitili jednog muškarca, drugi u bijegu', 'https://www.jutarnji.hr/vijesti/crna-kronika/plaski-otkriven-laboratorij-za-marihuanu-uhicen-28-godisnjak-traga-se-za-31-godisnjakom-15662345', '2026-01-16 13:10:25', 'Ogulinski policajci jučer su na području Plaškog u dvije kuće pronašli mali pogon za proizvodnju droge cannabis marihuane, odnosno opremu za pakiranje i prodaju. Korisnici kuća su 28-godišnjak, koji je uhićen, te 31-godišnjak za kojim policija još uvijek traga. - Tom prilikom u kući 28-godišnjaka pr...', '2026-01-16 13:13:40'),
(22, 3, 2, 'Najpoznatiji hrvatski uvoznik luksuzne hrane pred stečajem zbog 51.000 eura duga', 'https://www.jutarnji.hr/vijesti/hrvatska/najpoznatiji-hrvatski-uvoznik-luksuzne-hrane-pred-stecajem-zbog-51-000-eura-duga-15662340', '2026-01-16 13:05:00', 'Trgovački sud u Zagrebu zaprimio je prijedlog za otvaranje stečaja Selekcije MM, tvrtke Marija Mendeka – najpoznatijeg hrvatskog uvoznika luksuzne hrane i vrhunskih vina, zbog neizvršenih osnova za plaćanje od 51.713 eura. Prijedlog je podnijela Financijska agencija (Fina), a uslijedio je nakon što...', '2026-01-16 13:13:40'),
(23, 3, 3, 'Sindikat upozorava: Hitni prijem u najvećoj hrvatskoj bolnici je pred kolapsom', 'https://www.jutarnji.hr/vijesti/hrvatska/rebro-hitna-pred-kolapsom-sindikat-upozorava-uprava-najavljuje-strateski-projekt-15662337', '2026-01-16 12:57:22', 'Sindikat KBC-a Zagreb upozorio je u petak da je hitni prijem na Rebru pred kolapsom zbog loše organizacije i preopterećenosti osoblja koja ugrožava sigurnost pacijenata, na što su iz bolničke uprave poručili da im je unaprjeđenje hitnog bolničkog prijema strateški projekt. OHBP KBC-a Zagreb ne može...', '2026-01-16 13:13:40'),
(24, 3, 4, 'Maša je apsolutna senzacija. Ima osam godina, a ugošćuje zvijezde u svojoj emisiji: ‘Želim da mi dođe i Luka Modrić‘', 'https://www.jutarnji.hr/scena/domace-zvijezde/masa-gavranic-najmladi-radijski-glas-nominacija-zlatni-studio-15662334', '2026-01-16 12:50:00', '\"Učiteljica u školi je svima rekla da odmah glasaju za mene!\", kaže Maša Gavranić, najmlađi radijski glas u Hrvatskoj, koja je ove godine nominirana i za najbolji radijski glas za nagradu Zlatni Studio. Preslatka Maša ima tek osam godina, a iza sebe ima brojne intervjue s poznatim zvijezdama te već...', '2026-01-16 13:13:40'),
(25, 3, 5, 'U igri za veliki energetski projekt američki građevinski div, ali i tvrtka koja se povezuje s Trumpom; Guardian: Imaju snažno političko zaleđe', 'https://www.jutarnji.hr/vijesti/svijet/bechtel-juzna-interkonekcija-plinovod-bih-hrvatska-lng-krk-15662328', '2026-01-16 12:28:00', 'Velika američka građevinska tvrtka Bechtel među potencijalnim je partnerima za gradnju plinovoda koji bi povezao plinske mreže Hrvatske i BiH, a njezini predstavnici od četvrtka borave u Sarajevu kako bi ondje ispitali uvjete mogućeg angažmana na tom projektu, za što zanimanje pokazuju i druge tvrtk...', '2026-01-16 13:13:40'),
(26, 3, 6, 'Ministar Habijan se ogradio od Dropulića, fitness trener promijenio priču', 'https://www.jutarnji.hr/vijesti/hrvatska/habijan-dropulic-sporna-fotografija-trener-presuda-nasilje-skandal-15662320', '2026-01-16 12:03:00', 'Drama oko navodnog osobnog trenera ministra pravosuđa Damira Habijana dobila je novi obrat. O treneru Filipu Dropuliću krenulo se pisati nakon što se internetom proširila fotografija na kojoj pozira s Habijanom na božićnom domjenku u Ministarstvu 2024. godine, što je problematično jer je Dropulić 20...', '2026-01-16 13:13:40'),
(27, 3, 7, 'Bugarska će održati prijevremene izbore', 'https://www.jutarnji.hr/vijesti/svijet/bugarska-prijevremeni-izbori-rumen-radev-odbijeni-mandati-prosvjedi-15662315', '2026-01-16 11:47:58', 'Bugarski predsjednik Rumen Radev izjavio je u petak da će zemlja održati prijevremene izbore pošto su vodeće stranke odbile mandat za formiranje vlade nakon što je prethodna administracija prošlog mjeseca odstupila usred raširenih prosvjeda. Radev je u petak ponudio Savezu za prava i slobode posljed...', '2026-01-16 13:13:40'),
(28, 3, 8, 'Udruga Glas poduzetnika traži sastanak s ministrom financija zbog velikih problema u Fiskalizaciji 2.0', 'https://www.jutarnji.hr/vijesti/hrvatska/ugp-trazi-sastanak-s-markom-primorcem-zbog-fiskalizacije-2-0-i-peticije-6000-potpisa-15662310', '2026-01-16 11:39:41', 'Udruga Glas poduzetnika (UGP) u petak je objavila da traži sastanak s potpredsjednikom Vlade i ministrom financija Markom Primorcem kako bi mu izravno iznijeli probleme malih i srednjih poduzetnika vezano uz probleme u Fiskalizaciji 2.0 te uručili peticiju s više od 6.000 potpisa. Iz UGP-a upozorava...', '2026-01-16 13:13:40'),
(29, 3, 9, 'Oglasio se legendarni Julio Iglesias: Poričem da sam seksualno zlostavljao, prisiljavao ili omalovažavao bilo koju ženu!', 'https://www.jutarnji.hr/scena/strane-zvijezde/oglasio-se-legendarni-julio-iglesias-poricem-da-sam-seksualno-zlostavljao-prisiljavao-ili-omalovazavao-bilo-koju-zenu-15662309', '2026-01-16 11:37:44', 'Španjolski pjevač Julio Iglesias negirao je zlostavljanje dviju bivših kućnih pomoćnica koje su protiv njega podnijele kaznenu prijavu, opisujući optužbe kao lažne, u objavi na društvenim mrežama kasno u četvrtak. Tužiteljstvo španjolskog Visokog suda priopćilo je da je pokrenulo preliminarni postup...', '2026-01-16 13:13:40'),
(30, 3, 10, 'Miroljub pijan vrijeđao Hrvate, pa se na bicikliste zaletio Passatom, ozlijeđena žena: ‘Unakažena sam‘; Tužiteljstvo: ‘Kazna je preblaga‘', 'https://www.jutarnji.hr/vijesti/crna-kronika/karlovacko-tuziteljstvo-trazi-strozu-kaznu-za-incident-s-biciklistima-u-vojnicu-15662303', '2026-01-16 11:23:00', 'Karlovački tužitelji nisu zadovoljni visinom kazne na koju je nedavno osuđen automehaničar Miroljub K. (45), otac troje djece koji se u lipnju 2024. pijan u Vojišnici zaletio u bicikliste iz netrpeljivosti prema osobama hrvatske nacionalnosti. Prema presudi Općinskog suda u Karlovcu, Miroljub K. je...', '2026-01-16 13:13:40'),
(31, 4, 1, 'Sanela Pliško tužila Peđu Grbina zbog \"u cijelosti nepravilne i nezakonite\" smjene', 'https://www.vecernji.hr/kultura/sanela-plisko-tuzila-pedu-grbina-zbog-u-cijelosti-nepravilne-i-nezakonite-smjene-1925152', '2026-01-16 13:10:00', 'Istovremeno, Ministarstvo kulture i medija tražilo je očitovanje od Grada Pule, te najavilo nadzor zakonitosti rada Istarskog narodnog kazališta', '2026-01-16 13:13:41'),
(32, 4, 2, 'Ryanair iz Zagreba ukida još ove tri linije?', 'https://www.vecernji.hr/zagreb/ryanair-iz-zagreba-ukida-jos-ove-tri-linije-1925153', '2026-01-16 13:09:00', 'Prošlog rujna privremeno je ugašeno ukupno 11 linija.', '2026-01-16 13:13:41'),
(33, 4, 3, 'Na prvom koncertu ciklusa \'Foyer fortissimo\' nastupit će violončelist Luka Galuf, gitarist Silvio Bilić i sopranistica Marija Salečić', 'https://www.vecernji.hr/kultura/na-prvom-koncertu-ciklusa-foyer-fortissimo-nastupit-ce-violoncelist-luka-galuf-gitarist-silvio-bilic-i-sopranistica-marija-salecic-1925151', '2026-01-16 13:07:00', 'Ciklus \"Foyer fortissimo\" naslanja se na tradiciju Ciklusa mladih glazbenika \"mo. Vinko Lesić\", koji se u Splitu održava već 14 godina', '2026-01-16 13:13:41'),
(34, 4, 4, 'Ruski nacionalist poslao jezivu poruku Europi: \'Ako Amerika krene u rat, klečat ćete pred Moskvom\'', 'https://www.vecernji.hr/vijesti/ruski-nacionalist-poslao-jezivu-poruku-europi-ako-amerika-krene-u-rat-klecat-cete-pred-moskvom-1925149', '2026-01-16 13:03:00', 'Najprovokativniji dio njegove poruke odnosi se na budućnost Europe u slučaju izravnog sukoba između Rusije i SAD-a', '2026-01-16 13:13:41'),
(35, 4, 5, 'Što je najviše poskupjelo u Hrvatskoj od 1. siječnja? \'Ne brinemo, znamo da će biti samo gore\'', 'https://www.vecernji.hr/barkod/sto-je-najvise-poskupjelo-u-hrvatskoj-od-1-sijecnja-ne-brinemo-znamo-da-ce-biti-samo-gore-1925091', '2026-01-16 13:00:00', 'Jedan od zanimljivijih pogleda na uzroke poskupljenja ponudio je korisnik koji problem vidi u nesrazmjeru ponude i potražnje na tržištu rada', '2026-01-16 13:13:41'),
(36, 4, 6, 'Obitelj konobarice koja je poginula u požaru bjesna. Vlasnica je lagala, ovo je istina', 'https://www.vecernji.hr/vijesti/smrtonosni-pozar-u-crans-montani-obitelj-tvrdi-da-je-djevojka-bila-u-radnom-sporu-1925147', '2026-01-16 13:00:00', 'Odvjetnica obitelji Panine, Sophie Haenni, navela je da je Cyane imala ozbiljan sukob s vlasnicima zbog radnih uvjeta. Prema njezinim riječima, mlada konobarica obratila se službama za zaštitu radnika tražeći ugovor o radu, potvrde o zaposlenju i isplatne liste plaće, na što je imala zakonsko pravo', '2026-01-16 13:13:41'),
(37, 4, 7, 'Ne zna desna što radi lijeva: gdje si bila 2026. kad je gorjelo u Iranu?', 'https://www.vecernji.hr/vijesti/ne-zna-desna-sto-radi-lijeva-gdje-si-bila-2026-kad-je-gorjelo-u-iranu-1924945', '2026-01-16 12:54:00', 'Pa, kako se dogodilo da ljevičari – glasni kada valja osuditi američki napad na Venezuelu ili izraelski na Pojas Gaze – šute kada su ugroženi iranski prosvjednici na ulicama Teherana?', '2026-01-16 13:13:41'),
(38, 4, 8, 'Tužna vijest: Preminuo najstariji zadarski košarkaš: \'Hvala vam na svemu...\'', 'https://www.vecernji.hr/sport/tuzna-vijest-preminuo-najstariji-zadarski-kosarkas-hvala-vam-na-svemu-1925142', '2026-01-16 12:44:00', 'U 92. godini života u dalekom Čileu preminuo je Benito Maneta Maršan, posljednji izdanak generacije koja je udarila temelje Zadra kao grada košarke. Njegovo neprocjenjivo svjedočanstvo o pionirskim danima ostat će zauvijek sačuvano', '2026-01-16 13:13:41'),
(39, 4, 9, 'Sindikat: Hitna na Rebru je pred kolapsom, životno ugroženi pacijenti zbrinjavaju se u hodnicima', 'https://www.vecernji.hr/zagreb/sindikat-hitna-na-rebru-je-pred-kolapsom-zivotno-ugrozeni-pacijenti-zbrinjavaju-se-u-hodnicima-1925141', '2026-01-16 12:42:00', 'OHBP KBC Zagreb ne može i ne smije ostati mjesto na kojem se slijevaju svi propusti zdravstvenog i socijalnog sustava – od palijativne skrbi, socijalnih problema i neorganiziranog sanitetskog prijevoza, do nefunkcionalne hitne medicine na razini Grada Zagreba\", poručuje sindikat.', '2026-01-16 13:13:41'),
(40, 4, 10, 'VIDEO Black Hawk srušio se tijekom zračnog prijevoza: Snimljen pad izraelskog vojnog helikoptera', 'https://www.vecernji.hr/vijesti/video-black-hawk-srusio-se-tijekom-zracnog-prijevoza-snimljen-pad-izraelskog-vojnog-helikoptera-1925138', '2026-01-16 12:39:00', 'Jutros je helikopter CH-53 stigao kako bi ga prevezao na popravak u bazi', '2026-01-16 13:13:41'),
(41, 2, 1, 'Jezik opsade s Kaptola: Zašto novi laički pokret nastupa kao da je počeo posljednji rat?', 'https://www.24sata.hr/news/jezik-opsade-s-kaptola-zasto-novi-laicki-pokret-nastupa-kao-da-je-poceo-posljednji-rat-1101743', '2026-01-18 09:00:00', 'U prvom redu sjedili su prepoznatljivi akteri političkog katolicizma: Željka Markić, Vice Batarelo i predstavnik Glasa Koncila. Već ta scenografija govori više od najave: vjera, država i politika stavljene su u isti kadar', '2026-01-18 09:51:04'),
(42, 2, 2, 'Meteorolozi izdali upozorenje za ovaj dio Hrvatske: Hladni val! Stižu nam minusi', 'https://www.24sata.hr/news/meteorolozi-izdali-upozorenje-za-ovaj-dio-hrvatske-hladni-val-stizu-nam-minusi-1101741', '2026-01-18 08:02:00', 'Na većem dijelu Jadrana umjerena do jaka bura, lokalno s olujnim udarima, na jugu i umjeren istočnjak, a prema otvorenom moru i jugo', '2026-01-18 09:51:04'),
(43, 2, 3, '100 fotografija totalnog kaosa: Prosvjednici rastjerali krajnje desničare u Minneapolisu', 'https://www.24sata.hr/news/100-fotografija-totalnog-kaosa-prosvjednici-rastjerali-krajnje-desnicare-u-minneapolisu-1101749', '2026-01-18 09:13:00', 'Stotine prosvjednika protiv američke službe za imigraciju i carinu (ICE) rastjeralo je manju skupinu krajnjih desničara u Minneapolisu koji su u subotu namjeravali otići u četvrt u kojoj žive mnogi imigranti', '2026-01-18 09:51:04'),
(44, 2, 4, 'FOTO Vruće fotke: Paparazzi seksi sestre ulovili na plaži. Zaigrale su se na pijesku...', 'https://www.24sata.hr/fun/foto-vruce-fotke-paparazzi-seksi-sestre-ulovili-na-plazi-zaigrale-su-se-na-pijesku-1100948', '2026-01-18 09:00:00', 'Manekenka Brooks Nader boravila je u Cabo San Lucasu na odmoru gdje joj se pridružila mlađa sestra Sarah Jane. Sestre su uživale u suncu i opuštenoj atmosferi uz plažu...', '2026-01-18 09:51:04'),
(45, 2, 5, 'FOTO Njihova najveća podrška: Koga ljube naši rukometaši?', 'https://www.24sata.hr/sport/foto-njihova-najveca-podrska-koga-ljube-nasi-rukometasi-1101467', '2026-01-18 06:00:00', 'Hrvatska rukometna reprezentacija pobijedila je Gruziju na otvaranju Eura, a u pohodu na jedino zlato koje im nedostaje najglasnije navijačice su njihove bolje polovice. Neki su u vezi već nekoliko godina', '2026-01-18 09:51:04'),
(46, 2, 6, 'Malena Nikol (9) osvojila je srca publike u dječjem \'Voiceu\'. Pala je i suza: \'Srce mi je zadrhtalo\'', 'https://www.24sata.hr/show/malena-nikol-9-osvojila-je-srca-publike-u-djecjem-voiceu-pala-je-i-suza-srce-mi-je-zadrhtalo-1101739', '2026-01-18 08:42:00', 'Devetogodišnja pjevačica iz Svetog Martina na Muri u velikom je finalu osvojila najviše glasova publike i ponijela titulu pobjednice druge sezone popularnog glazbenog showa', '2026-01-18 09:51:04'),
(47, 2, 7, 'Anamarija (20) donirala sestri (43) matične stanice i spasila je od leukemije: \'Dar je od Boga!\'', 'https://www.24sata.hr/news/anamarija-20-donirala-sestri-43-maticne-stanice-i-spasila-je-od-leukemije-dar-je-od-boga-1101736', '2026-01-18 07:00:00', 'Martini (43) su dijagnosticirali leukemiju * Njezina sestra Anamarija (20) donirala joj je matične stanice * Znala sam da je poslana od Boga, rekla je Martina', '2026-01-18 09:51:04'),
(48, 2, 8, 'FOTO Dunav okovan ledom, stigla pomoć  iz Mađarske: Evo kako je to izgledalo 2017.', 'https://www.24sata.hr/news/foto-dunav-okovan-ledom-stigla-pomoc-iz-madarske-evo-kako-je-to-izgledalo-2017-1101747', '2026-01-18 08:27:00', 'Sve to dovelo je do toga da je površina Dunava, nekih 11 kilometara dužine i širine od obale do obale, bila smrznuta, kazali su tad iz centra za obranu od poplava...', '2026-01-18 09:51:04'),
(49, 2, 9, 'FOTO Zgodna, plavokosa i domaćica: \'Svi misle da mi je super, ali ja sam iscrpljena\'', 'https://www.24sata.hr/lifestyle/foto-zgodna-plavokosa-i-domacica-svi-misle-da-mi-je-super-ali-ja-sam-iscrpljena-1101246', '2026-01-18 06:01:00', 'U svijetu društvenih mreža i influencer kulture često se stvara idealizirana slika spoja luksuza, slobodnog vremena i \'bezbrižnog\' života. No 23-godišnja influencerica Diana Sidva odlučila je javno podijeliti svoju stranu priče - i upozoriti kako sličan način življenja, odnosno u njenom slučaju \'stay at home girlfriend \'koji se ponekad prikazuje kao glamurozan, u stvarnosti može biti emocionalno iscrpljujući i nezdrav', '2026-01-18 09:51:04'),
(50, 2, 10, 'FOTO Sjećate se \'dame\' koja je tukla ZET-ovca? Protjerali je iz Hrvatske. Evo gdje je danas', 'https://www.24sata.hr/fun/foto-sjecate-se-dame-koja-je-tukla-zet-ovca-protjerali-je-iz-hrvatske-evo-gdje-je-danas-1100938', '2026-01-17 09:45:00', 'Dajana Radošljević javnosti je postala poznata nakon fizičkog sukoba s vozačem ZET-a na Kvatriću u Zagrebu. Incident se dogodio u kolovozu 2025., a poduzetnica je nakon toga još par puta punila novinske stupce', '2026-01-18 09:51:04'),
(51, 1, 1, 'Stiže hladni val', 'https://www.index.hr/vijesti/clanak/stize-hladni-val/2751134.aspx', '2026-01-18 09:42:00', 'POD UTJECAJEM snažne anticiklone u naše krajeve pritječe osjetno hladniji zrak sa sjevera Europe, zbog čega se u danima koji slijede očekuju upozorenja na hladni val.', '2026-01-18 09:52:46'),
(52, 1, 2, 'Predivna gesta turskog velikana. Pomagat će onima kojima je to najpotrebnije', 'https://www.index.hr/sport/clanak/predivna-gesta-turskog-velikana-pomagat-ce-onima-kojima-je-to-najpotrebnije/2751132.aspx', '2026-01-18 09:39:00', 'FENERBAHÇE je uveo novo pravilo za pojačanja, svaki novi igrač koji dođe u prvu momčad ubuduće će 1 % svoje plaće izdvajati za program pomoći djeci u nepovoljnom položaju.', '2026-01-18 09:52:46'),
(53, 1, 3, 'Šok u Jugoslaviji 1977. - iznenadna smrt najvažnijeg političkog lica BiH', 'https://www.index.hr/vijesti/clanak/sok-u-jugoslaviji-1977-iznenadna-smrt-najvaznijeg-politickog-lica-bih/2633771.aspx', '2026-01-18 09:34:09', 'DŽEMAL Bijedić bio je vrlo važan političar u SFRJ. Tragično je preminuo u zrakoplovnoj nesreći.', '2026-01-18 09:52:46'),
(54, 1, 4, 'Ove godine će za 127.000 umirovljenika biti ukinuta penalizacija', 'https://www.index.hr/vijesti/clanak/ove-godine-ce-za-127000-umirovljenika-biti-ukinuta-penalizacija/2751131.aspx', '2026-01-18 09:31:00', 'UKIDANJE penalizacije za prijevremeno umirovljenje za građane koji su navršili 70 godina obuhvatit će 127.000 korisnika prijevremene mirovine tijekom 2026., kojima će ta promjena u prosjeku mjesečno donijeti oko 57 eura više mirovine.', '2026-01-18 09:52:46'),
(55, 1, 5, '\"On je idiot. Vrlo bogat, ali ipak idiot\": Svađaju se Musk i šef Ryanaira', 'https://www.index.hr/vijesti/clanak/on-je-idiot-vrlo-bogat-ali-ipak-idiot-svadjaju-se-musk-i-sef-ryanaira/2751130.aspx', '2026-01-18 09:25:00', 'ELON Musk i direktor Ryanaira, Michael O\'Leary, javno su se sukobili i izvrijeđali zbog uvođenja Starlink Wi-Fi usluge u zrakoplove, sporeći se oko troškova i performansi.', '2026-01-18 09:52:46'),
(56, 1, 6, 'Glumica o 18+ scenama s Benom Affleckom: \"Hihotali smo se cijelo vrijeme\"', 'https://www.index.hr/chill/clanak/glumica-o-18-scenama-s-benom-affleckom-hihotali-smo-se-cijelo-vrijeme/2751127.aspx', '2026-01-18 09:19:00', 'LINA Esco nije skrivala oduševljenje Affleckom.', '2026-01-18 09:52:46'),
(57, 1, 7, 'Otkriveno kako će EU odgovoriti Trumpu. \"SAD tajno prikupljao podatke o Grenlandu\"', 'https://www.index.hr/vijesti/clanak/otkriveno-kako-ce-eu-odgovoriti-trumpu-sad-tajno-prikupljao-podatke-o-grenlandu/2751110.aspx', '2026-01-18 09:18:00', 'TRUMP prijeti Europljanima carinama. Nižu se reakcije, sazvan je hitan sastanak u Bruxellesu.', '2026-01-18 09:52:46'),
(58, 1, 8, 'Trump prijeti tužbom banci JPMorgan Chase zbog diskriminacije', 'https://www.index.hr/vijesti/clanak/trump-prijeti-tuzbom-banci-jpmorgan-chase-zbog-diskriminacije/2751124.aspx', '2026-01-18 09:13:00', 'DONALD Trump najavio je tužbu protiv JPMorgan Chasea, tvrdeći da je diskriminiran nakon nereda na Kapitolu. Spor je povezan i s medijskim napisima o ponudi posla šefu banke, što Trump poriče.', '2026-01-18 09:52:46'),
(59, 1, 9, 'Dječak (12) se bori za život, napao ga morski pas u Sydneyju', 'https://www.index.hr/vijesti/clanak/djecak-12-se-bori-za-zivot-napao-ga-morski-pas-u-sydneyju/2751129.aspx', '2026-01-18 09:09:00', 'DJEČAK (12) bori se za život nakon napada morskog psa na plaži u Sydneyu. Zadobio je teške ozljede obje noge i hitno je prevezen u bolnicu. Plaža je zatvorena.', '2026-01-18 09:52:46'),
(60, 1, 10, 'UŽIVO Top transferi: Džeko dogovara \"bombastičan\" transfer. PSG preoteo talenta Barci', 'https://www.index.hr/sport/clanak/uzivo-top-transferi-dzeko-dogovara-bombastican-transfer-psg-preoteo-talenta-barci/2751085.aspx', '2026-01-18 09:08:00', 'NAJZANIMLJIVIJE vijesti i glasine u zimskom prijelaznom roku na nogometnom tržištu pratili ste uživo u tekstualnom prijenosu na Indexu.', '2026-01-18 09:52:46'),
(61, 2, 1, 'Jezik opsade s Kaptola: Zašto novi laički pokret nastupa kao da je počeo posljednji rat?', 'https://www.24sata.hr/news/jezik-opsade-s-kaptola-zasto-novi-laicki-pokret-nastupa-kao-da-je-poceo-posljednji-rat-1101743', '2026-01-18 09:00:00', 'U prvom redu sjedili su prepoznatljivi akteri političkog katolicizma: Željka Markić, Vice Batarelo i predstavnik Glasa Koncila. Već ta scenografija govori više od najave: vjera, država i politika stavljene su u isti kadar', '2026-01-18 10:57:45'),
(62, 2, 2, 'Livaja mjesecima drži Hajdukov ugovor na stolu. Dva su razloga', 'https://www.24sata.hr/sport/livaja-mjesecima-drzi-hajdukov-ugovor-na-stolu-dva-su-razloga-1101753', '2026-01-18 09:47:00', 'Status Marka Livaje u Hajdukovoj momčadi ove sezone nije idealan, a izgleda kako ne može naći zajednički jezik s upravom za produljenje suradnje iza 2027. godine, do kad mu vrijedi trenutačni ugovor', '2026-01-18 10:57:45'),
(63, 2, 3, '100 fotografija totalnog kaosa: Prosvjednici rastjerali krajnje desničare u Minneapolisu', 'https://www.24sata.hr/news/100-fotografija-totalnog-kaosa-prosvjednici-rastjerali-krajnje-desnicare-u-minneapolisu-1101749', '2026-01-18 09:13:00', 'Stotine prosvjednika protiv američke službe za imigraciju i carinu (ICE) rastjeralo je manju skupinu krajnjih desničara u Minneapolisu koji su u subotu namjeravali otići u četvrt u kojoj žive mnogi imigranti', '2026-01-18 10:57:45'),
(64, 2, 4, 'Senzacionalni Hrvati na slalomu u Wengenu! Dvojica su u top 10', 'https://www.24sata.hr/sport/uzivo-slalom-u-wengenu-cetiri-hrvata-u-lovu-na-nove-bodove-1101745', '2026-01-18 09:30:00', 'Kolega je sa startnim brojem 19 odvozio prvu vožnju, ali ima tri sekunde zaostatka za prvim Mcgrathom. Filip Zubčić i Istok Rodeš odvozili su najbolji slalom ove sezone i zasigurno idu u drugu vožnju koja je u 13 sati', '2026-01-18 10:57:45'),
(65, 2, 5, 'FOTO Vruće fotke: Paparazzi seksi sestre ulovili na plaži. Zaigrale su se na pijesku...', 'https://www.24sata.hr/fun/foto-vruce-fotke-paparazzi-seksi-sestre-ulovili-na-plazi-zaigrale-su-se-na-pijesku-1100948', '2026-01-18 09:00:00', 'Manekenka Brooks Nader boravila je u Cabo San Lucasu na odmoru gdje joj se pridružila mlađa sestra Sarah Jane. Sestre su uživale u suncu i opuštenoj atmosferi uz plažu...', '2026-01-18 10:57:45'),
(66, 2, 6, 'FOTO Ovo je najljepši bazen na Alpama: Topao je, proziran i ima pogled na Dolomite', 'https://www.24sata.hr/lifestyle/foto-ovo-je-najljepsi-bazen-na-alpama-topao-je-proziran-i-ima-pogled-na-dolomite-1100766', '2026-01-18 10:00:00', 'Hotel Cristallo u Alta Badiji nudi luksuzni planinski odmor usred šuma i zelenih prostranstava, spajajući udobnost, prirodu i Ladinsku kulturu. Posebnu pažnju privlači topli prozirni vanjski bazen s pogledom na Dolomite', '2026-01-18 10:57:45'),
(67, 2, 7, 'FOTO Njihova najveća podrška: Koga ljube naši rukometaši?', 'https://www.24sata.hr/sport/foto-njihova-najveca-podrska-koga-ljube-nasi-rukometasi-1101467', '2026-01-18 06:00:00', 'Hrvatska rukometna reprezentacija pobijedila je Gruziju na otvaranju Eura, a u pohodu na jedino zlato koje im nedostaje najglasnije navijačice su njihove bolje polovice. Neki su u vezi već nekoliko godina', '2026-01-18 10:57:45'),
(68, 2, 8, 'Meteorolozi izdali upozorenje za ovaj dio Hrvatske: Hladni val! Stižu nam minusi', 'https://www.24sata.hr/news/meteorolozi-izdali-upozorenje-za-ovaj-dio-hrvatske-hladni-val-stizu-nam-minusi-1101741', '2026-01-18 08:02:00', 'Na većem dijelu Jadrana umjerena do jaka bura, lokalno s olujnim udarima, na jugu i umjeren istočnjak, a prema otvorenom moru i jugo', '2026-01-18 10:57:45'),
(69, 2, 9, 'FOTO Sjećate se \'dame\' koja je tukla ZET-ovca? Protjerali je iz Hrvatske. Evo gdje je danas', 'https://www.24sata.hr/fun/foto-sjecate-se-dame-koja-je-tukla-zet-ovca-protjerali-je-iz-hrvatske-evo-gdje-je-danas-1100938', '2026-01-17 09:45:00', 'Dajana Radošljević javnosti je postala poznata nakon fizičkog sukoba s vozačem ZET-a na Kvatriću u Zagrebu. Incident se dogodio u kolovozu 2025., a poduzetnica je nakon toga još par puta punila novinske stupce', '2026-01-18 10:57:45'),
(70, 2, 10, '24 fotografije koje su obilježile tjedan: \'Golim\' haljinama i putovanjima prkose zimi', 'https://www.24sata.hr/show/24-fotografije-koje-su-obiljezile-tjedan-golim-haljinama-i-putovanjima-prkose-zimi-1101566', '2026-01-18 07:00:00', 'Iza nas je zanimljiv i ponovno golišav tjedan, a unatoč datumu poznate dame ne zamaraju se minusima. Izgleda da je uživanje u toplijim krajevima postala je omiljena razonoda zvijezda, barem onih koji nisu bili na dodjeli Zlatnih globusa. Prisjetimo se najboljih trenutaka u prošlom tjednu...', '2026-01-18 10:57:45'),
(71, 2, 1, 'Jezik opsade s Kaptola: Zašto novi laički pokret nastupa kao da je počeo posljednji rat?', 'https://www.24sata.hr/news/jezik-opsade-s-kaptola-zasto-novi-laicki-pokret-nastupa-kao-da-je-poceo-posljednji-rat-1101743', '2026-01-18 09:00:00', 'U prvom redu sjedili su prepoznatljivi akteri političkog katolicizma: Željka Markić, Vice Batarelo i predstavnik Glasa Koncila. Već ta scenografija govori više od najave: vjera, država i politika stavljene su u isti kadar', '2026-01-18 11:01:18'),
(72, 2, 2, 'Livaja mjesecima drži Hajdukov ugovor na stolu. Dva su razloga', 'https://www.24sata.hr/sport/livaja-mjesecima-drzi-hajdukov-ugovor-na-stolu-dva-su-razloga-1101753', '2026-01-18 09:47:00', 'Status Marka Livaje u Hajdukovoj momčadi ove sezone nije idealan, a izgleda kako ne može naći zajednički jezik s upravom za produljenje suradnje iza 2027. godine, do kad mu vrijedi trenutačni ugovor', '2026-01-18 11:01:18'),
(73, 2, 3, 'VIDEO Snježna apokalipsa na Kamčatki: Nanosi visoki kao zgrade, kopaju tunele da izađu!', 'https://www.24sata.hr/fun/video-snjezna-apokalipsa-na-kamcatki-nanosi-visoki-kao-zgrade-kopaju-tunele-da-izadu-1101763', '2026-01-18 10:56:00', 'Vlasti su za nesreće okrivile tvrtke za upravljanje zgradama, optuživši ih da nisu na vrijeme očistile krovove. Ministar za izvanredne situacije upozorio je stanovnike na opasnost koja i dalje prijeti', '2026-01-18 11:01:18'),
(74, 2, 4, 'FOTO Vruće fotke: Paparazzi seksi sestre ulovili na plaži. Zaigrale su se na pijesku...', 'https://www.24sata.hr/fun/foto-vruce-fotke-paparazzi-seksi-sestre-ulovili-na-plazi-zaigrale-su-se-na-pijesku-1100948', '2026-01-18 09:00:00', 'Manekenka Brooks Nader boravila je u Cabo San Lucasu na odmoru gdje joj se pridružila mlađa sestra Sarah Jane. Sestre su uživale u suncu i opuštenoj atmosferi uz plažu...', '2026-01-18 11:01:18'),
(75, 2, 5, 'FOTO Njihova najveća podrška: Koga ljube naši rukometaši?', 'https://www.24sata.hr/sport/foto-njihova-najveca-podrska-koga-ljube-nasi-rukometasi-1101467', '2026-01-18 06:00:00', 'Hrvatska rukometna reprezentacija pobijedila je Gruziju na otvaranju Eura, a u pohodu na jedino zlato koje im nedostaje najglasnije navijačice su njihove bolje polovice. Neki su u vezi već nekoliko godina', '2026-01-18 11:01:18'),
(76, 2, 6, 'FOTO Ovo je najljepši bazen na Alpama: Topao je, proziran i ima pogled na Dolomite', 'https://www.24sata.hr/lifestyle/foto-ovo-je-najljepsi-bazen-na-alpama-topao-je-proziran-i-ima-pogled-na-dolomite-1100766', '2026-01-18 10:00:00', 'Hotel Cristallo u Alta Badiji nudi luksuzni planinski odmor usred šuma i zelenih prostranstava, spajajući udobnost, prirodu i Ladinsku kulturu. Posebnu pažnju privlači topli prozirni vanjski bazen s pogledom na Dolomite', '2026-01-18 11:01:18'),
(77, 2, 7, '100 fotografija totalnog kaosa: Prosvjednici rastjerali krajnje desničare u Minneapolisu', 'https://www.24sata.hr/news/100-fotografija-totalnog-kaosa-prosvjednici-rastjerali-krajnje-desnicare-u-minneapolisu-1101749', '2026-01-18 09:13:00', 'Stotine prosvjednika protiv američke službe za imigraciju i carinu (ICE) rastjeralo je manju skupinu krajnjih desničara u Minneapolisu koji su u subotu namjeravali otići u četvrt u kojoj žive mnogi imigranti', '2026-01-18 11:01:18'),
(78, 2, 8, 'Senzacionalni Hrvati na slalomu u Wengenu! Dvojica su u top 10', 'https://www.24sata.hr/sport/uzivo-slalom-u-wengenu-cetiri-hrvata-u-lovu-na-nove-bodove-1101745', '2026-01-18 09:30:00', 'Kolega je sa startnim brojem 19 odvozio prvu vožnju, ali ima tri sekunde zaostatka za prvim Mcgrathom. Filip Zubčić i Istok Rodeš odvozili su najbolji slalom ove sezone i zasigurno idu u drugu vožnju koja je u 13 sati', '2026-01-18 11:01:18'),
(79, 2, 9, 'FOTO Sjećate se \'dame\' koja je tukla ZET-ovca? Protjerali je iz Hrvatske. Evo gdje je danas', 'https://www.24sata.hr/fun/foto-sjecate-se-dame-koja-je-tukla-zet-ovca-protjerali-je-iz-hrvatske-evo-gdje-je-danas-1100938', '2026-01-17 09:45:00', 'Dajana Radošljević javnosti je postala poznata nakon fizičkog sukoba s vozačem ZET-a na Kvatriću u Zagrebu. Incident se dogodio u kolovozu 2025., a poduzetnica je nakon toga još par puta punila novinske stupce', '2026-01-18 11:01:18'),
(80, 2, 10, '24 fotografije koje su obilježile tjedan: \'Golim\' haljinama i putovanjima prkose zimi', 'https://www.24sata.hr/show/24-fotografije-koje-su-obiljezile-tjedan-golim-haljinama-i-putovanjima-prkose-zimi-1101566', '2026-01-18 07:00:00', 'Iza nas je zanimljiv i ponovno golišav tjedan, a unatoč datumu poznate dame ne zamaraju se minusima. Izgleda da je uživanje u toplijim krajevima postala je omiljena razonoda zvijezda, barem onih koji nisu bili na dodjeli Zlatnih globusa. Prisjetimo se najboljih trenutaka u prošlom tjednu...', '2026-01-18 11:01:18'),
(81, 2, 1, 'Jezik opsade s Kaptola: Zašto novi laički pokret nastupa kao da je počeo posljednji rat?', 'https://www.24sata.hr/news/jezik-opsade-s-kaptola-zasto-novi-laicki-pokret-nastupa-kao-da-je-poceo-posljednji-rat-1101743', '2026-01-18 09:00:00', 'U prvom redu sjedili su prepoznatljivi akteri političkog katolicizma: Željka Markić, Vice Batarelo i predstavnik Glasa Koncila. Već ta scenografija govori više od najave: vjera, država i politika stavljene su u isti kadar', '2026-01-18 11:07:37'),
(82, 2, 2, 'Livaja mjesecima drži Hajdukov ugovor na stolu. Dva su razloga', 'https://www.24sata.hr/sport/livaja-mjesecima-drzi-hajdukov-ugovor-na-stolu-dva-su-razloga-1101753', '2026-01-18 09:47:00', 'Status Marka Livaje u Hajdukovoj momčadi ove sezone nije idealan, a izgleda kako ne može naći zajednički jezik s upravom za produljenje suradnje iza 2027. godine, do kad mu vrijedi trenutačni ugovor', '2026-01-18 11:07:37'),
(83, 2, 3, 'Senzacionalni Hrvati na slalomu u Wengenu! Dvojica su u top 10', 'https://www.24sata.hr/sport/uzivo-slalom-u-wengenu-cetiri-hrvata-u-lovu-na-nove-bodove-1101745', '2026-01-18 09:30:00', 'Kolega je sa startnim brojem 19 odvozio prvu vožnju, ali neće u drugu; Filip Zubčić i Istok Rodeš u lovu su na postolje jer su odvozili najbolji slalom ove sezone i zasigurno idu u drugu vožnju koja je u 13 sati', '2026-01-18 11:07:37'),
(84, 2, 4, 'FOTO Vruće fotke: Paparazzi seksi sestre ulovili na plaži. Zaigrale su se na pijesku...', 'https://www.24sata.hr/fun/foto-vruce-fotke-paparazzi-seksi-sestre-ulovili-na-plazi-zaigrale-su-se-na-pijesku-1100948', '2026-01-18 09:00:00', 'Manekenka Brooks Nader boravila je u Cabo San Lucasu na odmoru gdje joj se pridružila mlađa sestra Sarah Jane. Sestre su uživale u suncu i opuštenoj atmosferi uz plažu...', '2026-01-18 11:07:37'),
(85, 2, 5, 'VIDEO Snježna apokalipsa na Kamčatki: Nanosi visoki kao zgrade, kopaju tunele da izađu!', 'https://www.24sata.hr/fun/video-snjezna-apokalipsa-na-kamcatki-nanosi-visoki-kao-zgrade-kopaju-tunele-da-izadu-1101763', '2026-01-18 10:56:00', 'Vlasti su za nesreće okrivile tvrtke za upravljanje zgradama, optuživši ih da nisu na vrijeme očistile krovove. Ministar za izvanredne situacije upozorio je stanovnike na opasnost koja i dalje prijeti', '2026-01-18 11:07:37'),
(86, 2, 6, 'FOTO Njihova najveća podrška: Koga ljube naši rukometaši?', 'https://www.24sata.hr/sport/foto-njihova-najveca-podrska-koga-ljube-nasi-rukometasi-1101467', '2026-01-18 06:00:00', 'Hrvatska rukometna reprezentacija pobijedila je Gruziju na otvaranju Eura, a u pohodu na jedino zlato koje im nedostaje najglasnije navijačice su njihove bolje polovice. Neki su u vezi već nekoliko godina', '2026-01-18 11:07:37'),
(87, 2, 7, 'FOTO Ovo je najljepši bazen na Alpama: Topao je, proziran i ima pogled na Dolomite', 'https://www.24sata.hr/lifestyle/foto-ovo-je-najljepsi-bazen-na-alpama-topao-je-proziran-i-ima-pogled-na-dolomite-1100766', '2026-01-18 10:00:00', 'Hotel Cristallo u Alta Badiji nudi luksuzni planinski odmor usred šuma i zelenih prostranstava, spajajući udobnost, prirodu i Ladinsku kulturu. Posebnu pažnju privlači topli prozirni vanjski bazen s pogledom na Dolomite', '2026-01-18 11:07:37'),
(88, 2, 8, '100 fotografija totalnog kaosa: Prosvjednici rastjerali krajnje desničare u Minneapolisu', 'https://www.24sata.hr/news/100-fotografija-totalnog-kaosa-prosvjednici-rastjerali-krajnje-desnicare-u-minneapolisu-1101749', '2026-01-18 09:13:00', 'Stotine prosvjednika protiv američke službe za imigraciju i carinu (ICE) rastjeralo je manju skupinu krajnjih desničara u Minneapolisu koji su u subotu namjeravali otići u četvrt u kojoj žive mnogi imigranti', '2026-01-18 11:07:37'),
(89, 2, 9, 'FOTO Sjećate se \'dame\' koja je tukla ZET-ovca? Protjerali je iz Hrvatske. Evo gdje je danas', 'https://www.24sata.hr/fun/foto-sjecate-se-dame-koja-je-tukla-zet-ovca-protjerali-je-iz-hrvatske-evo-gdje-je-danas-1100938', '2026-01-17 09:45:00', 'Dajana Radošljević javnosti je postala poznata nakon fizičkog sukoba s vozačem ZET-a na Kvatriću u Zagrebu. Incident se dogodio u kolovozu 2025., a poduzetnica je nakon toga još par puta punila novinske stupce', '2026-01-18 11:07:37'),
(90, 2, 10, '24 fotografije koje su obilježile tjedan: \'Golim\' haljinama i putovanjima prkose zimi', 'https://www.24sata.hr/show/24-fotografije-koje-su-obiljezile-tjedan-golim-haljinama-i-putovanjima-prkose-zimi-1101566', '2026-01-18 07:00:00', 'Iza nas je zanimljiv i ponovno golišav tjedan, a unatoč datumu poznate dame ne zamaraju se minusima. Izgleda da je uživanje u toplijim krajevima postala je omiljena razonoda zvijezda, barem onih koji nisu bili na dodjeli Zlatnih globusa. Prisjetimo se najboljih trenutaka u prošlom tjednu...', '2026-01-18 11:07:37'),
(91, 2, 1, 'Evo kako je hrvatska Vlada reagirala na Trumpovu prijetnju carinama zbog Grenlanda', 'https://www.24sata.hr/news/evo-kako-je-hrvatska-vlada-reagirala-na-trumpovu-prijetnju-carinama-zbog-grenlanda-1101771', '2026-01-18 11:38:00', 'Predsjednik SAD-a Donald Trump u subotu je zaprijetio da će uvesti carine europskim saveznicima dok se Sjedinjenim Državama ne dopusti kupnja Grenlanda...', '2026-01-18 11:40:34'),
(92, 2, 2, 'Senzacionalni Hrvati na slalomu u Wengenu! Dvojica su u top 10', 'https://www.24sata.hr/sport/uzivo-slalom-u-wengenu-cetiri-hrvata-u-lovu-na-nove-bodove-1101745', '2026-01-18 09:30:00', 'Kolega je sa startnim brojem 19 odvozio prvu vožnju, ali neće u drugu; Filip Zubčić i Istok Rodeš u lovu su na postolje jer su odvozili najbolji slalom ove sezone i zasigurno idu u drugu vožnju koja je u 13 sati', '2026-01-18 11:40:34'),
(93, 2, 3, 'Jezik opsade s Kaptola: Zašto novi laički pokret nastupa kao da je počeo posljednji rat?', 'https://www.24sata.hr/news/jezik-opsade-s-kaptola-zasto-novi-laicki-pokret-nastupa-kao-da-je-poceo-posljednji-rat-1101743', '2026-01-18 09:00:00', 'U prvom redu sjedili su prepoznatljivi akteri političkog katolicizma: Željka Markić, Vice Batarelo i predstavnik Glasa Koncila. Već ta scenografija govori više od najave: vjera, država i politika stavljene su u isti kadar', '2026-01-18 11:40:34'),
(94, 2, 4, 'Livaja mjesecima drži Hajdukov ugovor na stolu. Dva su razloga', 'https://www.24sata.hr/sport/livaja-mjesecima-drzi-hajdukov-ugovor-na-stolu-dva-su-razloga-1101753', '2026-01-18 09:47:00', 'Status Marka Livaje u Hajdukovoj momčadi ove sezone nije idealan, a izgleda kako ne može naći zajednički jezik s upravom za produljenje suradnje iza 2027. godine, do kad mu vrijedi trenutačni ugovor', '2026-01-18 11:40:34'),
(95, 2, 5, 'FOTO Tange, TikTok i Tom Brady. Zvijezda NFL-a partijala je na jahti s čak 23 godine mlađom', 'https://www.24sata.hr/sport/foto-tange-tiktok-i-tom-brady-zvijezda-nfl-a-partijala-je-na-jahti-s-cak-23-godine-mladom-1101446', '2026-01-18 11:14:00', 'Tom Brady (48) viđen s Alix Earle (25) na luksuznoj jahti! Njihovo druženje pokrenulo glasine o romansi, no Brady tvrdi da je fokusiran na posao i djecu. Alix je nova TikTok senzacija', '2026-01-18 11:40:34'),
(96, 2, 6, 'VIDEO Snježna apokalipsa na Kamčatki: Nanosi visoki kao zgrade, kopaju tunele da izađu!', 'https://www.24sata.hr/fun/video-snjezna-apokalipsa-na-kamcatki-nanosi-visoki-kao-zgrade-kopaju-tunele-da-izadu-1101763', '2026-01-18 10:56:00', 'Vlasti su za nesreće okrivile tvrtke za upravljanje zgradama, optuživši ih da nisu na vrijeme očistile krovove. Ministar za izvanredne situacije upozorio je stanovnike na opasnost koja i dalje prijeti', '2026-01-18 11:40:34'),
(97, 2, 7, 'FOTO Njihova najveća podrška: Koga ljube naši rukometaši?', 'https://www.24sata.hr/sport/foto-njihova-najveca-podrska-koga-ljube-nasi-rukometasi-1101467', '2026-01-18 06:00:00', 'Hrvatska rukometna reprezentacija pobijedila je Gruziju na otvaranju Eura, a u pohodu na jedino zlato koje im nedostaje najglasnije navijačice su njihove bolje polovice. Neki su u vezi već nekoliko godina', '2026-01-18 11:40:34'),
(98, 2, 8, 'FOTO Ovo je najljepši bazen na Alpama: Topao je, proziran i ima pogled na Dolomite', 'https://www.24sata.hr/lifestyle/foto-ovo-je-najljepsi-bazen-na-alpama-topao-je-proziran-i-ima-pogled-na-dolomite-1100766', '2026-01-18 10:00:00', 'Hotel Cristallo u Alta Badiji nudi luksuzni planinski odmor usred šuma i zelenih prostranstava, spajajući udobnost, prirodu i Ladinsku kulturu. Posebnu pažnju privlači topli prozirni vanjski bazen s pogledom na Dolomite', '2026-01-18 11:40:34'),
(99, 2, 9, '100 fotografija totalnog kaosa: Prosvjednici rastjerali krajnje desničare u Minneapolisu', 'https://www.24sata.hr/news/100-fotografija-totalnog-kaosa-prosvjednici-rastjerali-krajnje-desnicare-u-minneapolisu-1101749', '2026-01-18 09:13:00', 'Stotine prosvjednika protiv američke službe za imigraciju i carinu (ICE) rastjeralo je manju skupinu krajnjih desničara u Minneapolisu koji su u subotu namjeravali otići u četvrt u kojoj žive mnogi imigranti', '2026-01-18 11:40:34'),
(100, 2, 10, 'FOTO Sjećate se \'dame\' koja je tukla ZET-ovca? Protjerali je iz Hrvatske. Evo gdje je danas', 'https://www.24sata.hr/fun/foto-sjecate-se-dame-koja-je-tukla-zet-ovca-protjerali-je-iz-hrvatske-evo-gdje-je-danas-1100938', '2026-01-17 09:45:00', 'Dajana Radošljević javnosti je postala poznata nakon fizičkog sukoba s vozačem ZET-a na Kvatriću u Zagrebu. Incident se dogodio u kolovozu 2025., a poduzetnica je nakon toga još par puta punila novinske stupce', '2026-01-18 11:40:34'),
(101, 1, 1, 'EU sprema oštar odgovor, Danci razotkrili tajni potez SAD-a. \"Kina i Rusija uživaju\"', 'https://www.index.hr/vijesti/clanak/eu-sprema-ostar-odgovor-danci-razotkrili-tajni-potez-sada-kina-i-rusija-uzivaju/2751110.aspx', '2026-01-18 11:24:00', 'TRUMP prijeti Europljanima carinama. Nižu se reakcije, sazvan je hitan sastanak u Bruxellesu.', '2026-01-18 11:40:36'),
(102, 1, 2, 'Dragi katolici, religija košta. I bio bi red da to sami plaćate', 'https://www.index.hr/vijesti/clanak/dragi-katolici-religija-kosta-i-bio-bi-red-da-to-sami-placate/2751077.aspx', '2026-01-18 11:20:00', 'AKO ĆE NETKO jamrati zbog davanja novaca Crkvi, to smo mi koji u Crkvu ne idemo, a plaćamo je.', '2026-01-18 11:40:36'),
(103, 1, 3, 'Talijanski ministar se rugao situaciji na Grenlandu. Reagirala Meloni', 'https://www.index.hr/vijesti/clanak/talijanski-ministar-se-rugao-situaciji-na-grenlandu-reagirala-meloni/2751157.aspx', '2026-01-18 11:18:00', 'TALIJANSKA premijerka Giorgia Meloni pojasnila je danas stav vlade o mogućem vojnom angažmanu na Grenlandu.', '2026-01-18 11:40:36'),
(104, 1, 4, 'Objavljen je slavljenički video Kramarića. Do navijača je došao uz Thompsonov hit', 'https://www.index.hr/sport/clanak/objavljen-je-slavljenicki-video-kramarica-do-navijaca-je-dosao-uz-thompsonov-hit/2751156.aspx', '2026-01-18 11:12:00', 'PROSLAVLJEN je jubilej.', '2026-01-18 11:40:36'),
(105, 1, 5, 'Zašto tinejdžeri i mladi sve rjeđe posjećuju liječnika?', 'https://www.index.hr/mame/clanak/zasto-tinejdzeri-i-mladi-sve-rjedje-posjecuju-lijecnika/2751152.aspx', '2026-01-18 11:05:00', 'NOVO istraživanje pokazuje da tinejdžeri i mladi odrasli prestaju redovito posjećivati liječnika.', '2026-01-18 11:40:36'),
(106, 1, 6, 'Sjajni nastupi Zubčića i Rodeša u Wengenu. Kolega i Ljutić bez druge vožnje', 'https://www.index.hr/sport/clanak/sjajni-nastupi-zubcica-i-rodesa-u-wengenu-kolega-i-ljutic-bez-druge-voznje/2751116.aspx', '2026-01-18 11:03:00', 'ODLIČNE vijesti za hrvatsko skijanje.', '2026-01-18 11:40:36'),
(107, 1, 7, 'Vozio po Zagrebu bez vozačke, odbio alkotest, vrijeđao policiju. Objavljena kazna', 'https://www.index.hr/vijesti/clanak/vozio-po-zagrebu-bez-vozacke-odbio-alkotest-vrijedjao-policiju-objavljena-kazna/2751154.aspx', '2026-01-18 11:00:00', '42-GODIŠNJI vozač u Zagrebu zaustavljen je s isteklom vozačkom dozvolom. Odbio je alkotest i vrijeđao policajce, zbog čega je kažnjen s 2.150 eura i zabranom vožnje na mjesec dana.', '2026-01-18 11:40:36'),
(108, 1, 8, 'Iranski dužnosnik: Teroristi su ubili najmanje 5000 nevinih Iranaca', 'https://www.index.hr/vijesti/clanak/iranski-duznosnik-teroristi-su-ubili-najmanje-5000-nevinih-iranaca/2751153.aspx', '2026-01-18 10:56:00', 'IRANSKI dužnosnik izjavio jeda su vlasti potvrdile da je u prosvjedima u Iranu ubijeno najmanje 5000 ljudi, uključujući oko 500 pripadnika sigurnosnih snaga, okrivljavajući \"teroriste i naoružane izgrednike\" za ubojstvo \"nevinih Iranaca\".', '2026-01-18 11:40:36'),
(109, 1, 9, 'VIDEO Urušio se kat zgrade u Parizu. Ozlijeđeno 20 ljudi', 'https://www.index.hr/vijesti/clanak/video-urusio-se-kat-zgrade-u-parizu-ozlijedjeno-20-ljudi/2751148.aspx', '2026-01-18 10:50:00', 'EVAKUIRANE su i dvije susjedne zgrade.', '2026-01-18 11:40:36'),
(110, 1, 10, 'Arogantni su: Horoskopski znakovi koji misle da se svijet vrti oko njih', 'https://www.index.hr/horoskop/clanak/arogantni-su-horoskopski-znakovi-koji-misle-da-se-svijet-vrti-oko-njih/2751147.aspx', '2026-01-18 10:50:00', 'POSTOJE ljudi koji u svakoj situaciji nastoje biti u središtu pažnje, uvjereni da su njihovo mišljenje, vrijeme i potrebe važniji od tuđih.', '2026-01-18 11:40:36');
INSERT INTO `portal_najcitanije` (`id`, `portal_id`, `pozicija`, `naslov`, `url`, `objavljeno_at`, `sadrzaj`, `dohvaceno_at`) VALUES
(111, 2, 1, 'Evo kako je hrvatska Vlada reagirala na Trumpovu prijetnju carinama zbog Grenlanda', 'https://www.24sata.hr/news/evo-kako-je-hrvatska-vlada-reagirala-na-trumpovu-prijetnju-carinama-zbog-grenlanda-1101771', '2026-01-18 11:38:00', 'Predsjednik SAD-a Donald Trump u subotu je zaprijetio da će uvesti carine europskim saveznicima dok se Sjedinjenim Državama ne dopusti kupnja Grenlanda...', '2026-01-18 13:21:46'),
(112, 2, 2, 'UŽIVO Slalom u Wengenu: Filip Zubčić i Istok Rodeš love najbolji plasman sezone u drugoj vožnji', 'https://www.24sata.hr/sport/uzivo-slalom-u-wengenu-cetiri-hrvata-u-lovu-na-nove-bodove-1101745', '2026-01-18 09:30:00', 'Filip Zubčić startao je utrku s brojem 28, nakon prve vožnje je osmi. Istok Rodeš pripremio je još veće iznenađenje te sa startnim brojem 31 završio prvi lauf na devetom mjestu. Od 13 sati počelo je drugi lauf u Wengenu', '2026-01-18 13:21:46'),
(113, 2, 3, 'VIDEO Trubači u zoru probudili vatrogasca Josipa: \'Rasplakali su me od sreće za 50. rođendan\'', 'https://www.24sata.hr/news/video-vatrogasac-josip-plakal-sam-od-srece-kolege-su-me-iznenadili-za-50-rodendan-1101766', '2026-01-18 09:53:00', 'Josip je član DVD-a Domašinec već 35 godina, a vatrogastvo opisuje kao poziv koji se temelji na zajedništvu, povjerenju i spremnosti na pomaganje drugima', '2026-01-18 13:21:46'),
(114, 2, 4, 'FOTO Sjećate se slatkog dječaka s Kinder čokolade? Matteo je danas uspješni ljepotan...', 'https://www.24sata.hr/fun/foto-sjecate-se-slatkog-djecaka-s-kinder-cokolade-matteo-je-danas-uspjesni-ljepotan-1025218', '2026-01-18 13:02:00', 'Lice nasmijanog dječaka krasilo je ambalažu Kinder čokolade od 2004. do 2019. godine, a mnogi su se pitali tko je zapravo taj dječak...', '2026-01-18 13:21:46'),
(115, 2, 5, 'Vozio po Zagrebu bez vozačke pa izvrijeđao policiju. Odbio i alkotest. Ovu je kaznu dobio', 'https://www.24sata.hr/news/vozio-po-zagrebu-bez-vozacke-pa-izvrijedao-policiju-odbio-i-alkotest-ovu-je-kaznu-dobio-1101767', '2026-01-18 12:36:00', 'Vozač (42) je uhićen i doveden na nadležni prekršajni sud...', '2026-01-18 13:21:46'),
(116, 2, 6, 'FOTO Mnoge je začudio grafit u Dubravi. Evo što on znači...', 'https://www.24sata.hr/fun/foto-mnoge-je-zacudio-grafit-u-dubravi-evo-sto-on-znaci-1101774', '2026-01-18 11:55:00', 'Grafitom je napisan odgovor na stari grafit,\"Oprosti mi\", \"On ili ona možda neće, ali za 93 metra Bog hoće\"', '2026-01-18 13:21:46'),
(117, 2, 7, 'FOTO Tange, TikTok i Tom Brady. Zvijezda NFL-a partijala je na jahti s čak 23 godine mlađom', 'https://www.24sata.hr/sport/foto-tange-tiktok-i-tom-brady-zvijezda-nfl-a-partijala-je-na-jahti-s-cak-23-godine-mladom-1101446', '2026-01-18 11:14:00', 'Tom Brady (48) viđen s Alix Earle (25) na luksuznoj jahti! Njihovo druženje pokrenulo glasine o romansi, no Brady tvrdi da je fokusiran na posao i djecu. Alix je nova TikTok senzacija', '2026-01-18 13:21:46'),
(118, 2, 8, 'U prtljažniku obavezno imajte ovih 14 stvari - za manje stresa!', 'https://www.24sata.hr/lifestyle/u-prtljazniku-obavezno-imajte-ovih-14-stvari-za-manje-stresa-1099910', '2026-01-18 12:00:00', 'Neki ne drže apsolutno ništa u prtljažnicima automobila, dok drugi imaju dovoljno stvari da bi mogli živjeti u automobilu tjednima. Negdje između nalazi se ovaj popis stvari koje bi svaki vlasnik automobila uvijek trebao imati pri ruci', '2026-01-18 13:21:46'),
(119, 2, 9, 'FOTO Vruće fotke: Paparazzi seksi sestre ulovili na plaži. Zaigrale su se na pijesku...', 'https://www.24sata.hr/fun/foto-vruce-fotke-paparazzi-seksi-sestre-ulovili-na-plazi-zaigrale-su-se-na-pijesku-1100948', '2026-01-18 09:00:00', 'Manekenka Brooks Nader boravila je u Cabo San Lucasu na odmoru gdje joj se pridružila mlađa sestra Sarah Jane. Sestre su uživale u suncu i opuštenoj atmosferi uz plažu...', '2026-01-18 13:21:46'),
(120, 2, 10, 'FOTO Njihova najveća podrška: Koga ljube naši rukometaši?', 'https://www.24sata.hr/sport/foto-njihova-najveca-podrska-koga-ljube-nasi-rukometasi-1101467', '2026-01-18 06:00:00', 'Hrvatska rukometna reprezentacija pobijedila je Gruziju na otvaranju Eura, a u pohodu na jedino zlato koje im nedostaje najglasnije navijačice su njihove bolje polovice. Neki su u vezi već nekoliko godina', '2026-01-18 13:21:46'),
(121, 1, 1, 'Knjige, ukrasi, lutke… Što se sve nudi na sajmu antikviteta na Britancu?', 'https://www.index.hr/shopping/clanak/knjige-ukrasi-lutke-sto-se-sve-nudi-na-sajmu-antikviteta-na-britancu/2751183.aspx', '2026-01-18 13:20:00', 'SAJAM antikviteta na Britancu u nedjelju je privukao brojne Zagrepčane.', '2026-01-18 13:22:57'),
(122, 1, 2, 'VIDEO Šest mrtvih u velikom požaru u trgovačkom centru u Pakistanu', 'https://www.index.hr/vijesti/clanak/video-sest-mrtvih-u-velikom-pozaru-u-trgovackom-centru-u-pakistanu/2751184.aspx', '2026-01-18 13:17:00', 'VATRA se brzo proširila, uništivši zgradu i uzrokujući strah od potpunog urušavanja.', '2026-01-18 13:22:57'),
(123, 1, 3, 'UŽIVO Zubčić i Rodeš nakon sjajne prve vožnje traže visoki plasman u Wengenu', 'https://www.index.hr/sport/clanak/uzivo-zubcic-i-rodes-nakon-sjajne-prve-voznje-traze-visoki-plasman-u-wengenu/2751169.aspx', '2026-01-18 13:15:00', 'TEKSTUALNI prijenos druge vožnje pratite na Indexu.', '2026-01-18 13:22:57'),
(124, 1, 4, 'Tri horoskopska znaka od danas će pratiti velika sreća', 'https://www.index.hr/horoskop/clanak/tri-horoskopska-znaka-od-danas-ce-pratiti-velika-sreca/2751181.aspx', '2026-01-18 13:13:00', 'OD 18. SIJEČNJA 2026., tri horoskopska znaka osjetit će nalet sreće i olakšanja kakav im je dugo nedostajao.', '2026-01-18 13:22:57'),
(125, 1, 5, 'Mama se \"osvetila\" djeci koja su maltretirala njezinu kćer (2). Reakcije podijeljene', 'https://www.index.hr/mame/clanak/mama-se-osvetila-djeci-koja-su-maltretirala-njezinu-kcer-2-reakcije-podijeljene/2751175.aspx', '2026-01-18 13:07:00', 'MAJKA je stala u obranu 2-godišnje kćeri koju su starija djeca maltretirala u igraonici.', '2026-01-18 13:22:57'),
(126, 1, 6, 'Meloni: Nazvala sam Trumpa i rekla mu što mislim. Macron: Vrijeme je za \"bazuku\"', 'https://www.index.hr/vijesti/clanak/meloni-nazvala-sam-trumpa-i-rekla-mu-sto-mislim-macron-vrijeme-je-za-bazuku/2751110.aspx', '2026-01-18 13:04:00', 'TRUMP prijeti Europljanima carinama. Nižu se reakcije, sazvan je hitan sastanak u Bruxellesu.', '2026-01-18 13:22:57'),
(127, 1, 7, 'Meloni: Nazvala sam Trumpa i rekla mu što mislim', 'https://www.index.hr/vijesti/clanak/meloni-nazvala-sam-trumpa-i-rekla-mu-sto-mislim/2751182.aspx', '2026-01-18 13:02:00', 'TALIJANSKA premijerka Giorgia Meloni rekla je Donaldu Trumpu da je njegova najava carina protiv zemalja koje pomažu sigurnosti Grenlanda pogreška, pozivajući na nastavak dijaloga i izbjegavanje eskalacije.', '2026-01-18 13:22:57'),
(128, 1, 8, 'Iranu prijeti trajna digitalna izolacija', 'https://www.index.hr/vijesti/clanak/iranu-prijeti-trajna-digitalna-izolacija/2751179.aspx', '2026-01-18 13:02:00', 'IRAN je već 10 dana bez interneta, što guši prosvjede i gospodarstvo. Vlasti navodno planiraju uvesti trajna ograničenja i sustav kontrole pristupa po uzoru na Kinu i Rusiju.', '2026-01-18 13:22:57'),
(129, 1, 9, 'Celtini navijači danas će nalakirati nokte u znak podrške Borji Iglesiasu', 'https://www.index.hr/sport/clanak/celtini-navijaci-danas-ce-nalakirati-nokte-u-znak-podrske-borji-iglesiasu/2751180.aspx', '2026-01-18 13:00:00', 'NAVIJAČI Celte Vigo pozvani su da nalakiraju nokte za utakmicu kao odgovor na homofobne uvrede upućene napadaču Borji Iglesiasu. Cilj akcije je poslati poruku protiv homofobije u nogometu.', '2026-01-18 13:22:57'),
(130, 1, 10, 'UŽIVO HR transferi: Livajine želje nisu slične ponudi Hajduka. Problem i oko Mlačića', 'https://www.index.hr/sport/clanak/uzivo-hr-transferi-livajine-zelje-nisu-slicne-ponudi-hajduka-problem-i-oko-mlacica/2748943.aspx', '2026-01-18 12:51:00', 'NAJZANIMLJIVIJE vijesti s hrvatskog nogometnog tržišta pratite uživo na Indexu.', '2026-01-18 13:22:57'),
(131, 2, 1, 'Evo kako je hrvatska Vlada reagirala na Trumpovu prijetnju carinama zbog Grenlanda', 'https://www.24sata.hr/news/evo-kako-je-hrvatska-vlada-reagirala-na-trumpovu-prijetnju-carinama-zbog-grenlanda-1101771', '2026-01-18 11:38:00', 'Predsjednik SAD-a Donald Trump u subotu je zaprijetio da će uvesti carine europskim saveznicima dok se Sjedinjenim Državama ne dopusti kupnja Grenlanda...', '2026-01-18 13:22:57'),
(132, 2, 2, 'UŽIVO Slalom u Wengenu: Filip Zubčić i Istok Rodeš love najbolji plasman sezone u drugoj vožnji', 'https://www.24sata.hr/sport/uzivo-slalom-u-wengenu-cetiri-hrvata-u-lovu-na-nove-bodove-1101745', '2026-01-18 09:30:00', 'Filip Zubčić startao je utrku s brojem 28, nakon prve vožnje je osmi. Istok Rodeš pripremio je još veće iznenađenje te sa startnim brojem 31 završio prvi lauf na devetom mjestu. Od 13 sati počelo je drugi lauf u Wengenu', '2026-01-18 13:22:57'),
(133, 2, 3, 'Otišao je moj Ante, plavokosi dječak iz Plinarske: \'Isprike su za gubitnike, ja to ne želim biti\'', 'https://www.24sata.hr/sport/otisao-je-moj-ante-plavokosi-djecak-iz-plinarske-isprike-su-za-gubitnike-ja-to-ne-zelim-biti-1101616', '2026-01-18 12:14:00', 'Splitski \"Superman\" Ante Grgurević, bivši košarkaš i trener KK Splita, preminuo je u četvrtak u 51. godini. Bio je sinonim snage i poštenja, a njegov utjecaj na mlade neizmjeran', '2026-01-18 13:22:57'),
(134, 2, 4, 'FOTO Sjećate se slatkog dječaka s Kinder čokolade? Matteo je danas uspješni ljepotan...', 'https://www.24sata.hr/fun/foto-sjecate-se-slatkog-djecaka-s-kinder-cokolade-matteo-je-danas-uspjesni-ljepotan-1025218', '2026-01-18 13:02:00', 'Lice nasmijanog dječaka krasilo je ambalažu Kinder čokolade od 2004. do 2019. godine, a mnogi su se pitali tko je zapravo taj dječak...', '2026-01-18 13:22:57'),
(135, 2, 5, 'VIDEO Trubači u zoru probudili vatrogasca Josipa: \'Rasplakali su me od sreće za 50. rođendan\'', 'https://www.24sata.hr/news/video-vatrogasac-josip-plakal-sam-od-srece-kolege-su-me-iznenadili-za-50-rodendan-1101766', '2026-01-18 09:53:00', 'Josip je član DVD-a Domašinec već 35 godina, a vatrogastvo opisuje kao poziv koji se temelji na zajedništvu, povjerenju i spremnosti na pomaganje drugima', '2026-01-18 13:22:57'),
(136, 2, 6, 'Vozio po Zagrebu bez vozačke pa izvrijeđao policiju. Odbio i alkotest. Ovu je kaznu dobio', 'https://www.24sata.hr/news/vozio-po-zagrebu-bez-vozacke-pa-izvrijedao-policiju-odbio-i-alkotest-ovu-je-kaznu-dobio-1101767', '2026-01-18 12:36:00', 'Vozač (42) je uhićen i doveden na nadležni prekršajni sud...', '2026-01-18 13:22:57'),
(137, 2, 7, 'FOTO Tange, TikTok i Tom Brady. Zvijezda NFL-a partijala je na jahti s čak 23 godine mlađom', 'https://www.24sata.hr/sport/foto-tange-tiktok-i-tom-brady-zvijezda-nfl-a-partijala-je-na-jahti-s-cak-23-godine-mladom-1101446', '2026-01-18 11:14:00', 'Tom Brady (48) viđen s Alix Earle (25) na luksuznoj jahti! Njihovo druženje pokrenulo glasine o romansi, no Brady tvrdi da je fokusiran na posao i djecu. Alix je nova TikTok senzacija', '2026-01-18 13:22:57'),
(138, 2, 8, 'U prtljažniku obavezno imajte ovih 14 stvari - za manje stresa!', 'https://www.24sata.hr/lifestyle/u-prtljazniku-obavezno-imajte-ovih-14-stvari-za-manje-stresa-1099910', '2026-01-18 12:00:00', 'Neki ne drže apsolutno ništa u prtljažnicima automobila, dok drugi imaju dovoljno stvari da bi mogli živjeti u automobilu tjednima. Negdje između nalazi se ovaj popis stvari koje bi svaki vlasnik automobila uvijek trebao imati pri ruci', '2026-01-18 13:22:57'),
(139, 2, 9, 'FOTO Vruće fotke: Paparazzi seksi sestre ulovili na plaži. Zaigrale su se na pijesku...', 'https://www.24sata.hr/fun/foto-vruce-fotke-paparazzi-seksi-sestre-ulovili-na-plazi-zaigrale-su-se-na-pijesku-1100948', '2026-01-18 09:00:00', 'Manekenka Brooks Nader boravila je u Cabo San Lucasu na odmoru gdje joj se pridružila mlađa sestra Sarah Jane. Sestre su uživale u suncu i opuštenoj atmosferi uz plažu...', '2026-01-18 13:22:57'),
(140, 2, 10, 'FOTO Njihova najveća podrška: Koga ljube naši rukometaši?', 'https://www.24sata.hr/sport/foto-njihova-najveca-podrska-koga-ljube-nasi-rukometasi-1101467', '2026-01-18 06:00:00', 'Hrvatska rukometna reprezentacija pobijedila je Gruziju na otvaranju Eura, a u pohodu na jedino zlato koje im nedostaje najglasnije navijačice su njihove bolje polovice. Neki su u vezi već nekoliko godina', '2026-01-18 13:22:57'),
(141, 3, 1, 'Zagrebački konobar zbog krađa mora odraditi 480 sati rada za opće dobro: ‘Svi ti kondomi bili su za druženje s jednom gospođom‘', 'https://www.jutarnji.hr/vijesti/crna-kronika/konobar-krao-parfeme-i-kondome-480-sati-rada-za-opce-dobro-zagreb-15662940', '2026-01-18 13:11:21', '\"Kriv sam i želio bih odmah iznijeti obranu. Je li mi žao? Naravno da jest. Jedan ukradeni parfem bio je za jednu osobu, a drugi za mene. Ukradeni prezervativi bili su, pak, za druženje s jednom gospođom, dok su Mustela kreme, gelovi i šamponi bili za prijateljicu koja ima malo dijete. Ne znam što d...', '2026-01-18 13:22:58'),
(142, 3, 2, 'U Hrvatsku sljedećeg tjedna stiže novi hladni val, DHMZ objavio upozorenje za ove regije...', 'https://www.jutarnji.hr/vijesti/hrvatska/u-hrvatsku-sljedeceg-tjedna-stize-novi-hladni-val-dhmz-objavio-upozorenje-za-ove-regije-15662938', '2026-01-18 12:58:00', 'Slavoniju će od slijedećeg tjedna \"stegnuti\" zima i niske temperature, a na veliku opasnost na hladne valove u Osječkoj regiji u ponedjeljak, utorak i srijedu upozorili su iz Državnog hidrometeorološkog zavoda. --- --- Umjerena opasnost na hladne valove u ponedjeljak, utorak i srijedu bit će u Zagre...', '2026-01-18 13:22:58'),
(143, 3, 3, 'FOTO: Pogledajte kako je ‘Međimurska princeza‘ slavila nakon pobjede u ‘Voiceu‘: ‘Ti si svemirski proizvod!‘', 'https://www.jutarnji.hr/scena/domace-zvijezde/foto-pogledajte-kako-je-medimurska-princeza-slavila-nakon-pobjede-u-voiceu-ti-si-svemirski-proizvod-15662935', '2026-01-18 12:45:00', '\"Osjećam se super i bilo je odlično\", rekla je uzbuđena devetogodišnja Nikol Kutnjak kroz suze nakon pobjede u finalu druge sezone showa \"The Voice Kids Hrvatska\". Njezin mentor bio je Davor Gobac, a u velikom je finalu pjevala \"Ti si princeza\" Dade Topića i Slađane Milošević te tradicionalnu pjesmu...', '2026-01-18 13:22:58'),
(144, 3, 4, 'Zvijezda RTL-ove hit serije ‘Divlje pčele‘ odrastala je u domu za nezbrinutu djecu: ‘To me u velikoj mjeri definiralo...‘', 'https://www.jutarnji.hr/scena/domace-zvijezde/ana-marija-veselcic-locarno-zlatni-studio-divlje-pcele-bog-nece-pomoci-15662925', '2026-01-18 12:07:00', 'Za glumicu Anu Mariju Veselčić prošla godina bit će definitivno za pamćenje. Talentirana 31-godišnja Vinkovčanka, stalna članica ansambla drame HNK Split, nanizala je nagrade za ulogu Milene u filmu \"Bog neće pomoći\" redateljice Hane Jušić, među kojima je i priznanje Leopard za najbolju glumačku int...', '2026-01-18 13:22:58'),
(145, 3, 5, 'Iranski dužnosnik: Broj potvrđenih žrtava u prosvjedima u Iranu dosegao najmanje 5000', 'https://www.jutarnji.hr/vijesti/svijet/iranski-duznosnik-broj-potvrdenih-zrtava-u-prosvjedima-u-iranu-dosegao-najmanje-5000-15662924', '2026-01-18 12:03:18', 'Iranski dužnosnik izjavio je u nedjelju da su vlasti potvrdile da je u prosvjedima u Iranu ubijeno najmanje 5000 ljudi, uključujući oko 500 pripadnika sigurnosnih snaga, okrivljavajući \"teroriste i naoružane izgrednike\" za ubojstvo \"nevinih Iranaca\". Dužnosnik, koji je želio ostati anoniman zbog osj...', '2026-01-18 13:22:58'),
(146, 3, 6, 'Imamo prvu reakciju hrvatske Vlade na Trumpovu prijetnju carinama zbog Grenlanda', 'https://www.jutarnji.hr/vijesti/hrvatska/imamo-prvu-reakciju-hrvatske-vlade-na-trumpovu-prijetnju-carinama-zbog-grenlanda-15662917', '2026-01-18 11:28:00', 'Stav hrvatske Vlade je da se saveznici u okviru NATO-a trebaju međusobno poštovati te uvažavati činjenicu da je Grenland dio Danske, priopćeno je danas iz Banskih dvora. Sve događaje vezane uz Grenland pratimo iz minute u minutu, opširnije pročitajte OVDJE \"U tom kontekstu izražavamo solidarnost s D...', '2026-01-18 13:22:58'),
(147, 3, 7, 'Katja Matković (13): Karizmatična Paulina P. svira klavir, crta i sigurna je da se i dalje želi baviti glumom', 'https://www.jutarnji.hr/scena/domace-zvijezde/katja-matkovic-novi-uspjesi-film-paulina-15662915', '2026-01-18 11:10:10', 'Mlada glumica Katja Matković (13) osvojila je srca gledatelja ulogom Pauline u filmu \"Dnevnik Pauline P.\". Osvojila je i članove žirija Zlatnog Studija 2025., koji su je nominirali u dvije kategorije, kao i glasače koji su je poslali u finale, a zatim i sve uzvanike na prošlogodišnjoj dodjeli nagrad...', '2026-01-18 13:22:58'),
(148, 3, 8, 'Indonezija pronašla ostatke nestalog zrakoplova s ​​10 tijela', 'https://www.jutarnji.hr/vijesti/svijet/olupina-atr-42-pronadena-na-bulusaraungu-kod-marosa-jedna-zrtva-15662913', '2026-01-18 11:03:29', 'Indonezijske vlasti u nedjelju su priopćile da su pronašle olupinu zrakoplova za nadzor ribarstva, koji je nestao u pokrajini Južni Sulawesi, na obronku planine prekrivene maglom, te da su pronašle tijelo jedne od 10 osoba u zrakoplovu. Turbopropelerski zrakoplov ATR 42-500 u vlasništvu zrakoplovne...', '2026-01-18 13:22:58'),
(149, 3, 9, 'Zagrebačka filharmonija otvorila koncertnu godinu u svjetskom centru klasične glazbe, Mozartovu gradu', 'https://www.jutarnji.hr/kultura/glazba/zagrebacka-filharmonija-otvorila-koncertnu-godinu-u-svjetskom-centru-klasicne-glazbe-mozartovu-gradu-15662908', '2026-01-18 10:56:43', 'Mozartov je grad istodobno i Karajanov – ta dva glazbena titana učinila su Salzburg nezaobilaznim središtem klasične glazbe u koji hrle svi koji je vole izvoditi i slušati, koji u njoj uživaju i s kojom se životno napajaju. Još od djelovanja Karajana tamo ekskluzivno pravo na redovite festivalske se...', '2026-01-18 13:22:58'),
(150, 3, 10, 'Može li Kongres zaustaviti Trumpa?', 'https://www.jutarnji.hr/vijesti/svijet/moze-li-kongres-zaustaviti-trumpa-15662902', '2026-01-18 10:33:00', 'Dok su Europljani šokirani Trumpovim najavama dodatnih carina zbog protivljenja njegovim namjerama o zauzimanju Grenlanda, raste nada da će američki Kongres, uključujući i republikance, zaustaviti Trumpa. Sve događaje vezane uz Grenland pratimo iz minute u minutu, opširnije pročitajte OVDJE Signali...', '2026-01-18 13:22:58'),
(151, 4, 1, 'Slavni glumac plakao je noćima: Živio je u kombiju, a tragedija s djevojkom ga je uništila', 'https://www.vecernji.hr/showbiz/slavni-glumac-plakao-je-nocima-zivio-je-u-kombiju-a-tragedija-s-djevojkom-ga-je-unistila-1925600', '2026-01-18 13:21:00', 'Jedan od najomiljenijih komičara na svijetu, Jim Carrey, izgradio je karijeru na zaraznom humoru i nevjerojatnoj energiji. Ipak, iza maske nasmijanog klauna krije se potresna priča o siromaštvu, depresiji i privatnim tragedijama koje su ga gotovo slomile', '2026-01-18 13:22:59'),
(152, 4, 2, 'Godina u kojoj se ukazivao \'duh ustaštva iz boce\'', 'https://www.vecernji.hr/vijesti/godina-u-kojoj-se-ukazivao-duh-ustastva-iz-boce-1925589', '2026-01-18 13:18:00', 'Tri su puta do odluke Ustavnog suda o Bojni Čavoglave i HOS-u. No otvoreno ostaje pitanje kako bi na apsolutnu zabranu ZDS-a reagirali branitelji, politička desnica i pola milijuna onih koji su pjevali na Hipodromu', '2026-01-18 13:22:59'),
(153, 4, 3, 'Šokantna ispovijest odvjetnice o vezi s nogometašem: \'Ponižavao me, pomišljala sam i na najgore\'', 'https://www.vecernji.hr/sport/sokantna-ispovijest-odvjetnice-o-vezi-s-nogometasem-ponizavao-me-pomisljala-sam-i-na-najgore-1925595', '2026-01-18 13:01:00', 'Bivši francuski nogometni reprezentativac Dimitri Payet (38) našao se u središtu teškog skandala nakon što ga je brazilska odvjetnica Larissa Ferrari (29) optužila za stravično zlostavljanje tijekom njihove sedmomjesečne veze', '2026-01-18 13:22:59'),
(154, 4, 4, 'EU sazvala hitan sastanak zbog Trumpovih prijetnji: Danci uzrujani, Macron poziva na korištenje \'trgovinske bazuke\'', 'https://www.vecernji.hr/vijesti/eu-sazvala-hitan-sastanak-zbog-trumpovih-prijetnji-1925548', '2026-01-18 12:57:00', 'Zemlje kojima je Trump zaprijetio carinama su ovog tjedna najavile raspoređivanje vojnog osoblja za izviđačku misiju na Grenlandu u sklopu danske vježbe \"Arctic Endurance\", organizirane sa saveznicima NATO-a.', '2026-01-18 13:22:59'),
(155, 4, 5, 'Bio je glazbeno čudo od djeteta, hvalila ga je i Metallica, a sada osvaja i film', 'https://www.vecernji.hr/kultura/bio-je-glazbeno-cudo-od-djeteta-hvalila-ga-je-i-metallica-a-sada-osvaja-i-film-1925599', '2026-01-18 12:53:00', 'Jadran Mihelčić, student pete godine klavira na Muzičkoj akademiji u Zagrebu, autor je svih skladbi centralnog koncerta kojim je u Hrvatskoj na Muzičkoj akademiji proslavljen Međunarodni rođendan umjetnosti. Hrvatsku će uskoro predstavljati na Europskom blues natjecanju u Poljskoj, potom i u Memphisu, a uskoro mu se smiješi međunarodna suradnja na jednom zanimljivom filmu', '2026-01-18 13:22:59'),
(156, 4, 6, 'VIDEO Srbi provocirali Nijemce, ovo nisu uspjeli sakriti od kamera, sve je snimljeno', 'https://www.vecernji.hr/sport/video-srbi-provocirali-nijemce-ovo-nisu-uspjeli-sakriti-od-kamera-sve-je-snimljeno-1925597', '2026-01-18 12:42:00', 'Rukometna reprezentacija Srbije priredila je prvorazrednu senzaciju na Europskom prvenstvu svladavši favoriziranu Njemačku. No, veliku pobjedu zasjenio je detalj nakon utakmice kada su srpski rukometaši odlučili provocirati suparnike, a sve su zabilježile kamere', '2026-01-18 13:22:59'),
(157, 4, 7, 'FOTO Nina Badrić uživa na odmoru: Pokazala zavidnu figuru u badiću i zapalila Instagram', 'https://www.vecernji.hr/showbiz/foto-nina-badric-uziva-na-odmoru-pokazala-zavidnu-figuru-u-badicu-i-zapalila-instagram-1925578', '2026-01-18 12:34:00', 'Objavila je fotografiju na kojoj samouvjereno šeće pješčanom plažom odjevena u elegantni jednodijelni kupaći kostim leopard uzorka. Uz preplanuli ten, sunčane naočale i slamnati šešir u ruci, Nina je izgledala poput prave holivudske zvijezde', '2026-01-18 13:22:59'),
(158, 4, 8, 'Veliki preokret na pomolu: Inter odustaje od Hajdukovog dragulja? Otkriveno čime ih je razljutio', 'https://www.vecernji.hr/sport/veliki-preokret-na-pomolu-inter-odustaje-od-hajdukovog-dragulja-otkriveno-cime-ih-je-razljutio-1925592', '2026-01-18 12:22:00', 'Fabrizio Romano otkrio je detalje posla Intera i Hajduka, kao i to da je mladi Mlačić svojim potezom transfer doveo u pitanje', '2026-01-18 13:22:59'),
(159, 4, 9, 'Trump traži milijardu dolara za članstvo u svom \'Odboru za mir\': Osobno će kontrolirati novac', 'https://www.vecernji.hr/vijesti/trump-trazi-milijardu-dolara-za-clanstvo-u-svom-odboru-za-mir-osobno-ce-kontrolirati-novac-1925593', '2026-01-18 12:21:00', 'Odbor za mir u statutu je opisan kao \"međunarodna organizacija koja nastoji promicati stabilnost, obnoviti pouzdanu i zakonitu vlast te osigurati trajni mir u područjima pogođenim ili ugroženim sukobom\".', '2026-01-18 13:22:59'),
(160, 4, 10, 'Dolazi kraj Dinamovog monopola? Ono što se donedavno činilo nezamislivim postaje stvarnost', 'https://www.vecernji.hr/sport/dolazi-kraj-dinamovog-monopola-ono-sto-se-donedavno-cinilo-nezamislivim-postaje-stvarnost-1925449', '2026-01-18 12:04:00', 'Hrvatski klupski nogomet više nije monopolni izvozni sustav u kojem sve ozbiljno prolazi isključivo kroz Maksimirsku 128. Liga u Hrvatskoj postaje popularnijom, no kao cjelina još nije popularna u širem marketinškom smislu', '2026-01-18 13:22:59'),
(161, 2, 1, 'FOTO Kako bi poznati hrvatski sportaši izgledali bez brade? Livaja, Petković, Joško, Rudić...', 'https://www.24sata.hr/sport/foto-kako-bi-poznati-hrvatski-sportasi-izgledali-bez-brade-livaja-petkovic-josko-rudic-1021299', '2026-01-18 14:25:00', 'Brade su postale zaštitni znak mnogih sportaša: od borilačkih arena do nogometnih stadiona. One simboliziraju snagu, disciplinu i karakter, ali i strpljenje, rutinu te osobni stil koji često prati vrhunske rezultate i samopouzdanje. A mi smo se poigrali i virtualno obrijali sportske face, nogometaše, košarkaše, vaterpoliste, trenere... Pogledajte galeriju', '2026-01-18 15:17:54'),
(162, 2, 2, 'Osam država kojima je Trump zaprijetio objavilo izjavu', 'https://www.24sata.hr/news/trump-1101792', '2026-01-18 15:05:00', 'Hrvatska je u nedjelju pozvala na jedinstven i koordiniran europski stav oko američkog \'nametanja\' carina državama koje su podržale Dansku u pogledu američkog svojatanja Grenlanda, otoka za koji vlada naglašava da je dio Danske...', '2026-01-18 15:17:54'),
(163, 2, 3, 'Ispovijest preživjele iz gorućeg kluba. Bilo je kao u Švicarskoj: \'Kao da ti je tijelo u pećnici...\'', 'https://www.24sata.hr/news/ispovijest-prezivjele-iz-goruceg-kluba-bilo-je-kao-u-svicarskoj-kao-da-ti-je-tijelo-u-pecnici-1101783', '2026-01-18 13:52:00', '\'To je noćna mora - gorivo je već iznad vas. Nemate vremena za reakciju. Kad dođe do flashovera, preživljavanje je gotovo nemoguće\', kaže žrtva', '2026-01-18 15:17:54'),
(164, 2, 4, 'VIDEO Srbi prasnuli u smijeh u prijenosu! Poslušajte reakciju', 'https://www.24sata.hr/sport/video-srbi-prasnuli-u-smijeh-u-prijenosu-poslusajte-reakciju-1101785', '2026-01-18 14:10:00', 'Srbija je pripremila najveći šok na Europskom rukometnom prvenstvu. Pobijedili su u subotu Njemačku u drugom kolu preliminarne runde 30-27, a pravi hit na društvenim mrežama postali su sportski komentatori u Srbiji', '2026-01-18 15:17:54'),
(165, 2, 5, 'FOTO Gužve na hrvatskoj granici', 'https://www.24sata.hr/news/foto-guzve-na-hrvatskoj-granici-1101787', '2026-01-18 14:33:00', 'Kolona osobnih i teretnih vozila pred graničnim prijelazom Bajakovo u smjeru Srbije je oko jedan kilometar, javlja HAK', '2026-01-18 15:17:54'),
(166, 2, 6, 'FOTO Sjećate se slatkog dječaka s Kinder čokolade? Matteo je danas uspješni ljepotan...', 'https://www.24sata.hr/fun/foto-sjecate-se-slatkog-djecaka-s-kinder-cokolade-matteo-je-danas-uspjesni-ljepotan-1025218', '2026-01-18 13:02:00', 'Lice nasmijanog dječaka krasilo je ambalažu Kinder čokolade od 2004. do 2019. godine, a mnogi su se pitali tko je zapravo taj dječak...', '2026-01-18 15:17:54'),
(167, 2, 7, 'Koristite li pećnicu pogrešno? Ovih 6 zanemarenih simbola većina ljudi ne razumije', 'https://www.24sata.hr/lifestyle/koristite-li-pecnicu-pogresno-ovih-6-zanemarenih-simbola-vecina-ljudi-ne-razumije-1098727', '2026-01-18 14:00:00', 'Možda mislite da ste shvatili kako radi vaša pećnica, ali većina nas nema pojma o pravom značenju zagonetnih simbola koji krase naš uređaj. Dešifriranje ovih ikona može biti minsko polje, a bez praktičnog priručnika, vaša gozba mogla bi platiti cijenu', '2026-01-18 15:17:54'),
(168, 2, 8, 'FOTO Vruće fotke: Paparazzi seksi sestre ulovili na plaži. Zaigrale su se na pijesku...', 'https://www.24sata.hr/fun/foto-vruce-fotke-paparazzi-seksi-sestre-ulovili-na-plazi-zaigrale-su-se-na-pijesku-1100948', '2026-01-18 09:00:00', 'Manekenka Brooks Nader boravila je u Cabo San Lucasu na odmoru gdje joj se pridružila mlađa sestra Sarah Jane. Sestre su uživale u suncu i opuštenoj atmosferi uz plažu...', '2026-01-18 15:17:54'),
(169, 2, 9, 'FOTO Tange, TikTok i Tom Brady. Zvijezda NFL-a partijala je na jahti s čak 23 godine mlađom', 'https://www.24sata.hr/sport/foto-tange-tiktok-i-tom-brady-zvijezda-nfl-a-partijala-je-na-jahti-s-cak-23-godine-mladom-1101446', '2026-01-18 11:14:00', 'Tom Brady (48) viđen s Alix Earle (25) na luksuznoj jahti! Njihovo druženje pokrenulo glasine o romansi, no Brady tvrdi da je fokusiran na posao i djecu. Alix je nova TikTok senzacija', '2026-01-18 15:17:54'),
(170, 2, 10, 'Ovih 11 znakova odaju da ste iznimno inteligentna osoba', 'https://www.24sata.hr/lifestyle/ovih-11-znakova-odaju-da-ste-iznimno-inteligentna-osoba-1021008', '2026-01-18 13:53:00', 'Iako mnogi misle da su inteligentni ljudi indiferentni, to je daleko od istine. Inteligentni ljudi nisu inteligentni zbog svog hladnog ponašanja; pravi znak iznimne inteligencije je njihova radoznalost', '2026-01-18 15:17:54'),
(171, 3, 1, 'Trump stvara svoju verziju UN-a. Cijena sudjelovanja? Milijardu dolara', 'https://www.jutarnji.hr/vijesti/svijet/trump-stvara-svoju-verziju-un-a-cijena-sudjelovanja-milijardu-dolara-15662959', '2026-01-18 14:50:00', 'Američki predsjednik Donald Trump ne zaustavlja se. Sada želi stvoriti vlastitu verziju Ujedinjenih naroda s ulaznom cijenom od milijardu dolara državama koje se namjeravaju uključiti u spomenuti projekt. Temelj svega jest Odbor za mir za nadzor Gaze, koji je osnovao Trump, a koji će po svemu sudeći...', '2026-01-18 15:18:03'),
(172, 3, 2, 'Parlament Republike Srpske ekspresno prihvatio ostavku Dodikovog premijera', 'https://www.jutarnji.hr/vijesti/svijet/parlament-republike-srpske-ekspresno-prihvatio-ostavku-dodikovog-premijera-15662952', '2026-01-18 13:59:47', 'Narodna skupština Republike Srpske u nedjelju je prihvatila ostavku entitetskog premijera čime je pala i cijela vlada a sve je to završeno na izvanrednoj sjednici u trajanju od petnaestak minuta pri čemu je predsjednik parlamenta Nenad Stevandić onemogućio svaku raspravu pa je oporba sve bojkotirala...', '2026-01-18 15:18:03'),
(173, 3, 3, 'Kako uzvratiti udarac Trumpu? Politico: Europa razmatra dosad nezamislive opcije', 'https://www.jutarnji.hr/vijesti/svijet/eu-odmazda-trump-carine-grenland-protumjere-trgovinska-bazuka-15662944', '2026-01-18 13:24:00', 'Odluka Donalda Trumpa da uvede carine zemljama koje su iskazale potporu Grenlandu dovodi transatlantske odnose do točke pucanja, a čelnici EU-a razmatraju načine odmazde protiv Washingtona koji su se dosad činili nezamislivima, piše Politico. Odnosi između Washingtona i Europe već su mjesecima nesta...', '2026-01-18 15:18:03'),
(174, 3, 4, 'Zagrebački konobar zbog krađa mora odraditi 480 sati rada za opće dobro: ‘Svi ti kondomi bili su za druženje s jednom gospođom‘', 'https://www.jutarnji.hr/vijesti/crna-kronika/konobar-krao-parfeme-i-kondome-480-sati-rada-za-opce-dobro-zagreb-15662940', '2026-01-18 13:11:21', '\"Kriv sam i želio bih odmah iznijeti obranu. Je li mi žao? Naravno da jest. Jedan ukradeni parfem bio je za jednu osobu, a drugi za mene. Ukradeni prezervativi bili su, pak, za druženje s jednom gospođom, dok su Mustela kreme, gelovi i šamponi bili za prijateljicu koja ima malo dijete. Ne znam što d...', '2026-01-18 15:18:03'),
(175, 3, 5, 'U Hrvatsku sljedećeg tjedna stiže novi hladni val, DHMZ objavio upozorenje za ove regije...', 'https://www.jutarnji.hr/vijesti/hrvatska/u-hrvatsku-sljedeceg-tjedna-stize-novi-hladni-val-dhmz-objavio-upozorenje-za-ove-regije-15662938', '2026-01-18 12:58:00', 'Slavoniju će od slijedećeg tjedna \"stegnuti\" zima i niske temperature, a na veliku opasnost na hladne valove u Osječkoj regiji u ponedjeljak, utorak i srijedu upozorili su iz Državnog hidrometeorološkog zavoda. --- --- Umjerena opasnost na hladne valove u ponedjeljak, utorak i srijedu bit će u Zagre...', '2026-01-18 15:18:03'),
(176, 3, 6, 'FOTO: Pogledajte kako je ‘Međimurska princeza‘ slavila nakon pobjede u ‘Voiceu‘: ‘Ti si svemirski proizvod!‘', 'https://www.jutarnji.hr/scena/domace-zvijezde/foto-pogledajte-kako-je-medimurska-princeza-slavila-nakon-pobjede-u-voiceu-ti-si-svemirski-proizvod-15662935', '2026-01-18 12:45:00', '\"Osjećam se super i bilo je odlično\", rekla je uzbuđena devetogodišnja Nikol Kutnjak kroz suze nakon pobjede u finalu druge sezone showa \"The Voice Kids Hrvatska\". Njezin mentor bio je Davor Gobac, a u velikom je finalu pjevala \"Ti si princeza\" Dade Topića i Slađane Milošević te tradicionalnu pjesmu...', '2026-01-18 15:18:03'),
(177, 3, 7, 'Zvijezda RTL-ove hit serije ‘Divlje pčele‘ odrastala je u domu za nezbrinutu djecu: ‘To me u velikoj mjeri definiralo...‘', 'https://www.jutarnji.hr/scena/domace-zvijezde/ana-marija-veselcic-locarno-zlatni-studio-divlje-pcele-bog-nece-pomoci-15662925', '2026-01-18 12:07:00', 'Za glumicu Anu Mariju Veselčić prošla godina bit će definitivno za pamćenje. Talentirana 31-godišnja Vinkovčanka, stalna članica ansambla drame HNK Split, nanizala je nagrade za ulogu Milene u filmu \"Bog neće pomoći\" redateljice Hane Jušić, među kojima je i priznanje Leopard za najbolju glumačku int...', '2026-01-18 15:18:03'),
(178, 3, 8, 'Iranski dužnosnik: Broj potvrđenih žrtava u prosvjedima u Iranu dosegao najmanje 5000', 'https://www.jutarnji.hr/vijesti/svijet/iranski-duznosnik-broj-potvrdenih-zrtava-u-prosvjedima-u-iranu-dosegao-najmanje-5000-15662924', '2026-01-18 12:03:18', 'Iranski dužnosnik izjavio je u nedjelju da su vlasti potvrdile da je u prosvjedima u Iranu ubijeno najmanje 5000 ljudi, uključujući oko 500 pripadnika sigurnosnih snaga, okrivljavajući \"teroriste i naoružane izgrednike\" za ubojstvo \"nevinih Iranaca\". Dužnosnik, koji je želio ostati anoniman zbog osj...', '2026-01-18 15:18:03'),
(179, 3, 9, 'Imamo prvu reakciju hrvatske Vlade na Trumpovu prijetnju carinama zbog Grenlanda', 'https://www.jutarnji.hr/vijesti/hrvatska/imamo-prvu-reakciju-hrvatske-vlade-na-trumpovu-prijetnju-carinama-zbog-grenlanda-15662917', '2026-01-18 11:28:00', 'Stav hrvatske Vlade je da se saveznici u okviru NATO-a trebaju međusobno poštovati te uvažavati činjenicu da je Grenland dio Danske, priopćeno je danas iz Banskih dvora. Sve događaje vezane uz Grenland pratimo iz minute u minutu, opširnije pročitajte OVDJE \"U tom kontekstu izražavamo solidarnost s D...', '2026-01-18 15:18:03'),
(180, 3, 10, 'Katja Matković (13): Karizmatična Paulina P. svira klavir, crta i sigurna je da se i dalje želi baviti glumom', 'https://www.jutarnji.hr/scena/domace-zvijezde/katja-matkovic-novi-uspjesi-film-paulina-15662915', '2026-01-18 11:10:10', 'Mlada glumica Katja Matković (13) osvojila je srca gledatelja ulogom Pauline u filmu \"Dnevnik Pauline P.\". Osvojila je i članove žirija Zlatnog Studija 2025., koji su je nominirali u dvije kategorije, kao i glasače koji su je poslali u finale, a zatim i sve uzvanike na prošlogodišnjoj dodjeli nagrad...', '2026-01-18 15:18:03');

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
(1, 'admin', '$2y$10$7lBLDiPS4ueIGjyT/HicWO15lSXxIGt/DPgYHcHaYzAeSXcHOzTgO', 'Administrator', 'admin@portal.hr', NULL, 'admin', NULL, 1, '2026-01-26 08:18:56', '2026-01-14 05:23:13', '2026-01-26 08:18:56'),
(5, 'ivek', '$2y$10$I9ov1VTU8MKSs4s8V0ciy.kUSFY.lvgpWmtoPwPDJBUaq/.woKcxK', 'Ivan Kovačić', 'ivek.privat@gmail.com', '', 'urednik', NULL, 1, '2026-01-18 14:17:42', '2026-01-14 06:17:11', '2026-01-18 14:17:42'),
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
  `supertitle` varchar(500) DEFAULT NULL,
  `title` varchar(500) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `author_text` varchar(255) DEFAULT NULL,
  `page_number` int(11) DEFAULT NULL,
  `char_count` int(11) DEFAULT 0,
  `word_count` int(11) DEFAULT 0,
  `status` enum('nacrt','za_pregled','odobreno','odbijeno','objavljeno') DEFAULT 'nacrt',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zl_articles`
--

INSERT INTO `zl_articles` (`id`, `issue_id`, `section_id`, `supertitle`, `title`, `subtitle`, `content`, `author_id`, `author_text`, `page_number`, `char_count`, `word_count`, `status`, `reviewed_by`, `reviewed_at`, `review_notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 2, 4, '', 'Općina Mače provodi besplatne edukativne aktivnosti za djecu', 'Provedbom ovog projekta Općina Mače nastavlja ulagati u obrazovanje, zdravlje i dobrobit djece, prepoznajući važnost ranog razvoja kao temelja za budući uspjeh', 'MAČE - U sklopu projekta koji Općina Mače provodi uz financijsku potporu Ministarstva demografije i useljeništva, poseban naglasak stavljen je na edukativne aktivnosti namijenjene predškolskoj djeci te djeci od 1. do 4. razreda osnovne škole. Cilj edukativnih aktivnosti je poticanje cjelovitog razvoja djece kroz učenje, igru i kreativno izražavanje, uz stvaranje pozitivnog i poticajnog okruženja za stjecanje novih znanja i vještina. Planirane edukativne aktivnosti uključuju: učenje stranih jezika (engleski i njemački) za predškolsku djecu, kroz prilagođene metode rada koje potiču razvoj komunikacijskih vještina, slušanja i govora, igraonicu \"Igrom do znanja\", u kojoj se kroz igru razvijaju socijalne vještine, suradnja i samopouzdanje, edukativne radionice za djecu od 1. do 4. razreda osnovne škole, usmjerene na razvoj kreativnosti, koncentracije, motoričkih sposobnosti i osnovnih životnih vještina, satove gimnastike, kojima se potiče zdrav razvoj tijela, pravilno držanje, koordinacija i ljubav prema kretanju i sportu. Sve aktivnosti osmišljene su tako da budu dobno primjerene, inkluzivne i dostupne, s ciljem pružanja dodatne podrške djeci i roditeljima te stvaranja kvalitetnih sadržaja unutar lokalne zajednice. Provedbom ovog projekta Općina Mače nastavlja ulagati u obrazovanje, zdravlje i dobrobit djece, prepoznajući važnost ranog razvoja kao temelja za budući uspjeh. (zl)', NULL, 'Sabina Sviben', 28, 1396, 186, 'odobreno', NULL, NULL, NULL, 1, '2026-01-19 09:56:44', '2026-01-19 09:56:44');

-- --------------------------------------------------------

--
-- Table structure for table `zl_article_images`
--

CREATE TABLE `zl_article_images` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `filepath` varchar(500) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `credit` varchar(255) DEFAULT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zl_article_images`
--

INSERT INTO `zl_article_images` (`id`, `article_id`, `filename`, `original_name`, `filepath`, `caption`, `credit`, `is_main`, `sort_order`, `created_at`) VALUES
(1, 1, '2026-01-19_105644_4fdee732.jpg', 'mace-1.jpg', 'zl-clanci/2026/01/2026-01-19_105644_4fdee732.jpg', 'm a', 'SABINA SVIBEN', 1, 0, '2026-01-19 09:56:44'),
(2, 1, '2026-01-19_105644_41d649d9.jpg', 'mace.jpg', 'zl-clanci/2026/01/2026-01-19_105644_41d649d9.jpg', 'm a', 'SABINA SVIBEN', 0, 0, '2026-01-19 09:56:44');

-- --------------------------------------------------------

--
-- Table structure for table `zl_issues`
--

CREATE TABLE `zl_issues` (
  `id` int(11) NOT NULL,
  `issue_number` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `publish_date` date NOT NULL,
  `status` enum('priprema','u_izradi','zatvoren') DEFAULT 'priprema',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zl_issues`
--

INSERT INTO `zl_issues` (`id`, `issue_number`, `year`, `publish_date`, `status`, `notes`, `created_by`, `created_at`) VALUES
(1, 1121, 2026, '2026-01-27', 'priprema', NULL, 1, '2026-01-19 09:55:36'),
(2, 1120, 2026, '2026-01-20', 'priprema', NULL, 1, '2026-01-19 09:56:44');

-- --------------------------------------------------------

--
-- Table structure for table `zl_sections`
--

CREATE TABLE `zl_sections` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zl_sections`
--

INSERT INTO `zl_sections` (`id`, `name`, `slug`, `sort_order`, `active`, `created_at`) VALUES
(1, 'Naslovnica', 'naslovnica', 1, 1, '2026-01-16 12:14:33'),
(2, 'Aktualno', 'aktualno', 2, 1, '2026-01-16 12:14:33'),
(3, 'Županija', 'zupanija', 3, 1, '2026-01-16 12:14:33'),
(4, 'Panorama', 'panorama', 4, 1, '2026-01-16 12:14:33'),
(5, 'Sport', 'sport', 5, 1, '2026-01-16 12:14:33'),
(6, 'Špajza', 'spajza', 6, 1, '2026-01-16 12:14:33'),
(7, 'Vodič', 'vodic', 7, 1, '2026-01-16 12:14:33'),
(8, 'Prilog', 'prilog', 8, 1, '2026-01-16 12:14:33'),
(9, 'Mala burza', 'mala-burza', 9, 1, '2026-01-16 12:14:33'),
(10, 'Nekretnine', 'nekretnine', 10, 1, '2026-01-16 12:14:33'),
(11, 'Zagorski oglasnik', 'zagorski-oglasnik', 11, 1, '2026-01-16 12:14:33'),
(12, 'Zadnja', 'zadnja', 12, 1, '2026-01-16 12:14:33'),
(13, 'Ostalo', 'ostalo', 99, 1, '2026-01-16 12:14:33');

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
  ADD KEY `author_id` (`author_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_issue` (`issue_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_section` (`section_id`);

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
  ADD UNIQUE KEY `unique_issue` (`issue_number`,`year`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `zl_sections`
--
ALTER TABLE `zl_sections`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=183;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `event_assignments`
--
ALTER TABLE `event_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `photos`
--
ALTER TABLE `photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portali`
--
ALTER TABLE `portali`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `portal_najcitanije`
--
ALTER TABLE `portal_najcitanije`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `zl_articles`
--
ALTER TABLE `zl_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `zl_article_images`
--
ALTER TABLE `zl_article_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `zl_issues`
--
ALTER TABLE `zl_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `zl_sections`
--
ALTER TABLE `zl_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- Constraints for table `zl_articles`
--
ALTER TABLE `zl_articles`
  ADD CONSTRAINT `zl_articles_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `zl_issues` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `zl_articles_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `zl_sections` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `zl_articles_ibfk_3` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `zl_articles_ibfk_4` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `zl_articles_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zl_article_images`
--
ALTER TABLE `zl_article_images`
  ADD CONSTRAINT `zl_article_images_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `zl_articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zl_issues`
--
ALTER TABLE `zl_issues`
  ADD CONSTRAINT `zl_issues_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
