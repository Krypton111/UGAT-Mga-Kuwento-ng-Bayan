-- Run this once in the game_launcher database.
-- This table stores completed playthrough durations separately from high scores.
CREATE TABLE IF NOT EXISTS game_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_name VARCHAR(100) NOT NULL,
    elapsed_seconds INT UNSIGNED NOT NULL,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_game_runs_user_game_time (user_id, game_name, elapsed_seconds),
    INDEX idx_game_runs_completed (user_id, game_name, completed_at),
    CONSTRAINT fk_game_runs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
