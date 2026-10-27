-- Run once in the existing game_launcher database.
-- One row per account, game, and achievement. Duplicate submissions do not duplicate rows.
USE game_launcher;

CREATE TABLE IF NOT EXISTS player_achievements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_name VARCHAR(100) NOT NULL,
    achievement_id VARCHAR(64) NOT NULL,
    unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_player_game_achievement (user_id, game_name, achievement_id),
    KEY idx_player_achievements_user_game (user_id, game_name),
    CONSTRAINT fk_player_achievements_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
