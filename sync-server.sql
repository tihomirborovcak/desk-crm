-- Sync za zagorski-list.net server
-- Dodaje tablice i podatke koji fale

-- =====================
-- 1. DODAJ PORTALE KOJI FALE
-- =====================
INSERT IGNORE INTO portali (id, naziv, url, rss_url, aktivan) VALUES
(5, 'Zagorje International', 'https://zagorje-international.hr', 'https://zagorje-international.hr/feed', 1),
(6, 'Net.hr', 'https://net.hr', 'https://net.hr/feed', 1);

-- =====================
-- 2. TABLICA TRANSCRIPTIONS
-- =====================
CREATE TABLE IF NOT EXISTS `transcriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `audio_filename` varchar(255) DEFAULT NULL,
  `transcript` longtext DEFAULT NULL,
  `article` longtext DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transcriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

-- =====================
-- 3. TABLICE ZA ZL ČLANKE
-- =====================
CREATE TABLE IF NOT EXISTS `zl_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

CREATE TABLE IF NOT EXISTS `zl_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_number` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `issue_number` (`issue_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

CREATE TABLE IF NOT EXISTS `zl_articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `title` varchar(500) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `author` varchar(200) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `page_number` int(11) DEFAULT NULL,
  `status` enum('draft','review','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`),
  KEY `section_id` (`section_id`),
  CONSTRAINT `zl_articles_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `zl_issues` (`id`) ON DELETE SET NULL,
  CONSTRAINT `zl_articles_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `zl_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

CREATE TABLE IF NOT EXISTS `zl_article_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  CONSTRAINT `zl_article_images_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `zl_articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

-- =====================
-- 4. DEFAULT ZL SEKCIJE
-- =====================
INSERT IGNORE INTO zl_sections (id, name, slug, sort_order) VALUES
(1, 'Naslovnica', 'naslovnica', 1),
(2, 'Aktualno', 'aktualno', 2),
(3, 'Politika', 'politika', 3),
(4, 'Gospodarstvo', 'gospodarstvo', 4),
(5, 'Kultura', 'kultura', 5),
(6, 'Sport', 'sport', 6),
(7, 'Crna kronika', 'crna-kronika', 7),
(8, 'Zabava', 'zabava', 8);

-- Gotovo!
SELECT 'Sync completed!' AS status;
