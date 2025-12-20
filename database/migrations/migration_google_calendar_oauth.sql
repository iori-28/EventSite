-- ==========================================
-- Migration: Google Calendar OAuth Integration
-- ==========================================
-- Purpose: Add Google Calendar OAuth token storage for auto-add calendar feature
-- Date: December 20, 2025
--
-- This migration adds columns to store Google Calendar OAuth tokens,
-- allowing the system to automatically add events to user's Google Calendar
-- when they register for an event (Hybrid approach - optional feature)
--
-- Features enabled:
-- - Store OAuth access & refresh tokens per user
-- - Track calendar connection status
-- - Auto-add events to Google Calendar on registration
-- - Preference: user can enable/disable auto-add
-- ==========================================

-- Add Google Calendar OAuth columns to users table
ALTER TABLE users
ADD COLUMN google_calendar_token TEXT NULL COMMENT 'Google Calendar OAuth access token' AFTER oauth_provider,
ADD COLUMN google_calendar_refresh_token TEXT NULL COMMENT 'Google Calendar OAuth refresh token' AFTER google_calendar_token,
ADD COLUMN google_calendar_token_expires DATETIME NULL COMMENT 'Token expiry timestamp' AFTER google_calendar_refresh_token,
ADD COLUMN calendar_auto_add TINYINT(1) DEFAULT 0 COMMENT '1 = auto-add events to calendar, 0 = manual' AFTER google_calendar_token_expires,
ADD COLUMN calendar_connected_at DATETIME NULL COMMENT 'When user connected Google Calendar' AFTER calendar_auto_add;

-- Add index for quick lookup of users with active calendar connection
CREATE INDEX idx_calendar_auto_add ON users (calendar_auto_add);

CREATE INDEX idx_calendar_token_expires ON users (google_calendar_token_expires);

-- ==========================================
-- Verification Queries
-- ==========================================

-- Check if columns were added successfully
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = 'eventsite'
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME IN (
        'google_calendar_token',
        'google_calendar_refresh_token',
        'google_calendar_token_expires',
        'calendar_auto_add',
        'calendar_connected_at'
    )
ORDER BY ORDINAL_POSITION;

-- Check indexes
SHOW INDEX
FROM users
WHERE
    Key_name IN (
        'idx_calendar_auto_add',
        'idx_calendar_token_expires'
    );

-- Sample query to see users with calendar connected
-- SELECT id, name, email, calendar_auto_add, calendar_connected_at
-- FROM users
-- WHERE google_calendar_token IS NOT NULL;

-- ==========================================
-- Rollback (if needed)
-- ==========================================
-- ALTER TABLE users DROP COLUMN google_calendar_token;
-- ALTER TABLE users DROP COLUMN google_calendar_refresh_token;
-- ALTER TABLE users DROP COLUMN google_calendar_token_expires;
-- ALTER TABLE users DROP COLUMN calendar_auto_add;
-- ALTER TABLE users DROP COLUMN calendar_connected_at;
-- DROP INDEX idx_calendar_auto_add ON users;
-- DROP INDEX idx_calendar_token_expires ON users;