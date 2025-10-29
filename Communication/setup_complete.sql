-- ====================================================================
-- COMPLETE SETUP - Church Management System Social Media Module
-- ====================================================================
-- This script will:
-- 1. Create the database if it doesn't exist
-- 2. Create all required tables
-- 3. Insert sample data
-- ====================================================================

-- Step 1: Create database
CREATE DATABASE IF NOT EXISTS `church_management_system` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Step 2: Use the database
USE `church_management_system`;

-- Step 3: Create tables
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

-- Messages Table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_type` varchar(50) DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `delivery_channels` json DEFAULT NULL,
  `status` enum('draft', 'sent', 'scheduled', 'failed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `scheduled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Social Posts Table (legacy compatibility)
CREATE TABLE IF NOT EXISTS `social_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `status` enum('draft', 'scheduled', 'published', 'failed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `scheduled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 4: Insert sample data for social media accounts
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

-- ====================================================================
-- SETUP COMPLETE!
-- ====================================================================
-- You should now see:
-- - Database: church_management_system (created)
-- - 5 tables created
-- - 4 social media accounts with sample data
-- 
-- Next step: Refresh test_social_api.php to verify
-- ====================================================================
