-- Tablica za više assignee-a po tasku
-- Pokreni ovo na serveru

CREATE TABLE IF NOT EXISTS task_assignees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (task_id, user_id)
);

-- Migriraj postojeće assigned_to u novu tablicu
INSERT IGNORE INTO task_assignees (task_id, user_id)
SELECT id, assigned_to FROM tasks WHERE assigned_to IS NOT NULL;

-- Kreator taska je automatski assignee
INSERT IGNORE INTO task_assignees (task_id, user_id)
SELECT id, created_by FROM tasks WHERE created_by IS NOT NULL;
