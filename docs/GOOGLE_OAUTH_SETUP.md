# Google OAuth Setup Guide

## Step 1: Buat/Pilih Project di Google Cloud Console

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Klik **Select a Project** (pojok kiri atas)
3. Klik **NEW PROJECT**
4. Isi:
   - Project name: `EventSite` (atau nama lain)
   - Location: biarkan default
5. Klik **CREATE**

## Step 2: Enable Google APIs

1. Di sidebar kiri, pilih **APIs & Services** > **Library**
2. Cari **"Google+ API"** atau **"Google Identity Services"**
3. Klik pada hasil pencarian
4. Klik tombol **ENABLE**

## Step 3: Configure OAuth Consent Screen

1. Di sidebar, pilih **APIs & Services** > **OAuth consent screen**
2. Pilih **External** (untuk testing)
3. Klik **CREATE**
4. Isi form:
   - **App name**: EventSite
   - **User support email**: email kamu
   - **Developer contact**: email kamu
5. Klik **SAVE AND CONTINUE**
6. Di **Scopes**, klik **ADD OR REMOVE SCOPES**
7. Pilih:
   - `userinfo.email`
   - `userinfo.profile`
   - `openid`
8. Klik **UPDATE** > **SAVE AND CONTINUE**
9. Di **Test users**, klik **ADD USERS**
10. Tambahkan email Google kamu untuk testing
11. Klik **SAVE AND CONTINUE**

## Step 4: Create OAuth 2.0 Credentials

1. Di sidebar, pilih **APIs & Services** > **Credentials**
2. Klik **+ CREATE CREDENTIALS** (atas)
3. Pilih **OAuth client ID**
4. Application type: **Web application**
5. Name: `EventSite Web Client`
6. **Authorized JavaScript origins**:
   - `http://localhost`
   - `http://localhost:80`
7. **Authorized redirect URIs**:
   - `http://localhost/EventSite/public/api/google-callback.php`
8. Klik **CREATE**
9. **PENTING**: Salin **Client ID** dan **Client Secret**
   - Contoh Client ID: `123456789-abcdefg.apps.googleusercontent.com`
   - Contoh Client Secret: `GOCSPX-xxxxxxxxxxxxx`

## Step 5: Simpan Credentials

Buka file `config/env.php` di EventSite dan tambahkan:

```php
define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', 'http://localhost/EventSite/public/api/google-callback.php');
```

## Testing Mode

Saat development, Google OAuth hanya bisa digunakan oleh:
- Test users yang sudah ditambahkan di OAuth consent screen
- Email developer yang terdaftar

Untuk production, perlu submit app untuk verification.

## Troubleshooting

### Error: redirect_uri_mismatch
- Pastikan redirect URI di Google Cloud **PERSIS SAMA** dengan yang di code
- Cek tidak ada typo atau trailing slash

### Error: invalid_client
- Cek Client ID dan Client Secret sudah benar
- Jangan ada spasi atau karakter tambahan

### Error: access_denied
- User belum ditambahkan sebagai test user
- Coba tambahkan email di OAuth consent screen > Test users
