# Google Calendar API Key Setup Guide

## 📝 Kenapa Perlu API Key?

Google Calendar API Key digunakan untuk fitur **"Add to Calendar"** (kalau ada di EventSite). Ini berbeda dengan OAuth yang untuk login.

**Perbedaan:**
- **OAuth** → Login dengan Google account
- **API Key** → Akses public APIs tanpa user authentication

---

## 🔑 Cara Mendapatkan Google Calendar API Key

### **Step 1: Buka Google Cloud Console**

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Pilih project yang sama (EventSite)
3. Pastikan Google Calendar API sudah **enabled** ✅

---

### **Step 2: Buat API Key**

1. Di sidebar kiri, pilih **APIs & Services** > **Credentials**
2. Klik tombol **+ CREATE CREDENTIALS** (pojok kiri atas)
3. Pilih **API key**

   ![Create API Key](https://i.imgur.com/example.png)

4. **API key created!** 
   - Akan muncul popup dengan API Key
   - Contoh: `AIzaSyDxxxxxxxxxxxxxxxxxxxxxxxxxxx`
   - **COPY API KEY INI!** 📋

5. Klik **RESTRICT KEY** (recommended untuk security)

---

### **Step 3: Restrict API Key (Optional tapi Recommended)**

Di halaman **Edit API key**:

#### **A. Application Restrictions**
Pilih salah satu:
- **HTTP referrers (web sites)**
  - Tambahkan domain kamu:
    ```
    http://localhost/*
    http://localhost:80/*
    https://yourdomain.com/*
    https://www.yourdomain.com/*
    ```
  - Ini membatasi hanya website kamu yang bisa pakai API Key

- **None** (not recommended)
  - API Key bisa dipakai dari mana saja
  - Berisiko disalahgunakan

#### **B. API Restrictions**
1. Pilih **Restrict key**
2. Select APIs:
   - ✅ **Google Calendar API**
   - (Centang hanya ini)
3. Klik **SAVE**

---

### **Step 4: Copy API Key**

Setelah save, copy API Key dari halaman Credentials:

```
Contoh API Key yang BENAR:
AIzaSyBxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
AIzaSyCxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
AIzaSyDxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

Panjang: sekitar 39 karakter
Awalan: AIzaSy
```

⚠️ **BUKAN Client ID!**
```
Client ID (SALAH untuk API Key):
123456789-abcdefghijk.apps.googleusercontent.com  ❌
```

---

### **Step 5: Update .env File**

Buka file `.env` di EventSite dan update baris ini:

**SEBELUM (SALAH):**
```env
GOOGLE_CALENDAR_API_KEY=589248932856-octvt4hpfb5gvufkpo41h7186mfimd7u.apps.googleusercontent.com
```

**SESUDAH (BENAR):**
```env
GOOGLE_CALENDAR_API_KEY=AIzaSyBxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Replace `AIzaSyBxxxxxxxxxxxxxxxxxxxxxxxxxxxxx` dengan API Key yang kamu copy tadi.

---

## 🧪 Test API Key

### **Method 1: Via Browser**

Buka URL ini di browser (ganti `YOUR_API_KEY`):

```
https://www.googleapis.com/calendar/v3/calendars/primary/events?key=YOUR_API_KEY
```

**Response yang Benar:**
```json
{
  "error": {
    "code": 401,
    "message": "Request is missing required authentication credential..."
  }
}
```
✅ Ini normal! Artinya API Key valid (401 karena perlu OAuth untuk calendar pribadi)

**Response yang Salah:**
```json
{
  "error": {
    "code": 400,
    "message": "API key not valid..."
  }
}
```
❌ API Key invalid atau belum diaktifkan

---

### **Method 2: Via PHP Code**

Buat file test di `public/test-calendar-api.php`:

```php
<?php
require_once '../config/env.php';

$apiKey = GOOGLE_CALENDAR_API_KEY;

echo "<h2>Testing Google Calendar API Key</h2>";
echo "<p><strong>API Key:</strong> " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -5) . "</p>";

// Test API call
$testUrl = "https://www.googleapis.com/calendar/v3/calendars/primary/events?key=" . $apiKey;

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $httpCode</p>";

if ($httpCode == 401) {
    echo "<p style='color: green;'>✅ API Key is VALID! (401 is expected for this test)</p>";
} elseif ($httpCode == 400) {
    echo "<p style='color: red;'>❌ API Key is INVALID!</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Unexpected response: $httpCode</p>";
}

echo "<h3>Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
```

Akses: `http://localhost/EventSite/public/test-calendar-api.php`

---

## 🔒 Security Best Practices

1. **Jangan commit API Key ke Git!**
   - `.env` harus ada di `.gitignore`
   - Share API Key via secure channel (LastPass, 1Password)

2. **Use Restrictions**
   - Restrict by HTTP referrer
   - Restrict to Google Calendar API only

3. **Rotate Keys**
   - Kalau API Key leak, langsung create baru dan hapus yang lama

4. **Monitor Usage**
   - Google Cloud Console > APIs & Services > Dashboard
   - Lihat usage metrics dan anomali

---

## 🔄 Kalau API Key Leak/Hilang

**Step-by-step:**

1. **Delete Old Key**
   - Google Cloud Console > Credentials
   - Klik 🗑️ di sebelah API Key yang lama
   - Confirm delete

2. **Create New Key**
   - Ikuti Step 2-5 lagi
   - Generate API Key baru

3. **Update .env**
   - Replace dengan API Key baru

4. **Deploy Update**
   - Upload .env baru ke production (kalau sudah hosting)

---

## ❓ Troubleshooting

### **Error: API key not valid**
**Penyebab:**
- API Key salah/typo
- API Key belum aktif (tunggu 1-5 menit)
- Google Calendar API belum enabled

**Fix:**
- Re-copy API Key dari Google Cloud Console
- Pastikan tidak ada spasi di awal/akhir
- Wait 5 minutes dan coba lagi

---

### **Error: This API project is not authorized**
**Penyebab:**
- HTTP referrer restriction terlalu ketat
- Domain tidak match

**Fix:**
- Edit API Key > Application restrictions
- Add `http://localhost/*` dan `https://yourdomain.com/*`

---

### **Warning: "This API key is unrestricted"**
**Not Critical but:**
- Anyone bisa pakai API Key kamu
- Bisa kena abuse/quota limit

**Fix:**
- Edit API Key
- Set HTTP referrers restriction
- Set API restrictions (Google Calendar API only)

---

## 📚 Referensi

**Official Docs:**
- [Google Calendar API Documentation](https://developers.google.com/calendar/api/guides/overview)
- [API Key Best Practices](https://cloud.google.com/docs/authentication/api-keys)

**EventSite Files:**
- Config: `config/env.php`
- Calendar Service: `services/CalendarService.php` (kalau ada)

---

## ✅ Quick Checklist

Setelah setup, pastikan:
- [ ] Google Calendar API enabled di Google Cloud Console
- [ ] API Key created (format: AIzaSy...)
- [ ] API Key restricted (HTTP referrers + Calendar API only)
- [ ] `.env` updated dengan API Key baru
- [ ] Test API Key (via browser atau PHP script)
- [ ] Delete test scripts setelah selesai

---

**Done! 🎉**

Kalau ada error, cek section Troubleshooting di atas.
