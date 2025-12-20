# 🚀 Quick Setup - Google Calendar Auto-Add

## ⚡ Setup dalam 5 Menit

### **Step 1: Run Database Migration** (2 menit)

```bash
# Buka terminal/PowerShell
cd c:\laragon\www\EventSite

# Login MySQL
mysql -u root

# Di MySQL prompt:
USE eventsite;
SOURCE database/migrations/migration_google_calendar_oauth.sql;

# Verify
SHOW COLUMNS FROM users LIKE '%calendar%';
# Harus muncul 5 columns baru

# Exit
EXIT;
```

**Expected Output:**
```
google_calendar_token
google_calendar_refresh_token
google_calendar_token_expires
calendar_auto_add
calendar_connected_at
```

---

### **Step 2: Update Google OAuth Scopes** (2 menit)

1. **Buka Google Cloud Console**: https://console.cloud.google.com/
2. **Pilih Project**: EventSite
3. **Klik sidebar**: APIs & Services → **OAuth consent screen**
4. **Klik**: Edit App
5. **Scroll ke Scopes** → **Add or Remove Scopes**
6. **Search**: `calendar.events`
7. **Centang**: `https://www.googleapis.com/auth/calendar.events`
8. **Klik**: Update → Save and Continue

---

### **Step 3: Add Redirect URI** (1 menit)

1. **Masih di Google Cloud Console**
2. **Klik sidebar**: Credentials
3. **Klik OAuth 2.0 Client ID** yang kamu punya
4. **Authorized redirect URIs** → **Add URI**
5. **Paste**:
   ```
   http://localhost/EventSite/public/api/google-calendar-callback.php
   ```
6. **Save**

**⚠️ PENTING**: 
- Exact match required (no trailing slash)
- Must start with `http://` or `https://`
- Include full path

---

### **Step 4: Verify .env File** (Optional - sudah ada)

File `.env` sudah punya ini kan?
```env
GOOGLE_OAUTH_CLIENT_ID=your_client_id
GOOGLE_OAUTH_CLIENT_SECRET=your_client_secret
GOOGLE_OAUTH_REDIRECT_URI=http://localhost/EventSite/public/api/google-callback.php
```

Kalau belum, tambahkan.

---

### **Step 5: Test!** 🎉

1. **Login ke EventSite** sebagai user
2. **Buka Dashboard**: http://localhost/EventSite/public/index.php?page=user_dashboard
3. **Lihat widget**: "Hubungkan Google Calendar"
4. **Klik**: "Hubungkan Sekarang"
5. **Authorize** di Google
6. **Success!** Widget berubah jadi "Google Calendar Terhubung" ✅

---

## 🧪 Testing Auto-Add

### **Test Case 1: Auto-Add saat Register Event**

1. Browse event: http://localhost/EventSite/public/index.php?page=user_browse_events
2. Pilih event → klik "Daftar Event"
3. **Expected**: 
   - Toast: "Pendaftaran berhasil!"
   - Toast: "Event otomatis ditambahkan ke Google Calendar!"
   - Event masuk ke Google Calendar kamu ✅

### **Test Case 2: Modal untuk User Belum Connect**

1. **Logout** dari EventSite
2. **Login** dengan user lain (yang belum connect calendar)
3. Register event
4. **Expected**: Modal muncul dengan option "Hubungkan Google Calendar"

### **Test Case 3: Toggle Auto-Add**

1. Dashboard → widget Google Calendar
2. **Uncheck** checkbox "Auto-add event ke kalender"
3. **Expected**: Alert "Auto-add dinonaktifkan"
4. Register event baru
5. **Expected**: Modal muncul (tidak auto-add)

---

## ✅ Success Indicators

### **Database Check**

```sql
-- User dengan calendar connected
SELECT id, name, calendar_auto_add, calendar_connected_at 
FROM users 
WHERE google_calendar_token IS NOT NULL;
```

Harus ada row jika kamu sudah connect.

### **Dashboard Check**

Widget menampilkan:
- ✅ Badge hijau "Google Calendar Terhubung"
- ✅ Checkbox "Auto-add event ke kalender" (checked)
- ✅ Button "Putuskan Koneksi"

### **Registration Check**

Setelah register event:
- ✅ Event masuk ke Google Calendar
- ✅ Reminder ter-set (1 day before & 1 hour before)

---

## 🐛 Common Issues

### **Issue: "redirect_uri_mismatch"**

**Fix**: Check exact match di Google Console
```
❌ http://localhost/EventSite/public/api/google-calendar-callback.php/
✅ http://localhost/EventSite/public/api/google-calendar-callback.php
```

### **Issue: "Access blocked: This app's request is invalid"**

**Fix**: Scope belum ditambahkan di OAuth consent screen
- Tambahkan scope: `https://www.googleapis.com/auth/calendar.events`

### **Issue: Modal tidak muncul**

**Fix**: Clear browser cache
- Ctrl+Shift+R (hard refresh)
- Check browser console untuk errors

### **Issue: Auto-add tidak jalan**

**Debug**:
```sql
-- Check token
SELECT calendar_auto_add, google_calendar_token 
FROM users 
WHERE id = [your_user_id];
```

Jika `google_calendar_token` NULL → re-connect calendar

---

## 📞 Need Help?

Check full documentation: [GOOGLE_CALENDAR_AUTO_ADD_GUIDE.md](GOOGLE_CALENDAR_AUTO_ADD_GUIDE.md)

---

**Setup Time**: ~5 minutes  
**Difficulty**: ⭐⭐☆☆☆ (Easy)  
**Last Updated**: December 20, 2025
