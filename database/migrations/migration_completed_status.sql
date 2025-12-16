-- Migration: Add 'completed' status to events
-- This allows events to be marked as completed by panitia
-- When an event is completed, certificates are auto-generated for all checked-in participants

-- Update the events table status enum to include 'completed'
ALTER TABLE events
MODIFY COLUMN status ENUM(
    'pending',
    'approved',
    'rejected',
    'cancelled',
    'completed'
) DEFAULT 'pending';

-- Optional: Update any old completed events (if you have events that already ended)
-- UPDATE events
-- SET status = 'completed'
-- WHERE status = 'approved' AND end_at < NOW();

-- Note: Run this migration before using the complete event feature