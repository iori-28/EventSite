# Google Calendar Integration - Quick Fix Guide

## ✅ Problem Fixed: "Connect Calendar button still shows after connecting"

### Root Cause
Database migration for Google Calendar OAuth was not run, so the required columns didn't exist in the `users` table.

### Columns Added
- `google_calendar_token` - OAuth access token
- `google_calendar_refresh_token` - OAuth refresh token  
- `google_calendar_token_expires` - Token expiry timestamp
- `calendar_auto_add` - Auto-add preference (0/1)
- `calendar_connected_at` - Connection timestamp

---

## 🔧 How to Fix (Already Done)

### Migration was run using:
```bash
php scripts/run_calendar_migration.php
```

### Verification:
```bash
php scripts/check_calendar_migration.php
```

---

## 🎯 What to Do Now

1. **Disconnect & Reconnect Calendar:**
   - Go to User Dashboard
   - If you see "Hubungkan Google Calendar" button → Click it
   - Authorize Google Calendar
   - After redirect, you should see "✅ Google Calendar Terhubung"

2. **Test Auto-Add Feature:**
   - Register to a new event
   - Post-registration modal will show
   - Event will auto-add to your Google Calendar (if enabled)

3. **Toggle Auto-Add:**
   - Dashboard shows checkbox: "Auto-add event ke kalender saat mendaftar"
   - Toggle it to enable/disable auto-add

---

## 📁 Files Changed/Created

### New Files:
- `scripts/check_calendar_migration.php` - Check if migration ran
- `scripts/run_calendar_migration.php` - Run migration via PHP
- `public/api/google-oauth-callback.php` - Universal OAuth callback (smart detection)
- `controllers/GoogleCalendarController.php` - Calendar OAuth handler
- `services/CalendarService.php` - Auto-add to calendar logic
- `database/migrations/migration_google_calendar_oauth.sql` - Migration file

### Modified Files:
- `.env` - Updated redirect URI to universal callback
- `views/user_dashboard.php` - Shows calendar connection status
- `public/api/participants.php` - Auto-add on registration

---

## 🐛 Troubleshooting

### Button still shows "Hubungkan" after connecting?
1. Clear browser cache/cookies
2. Logout & login again
3. Check database:
   ```sql
   SELECT id, name, email, calendar_auto_add, calendar_connected_at 
   FROM users 
   WHERE google_calendar_token IS NOT NULL;
   ```

### "Access blocked" error?
- Add your email to Test Users in Google Cloud Console
- OAuth consent screen → Test users → + ADD USERS

### Connection keeps failing?
1. Check `.env` has correct Client ID & Secret
2. Verify redirect URI in Google Cloud Console:
   ```
   http://localhost/EventSite/public/api/google-oauth-callback.php
   ```
3. Check error logs in browser console

---

## 📖 Related Documentation

- Google Calendar API Setup: `docs/GOOGLE_CALENDAR_API_SETUP.md`
- Google OAuth Setup: `docs/GOOGLE_OAUTH_SETUP.md`

---

**Last Updated:** December 20, 2025
