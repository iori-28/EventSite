-- Migration: Add email reminders toggle to users table
-- Date: 2025-12-20
-- Description: Add global email reminder preference for users

-- Add email_reminders_enabled column
ALTER TABLE users
ADD COLUMN email_reminders_enabled TINYINT(1) DEFAULT 1 COMMENT 'Enable/disable all email reminders (H-1, H-0). 1=enabled, 0=disabled';

-- Create index for quick lookup during cron job
CREATE INDEX idx_email_reminders ON users (email_reminders_enabled);

-- Verification query
-- Run this to verify the column was added successfully:
-- SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = 'eventsite'
--   AND TABLE_NAME = 'users'
--   AND COLUMN_NAME = 'email_reminders_enabled';

-- Rollback (if needed):
-- ALTER TABLE users DROP COLUMN email_reminders_enabled;
-- DROP INDEX idx_email_reminders ON users;