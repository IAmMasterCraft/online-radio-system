-- ============================================
-- TRADITIONAL RADIO SYSTEM - DATABASE SCHEMA
-- ============================================
-- Run this SQL to set up your database tables.
-- Or use install.php for automatic setup.
-- ============================================

CREATE TABLE IF NOT EXISTS radio_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    artist VARCHAR(255) DEFAULT '',
    description TEXT,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    media_type ENUM('audio', 'video') NOT NULL DEFAULT 'audio',
    mime_type VARCHAR(100) DEFAULT '',
    duration FLOAT NOT NULL DEFAULT 0 COMMENT 'Duration in seconds',
    file_size BIGINT DEFAULT 0,
    is_loop TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = filler/loop track',
    loop_position INT NOT NULL DEFAULT 0 COMMENT 'Order in loop playlist',
    cover_image VARCHAR(500) DEFAULT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_loop (is_loop, loop_position),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS radio_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    media_id INT NOT NULL,
    title VARCHAR(255) DEFAULT NULL COMMENT 'Optional override title for this slot',
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL COMMENT 'Auto-calculated: start_time + media duration',
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (media_id) REFERENCES radio_media(id) ON DELETE CASCADE,
    INDEX idx_schedule_time (start_time, end_time),
    INDEX idx_active_schedule (active, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS radio_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT INTO radio_settings (setting_key, setting_value) VALUES
('station_name', 'Online Radio'),
('station_tagline', 'Broadcasting hope, one story at a time'),
('loop_epoch', '2024-01-01 00:00:00'),
('youtube_api_key', '')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

CREATE TABLE IF NOT EXISTS radio_listen_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    media_id INT,
    live_stream_id INT,
    listen_time DATETIME NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    FOREIGN KEY (media_id) REFERENCES radio_media(id) ON DELETE SET NULL,
    FOREIGN KEY (live_stream_id) REFERENCES radio_live_streams(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS radio_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    media_id INT NOT NULL,
    requester_name VARCHAR(255),
    message TEXT,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (media_id) REFERENCES radio_media(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS radio_live_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform ENUM('youtube', 'facebook', 'tiktok', 'instagram') NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_checked DATETIME DEFAULT NULL,
    is_live TINYINT(1) NOT NULL DEFAULT 0,
    stream_url VARCHAR(500) DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active_streams (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
