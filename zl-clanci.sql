-- Zagorski list - Članci

-- Rubrike/sekcije
CREATE TABLE IF NOT EXISTS zl_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Rubrike Zagorskog lista
INSERT INTO zl_sections (name, slug, sort_order) VALUES
('Naslovnica', 'naslovnica', 1),
('Aktualno', 'aktualno', 2),
('Županija', 'zupanija', 3),
('Panorama', 'panorama', 4),
('Sport', 'sport', 5),
('Špajza', 'spajza', 6),
('Vodič', 'vodic', 7),
('Prilog', 'prilog', 8),
('Mala burza', 'mala-burza', 9),
('Nekretnine', 'nekretnine', 10),
('Zagorski oglasnik', 'zagorski-oglasnik', 11),
('Zadnja', 'zadnja', 12),
('Ostalo', 'ostalo', 99);

-- Brojevi/izdanja ZL
CREATE TABLE IF NOT EXISTS zl_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_number INT NOT NULL,
    year INT NOT NULL,
    publish_date DATE NOT NULL,
    status ENUM('priprema', 'u_izradi', 'zatvoren') DEFAULT 'priprema',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_issue (issue_number, year),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Članci
CREATE TABLE IF NOT EXISTS zl_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT,
    section_id INT,

    -- Tekstualni sadržaj
    supertitle VARCHAR(500),
    title VARCHAR(500) NOT NULL,
    subtitle TEXT,
    content LONGTEXT,

    -- Meta podaci
    author_id INT,
    author_text VARCHAR(255),
    page_number INT,
    char_count INT DEFAULT 0,
    word_count INT DEFAULT 0,

    -- Status workflow
    status ENUM('nacrt', 'za_pregled', 'odobreno', 'odbijeno', 'objavljeno') DEFAULT 'nacrt',
    reviewed_by INT,
    reviewed_at DATETIME,
    review_notes TEXT,

    -- Timestamps
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (issue_id) REFERENCES zl_issues(id) ON DELETE SET NULL,
    FOREIGN KEY (section_id) REFERENCES zl_sections(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_issue (issue_id),
    INDEX idx_status (status),
    INDEX idx_section (section_id)
) ENGINE=InnoDB;

-- Slike uz članke
CREATE TABLE IF NOT EXISTS zl_article_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    caption TEXT,
    credit VARCHAR(255),
    is_main TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES zl_articles(id) ON DELETE CASCADE
) ENGINE=InnoDB;
