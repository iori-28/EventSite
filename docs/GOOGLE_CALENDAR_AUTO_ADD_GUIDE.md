# 📅 Google Calendar Auto-Add Integration - Implementation Guide

## 🎯 Overview

Implementasi **Hybrid Approach** untuk Google Calendar integration yang memberikan user pilihan:
1. **Auto-add** - Event otomatis masuk ke Google Calendar saat register
2. **Manual** - User klik button untuk add ke calendar (existing feature)
3. **Skip** - User tidak perlu add ke calendar

## ✅ Fitur yang Diimplementasikan

### 1. **Post-Registration Modal** 
Setelah user register event, muncul modal dengan 3 opsi:
- Connect Google Calendar (jika belum connect)
- Auto-add ke Google Calendar (jika sudah connect)
- Add manual dengan button existing
- Skip

### 2. **Dashboard Widget**
User dashboard menampilkan status koneksi Google Calendar:
- Badge terhubung/tidak terhubung
- Toggle auto-add on/off
- Button connect/disconnect

### 3. **Auto-Add Functionality**
Jika user enable auto-add:
- Event otomatis masuk ke Google Calendar saat register
- Tidak perlu klik button manual lagi
- Background process (tidak ganggu user flow)

### 4. **OAuth Management**
- Token auto-refresh jika expired
- Secure token storage di database
- Easy disconnect

---

## 📁 Files Created/Modified

### **New Files Created:**

#### **Database Migration**
```
database/migrations/migration_google_calendar_oauth.sql
```
- Add columns: `google_calendar_token`, `google_calendar_refresh_token`, `google_calendar_token_expires`, `calendar_auto_add`, `calendar_connected_at`

#### **Controller**
```
controllers/GoogleCalendarController.php
```
- OAuth flow management
- Token refresh
- Connection management

#### **API Endpoints**
```
public/api/google-calendar-connect.php         - Redirect to OAuth
public/api/google-calendar-callback.php        - OAuth callback handler
public/api/google-calendar-disconnect.php      - Disconnect calendar
public/api/google-calendar-auto-add.php        - Auto-add single event
public/api/google-calendar-toggle-auto-add.php - Toggle preference
```

#### **UI Components**
```
public/components/post_registration_modal.php  - Post-registration modal
```

### **Modified Files:**

```
services/CalendarService.php                    - Added autoAddToGoogleCalendar()
public/api/participants.php                     - Return JSON dengan event data
views/user_dashboard.php                        - Calendar connection widget
views/event-detail.php                          - Handle JSON response & show modal
```

---

## 🗄️ Database Schema

### **New Columns in `users` Table:**

```sql
google_calendar_token          TEXT        OAuth access token
google_calendar_refresh_token  TEXT        OAuth refresh token
google_calendar_token_expires  DATETIME    Token expiry
calendar_auto_add              TINYINT(1)  1=enabled, 0=disabled
calendar_connected_at          DATETIME    Connection timestamp
```

### **Indexes:**
- `idx_calendar_auto_add` - Quick lookup users dengan auto-add
- `idx_calendar_token_expires` - Monitor token expiry

---

## 🚀 Setup Instructions

### **Step 1: Run Database Migration**

```bash
# Login ke MySQL
mysql -u root

# Select database
USE eventsite;

# Run migration
SOURCE database/migrations/migration_google_calendar_oauth.sql;

# Verify
SHOW COLUMNS FROM users LIKE '%calendar%';
```

### **Step 2: Verify Google OAuth Setup**

File `.env` sudah harus punya:
```env
GOOGLE_OAUTH_CLIENT_ID=your_client_id
GOOGLE_OAUTH_CLIENT_SECRET=your_client_secret
GOOGLE_OAUTH_REDIRECT_URI=http://localhost/EventSite/public/api/google-callback.php
```

### **Step 3: Update OAuth Scopes di Google Cloud Console**

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Pilih project EventSite
3. **APIs & Services** > **OAuth consent screen**
4. **Edit App** > **Scopes**
5. Tambahkan scope:
   ```
   https://www.googleapis.com/auth/calendar.events
   ```
6. **Save and Continue**

### **Step 4: Update Redirect URIs**

Di **Credentials** > **OAuth 2.0 Client IDs** > Edit:

Tambahkan redirect URI baru:
```
http://localhost/EventSite/public/api/google-calendar-callback.php
```

**PENTING**: Redirect URI harus EXACT match, termasuk protocol (http/https) dan path.

---

## 📖 User Flow

