CREATE TABLE IF NOT EXISTS app_sessions (
    session_id VARCHAR(128) NOT NULL PRIMARY KEY,
    session_data MEDIUMBLOB NOT NULL,
    last_activity BIGINT UNSIGNED NOT NULL,
    INDEX idx_app_sessions_last_activity (last_activity)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS app_media (
    id CHAR(32) NOT NULL PRIMARY KEY,
    image_data MEDIUMBLOB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
