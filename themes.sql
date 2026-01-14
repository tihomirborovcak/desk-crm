-- Teme za Zagorski list
-- Pokreni u phpMyAdmin

CREATE TABLE IF NOT EXISTS themes (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Komentari na teme
CREATE TABLE IF NOT EXISTS theme_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    theme_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