### **Flow 1: First-Time User (Belum Connect Calendar)**

1. User browse event → klik "Daftar Event"
2. Registration berhasil → **Modal muncul**
3. Modal menampilkan:
   - Option: "Hubungkan Google Calendar" (primary button)
   - Option: "Tambahkan Manual" (secondary)
   - Option: "Skip"
4. Jika user klik "Hubungkan Google Calendar":
   - Redirect ke Google OAuth
   - User authorize app
   - Redirect kembali ke dashboard
   - Auto-add enabled by default
   - Future registrations otomatis masuk kalender

### **Flow 2: User dengan Calendar Connected**

1. User browse event → klik "Daftar Event"
2. Registration berhasil → **Event auto-added ke Google Calendar** ✅
3. Modal tidak muncul (sudah auto-add)
4. Toast notification: "Event berhasil ditambahkan ke Google Calendar!"

### **Flow 3: User dengan Calendar Connected tapi Auto-Add Disabled**

1. User browse event → klik "Daftar Event"
2. Registration berhasil → **Modal muncul**
3. Modal menampilkan:
   - Option: "Tambahkan Otomatis ke Google Calendar" (primary)
   - Option: "Tambahkan Manual" (secondary)
   - Option: "Skip"

---

## 🔧 API Reference

### **POST /api/google-calendar-connect.php**
Redirect user ke Google OAuth.

**Response**: HTTP 302 redirect ke Google

---

### **GET /api/google-calendar-callback.php**
Handle OAuth callback.

**Query Params:**
- `code` - Authorization code dari Google

**Response**: HTTP 302 redirect ke dashboard

---

### **POST /api/google-calendar-disconnect.php**
Disconnect Google Calendar.

**Response:**
```json
{
  "success": true
}
```

---

### **POST /api/google-calendar-auto-add.php**
Auto-add specific event ke Google Calendar.

**Body Params:**
- `event_id` (required)

**Response:**
```json
{
  "success": true,
  "event_id": "google_event_id",
  "error": null
}
```

---

### **POST /api/google-calendar-toggle-auto-add.php**
Toggle auto-add preference.

**Body Params:**
- `enabled` - "1" or "0"

**Response:**
```json
{
  "success": true,
  "enabled": true
}
```

---

## 🎨 UI Components

### **Post-Registration Modal**

**Features:**
- Responsive design
- Animated entrance
- Auto-close on overlay click
- Loading state untuk auto-add
- Dynamic content based on connection status

**Usage:**
```php
<?php include 'components/post_registration_modal.php'; ?>

<script>
showPostRegistrationModal({
    event_id: 123,
    event_title: 'Workshop PHP',
    calendar_connected: true,
    auto_add_enabled: true
});
</script>
```

---

### **Dashboard Calendar Widget**

**Features:**
- Connection status badge
- Auto-add toggle switch
- Connect/disconnect buttons
- Success/error notifications

**States:**
1. **Not Connected**: Show connect button
2. **Connected + Auto-add ON**: Green badge, toggle ON
3. **Connected + Auto-add OFF**: Yellow badge, toggle OFF

---

## 🔐 Security Considerations

### **Token Storage**
- Access tokens stored encrypted in database (TEXT field)
- Refresh tokens never exposed to client
- Tokens auto-expire dan di-refresh automatically

### **OAuth Flow**
- CSRF protection via Google's state parameter
- Redirect URI whitelist di Google Console
- Session validation before token exchange

### **API Security**
- All endpoints require authentication
- User can only manage their own tokens
- No token exposed in response

---

## 🐛 Troubleshooting

### **Problem: OAuth redirect error "redirect_uri_mismatch"**

**Solution:**
1. Check `.env` file - `GOOGLE_OAUTH_REDIRECT_URI` harus exact match
2. Check Google Console - Authorized redirect URIs
3. Format harus: `http://localhost/EventSite/public/api/google-calendar-callback.php`
4. **Jangan ada trailing slash!**

---

### **Problem: Token expired dan tidak auto-refresh**

**Solution:**
1. Check `google_calendar_refresh_token` di database
2. Jika NULL → user perlu re-connect calendar
3. Jika ada → check error log di `error_log`
4. Possible cause: refresh token revoked by user

---

### **Problem: Auto-add not working**

**Debug Steps:**
1. Check `calendar_auto_add` column:
   ```sql
   SELECT id, name, calendar_auto_add, google_calendar_token 
   FROM users 
   WHERE id = [user_id];
   ```
