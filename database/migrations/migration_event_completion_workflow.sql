-- Migration: Add waiting_completion status for event workflow
-- Date: 2025-12-15
-- Description: Add new status for events waiting admin approval after panitia completion

-- Step 1: Add tracking columns first (will be ignored if already exists)
ALTER TABLE events ADD COLUMN completed_by INT NULL AFTER status;

ALTER TABLE events
ADD COLUMN completed_at TIMESTAMP NULL AFTER completed_by;

ALTER TABLE events
ADD COLUMN approved_by INT NULL AFTER completed_at;

ALTER TABLE events
ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by;

-- Step 2: Add waiting_completion to status enum
ALTER TABLE events
MODIFY COLUMN status ENUM(
    'pending',
    'approved',
    'rejected',
    'completed',
    'waiting_completion'
) DEFAULT 'pending';

-- Step 3: Add foreign keys
ALTER TABLE events
ADD CONSTRAINT fk_events_completed_by FOREIGN KEY (completed_by) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE events
ADD CONSTRAINT fk_events_approved_by FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL;