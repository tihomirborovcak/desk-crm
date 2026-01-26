-- Transkripcije audio snimki
CREATE TABLE IF NOT EXISTS transcriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    transcript LONGTEXT,
    article LONGTEXT,
    audio_filename VARCHAR(255),
    audio_path VARCHAR(500),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Ako tablica već postoji, dodaj stupac audio_path
-- ALTER TABLE transcriptions ADD COLUMN audio_path VARCHAR(500) AFTER audio_filename;
