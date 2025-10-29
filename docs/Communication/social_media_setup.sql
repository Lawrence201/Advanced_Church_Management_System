-- NOTE: Make sure you're using database 'church_management_system'
-- Or use setup_complete.sql which creates the database automatically

-- Social Media Accounts Table
CREATE TABLE IF NOT EXISTS `social_media_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `account_id` varchar(100) DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `is_connected` tinyint(1) DEFAULT 0,
  `followers_count` int(11) DEFAULT 0,
  `engagement_count` int(11) DEFAULT 0,
  `engagement_rate` decimal(5,2) DEFAULT 0.00,
  `additional_metrics` json DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_unique` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default social media platforms
INSERT INTO `social_media_accounts` (`platform`, `is_connected`, `followers_count`, `engagement_count`, `engagement_rate`, `additional_metrics`) 
VALUES 
('facebook', 1, 4200, 342, 8.10, '{"views": 0, "growth": 0}'),
('instagram', 1, 3800, 512, 13.50, '{"views": 0, "growth": 0}'),
('twitter', 1, 2100, 189, 9.00, '{"views": 0, "growth": 0}'),
('youtube', 1, 1500, 0, 8.30, '{"views": 12400, "growth": 8.30}')
ON DUPLICATE KEY UPDATE 
  `followers_count` = VALUES(`followers_count`),
  `engagement_count` = VALUES(`engagement_count`),
  `engagement_rate` = VALUES(`engagement_rate`),
  `additional_metrics` = VALUES(`additional_metrics`);

-- Social Media Posts History Table
CREATE TABLE IF NOT EXISTS `social_media_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `post_type` enum('text', 'image', 'video', 'reel', 'story', 'live', 'playlist') DEFAULT 'text',
  `content` text NOT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `post_url` varchar(255) DEFAULT NULL,
  `likes_count` int(11) DEFAULT 0,
  `comments_count` int(11) DEFAULT 0,
  `shares_count` int(11) DEFAULT 0,
  `views_count` int(11) DEFAULT 0,
  `status` enum('draft', 'scheduled', 'published', 'failed') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `platform` (`platform`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Social Media Analytics Table (for tracking trends over time)
CREATE TABLE IF NOT EXISTS `social_media_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `followers_count` int(11) DEFAULT 0,
  `new_followers` int(11) DEFAULT 0,
  `engagement_count` int(11) DEFAULT 0,
  `engagement_rate` decimal(5,2) DEFAULT 0.00,
  `posts_count` int(11) DEFAULT 0,
  `reach` int(11) DEFAULT 0,
  `impressions` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_date` (`platform`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
