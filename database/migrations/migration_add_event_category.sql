-- =====================================================
-- Migration: Add Event Category Column
-- Purpose: Enable event categorization for analytics
-- Date: 2025-12-16
-- =====================================================

-- Add category column to events table
ALTER TABLE events
ADD COLUMN category VARCHAR(50) DEFAULT 'Lainnya' AFTER description;

-- Add index for better query performance
ALTER TABLE events ADD INDEX idx_category (category);

-- Update existing events to have default category
UPDATE events SET category = 'Lainnya' WHERE category IS NULL;

-- Verify migration
SELECT
    'Migration completed successfully' as status,
    COUNT(*) as total_events,
    COUNT(
        CASE
            WHEN category IS NOT NULL THEN 1
        END
    ) as events_with_category
FROM events;

-- Sample categories that can be used:
-- 'Seminar' - Seminar/Talk
-- 'Workshop' - Workshop/Training
-- 'Webinar' - Online Webinar
-- 'Competition' - Lomba/Competition
-- 'Training' - Pelatihan
-- 'Sosialisasi' - Sosialisasi/Campaign
-- 'Expo' - Pameran/Exhibition
-- 'Lainnya' - Others

COMMIT;