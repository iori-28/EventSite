# EventSite Hosting Guide - Production Deployment

## 🚀 Persiapan Sebelum Hosting

### **Requirement Minimal Server:**

**Server Specification:**
- PHP 7.4 atau lebih tinggi (recommended: PHP 8.1+)
- MySQL 5.7+ atau MariaDB 10.3+
- Apache/Nginx web server
- SSL Certificate (WAJIB untuk Google OAuth)
- Memory: Minimal 512MB RAM
- Storage: Minimal 1GB

**PHP Extensions Required:**
```
- php-mysqli
- php-pdo
- php-curl
- php-json
- php-mbstring
- php-openssl
- php-gd (untuk QR Code)
- php-xml
```

**Composer:**
- Composer installed (untuk install dependencies)

---

## 📋 Checklist Persiapan

### **1. Domain & SSL Certificate** ✅

**Kenapa SSL Wajib?**
- Google OAuth HANYA jalan di HTTPS (production)
- Kalau HTTP, Google akan reject

**Pilihan:**
1. **Domain Berbayar** + SSL dari provider (Namecheap, GoDaddy)
2. **Cloudflare** (Free SSL + CDN)
3. **Let's Encrypt** (Free SSL Certificate)

**Contoh Domain:**
```
https://eventsite.com
https://yourdomain.com
https://eventsite.yourdomain.com (subdomain)
```

---

### **2. Hosting Provider** 💻

**Pilihan Hosting:**

#### **A. Shared Hosting (Murah, Simple)**
- **Providers:** Niagahoster, Hostinger, Dewaweb
- **Harga:** Rp 20.000 - 50.000/bulan
- **Pro:** Setup mudah, ada cPanel
- **Cons:** Limited resources

#### **B. VPS (Flexible, Full Control)**
- **Providers:** DigitalOcean, Vultr, Linode, AWS Lightsail
- **Harga:** $5 - $10/bulan (~Rp 75.000 - 150.000)
- **Pro:** Full root access, scalable
- **Cons:** Perlu setup manual

#### **C. Cloud Hosting**
- **Providers:** Google Cloud, AWS, Azure
- **Harga:** Pay as you go
- **Pro:** Auto-scaling, high availability
- **Cons:** Kompleks, mahal untuk traffic tinggi

**Rekomendasi untuk Start:**
- **Development/Testing:** Heroku, Railway, Render (Free tier)
- **Production:** Niagahoster (shared hosting) atau DigitalOcean (VPS)

---

### **3. Google Cloud Console Update** 🔐

**PENTING:** OAuth credentials harus di-update untuk production domain!

#### **Step-by-Step:**

1. **Buka Google Cloud Console**
   - https://console.cloud.google.com/
   - Pilih project EventSite

2. **Update OAuth Consent Screen**
   - APIs & Services > OAuth consent screen
   - Update **App domain**:
     - Application home page: `https://eventsite.com`
     - Privacy policy: `https://eventsite.com/privacy`
     - Terms of service: `https://eventsite.com/terms`
   - (Buat halaman privacy & terms kalau belum ada)

3. **Update OAuth 2.0 Credentials**
   - APIs & Services > Credentials
   - Edit OAuth client ID yang sudah ada
   
   **Authorized JavaScript origins:**
   ```
   https://eventsite.com
   https://www.eventsite.com
   ```
   
   **Authorized redirect URIs:**
   ```
   https://eventsite.com/public/api/google-callback.php
   https://www.eventsite.com/public/api/google-callback.php
   ```
   
   ⚠️ **Jangan hapus localhost** (untuk development)
   
4. **Submit for Verification (Optional)**
   - Kalau app masih "Testing", hanya test users bisa login
   - Untuk public access, submit app untuk verification
   - Proses: 1-2 minggu review dari Google
   - Perlu video demo & privacy policy

---

### **4. File & Folder Structure** 📁

**Yang Perlu Di-Upload:**

