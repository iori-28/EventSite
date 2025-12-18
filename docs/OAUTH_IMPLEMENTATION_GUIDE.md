# Google OAuth Implementation - Complete Guide

## 🎯 Apa Yang Sudah Dibuat?

### 1. **Database Migration** ✅
File: `database/migrations/migration_add_oauth_columns.sql`

**Kolom Baru di Tabel `users`:**
- `google_id` (VARCHAR 255, UNIQUE) - Menyimpan Google User ID
- `profile_picture` (VARCHAR 500) - URL foto profil dari Google
- `oauth_provider` (VARCHAR 50) - Provider OAuth (google, facebook, dll)

**Fungsi:**
- `google_id` digunakan untuk matching user saat login
- `profile_picture` untuk tampilkan foto profil di dashboard
- `oauth_provider` untuk track user login via metode apa

---

### 2. **Google Cloud Console Setup** ✅
File: `docs/GOOGLE_OAUTH_SETUP.md`

**Yang Perlu Kamu Lakukan:**
1. Buka Google Cloud Console
2. Buat OAuth 2.0 Credentials
3. Dapatkan Client ID & Client Secret
4. Set Redirect URI: `http://localhost/EventSite/public/api/google-callback.php`
5. Tambahkan test users (email kamu)

**Config File:**
- `.env` - Simpan credentials (JANGAN commit ke Git!)
- `config/env.php` - Load credentials ke konstanta PHP

---

### 3. **OAuth Flow Files** ✅

#### File: `public/api/google-login.php`
**Fungsi:** Redirect user ke Google login page

**Cara Kerja:**
```
User klik "Login with Google"
    ↓
google-login.php membuat OAuth URL dengan params:
    - client_id
    - redirect_uri  
    - scope (email, profile)
    ↓
User di-redirect ke Google
User pilih akun & klik "Allow"
    ↓
Google redirect kembali ke google-callback.php dengan code
```

**Query Params yang Dikirim:**
- `response_type=code` - Minta authorization code
- `scope` - Permission yang diminta (email, profile)
- `access_type=offline` - Bisa refresh token
- `prompt=consent` - Selalu minta consent screen

---

#### File: `public/api/google-callback.php`
**Fungsi:** Handle response dari Google & login/register user

**Alur Lengkap:**

```php
// 1. TERIMA AUTHORIZATION CODE
$authCode = $_GET['code'];  // Dari Google redirect

// 2. EXCHANGE CODE FOR ACCESS TOKEN
$oauth = new OAuth2([...]);
$oauth->setCode($authCode);
$authToken = $oauth->fetchAuthToken();
$accessToken = $authToken['access_token'];

// 3. GET USER INFO FROM GOOGLE
$curl = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
$userInfo = json_decode(curl_exec($curl), true);

// Data yang didapat:
// - id (Google User ID)
// - email
// - name
// - picture (URL foto)

// 4. CEK APAKAH USER SUDAH TERDAFTAR
$existingUser = $db->query("SELECT * FROM users WHERE google_id = ? OR email = ?");

// 5A. JIKA USER SUDAH ADA
if ($existingUser) {
    // Update google_id jika belum ada
    // Login user (set session)
    // Redirect ke dashboard sesuai role
}

// 5B. JIKA USER BELUM ADA
else {
    // Generate random password (user tidak perlu tahu)
    // Insert user baru dengan:
    //   - name dari Google
    //   - email dari Google
    //   - google_id
    //   - profile_picture
    //   - oauth_provider = 'google'
    //   - role = 'user' (default)
    // 
    // Auto login (set session)
    // Redirect ke dashboard dengan welcome message
}
```

**Error Handling:**
- Kalau gagal dapat token → Redirect ke login dengan error
- Kalau gagal dapat user info → Redirect ke login dengan error
- Semua error di-log dengan `error_log()`

---

### 4. **UI Updates** ✅

#### File: `views/login.php` & `views/register.php`
**Tambahan:**
- Button "Login/Daftar dengan Google"
- Icon Google official (SVG)
- Link ke `api/google-login.php`

#### File: `public/css/auth.css`
**CSS Baru:**
```css
.btn-google {
    /* Style button Google dengan border */
    /* Hover effect: border color change */
    /* Icon Google di sebelah kiri */
}
```

---

## 🔐 Keamanan OAuth vs Password Biasa

### **OAuth Login (Google):**
✅ **Lebih Aman:**
- Password tidak pernah tersimpan di database kamu
- Google yang handle authentication
- User tidak perlu ingat password lain
- Two-factor authentication otomatis (kalau user punya)

✅ **User Experience:**
- Login 1 klik (pilih akun Google)
- No registration form (auto-create account)
- Email sudah verified otomatis

### **Password Login (Manual):**
- Password di-hash dengan `password_hash()` (aman)
- User perlu isi form lengkap saat register
- Perlu remember/reset password

---

## 🔄 Dual Login System

**EventSite sekarang support 2 cara login:**

### **1. Login Manual (Email + Password)**
```php
// AuthController.php
public static function login($email, $password) {
    // Cek user by email
    // Verify password dengan password_verify()
    // Set session
}
```

**Flow:**
- User isi form login
- POST ke AuthController::login()
- Password diverifikasi
- Session dibuat
- Redirect ke dashboard

### **2. Login OAuth (Google)**
```php
// google-callback.php
// Cek user by google_id OR email
// Tidak perlu verify password
// Set session (sama seperti manual login)
// Redirect ke dashboard
```

