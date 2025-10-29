-- Add follow-up tracking fields to visitors table
-- Run this in phpMyAdmin or MySQL command line

ALTER TABLE visitors 
ADD COLUMN IF NOT EXISTS follow_up_status ENUM('pending', 'contacted', 'scheduled', 'completed', 'no_response') DEFAULT 'pending' AFTER visitors_purpose,
ADD COLUMN IF NOT EXISTS follow_up_date DATE DEFAULT NULL AFTER follow_up_status,
ADD COLUMN IF NOT EXISTS follow_up_notes TEXT DEFAULT NULL AFTER follow_up_date,
ADD COLUMN IF NOT EXISTS assigned_to VARCHAR(255) DEFAULT NULL AFTER follow_up_notes,
ADD COLUMN IF NOT EXISTS visit_count INT DEFAULT 1 AFTER assigned_to,
ADD COLUMN IF NOT EXISTS last_visit_date DATE DEFAULT NULL AFTER visit_count,
ADD COLUMN IF NOT EXISTS converted_to_member BOOLEAN DEFAULT FALSE AFTER last_visit_date;

-- Create visitor_followups table for tracking multiple follow-up interactions
CREATE TABLE IF NOT EXISTS visitor_followups (
    followup_id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id INT NOT NULL,
    followup_date DATE NOT NULL,
    contact_method ENUM('phone', 'email', 'sms', 'visit', 'other') DEFAULT 'phone',
    notes TEXT,
    outcome ENUM('contacted', 'no_answer', 'scheduled_meeting', 'not_interested', 'interested') DEFAULT 'contacted',
    next_followup_date DATE DEFAULT NULL,
    created_by VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_id) REFERENCES visitors(visitor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for faster queries
CREATE INDEX idx_visitor_followup_status ON visitors(follow_up_status);
CREATE INDEX idx_visitor_last_visit ON visitors(last_visit_date);
CREATE INDEX idx_followup_date ON visitor_followups(followup_date);
