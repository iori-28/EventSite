-- Migration: Add event_image column to events table
-- Date: 2025-12-18
-- Description: Allow panitia to upload event banner/poster image

-- Add event_image column (nullable for backward compatibility)
ALTER TABLE events
ADD COLUMN event_image VARCHAR(255) NULL AFTER description;

-- Optional: Add default placeholder for existing events
-- UPDATE events SET event_image = 'default-event.jpg' WHERE event_image IS NULL;

-- Verify migration
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = 'eventsite'
    AND TABLE_NAME = 'events'
    AND COLUMN_NAME = 'event_image';