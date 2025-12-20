# 📋 Google OAuth Files - Quick Reference

## ✅ PENTING (JANGAN HAPUS!)

| File                                  | Status         | Fungsi                       | Kapan Dipakai                                    |
| ------------------------------------- | -------------- | ---------------------------- | ------------------------------------------------ |
| `google-login.php`                    | ✅ ACTIVE       | Initiate Google login        | User klik "Login with Google"                    |
| `google-oauth-callback.php`           | ✅ **PENTING!** | Universal callback handler   | Setelah authorize di Google (login DAN calendar) |
| `google-calendar-connect.php`         | ✅ ACTIVE       | Initiate calendar connection | User klik "Hubungkan Google Calendar"            |
| `google-calendar-disconnect.php`      | ✅ ACTIVE       | Disconnect calendar          | User klik "Putuskan Koneksi"                     |
| `google-calendar-toggle-auto-add.php` | ✅ ACTIVE       | Toggle auto-add preference   | User toggle checkbox auto-add                    |
| `google-calendar-auto-add.php`        | ✅ ACTIVE       | Manual add event             | Dari modal post-registration                     |

---

## ❌ GA KEPAKE (BISA DIHAPUS!)

| File                                      | Status       | Alasan                              |
| ----------------------------------------- | ------------ | ----------------------------------- |
| `google-callback.php.deprecated`          | ❌ DEPRECATED | Diganti `google-oauth-callback.php` |
| `google-calendar-callback.php.deprecated` | ❌ DEPRECATED | Diganti `google-oauth-callback.php` |

### Cara Hapus:
```powershell
cd C:\laragon\www\EventSite\public\api
Remove-Item google-callback.php.deprecated
Remove-Item google-calendar-callback.php.deprecated
```

**Safe to delete?** ✅ **YA!** Kapan aja bisa dihapus, ga akan break system.

---

## 🔄 Flow Diagram

### Login Flow:
```
User klik "Login with Google"
    ↓
google-login.php (redirect ke Google)
    ↓
Google OAuth authorize
    ↓
google-oauth-callback.php ← PENTING!
    ↓
User logged in
```

### Calendar Connection Flow:
```
User klik "Hubungkan Google Calendar"
    ↓
google-calendar-connect.php (redirect ke Google)
    ↓
Google OAuth authorize
    ↓
google-oauth-callback.php ← PENTING!
    ↓
Calendar connected
```

---

## 🎯 Yang Paling Penting

**File paling krusial:**
1. `google-oauth-callback.php` ← **INI YANG PALING PENTING!**

Kalau file ini dihapus/rusak → Login & Calendar connection ga akan work!

**File lain:** Semua penting, tapi bisa di-recreate dengan mudah. Yang ini handle semua OAuth logic.

---

**Last Updated:** December 20, 2025
