-- Portal CMS - Import za cPanel
-- Baza: slatkidar_tihomi_portal_cms

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================
-- TABLICE
-- =====================

-- Korisnici
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role ENUM('novinar', 'urednik', 'admin') NOT NULL DEFAULT 'novinar',
    avatar VARCHAR(255),
    active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Taskovi / Zadaci
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT,
    created_by INT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'in_progress', 'done', 'cancelled') DEFAULT 'pending',
    due_date DATE,
    due_time TIME,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_assigned (assigned_to),
    INDEX idx_status (status),
    INDEX idx_due (due_date)
) ENGINE=InnoDB;

-- Komentari na taskove
CREATE TABLE task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Događanja / Eventi
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    event_date DATE NOT NULL,
    event_time TIME,
    all_day TINYINT(1) DEFAULT 0,
    shift_type ENUM('urednik', 'novinar', 'fotograf', 'web', 'dezurni') DEFAULT NULL,
    end_time TIME,
    event_type ENUM('press', 'sport', 'kultura', 'politika', 'drustvo', 'dezurstvo', 'ostalo') DEFAULT 'ostalo',
    importance ENUM('normal', 'important', 'must_cover') DEFAULT 'normal',
    created_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_date (event_date),
    INDEX idx_type (event_type)
) ENGINE=InnoDB;

-- Tko ide na događaj
CREATE TABLE event_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('reporter', 'photographer', 'camera', 'backup') DEFAULT 'reporter',
    confirmed TINYINT(1) DEFAULT 0,
    notes VARCHAR(255),
    assigned_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_assignment (event_id, user_id)
) ENGINE=InnoDB;

-- RSS izvori
CREATE TABLE rss_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    website VARCHAR(255),
    logo VARCHAR(255),
    category VARCHAR(50),
    active TINYINT(1) DEFAULT 1,
    last_fetch DATETIME,
    fetch_interval INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- RSS članci (cache)
CREATE TABLE rss_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    link VARCHAR(1000) NOT NULL,
    description TEXT,
    pub_date DATETIME,
    guid VARCHAR(500),
    image VARCHAR(1000),
    is_read TINYINT(1) DEFAULT 0,
    is_starred TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES rss_sources(id) ON DELETE CASCADE,
    UNIQUE KEY unique_guid (source_id, guid(255)),
    INDEX idx_source (source_id),
    INDEX idx_date (pub_date),
    INDEX idx_starred (is_starred)
) ENGINE=InnoDB;

-- Dežurstva
CREATE TABLE shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shift_date DATE NOT NULL,
    shift_type ENUM('morning', 'afternoon', 'full') NOT NULL,
    notes TEXT,
    assigned_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_shift (user_id, shift_date, shift_type),
    INDEX idx_date (shift_date)
) ENGINE=InnoDB;

-- Fotografije
CREATE TABLE photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    thumbnail VARCHAR(500),
    mime_type VARCHAR(50),
    file_size INT,
    width INT,
    height INT,
    caption TEXT,
    credit VARCHAR(255),
    event_id INT,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event (event_id),
    INDEX idx_uploader (uploaded_by)
) ENGINE=InnoDB;

-- Aktivnosti (log)
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Teme
CREATE TABLE themes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('vijesti', 'sport', 'kultura', 'gospodarstvo', 'lifestyle', 'crna_kronika', 'politika', 'lokalno', 'ostalo') DEFAULT 'vijesti',
    week_number INT NOT NULL,
    year INT NOT NULL,
    planned_date DATE,
    status ENUM('predlozeno', 'odobreno', 'u_izradi', 'zavrseno', 'odbijeno') DEFAULT 'predlozeno',
    priority ENUM('niska', 'normalna', 'visoka', 'hitno') DEFAULT 'normalna',
    proposed_by INT NOT NULL,
    approved_by INT,
    assigned_to INT,
    notes TEXT,
    rejection_reason VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proposed_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_week (year, week_number),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Komentari na teme
CREATE TABLE theme_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    theme_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================
-- POČETNI PODACI
-- =====================

-- Admin korisnik (lozinka: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@portal.hr', 'admin'),
('urednik1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ivan Horvat', 'ivan@portal.hr', 'urednik'),
('novinar1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana Kovač', 'ana@portal.hr', 'novinar'),
('novinar2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marko Babić', 'marko@portal.hr', 'novinar');

-- RSS izvori
INSERT INTO rss_sources (name, url, website, category) VALUES
('Večernji list', 'https://www.vecernji.hr/feeds/latest', 'https://www.vecernji.hr', 'nacional'),
('24sata', 'https://www.24sata.hr/feeds/news.xml', 'https://www.24sata.hr', 'nacional'),
('Index.hr', 'https://www.index.hr/rss', 'https://www.index.hr', 'nacional'),
('Jutarnji list', 'https://www.jutarnji.hr/feed', 'https://www.jutarnji.hr', 'nacional'),
('Net.hr', 'https://net.hr/feed', 'https://net.hr', 'nacional'),
('Dnevnik.hr', 'https://dnevnik.hr/assets/feed/articles/', 'https://dnevnik.hr', 'nacional'),
('RTL.hr', 'https://www.rtl.hr/feed/', 'https://www.rtl.hr', 'nacional'),
('HRT Vijesti', 'https://vijesti.hrt.hr/feed/all', 'https://vijesti.hrt.hr', 'nacional'),
('Telegram', 'https://www.telegram.hr/feed/', 'https://www.telegram.hr', 'nacional'),
('Sportske novosti', 'https://sportske.jutarnji.hr/feed', 'https://sportske.jutarnji.hr', 'sport');

SET FOREIGN_KEY_CHECKS = 1;
