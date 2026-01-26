-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: portal_cms
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `portali`
--

DROP TABLE IF EXISTS `portali`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portali` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `naziv` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `rss_url` varchar(255) DEFAULT NULL,
  `aktivan` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portali`
--

LOCK TABLES `portali` WRITE;
/*!40000 ALTER TABLE `portali` DISABLE KEYS */;
INSERT INTO `portali` VALUES (1,'Index.hr','https://www.index.hr','https://www.index.hr/rss',1,'2026-01-16 08:53:59'),(2,'24sata','https://www.24sata.hr','https://www.24sata.hr/feeds/aktualno.xml',1,'2026-01-16 08:53:59'),(3,'Jutarnji list','https://www.jutarnji.hr','https://www.jutarnji.hr/feed',1,'2026-01-16 08:53:59'),(4,'Večernji list','https://www.vecernji.hr','https://www.vecernji.hr/feeds/latest',1,'2026-01-16 08:53:59'),(5,'Zagorje International','https://zagorje-international.hr','https://zagorje-international.hr/feed',1,'2026-01-18 11:03:46'),(6,'Net.hr','https://net.hr','https://net.hr/feed',1,'2026-01-18 11:03:46');
/*!40000 ALTER TABLE `portali` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rss_sources`
--

DROP TABLE IF EXISTS `rss_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rss_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `url` varchar(500) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `last_fetch` datetime DEFAULT NULL,
  `fetch_interval` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rss_sources`
--

LOCK TABLES `rss_sources` WRITE;
/*!40000 ALTER TABLE `rss_sources` DISABLE KEYS */;
INSERT INTO `rss_sources` VALUES (17,'Zagorje International','https://www.zagorje-international.hr/index.php/feed/','https://zagorje-international.hr',NULL,'lokalno',1,'2026-01-12 09:14:35',30,'2026-01-12 08:05:31'),(18,'Radio Stubica','https://radio-stubica.hr/feed/','https://radio-stubica.hr',NULL,'lokalno',1,'2026-01-12 09:14:36',30,'2026-01-12 08:05:31');
/*!40000 ALTER TABLE `rss_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zl_sections`
--

DROP TABLE IF EXISTS `zl_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zl_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zl_sections`
--

LOCK TABLES `zl_sections` WRITE;
/*!40000 ALTER TABLE `zl_sections` DISABLE KEYS */;
INSERT INTO `zl_sections` VALUES (1,'Naslovnica','naslovnica',1,1,'2026-01-16 06:42:38'),(2,'Aktualno','aktualno',2,1,'2026-01-16 06:42:38'),(3,'Županija','zupanija',3,1,'2026-01-16 06:42:38'),(4,'Panorama','panorama',4,1,'2026-01-16 06:42:38'),(5,'Sport','sport',5,1,'2026-01-16 06:42:38'),(6,'Špajza','spajza',6,1,'2026-01-16 06:42:38'),(7,'Vodič','vodic',7,1,'2026-01-16 06:42:38'),(8,'Prilog','prilog',8,1,'2026-01-16 06:42:38'),(9,'Mala burza','mala-burza',9,1,'2026-01-16 06:42:38'),(10,'Nekretnine','nekretnine',10,1,'2026-01-16 06:42:38'),(11,'Zagorski oglasnik','zagorski-oglasnik',11,1,'2026-01-16 06:42:38'),(12,'Zadnja','zadnja',12,1,'2026-01-16 06:42:38'),(13,'Ostalo','ostalo',99,1,'2026-01-16 06:42:38');
/*!40000 ALTER TABLE `zl_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zl_issues`
--

DROP TABLE IF EXISTS `zl_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zl_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_number` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `publish_date` date NOT NULL,
  `status` enum('priprema','u_izradi','zatvoren') DEFAULT 'priprema',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_issue` (`issue_number`,`year`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `zl_issues_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zl_issues`
--

LOCK TABLES `zl_issues` WRITE;
/*!40000 ALTER TABLE `zl_issues` DISABLE KEYS */;
INSERT INTO `zl_issues` VALUES (1,1120,2026,'2026-01-20','priprema',NULL,1,'2026-01-16 06:42:55'),(2,1119,2026,'2026-01-13','priprema',NULL,1,'2026-01-16 07:57:42');
/*!40000 ALTER TABLE `zl_issues` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-26  8:35:11