**Flow:**
- User klik "Login with Google"
- Google OAuth flow
- Callback handler verify dengan Google
- Session dibuat
- Redirect ke dashboard

---

## 💡 Perbedaan User OAuth vs Manual

| Aspek               | User Manual  | User OAuth (Google)      |
| ------------------- | ------------ | ------------------------ |
| **Password**        | Ada (hashed) | Random (tidak digunakan) |
| **google_id**       | NULL         | Google User ID           |
| **profile_picture** | NULL         | URL dari Google          |
| **oauth_provider**  | NULL         | 'google'                 |
| **Email Verified**  | Perlu manual | Otomatis verified        |

---

## 🎬 Cara User Pakai

### **Skenario 1: User Baru (Belum Punya Akun)**
1. Buka halaman register
2. Klik "Daftar dengan Google"
3. Pilih akun Google
4. Klik "Allow"
5. ✅ Langsung masuk ke dashboard (account auto-created)

### **Skenario 2: User Lama (Sudah Punya Akun Manual)**
1. User pernah register dengan email + password
2. Sekarang klik "Login with Google" dengan email SAMA
3. System detect email sudah ada
4. Update `google_id` di database
5. ✅ Langsung masuk (sekarang bisa login 2 cara)

### **Skenario 3: User Switching**
- Login bisa pakai email+password ATAU Google
- Keduanya masuk ke akun yang sama (matched by email)

---

## 🛠️ Testing Steps

### **1. Run Migration**
```
http://localhost/EventSite/scripts/run_oauth_migration.php
```
- Cek kolom google_id, profile_picture, oauth_provider ada

### **2. Update Config**
Edit `.env`:
```env
GOOGLE_OAUTH_CLIENT_ID=YOUR_CLIENT_ID_HERE
GOOGLE_OAUTH_CLIENT_SECRET=YOUR_CLIENT_SECRET_HERE
```

### **3. Test Google Cloud**
- Pastikan redirect URI sudah terdaftar
- Tambahkan email kamu sebagai test user

### **4. Test OAuth Flow**
1. Buka http://localhost/EventSite/public/index.php?page=login
2. Klik "Masuk dengan Google"
3. Pilih akun Google kamu
4. Klik "Allow"
5. Should redirect ke dashboard ✅

### **5. Cek Database**
```sql
SELECT id, name, email, google_id, profile_picture, oauth_provider 
FROM users 
WHERE email = 'your-email@gmail.com';
```
- `google_id` harus terisi
- `profile_picture` ada URL
- `oauth_provider` = 'google'

---

## 🐛 Troubleshooting

### **Error: redirect_uri_mismatch**
**Penyebab:** Redirect URI di code beda dengan di Google Cloud
**Fix:**
- Google Cloud Console → Credentials → Edit OAuth client
- Pastikan ada: `http://localhost/EventSite/public/api/google-callback.php`
- PERSIS SAMA (no trailing slash, case sensitive)

### **Error: invalid_client**
**Penyebab:** Client ID/Secret salah
**Fix:**
- Cek `.env` file, pastikan tidak ada spasi
- Copy ulang dari Google Cloud Console

### **Error: access_denied**
**Penyebab:** User belum jadi test user
**Fix:**
- Google Cloud Console → OAuth consent screen → Test users
- Add email kamu

### **User Stuck di Google Login**
**Penyebab:** Callback script error
**Fix:**
- Cek error log: `tail -f /path/to/php-error.log`
- Debug `google-callback.php` dengan echo

---

## 📊 Database Schema Update

**Before:**
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    role ENUM('user','panitia','admin'),
    created_at TIMESTAMP
);
```

**After (dengan OAuth):**
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),  -- Random untuk OAuth users
    role ENUM('user','panitia','admin'),
    google_id VARCHAR(255) UNIQUE,  -- ✨ NEW
    profile_picture VARCHAR(500),    -- ✨ NEW
    oauth_provider VARCHAR(50),      -- ✨ NEW
    created_at TIMESTAMP
);
```

---

## 🎯 Next Steps (Optional)

### **Enhancement Ideas:**
1. **Profile Picture Display**
   - Show Google profile pic di navbar/sidebar
   - Update user_profile.php untuk tampilkan foto

2. **Account Linking**
   - Allow user link Google account ke existing account
   - UI di settings page

3. **Multiple OAuth Providers**
   - Add Facebook Login
   - Add GitHub Login
   - Reuse same `oauth_provider` column

4. **OAuth-only Accounts**
   - Detect user dengan random password
   - Show notice "Account linked to Google"
   - Disable password change untuk OAuth-only users

---

## ✅ Summary

**Apa yang Selesai:**
- ✅ Database migration (google_id, profile_picture, oauth_provider)
- ✅ Google OAuth config (.env, env.php)
- ✅ OAuth flow (google-login.php, google-callback.php)
- ✅ UI updates (login button, register button)
- ✅ CSS styling (btn-google)
- ✅ Dual authentication support

**Yang Perlu Kamu Lakukan:**
1. Setup Google Cloud Console (ambil Client ID & Secret)
2. Update `.env` dengan credentials
3. Run migration script
4. Test OAuth flow
5. Enjoy! 🎉

**AuthController:**
- Tidak perlu diubah! OAuth menggunakan session yang sama
- `$_SESSION['user']` format tetap sama
- Role-based redirect tetap jalan

**Session Format (Manual & OAuth sama):**
```php
$_SESSION['user'] = [
    'id' => 123,
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'role' => 'user',
    'profile_picture' => 'https://...' // ✨ NEW (OAuth only)
];
```

---

Happy Coding! 🚀
