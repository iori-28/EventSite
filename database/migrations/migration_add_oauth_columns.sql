-- Migration: Add Google OAuth columns to users table
-- Purpose: Support Google OAuth login/registration
-- Date: 2025-12-18

-- Add google_id column for storing Google user ID
ALTER TABLE users
ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email,
ADD COLUMN profile_picture VARCHAR(500) NULL AFTER google_id,
ADD COLUMN oauth_provider VARCHAR(50) NULL AFTER profile_picture;

-- Add index for faster Google ID lookup
CREATE INDEX idx_google_id ON users (google_id);

-- Verification query
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = 'eventsite'
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME IN (
        'google_id',
        'profile_picture',
        'oauth_provider'
    );