```
eventsite/
├── config/
│   ├── db.php
│   ├── env.php
│   └── .env (EDIT: production values)
├── controllers/
├── cron/
├── database/
│   └── migrations/
├── docs/
├── models/
├── public/
│   ├── api/
│   ├── css/
│   ├── uploads/ (CHMOD 755)
│   ├── certificates/ (CHMOD 755)
│   ├── .htaccess (PENTING)
│   └── index.php
├── scripts/
├── services/
├── templates/
├── vendor/ (composer install)
├── views/
├── composer.json
└── .htaccess (root)
```

**File yang JANGAN di-upload:**
- `.git/` folder
- `.env` file lama (buat baru di server)
- `node_modules/` (kalau ada)
- File testing/debug

---

### **5. Upload Files** 📤

**Via FTP/SFTP:**
```
Tools: FileZilla, WinSCP, atau cPanel File Manager

Connect ke server:
- Host: ftp.yourdomain.com atau IP server
- Username: username dari hosting
- Password: password dari hosting
- Port: 21 (FTP) atau 22 (SFTP)

Upload semua folder kecuali yang di blacklist
```

**Via Git (VPS):**
```bash
# SSH ke server
ssh user@server-ip

# Clone repository
cd /var/www/html/
git clone https://github.com/yourusername/eventsite.git
cd eventsite

# Install dependencies
composer install --no-dev --optimize-autoloader
```

---

### **6. Setup Database di Production** 🗄️

#### **A. Create Database**

**Via cPanel:**
1. cPanel > MySQL Databases
2. Create new database: `eventsite_prod`
3. Create user: `eventsite_user`
4. Set password (strong password!)
5. Add user to database dengan ALL PRIVILEGES

**Via MySQL CLI:**
```sql
CREATE DATABASE eventsite_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'eventsite_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON eventsite_prod.* TO 'eventsite_user'@'localhost';
FLUSH PRIVILEGES;
```

#### **B. Import Database Schema**

**Export dari Development:**
```bash
# Di localhost
mysqldump -u root -p eventsite > eventsite_schema.sql
```

**Import ke Production:**
```bash
# Via SSH
mysql -u eventsite_user -p eventsite_prod < eventsite_schema.sql

# Via cPanel phpMyAdmin
# Upload file SQL atau copy-paste
```

#### **C. Run All Migrations**

```
https://eventsite.com/scripts/run_migration.php
https://eventsite.com/scripts/run_category_migration.php
https://eventsite.com/scripts/run_event_image_migration.php
https://eventsite.com/scripts/run_oauth_migration.php
```

⚠️ **Setelah selesai, DELETE atau PROTECT migration scripts!**

---

### **7. Update .env File (PENTING!)** ⚙️

**Lokasi:** `/config/.env`

```env
# DATABASE (Production)
DB_HOST=localhost
DB_USER=eventsite_user
DB_PASS=YOUR_STRONG_PASSWORD_HERE
DB_NAME=eventsite_prod

# MAIL (Production - Gunakan SMTP Production)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=production-email@yourdomain.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_NAME=EventSite

# EVENT REMINDER
EVENT_REMINDER_ENABLED=true
EVENT_REMINDER_HOURS=1,24

# APP
APP_BASE_URL=https://eventsite.com/public

# GOOGLE CALENDAR API
GOOGLE_CALENDAR_API_KEY=your-api-key

# GOOGLE OAUTH (PRODUCTION)
GOOGLE_OAUTH_CLIENT_ID=123456789-abcdefg.apps.googleusercontent.com
GOOGLE_OAUTH_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxx
GOOGLE_OAUTH_REDIRECT_URI=https://eventsite.com/public/api/google-callback.php
```

**⚠️ Security:**
- File `.env` harus di-protect (chmod 600)
- Jangan commit ke Git
- Backup terpisah yang aman

---

### **8. File Permissions** 🔒

**Set Permissions yang Benar:**

