-- ====================================================================
-- COMPREHENSIVE COMMUNICATION SYSTEM - Database Setup
-- ====================================================================
-- This creates a complete email/SMS communication system for your church
-- Features: Email, SMS, Recipient Groups, Scheduling, Delivery Tracking
-- ====================================================================

-- Ensure we're using the correct database
USE `church_management_system`;

-- ====================================================================
-- TABLE 1: MESSAGES (Main message storage)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` INT(11) NOT NULL AUTO_INCREMENT,
  `message_type` ENUM('announcement', 'event', 'prayer_request', 'newsletter', 'general') DEFAULT 'general',
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `delivery_channels` JSON DEFAULT NULL COMMENT 'Array of channels: email, sms, push',
  `status` ENUM('draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled') DEFAULT 'draft',
  `created_by` INT(11) DEFAULT NULL COMMENT 'Admin/User who created the message',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `scheduled_at` DATETIME DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `total_recipients` INT(11) DEFAULT 0,
  `total_sent` INT(11) DEFAULT 0,
  `total_failed` INT(11) DEFAULT 0,
  `total_opened` INT(11) DEFAULT 0,
  `total_clicked` INT(11) DEFAULT 0,
  PRIMARY KEY (`message_id`),
  KEY `status` (`status`),
  KEY `message_type` (`message_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 2: MESSAGE RECIPIENTS (Who receives each message)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `message_recipients` (
  `recipient_id` INT(11) NOT NULL AUTO_INCREMENT,
  `message_id` INT(11) NOT NULL,
  `member_id` INT(11) DEFAULT NULL COMMENT 'Links to members table',
  `recipient_type` ENUM('member', 'visitor', 'custom') DEFAULT 'member',
  `recipient_name` VARCHAR(255) DEFAULT NULL,
  `recipient_email` VARCHAR(255) DEFAULT NULL,
  `recipient_phone` VARCHAR(20) DEFAULT NULL,
  `delivery_channel` ENUM('email', 'sms', 'push') NOT NULL,
  `delivery_status` ENUM('pending', 'sent', 'failed', 'bounced', 'opened', 'clicked') DEFAULT 'pending',
  `sent_at` DATETIME DEFAULT NULL,
  `opened_at` DATETIME DEFAULT NULL,
  `clicked_at` DATETIME DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`recipient_id`),
  KEY `message_id` (`message_id`),
  KEY `member_id` (`member_id`),
  KEY `delivery_status` (`delivery_status`),
  FOREIGN KEY (`message_id`) REFERENCES `messages`(`message_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 3: MESSAGE TEMPLATES (Reusable message templates)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `message_templates` (
  `template_id` INT(11) NOT NULL AUTO_INCREMENT,
  `template_name` VARCHAR(255) NOT NULL,
  `template_type` ENUM('announcement', 'event', 'prayer_request', 'newsletter', 'general') DEFAULT 'general',
  `subject` VARCHAR(255) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `variables` JSON DEFAULT NULL COMMENT 'Available variables: {first_name}, {church_name}, etc.',
  `created_by` INT(11) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`template_id`),
  KEY `template_type` (`template_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 4: MESSAGE GROUPS (Define recipient groups)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `message_groups` (
  `group_id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_name` VARCHAR(255) NOT NULL,
  `group_type` ENUM('static', 'dynamic') DEFAULT 'static' COMMENT 'static=manual, dynamic=query-based',
  `description` TEXT DEFAULT NULL,
  `filter_criteria` JSON DEFAULT NULL COMMENT 'For dynamic groups: age, gender, church_group, etc.',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 5: MESSAGE GROUP MEMBERS (Members in each group)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `message_group_members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_id` INT(11) NOT NULL,
  `member_id` INT(11) NOT NULL,
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_member` (`group_id`, `member_id`),
  KEY `group_id` (`group_id`),
  KEY `member_id` (`member_id`),
  FOREIGN KEY (`group_id`) REFERENCES `message_groups`(`group_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 6: SCHEDULED MESSAGES (For scheduled deliveries)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `scheduled_messages` (
  `schedule_id` INT(11) NOT NULL AUTO_INCREMENT,
  `message_id` INT(11) NOT NULL,
  `scheduled_time` DATETIME NOT NULL,
  `recurrence` ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none',
  `recurrence_end` DATE DEFAULT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  `last_run` DATETIME DEFAULT NULL,
  `next_run` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`),
  KEY `message_id` (`message_id`),
  KEY `status` (`status`),
  KEY `next_run` (`next_run`),
  FOREIGN KEY (`message_id`) REFERENCES `messages`(`message_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 7: SMS LOGS (Track all SMS sent)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `sms_logs` (
  `sms_id` INT(11) NOT NULL AUTO_INCREMENT,
  `message_id` INT(11) DEFAULT NULL,
  `recipient_phone` VARCHAR(20) NOT NULL,
  `message_content` TEXT NOT NULL,
  `status` ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
  `provider` VARCHAR(50) DEFAULT NULL COMMENT 'Twilio, Africa''s Talking, etc.',
  `provider_message_id` VARCHAR(255) DEFAULT NULL,
  `cost` DECIMAL(10, 4) DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `delivered_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sms_id`),
  KEY `message_id` (`message_id`),
  KEY `status` (`status`),
  KEY `sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 8: EMAIL LOGS (Track all emails sent)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `email_logs` (
  `email_id` INT(11) NOT NULL AUTO_INCREMENT,
  `message_id` INT(11) DEFAULT NULL,
  `recipient_email` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message_content` TEXT NOT NULL,
  `status` ENUM('pending', 'sent', 'failed', 'bounced', 'opened', 'clicked') DEFAULT 'pending',
  `opened_at` DATETIME DEFAULT NULL,
  `clicked_at` DATETIME DEFAULT NULL,
  `bounce_reason` TEXT DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email_id`),
  KEY `message_id` (`message_id`),
  KEY `status` (`status`),
  KEY `sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABLE 9: COMMUNICATION SETTINGS (System configuration)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `communication_settings` (
  `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `setting_type` ENUM('email', 'sms', 'general') DEFAULT 'general',
  `description` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- INSERT DEFAULT MESSAGE GROUPS
-- ====================================================================
INSERT INTO `message_groups` (`group_name`, `group_type`, `description`) VALUES
('All Members', 'dynamic', 'All active church members'),
('Youth Group', 'dynamic', 'Members aged 13-30'),
('Prayer Team', 'static', 'Dedicated prayer warriors'),
('Volunteers', 'static', 'Church volunteers and servants'),
('Leadership', 'static', 'Church leadership and pastors'),
('New Members', 'dynamic', 'Members joined in last 6 months')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- ====================================================================
-- INSERT DEFAULT MESSAGE TEMPLATES
-- ====================================================================
INSERT INTO `message_templates` (`template_name`, `template_type`, `subject`, `content`, `variables`) VALUES
(
  'Sunday Service Reminder',
  'announcement',
  'Join Us This Sunday - {church_name}',
  'Dear {first_name},\n\nYou are warmly invited to join us this Sunday at {service_time}.\n\nTopic: {sermon_topic}\nSpeaker: {speaker_name}\n\nSee you there!\n\n{church_name}',
  '{"variables": ["first_name", "church_name", "service_time", "sermon_topic", "speaker_name"]}'
),
(
  'Event Invitation',
  'event',
  'You''re Invited: {event_name}',
  'Hello {first_name},\n\nWe are excited to invite you to {event_name}!\n\nDate: {event_date}\nTime: {event_time}\nLocation: {event_location}\n\n{event_description}\n\nRSVP: {rsvp_link}\n\nBlessings,\n{church_name}',
  '{"variables": ["first_name", "event_name", "event_date", "event_time", "event_location", "event_description", "rsvp_link", "church_name"]}'
),
(
  'Prayer Request',
  'prayer_request',
  'Prayer Request - {church_name}',
  'Dear Prayer Warrior,\n\n{prayer_request_content}\n\nLet us agree together in prayer.\n\nIn Christ,\n{church_name}',
  '{"variables": ["prayer_request_content", "church_name"]}'
)
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- ====================================================================
-- INSERT DEFAULT COMMUNICATION SETTINGS
-- ====================================================================
INSERT INTO `communication_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('email_from_name', 'Church Management System', 'email', 'Sender name for emails'),
('email_from_address', 'noreply@church.com', 'email', 'Sender email address'),
('smtp_host', 'smtp.gmail.com', 'email', 'SMTP server host'),
('smtp_port', '587', 'email', 'SMTP server port'),
('smtp_username', '', 'email', 'SMTP username'),
('smtp_password', '', 'email', 'SMTP password (encrypted)'),
('smtp_encryption', 'tls', 'email', 'SMTP encryption (tls/ssl)'),
('sms_provider', 'twilio', 'sms', 'SMS provider (twilio/africastalking/none)'),
('sms_api_key', '', 'sms', 'SMS API key'),
('sms_api_secret', '', 'sms', 'SMS API secret'),
('sms_sender_id', 'CHURCH', 'sms', 'SMS sender ID'),
('max_sms_per_batch', '100', 'sms', 'Maximum SMS to send per batch'),
('max_email_per_batch', '50', 'email', 'Maximum emails to send per batch')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- ====================================================================
-- SETUP COMPLETE!
-- ====================================================================
-- Tables created:
-- 1. messages - Main message storage
-- 2. message_recipients - Track who receives messages
-- 3. message_templates - Reusable templates
-- 4. message_groups - Recipient groups
-- 5. message_group_members - Group membership
-- 6. scheduled_messages - Scheduled deliveries
-- 7. sms_logs - SMS tracking
-- 8. email_logs - Email tracking
-- 9. communication_settings - System settings
--
-- Next steps:
-- 1. Configure SMTP settings for email
-- 2. Configure SMS provider settings
-- 3. Create message groups based on your church structure
-- ====================================================================