2. Check token expiry:
   ```sql
   SELECT google_calendar_token_expires 
   FROM users 
   WHERE id = [user_id];
   ```
3. Check error log di `CalendarService::autoAddToGoogleCalendar()`
4. Test manual add via `/api/google-calendar-auto-add.php`

---

### **Problem: Modal tidak muncul setelah registration**

**Debug Steps:**
1. Check browser console untuk JavaScript errors
2. Verify `post_registration_modal.php` included di page
3. Check API response format - harus JSON
4. Test dengan:
   ```javascript
   showPostRegistrationModal({
       event_id: 1,
       event_title: 'Test',
       calendar_connected: false,
       auto_add_enabled: false
   });
   ```

---

## 📊 Testing Checklist

### **Manual Testing:**

- [ ] User belum connect calendar → modal muncul dengan "Connect" option
- [ ] User connect calendar → redirect ke Google → callback berhasil
- [ ] User dengan calendar connected → auto-add works
- [ ] Toggle auto-add di dashboard → preference saved
- [ ] Disconnect calendar → tokens removed
- [ ] Token expired → auto-refresh works
- [ ] Register event dengan auto-add ON → event masuk kalender
- [ ] Register event dengan auto-add OFF → modal muncul
- [ ] Manual add via modal → event masuk kalender

### **Database Testing:**

```sql
-- Check user dengan calendar connected
SELECT id, name, calendar_auto_add, calendar_connected_at 
FROM users 
WHERE google_calendar_token IS NOT NULL;

-- Check token expiry
SELECT id, name, google_calendar_token_expires,
       TIMESTAMPDIFF(HOUR, NOW(), google_calendar_token_expires) as hours_until_expire
FROM users 
WHERE google_calendar_token IS NOT NULL;
```

---

## 🎯 Future Enhancements

### **Planned Features:**

1. **Bulk Add to Calendar**
   - Add all registered events at once
   - Useful untuk user yang baru connect calendar

2. **Calendar Sync Status**
   - Show which events sudah di-add ke calendar
   - Badge di event list

3. **Multiple Calendar Support**
   - Outlook Calendar
   - Apple Calendar via CalDAV
   - Yahoo Calendar

4. **Event Update Sync**
   - Jika event details berubah → update di calendar
   - Jika event dibatalkan → hapus dari calendar

5. **Reminder Customization**
   - User bisa set custom reminder time
   - Multiple reminders per event

---

## 📝 Code Examples

### **Check if User Has Auto-Add Enabled**

```php
require_once 'controllers/GoogleCalendarController.php';

$user_id = $_SESSION['user']['id'];
$auto_add_enabled = GoogleCalendarController::isAutoAddEnabled($user_id);

if ($auto_add_enabled) {
    echo "Auto-add is ON";
} else {
    echo "Auto-add is OFF";
}
```

---

### **Manually Add Event to Calendar**

```php
require_once 'services/CalendarService.php';
require_once 'models/Event.php';

$user_id = $_SESSION['user']['id'];
$event_id = 123;

$event = Event::getById($event_id);
$result = CalendarService::autoAddToGoogleCalendar($user_id, $event);

if ($result['success']) {
    echo "Event added to Google Calendar!";
    echo "Google Event ID: " . $result['event_id'];
} else {
    echo "Failed: " . $result['error'];
}
```

---

### **Get Connection Info**

```php
require_once 'controllers/GoogleCalendarController.php';

$user_id = $_SESSION['user']['id'];
$info = GoogleCalendarController::getConnectionInfo($user_id);

echo "Connected: " . ($info['connected'] ? 'Yes' : 'No') . "\n";
echo "Auto-add: " . ($info['auto_add'] ? 'Enabled' : 'Disabled') . "\n";
echo "Connected at: " . $info['connected_at'] . "\n";
```

---

## 📚 Related Documentation

- [GOOGLE_OAUTH_SETUP.md](GOOGLE_OAUTH_SETUP.md) - Google OAuth configuration
- [GOOGLE_CALENDAR_API_SETUP.md](GOOGLE_CALENDAR_API_SETUP.md) - Calendar API setup
- [API_ENDPOINTS.md](API_ENDPOINTS.md) - Complete API reference

---

## 👥 Support

Jika ada issue atau pertanyaan:
1. Check troubleshooting section di atas
2. Check error logs di browser console & PHP error log
3. Verify Google OAuth setup di Cloud Console
4. Test dengan different user account

---

**Last Updated**: December 20, 2025  
**Version**: 1.0  
**Author**: EventSite Team