```bash
# Via SSH
cd /var/www/html/eventsite

# Root folder
chmod 755 .

# Config folder (protect)
chmod 750 config/
chmod 600 config/.env

# Upload folders (writable)
chmod 755 public/uploads/
chmod 755 public/uploads/events/
chmod 755 public/certificates/

# Public folder
chmod 755 public/

# All PHP files
find . -type f -name "*.php" -exec chmod 644 {} \;

# All directories
find . -type d -exec chmod 755 {} \;
```

**Via cPanel File Manager:**
- Right-click folder > Change Permissions
- uploads/ = 755
- .env = 600
- config/ = 750

---

### **9. .htaccess Configuration** 🛡️

**Root .htaccess** (`/eventsite/.htaccess`):

```apache
# Protect sensitive files
<FilesMatch "^\.env$">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "^composer\.(json|lock)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Redirect to public folder
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ /public/$1 [L]
```

**Public .htaccess** (`/public/.htaccess`):

```apache
# Enable RewriteEngine
RewriteEngine On

# Force HTTPS (Production)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove .php extension
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}\.php -f
RewriteRule ^(.*)$ $1.php [L]

# Protect uploads folder from PHP execution
<Directory "uploads">
    php_flag engine off
</Directory>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

---

### **10. Setup Cron Job (Event Reminders)** ⏰

**Via cPanel:**
1. cPanel > Cron Jobs
2. Add New Cron Job
3. Command:
   ```bash
   /usr/bin/php /home/username/public_html/eventsite/cron/send_event_reminders.php
   ```
4. Timing: Every hour (0 * * * *)

**Via SSH (VPS):**
```bash
# Edit crontab
crontab -e

# Add line:
0 * * * * /usr/bin/php /var/www/html/eventsite/cron/send_event_reminders.php >> /var/log/eventsite-cron.log 2>&1
```

**Test Cron:**
```bash
php /path/to/eventsite/cron/send_event_reminders.php
```

---

### **11. Security Checklist** 🔐

**WAJIB:**
- ✅ SSL Certificate installed
- ✅ `.env` file protected (chmod 600)
- ✅ Database password strong (min 16 char, random)
- ✅ Disable directory listing
- ✅ Remove/protect migration scripts setelah run
- ✅ Remove phpinfo() atau debug files
- ✅ Update PHP ke versi latest
- ✅ Disable error display (production)

**php.ini Settings (Production):**
```ini
display_errors = Off
log_errors = On
error_log = /path/to/php-error.log
upload_max_filesize = 5M
post_max_size = 10M
max_execution_time = 60
```

**Protect Scripts:**
```apache
# Add to .htaccess
<FilesMatch "^(run_.*\.php|seed_.*\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

### **12. Testing Production** ✅

**Testing Checklist:**

