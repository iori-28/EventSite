# QR Code Attendance Implementation

## Overview
Implementasi sistem kehadiran menggunakan QR code untuk menggantikan sistem manual button-based.

## Database Changes

### Migration File
`database/migrations/migration_add_qr_token.sql`

**Changes:**
- Added `qr_token VARCHAR(64) UNIQUE` column to `participants` table
- Auto-generated tokens for existing participants using SHA256
- Added index `idx_participants_qr_token` for faster lookups

**Status:** ✅ Executed successfully

---

## Backend Changes

### 1. Participant Model (`models/Participant.php`)

**Modified:** `register()` method

**Changes:**
```php
// Generate unique QR token on registration
$qr_token = hash('sha256', $user_id . $event_id . time() . random_bytes(16));

INSERT INTO participants (user_id, event_id, qr_token)
VALUES (?, ?, ?)
```

**Purpose:** Every new participant registration automatically gets a unique QR token

---

### 2. Attendance API (`public/api/participants_attendance.php`)

**Added:** `verify_qr` action

**Endpoint:**
```
POST api/participants_attendance.php
action=verify_qr&qr_token=<token>
```

**Logic:**
1. Verify QR token exists and belongs to panitia's event
2. Check if already checked_in (prevent duplicate)
3. Update participant status to 'checked_in'
4. Return participant name and event title

**Security:**
- Only panitia who created the event can scan QR codes for that event
- Validates token ownership through JOIN with events table

**Response:**
```json
{
  "success": true,
  "message": "Kehadiran berhasil dikonfirmasi",
  "participant_name": "John Doe",
  "event_title": "Seminar AI"
}
```

---

## Frontend Changes

### 1. User Side: QR Code Display (`views/user_my_events.php`)

**Added:**
- "📱 QR Code" button on each upcoming event card
- QR code modal with QRCode.js library
- Function `showQRCode(token, eventTitle)` to display QR

**Features:**
- Modal shows QR code (256x256px)
- Uses `qrcodejs@1.0.0` library from CDN
- High error correction level (CorrectLevel.H)
- Shows event title in modal

**User Flow:**
1. User goes to "Event Saya" page
2. Clicks "📱 QR Code" button on upcoming event
3. Modal displays QR code
4. User shows QR code to panitia at event venue

**Database Query Update:**
```sql
SELECT e.*, p.status as registration_status, p.registered_at, p.qr_token, p.id as participant_id
```

---

### 2. Panitia Side: QR Scanner (`views/panitia_participants.php`)

**Replaced:** Placeholder `openQRScanner()` alert with full implementation

**Added:**
- html5-qrcode library v2.3.8 from CDN
- QR scanner modal with camera feed
- Scanner initialization with 250x250px scan box
- Functions:
  - `openQRScanner()` - Initialize and show scanner
  - `closeQRScanner()` - Stop scanner and hide modal
  - `onScanSuccess()` - Handle successful scan
  - `verifyQRToken()` - API call to verify and mark attendance

**Features:**
- Real-time camera QR scanning at 10 FPS
- Auto-close modal after successful scan
- Shows participant name on successful confirmation
- Error handling for invalid/duplicate QR codes

**Panitia Flow:**
1. Panitia goes to "Daftar Peserta" page
2. Clicks "📷 Scan QR Code" button
3. Scanner modal opens with camera feed
4. Scans participant's QR code
5. System automatically marks attendance
6. Shows success message with participant name
7. Page reloads to show updated status

---

## Libraries Used

### User Side (QR Generation)
**Library:** qrcodejs v1.0.0
**CDN:** `https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js`
**Usage:** Generate QR code image from token string

### Panitia Side (QR Scanning)
**Library:** html5-qrcode v2.3.8
**CDN:** `https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js`
**Usage:** Access device camera and decode QR codes in real-time

---

## Security Features

1. **Unique Tokens:** Each participant gets SHA256 hash with random bytes
2. **Event Ownership:** Panitia can only scan QR for their own events
3. **Duplicate Prevention:** System rejects already checked-in participants
4. **Token Uniqueness:** Database UNIQUE constraint on qr_token column
5. **No Direct Access:** QR verification requires valid panitia session

---

## Testing Checklist

### User Testing
- ✅ Register for an event → Check qr_token generated in database
- ✅ Go to "Event Saya" page
- ✅ Click "📱 QR Code" button
- ✅ Verify QR code displays correctly
- ✅ Screenshot/print QR code for testing

### Panitia Testing
- ✅ Create event and wait for admin approval
- ✅ User registers for event
- ✅ Go to "Daftar Peserta" page
- ✅ Click "📷 Scan QR Code"
- ✅ Scan user's QR code (from phone/printed)
- ✅ Verify success message shows participant name
- ✅ Check participant status changed to "✓ Hadir"
- ✅ Try scanning same QR again → Should show "already checked in"

### Error Cases
- ✅ Scan invalid QR code → "QR Code tidak valid"
- ✅ Scan QR from different event → "bukan event Anda"
- ✅ Scan duplicate → "sudah melakukan check-in"

---

## Backward Compatibility

**All existing features still work:**
- ✅ Manual "✓ Hadir" button (individual)
- ✅ "Tandai Hadir (Selected)" bulk operation
- ✅ "Tandai Semua Hadir" bulk operation
- ✅ "Batal Hadir" button to unmark attendance

**QR code is an additional option, not a replacement.**

---

## Migration Notes

**For Existing Participants:**
- Migration automatically generated QR tokens for all existing participants
- Users can immediately see and use QR codes for previously registered events

**Database Backup:**
- Recommended to backup `participants` table before migration
- Run: `mysqldump eventsite participants > participants_backup.sql`

---

## Files Modified

1. `database/migrations/migration_add_qr_token.sql` (NEW)
2. `models/Participant.php` (MODIFIED)
3. `views/user_my_events.php` (MODIFIED)
4. `views/panitia_participants.php` (MODIFIED)
5. `public/api/participants_attendance.php` (MODIFIED)

---

## Performance Considerations

1. **QR Token Generation:** SHA256 + random_bytes = secure and fast
2. **Database Index:** `idx_participants_qr_token` for O(log n) lookups
3. **Scanner FPS:** Limited to 10 FPS to reduce CPU usage
4. **Library Size:** 
   - qrcodejs: ~12KB
   - html5-qrcode: ~180KB (only loaded on panitia participants page)

---

## Future Enhancements

### Possible Improvements:
1. **Offline QR Scanning:** Use Service Worker for offline capability
2. **QR Download:** Allow users to download QR as PNG/PDF
3. **Email QR:** Include QR code in registration confirmation email
4. **Statistics:** Track scan time and location
5. **Multiple Scans:** Allow check-out functionality with QR
6. **Admin Dashboard:** Show QR scan analytics

---

## Implementation Summary

**Total Time:** ~30 minutes
**Lines of Code:** ~150 lines added/modified
**Database Changes:** 1 column + 1 index
**New Dependencies:** 2 CDN libraries (no npm install needed)
**Breaking Changes:** None (fully backward compatible)

**Status:** ✅ **PRODUCTION READY**
