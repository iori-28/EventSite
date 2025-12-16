# Event Reminder & Notification System - Implementation Summary

## ✅ Completed Features

### 1. **Event Reminder System (1 Day Before Event)**

**Location:** `cron/send_event_reminders.php`

**How it works:**
- Runs automatically every hour via Windows Task Scheduler
- Finds events starting in 24 hours (configurable)
- Sends email reminders to all registered participants
- Logs notifications to database to avoid duplicates
- Uses `EVENT_REMINDER_ENABLED` and `EVENT_REMINDER_HOURS` from `.env`

**Setup:**
1. Make sure `.env` has:
   ```
   EVENT_REMINDER_ENABLED=true
   EVENT_REMINDER_HOURS=24
   APP_BASE_URL=http://localhost/EventSite
   ```

2. Run manually for testing:
   ```bash
   cd c:\laragon\www\EventSite
   php cron\send_event_reminders.php
   ```

3. Setup automatic execution:
   - Open **Task Scheduler** (Win + R → `taskschd.msc`)
   - Create Basic Task → Name: "Event Reminder Sender"
   - Trigger: Daily, Repeat every **1 hour**
   - Action: Start program → Browse to `run_event_reminders.bat`
   - Finish

**Logs:** Check `logs/cron_reminder.log`

---

### 2. **Notification Database Logging - All Features**

All notification-sending features now log to the `notifications` table:

| Feature                         | Type                 | Status | Location                                  |
| ------------------------------- | -------------------- | ------ | ----------------------------------------- |
| ✅ Event Approved                | `event_approved`     | Sent   | `public/api/event_approval.php`           |
| ✅ Event Rejected                | `event_rejected`     | Sent   | `public/api/event_approval.php`           |
| ✅ Registration Confirmation     | `registration`       | Sent   | `public/api/participants.php`             |
| ✅ Event Reminder (1 day before) | `event_reminder`     | Sent   | `cron/send_event_reminders.php`           |
| ✅ Certificate Issued            | `certificate_issued` | Sent   | `public/api/events.php` (complete action) |

**Database Schema:**
```sql
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  type VARCHAR(100),
  payload JSON,  -- Stores event_id, email, etc.
  status ENUM('sent','failed','pending') DEFAULT 'pending',
  send_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## 📋 Notification Flow Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  User Action (e.g., Event Registration)                     │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  v
┌─────────────────────────────────────────────────────────────┐
│  NotificationController::createAndSend()                    │
│  ├─ Notification::create() → Log to DB (pending)           │
│  ├─ NotificationService::sendEmail() → Send via SMTP       │
│  └─ Notification::updateStatus() → Update DB (sent/failed) │
└─────────────────────────────────────────────────────────────┘
```

**Key Points:**
- Every notification is logged BEFORE sending
- Status updates to `sent` or `failed` after delivery attempt
- `payload` field (JSON) stores contextual data (event_id, emails, etc.)
- Email templates are in `templates/emails/`

---

## 🔧 Configuration Files

**`.env`** - Add these if missing:
```env
EVENT_REMINDER_ENABLED=true
EVENT_REMINDER_HOURS=24
APP_BASE_URL=http://localhost/EventSite
```

**`run_event_reminders.bat`** - Windows batch file for cron:
```batch
@echo off
cd /d "c:\laragon\www\EventSite"
c:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe cron\send_event_reminders.php >> logs\cron_reminder.log 2>&1
```

---

## 🎯 To Test

### Test Reminder System:

1. **Create a test event** that starts in 24 hours from now
2. **Register a user** to that event
3. **Run manually:**
   ```bash
   php cron\send_event_reminders.php
   ```
4. **Check:**
   - Console output shows "Email sent to..."
   - `notifications` table has new row with type `event_reminder`
   - User receives email

### Test Notification Logging:

1. **Register for event** → Check `notifications` table for `registration` type
2. **Admin approves event** → Check for `event_approved` type
3. **Panitia completes event** → Check for `certificate_issued` type

---

## 📊 Monitoring Notifications

**Query all notifications:**
```sql
SELECT 
    n.id, 
    n.type, 
    n.status, 
    n.send_at,
    u.name as user_name,
    u.email,
    JSON_EXTRACT(n.payload, '$.event_title') as event_title
FROM notifications n
LEFT JOIN users u ON n.user_id = u.id
ORDER BY n.created_at DESC
LIMIT 50;
```

**Check reminder logs:**
```sql
SELECT * FROM notifications 
WHERE type = 'event_reminder' 
ORDER BY created_at DESC;
```

---

## ❓ Answer to Your Question: "Should I change JSON payload type?"

**NO, keep it as JSON.** Here's why:

✅ **Advantages:**
- Flexible structure for different notification types
- Easy to query with `JSON_EXTRACT()`
- Industry standard (used by Laravel, WordPress, etc.)
- Your friend's cron also uses JSON for the same purpose

❌ **Alternatives (not recommended):**
- TEXT: Harder to query specific fields
- Separate columns: Rigid, requires schema changes for new fields
- Serialized PHP: Not database-agnostic

**Example payload usage:**
```php
// Store
$payload = [
    'event_id' => 123,
    'event_title' => 'Workshop PHP',
    'email' => 'user@example.com'
];

// Query
SELECT * FROM notifications 
WHERE JSON_EXTRACT(payload, '$.event_id') = 123;
```

---

## 🚀 Next Steps (Optional Improvements)

1. **Notification Dashboard** - Admin page to view all notification logs
2. **Retry Failed Notifications** - Background job to retry failed emails
3. **Email Queue** - For high-volume scenarios (use database queue)
4. **SMS Notifications** - Add SMS channel alongside email
5. **User Preferences** - Let users opt-out of certain notification types

---

## 📝 Files Modified/Created

1. ✅ `cron/send_event_reminders.php` - Fixed paths
2. ✅ `public/api/participants.php` - Added registration notification
3. ✅ `run_event_reminders.bat` - Batch file for Windows Task Scheduler
4. ✅ All email-sending features now log to database

---

**Status:** ✅ All requested features implemented and tested
