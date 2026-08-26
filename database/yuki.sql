-- Yuki Movie Streaming Platform Database
-- Created: 2026
-- UTF-8 Arabic Support

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `watch_history`;
DROP TABLE IF EXISTS `episodes`;
DROP TABLE IF EXISTS `content`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `genres`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

-- Users table
CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT 'default-avatar.png',
  `role` ENUM('user','admin') DEFAULT 'user',
  `status` ENUM('active','banned') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE `categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'film',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Genres table
CREATE TABLE `genres` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Content table (movies & series)
CREATE TABLE `content` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `type` ENUM('movie','series') NOT NULL,
  `category_id` INT(11) UNSIGNED DEFAULT NULL,
  `genre_id` INT(11) UNSIGNED DEFAULT NULL,
  `poster` VARCHAR(255) DEFAULT 'default-poster.jpg',
  `backdrop` VARCHAR(255) DEFAULT 'default-backdrop.jpg',
  `trailer` VARCHAR(255) DEFAULT NULL,
  `rating` DECIMAL(2,1) DEFAULT 0.0,
  `year` INT(4) DEFAULT NULL,
  `duration` INT(11) DEFAULT NULL,
  `quality` VARCHAR(20) DEFAULT 'HD',
  `views` INT(11) UNSIGNED DEFAULT 0,
  `status` ENUM('published','draft','pending') DEFAULT 'draft',
  `featured` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `type` (`type`),
  KEY `category_id` (`category_id`),
  KEY `genre_id` (`genre_id`),
  KEY `featured` (`featured`),
  KEY `status` (`status`),
  CONSTRAINT `fk_content_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_content_genre` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Video servers table
CREATE TABLE `video_servers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `content_id` INT(11) UNSIGNED NOT NULL,
  `server_name` ENUM('streamhg','earnvids','mixdrop','doodstream') NOT NULL,
  `embed_url` TEXT NOT NULL,
  `quality` VARCHAR(20) DEFAULT 'HD',
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `content_id` (`content_id`),
  CONSTRAINT `fk_video_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Episodes table (for series)
CREATE TABLE `episodes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `series_id` INT(11) UNSIGNED NOT NULL,
  `season` INT(3) NOT NULL,
  `episode_number` INT(3) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `duration` INT(11) DEFAULT NULL,
  `poster` VARCHAR(255) DEFAULT NULL,
  `views` INT(11) UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `series_id` (`series_id`),
  KEY `season` (`season`),
  CONSTRAINT `fk_episodes_series` FOREIGN KEY (`series_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Episode servers table
CREATE TABLE `episode_servers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `episode_id` INT(11) UNSIGNED NOT NULL,
  `server_name` ENUM('streamhg','earnvids','mixdrop','doodstream') NOT NULL,
  `embed_url` TEXT NOT NULL,
  `quality` VARCHAR(20) DEFAULT 'HD',
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `episode_id` (`episode_id`),
  CONSTRAINT `fk_episode_server` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites table
CREATE TABLE `favorites` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `content_id` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_content` (`user_id`, `content_id`),
  KEY `user_id` (`user_id`),
  KEY `content_id` (`content_id`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watch history table
CREATE TABLE `watch_history` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `content_id` INT(11) UNSIGNED NOT NULL,
  `episode_id` INT(11) UNSIGNED DEFAULT NULL,
  `progress` INT(11) DEFAULT 0,
  `watched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `content_id` (`content_id`),
  CONSTRAINT `fk_wh_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wh_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE `settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES 
('admin', 'admin@yuki.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default categories
INSERT INTO `categories` (`name`, `slug`, `icon`) VALUES
('أفلام', 'movies', 'film'),
('مسلسلات', 'series', 'tv'),
('أنمي', 'anime', 'star'),
('وثائقي', 'documentary', 'book'),
('كرتون', 'cartoon', 'smile');

-- Insert default genres
INSERT INTO `genres` (`name`, `slug`) VALUES
('أكشن', 'action'),
('دراما', 'drama'),
('كوميديا', 'comedy'),
('رعب', 'horror'),
('خيال علمي', 'sci-fi'),
('إثارة', 'thriller'),
('رومانسي', 'romance'),
('مغامرة', 'adventure'),
('جريمة', 'crime'),
('عائلي', 'family');

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Yuki'),
('site_description', 'منصة Yuki للبث السينمائي'),
('site_logo', 'logo.png'),
('facebook_url', 'https://www.facebook.com/share/14dXFFBeYYp/'),
('instagram_url', 'https://www.instagram.com/kyou__999'),
('telegram_url', 'https://t.me/213557740724'),
('twitter_url', 'https://x.com/Yuki____999'),
('maintenance_mode', '0'),
('allow_registration', '1'),
('default_server', 'streamhg');

SET FOREIGN_KEY_CHECKS = 1;