1. **Homepage**
   - [ ] Load tanpa error
   - [ ] SSL aktif (https://)
   - [ ] Event cards tampil

2. **Registration**
   - [ ] Register via email+password
   - [ ] Register via Google OAuth
   - [ ] Email confirmation diterima

3. **Login**
   - [ ] Login email+password
   - [ ] Login Google OAuth
   - [ ] Redirect ke dashboard sesuai role

4. **Event Management**
   - [ ] Panitia bisa create event
   - [ ] Upload image works
   - [ ] Admin approve event
   - [ ] User bisa register event

5. **Email Notifications**
   - [ ] Registration email diterima
   - [ ] Event reminder email (test cron)
   - [ ] Image di email tampil

6. **QR Code**
   - [ ] Generate QR code
   - [ ] Scan QR works
   - [ ] Attendance confirmation

7. **Google OAuth**
   - [ ] Button tampil
   - [ ] Redirect ke Google works
   - [ ] Callback works
   - [ ] Auto login/register
   - [ ] Profile picture tampil

---

### **13. Post-Deployment** 🎉

**Monitoring:**
```bash
# Check error logs
tail -f /var/log/apache2/error.log
tail -f /path/to/php-error.log

# Check database
mysql -u eventsite_user -p
USE eventsite_prod;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM events;
```

**Analytics:**
- Setup Google Analytics (optional)
- Monitor server resources
- Check email delivery rate

**Backup:**
```bash
# Database backup (daily)
mysqldump -u eventsite_user -p eventsite_prod > backup_$(date +%Y%m%d).sql

# Files backup (weekly)
tar -czf eventsite_backup_$(date +%Y%m%d).tar.gz /path/to/eventsite/
```

---

## 🚨 Troubleshooting Production

### **Google OAuth Error: redirect_uri_mismatch**
**Fix:**
- Cek Google Cloud Console > Credentials
- Pastikan ada: `https://yourdomain.com/public/api/google-callback.php`
- NO HTTP, MUST HTTPS!

### **Database Connection Failed**
**Fix:**
- Cek `.env` credentials
- Test: `mysql -u eventsite_user -p eventsite_prod`
- Cek host (bisa `localhost` atau IP)

### **Email Not Sending**
**Fix:**
- Cek SMTP credentials di `.env`
- Test port: `telnet smtp.gmail.com 587`
- Enable "Less secure apps" di Gmail (atau use App Password)

### **404 Not Found**
**Fix:**
- Cek `.htaccess` files
- Enable `mod_rewrite`: `a2enmod rewrite` (VPS)
- Restart Apache: `service apache2 restart`

### **Permission Denied (uploads/)**
**Fix:**
```bash
chmod 755 public/uploads/
chown www-data:www-data public/uploads/ (Linux)
```

---

## 📊 Hosting Provider Comparison

| Provider         | Type   | Harga/Bulan  | SSL      | cPanel | Support OAuth |
| ---------------- | ------ | ------------ | -------- | ------ | ------------- |
| **Niagahoster**  | Shared | Rp 20k       | ✅ Free   | ✅      | ✅             |
| **Hostinger**    | Shared | Rp 25k       | ✅ Free   | ✅      | ✅             |
| **Dewaweb**      | Shared | Rp 30k       | ✅ Free   | ✅      | ✅             |
| **DigitalOcean** | VPS    | $6 (~Rp 90k) | ⚠️ Manual | ❌      | ✅             |
| **Heroku**       | Cloud  | Free tier    | ✅        | ❌      | ✅             |
| **Railway**      | Cloud  | Free tier    | ✅        | ❌      | ✅             |

**Rekomendasi:**
- **Budget:** Niagahoster Basic (Rp 20k/bulan)
- **Performance:** DigitalOcean Droplet ($6/bulan)
- **Testing:** Railway/Render (Free)

---

## ✅ Final Checklist

Sebelum Go Live:

- [ ] Domain registered & DNS configured
- [ ] SSL Certificate installed
- [ ] All files uploaded
- [ ] Database imported & migrations run
- [ ] `.env` updated with production values
- [ ] Google Cloud Console updated (redirect URIs)
- [ ] File permissions set correctly
- [ ] Cron job configured
- [ ] Test all features (login, register, OAuth, events)
- [ ] Error logs checked
- [ ] Backup strategy setup
- [ ] Documentation updated

---

## 🎯 Quick Start Commands

**VPS Setup (Ubuntu/Debian):**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install LAMP stack
sudo apt install apache2 mysql-server php php-mysql php-curl php-gd php-mbstring php-xml -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2

# Setup SSL (Let's Encrypt)
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

**Deploy EventSite:**
```bash
# Clone & setup
cd /var/www/html/
git clone your-repo eventsite
cd eventsite
composer install --no-dev
cp .env.example .env
nano .env  # Edit production values

# Set permissions
chmod -R 755 .
chmod 600 config/.env
chmod 755 public/uploads/

# Import database
mysql -u root -p < database.sql
```

---

**Good luck dengan hosting! 🚀**

Kalau ada pertanyaan saat deployment, tanya aja! 💪
