-- Add QR Token to Participants Table
-- This token will be used to generate QR codes for attendance confirmation

USE eventsite;

-- Add qr_token column
ALTER TABLE participants
ADD COLUMN qr_token VARCHAR(64) UNIQUE DEFAULT NULL AFTER status;

-- Generate tokens for existing participants
UPDATE participants
SET
    qr_token = SHA2(
        CONCAT(
            id,
            user_id,
            event_id,
            UNIX_TIMESTAMP()
        ),
        256
    )
WHERE
    qr_token IS NULL;

-- Create index for faster QR lookups
CREATE INDEX idx_participants_qr_token ON participants (qr_token);