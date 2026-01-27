-- Sync za zagorje-promocija.com server
-- Dodaje tablicu i portale koji fale

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

-- Gotovo!
SELECT 'Sync promoc completed!' AS status;
