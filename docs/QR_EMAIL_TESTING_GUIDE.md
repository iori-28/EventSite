# Testing Guide: QR Code in Email

## Overview
QR code otomatis terkirim di 2 jenis email:
1. **Email Registrasi** - Saat user daftar event
2. **Email Reminder** - 24h & 1h sebelum event

---

## How to Test

### Test 1: Registration Email with QR

**Steps:**
1. Login sebagai **user** (bukan admin/panitia)
2. Browse event yang sudah approved
3. Klik "Daftar" pada event
4. Tunggu notifikasi "Pendaftaran berhasil"
5. Buka email inbox (check SMTP_USERNAME di .env)
6. Cari email dengan subject: **"Registrasi Event Berhasil - [Event Title]"**

**Expected Email Content:**
```
✓ Pendaftaran Berhasil!

Hai [User Name],

Kamu berhasil mendaftar untuk event [Event Title].

Detail Event:
📅 Tanggal: [Date & Time]
📍 Lokasi: [Location]

[Button: Lihat Detail Event]

━━━━━━━━━━━━━━━━━━━━━━

QR Code Kehadiran
[QR CODE IMAGE - 250x250px]

Tunjukkan QR code ini kepada panitia untuk konfirmasi kehadiran Anda

━━━━━━━━━━━━━━━━━━━━━━

Kami menunggu kehadiran Anda! Sampai jumpa di event.
```

**Checklist:**
- ✅ Email diterima
- ✅ QR code tampil (bukan broken image)
- ✅ QR code size 250x250px
- ✅ Button "Lihat Detail Event" ada dan clickable
- ✅ Button redirect ke event-detail dengan `from=email`

---

### Test 2: Reminder Email with QR

**Setup:**
1. Buat event dengan start_at = **24 jam dari sekarang** (atau 1 jam untuk quick test)
2. Admin approve event
3. User daftar ke event
4. Tunggu sampai waktu reminder (atau adjust EVENT_REMINDER_HOURS di .env)

**Manual Trigger Cron:**
```bash
cd c:\laragon\www\EventSite
php cron/send_event_reminders.php
```

**Expected Output (Console):**
```
[2025-12-17 10:00:00] === Event Reminder Cron Job Started ===
[2025-12-17 10:00:00] Reminder timings: 24, 1 hours before event
[2025-12-17 10:00:00] 
Checking for events starting in 24 hours...
[2025-12-17 10:00:00] Time window: 2025-12-18 10:00:00 to 2025-12-18 11:00:00
[2025-12-17 10:00:00] Found 1 event(s) for 24h timing
[2025-12-17 10:00:00] 
=== Total events to process: 1 ===
[2025-12-17 10:00:00] Processing event #123: Seminar AI
[2025-12-17 10:00:00]   Found 5 participant(s)
[2025-12-17 10:00:01]   ✓ Email sent to John Doe (john@example.com)
...
[2025-12-17 10:00:03] === Summary ===
[2025-12-17 10:00:03] Total emails sent: 5
[2025-12-17 10:00:03] Total failed: 0
[2025-12-17 10:00:03] === Event Reminder Cron Job Completed ===
```

**Expected Email Content:**
```
⏰ Reminder Event

🔔 Event Anda akan segera dimulai!

Halo [Name],

Ini adalah pengingat bahwa event yang Anda daftarkan akan dimulai dalam 24 jam.
Pastikan Anda sudah mempersiapkan diri dan tidak melewatkan event ini!

[Event Title]
📅 Tanggal & Waktu: [Datetime]
📍 Lokasi: [Location]
📝 Deskripsi: [Description]

[Button: Lihat Detail Event]

━━━━━━━━━━━━━━━━━━━━━━

📱 QR Code Kehadiran

[QR CODE IMAGE - 250x250px]

Tunjukkan QR code ini kepada panitia untuk konfirmasi kehadiran Anda

Simpan email ini atau screenshot QR code untuk memudahkan check-in

━━━━━━━━━━━━━━━━━━━━━━
```

**Checklist:**
- ✅ Email diterima
- ✅ Subject: "⏰ Reminder: [Event] akan dimulai dalam 24 jam"
- ✅ QR code tampil dengan styling border dashed crimson
- ✅ Button "Lihat Detail Event" redirect ke event-detail
- ✅ Email HTML properly formatted

---

## Troubleshooting

### QR Code tidak muncul di email

**Check 1: Composer Library Installed**
```bash
cd c:\laragon\www\EventSite
composer show chillerlan/php-qrcode
```

Expected output:
```
name     : chillerlan/php-qrcode
descrip. : A QR code generator
...
versions : * 5.0.0
```

If not installed:
```bash
composer install
```

**Check 2: QR Token exists in Database**
```sql
SELECT id, user_id, event_id, qr_token 
FROM participants 
WHERE qr_token IS NOT NULL
LIMIT 5;
```

Expected: qr_token berisi string 64 characters (SHA256)

**Check 3: QRCodeService Class Loaded**
- Check file exists: `services/QRCodeService.php`
- Check require_once di `public/api/events.php` line 159
- Check require_once di `cron/send_event_reminders.php` line 21

**Check 4: Email HTML Source**
View email source di email client, cari:
```html
<img src="data:image/png;base64,iVBORw0KG..." 
     alt="QR Code Kehadiran" 
     style="width:250px; height:250px;" />
```

Jika ada, berarti QR generated. Jika image broken, check base64 string valid.

---

### Email tidak terkirim sama sekali

**Check SMTP Settings (.env):**
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password  # Not regular password!
SMTP_SECURE=tls
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=EventSite
```

**Check Gmail App Password:**
- Bukan password biasa
- Generate di: Google Account → Security → 2-Step Verification → App passwords
- Format: 16 characters lowercase (abcd efgh ijkl mnop)

**Test Email Manually:**
```bash
cd c:\laragon\www\EventSite
php -r "
require_once 'services/NotificationService.php';
\$sent = NotificationService::sendEmail(1, 'test@example.com', 'Test Subject', '<h1>Test Body</h1>');
echo \$sent ? 'Email sent!' : 'Failed!';
"
```

---

### QR Code tidak bisa di-scan

**Possible Issues:**
1. **Image size terlalu kecil** - Minimal 200x200px (kita pakai 250x250)
2. **Error correction level rendah** - Kita pakai ECC_H (highest)
3. **Token format salah** - Check qr_token di database (64 chars SHA256)
4. **Scanner app issue** - Test dengan multiple apps (Google Lens, native camera, QR scanner apps)

**Test QR Code Generation:**
```bash
cd c:\laragon\www\EventSite
php -r "
require_once 'vendor/autoload.php';
require_once 'services/QRCodeService.php';

\$testToken = 'test123456789';
\$qrImage = QRCodeService::generateQRImageTag(\$testToken, 250);
echo \$qrImage;
"
```

Expected: HTML img tag dengan base64 image

**Manual QR Test:**
1. Copy base64 string from email source
2. Paste di browser: `data:image/png;base64,[PASTE_HERE]`
3. Screenshot displayed QR
4. Test scan dengan phone camera

---

## Production Checklist

Before going live with QR email feature:

- [ ] Composer dependencies installed
- [ ] QR tokens generated for all existing participants (migration ran)
- [ ] Test registration email received with QR
- [ ] Test reminder cron job successfully sends emails
- [ ] QR codes scannable with multiple scanner apps
- [ ] Email displays properly in Gmail, Outlook, Yahoo Mail
- [ ] SMTP credentials secure (app password, not regular password)
- [ ] Cron job scheduled (Windows Task Scheduler or alternative)
- [ ] Error logging enabled in NotificationService
- [ ] Backup database before deployment

---

## Quick Test Commands

**1. Generate test participant with QR:**
```sql
INSERT INTO participants (user_id, event_id, qr_token, status)
VALUES (1, 1, SHA2(CONCAT('test', UNIX_TIMESTAMP()), 256), 'registered');
```

**2. Test QR generation:**
```bash
php -r "require_once 'vendor/autoload.php'; require_once 'services/QRCodeService.php'; echo QRCodeService::generateQRImageTag('testtoken123', 250);"
```

**3. Run reminder cron (dry run):**
```bash
php cron/send_event_reminders.php
```

**4. Check sent notifications:**
```sql
SELECT * FROM notifications 
WHERE type IN ('registration', 'event_reminder') 
ORDER BY send_at DESC 
LIMIT 10;
```

---

## Success Indicators

### Registration Email Success:
1. Email received within 5 seconds of registration
2. QR code displays as 250x250px image
3. Button "Lihat Detail Event" works
4. QR code scannable with panitia account

### Reminder Email Success:
1. Cron job completes without errors
2. All participants receive email (check console log)
3. QR code displays with dashed crimson border
4. Database has notification record with type='event_reminder'

### Overall Success:
- No PHP errors in console/logs
- 100% email delivery rate
- QR codes 100% scannable
- Attendance confirmation works after scan

---

## Files to Monitor

**Logs to check:**
- PHP error log: `c:\laragon\bin\php\php-8.x\logs\error.log`
- Apache error log: `c:\laragon\www\apache\logs\error.log`
- Cron output: Run manually to see console output

**Database tables:**
- `participants` - Check qr_token populated
- `notifications` - Check email sent status
- `events` - Check event timing for reminder

**Email client:**
- Gmail inbox (or SMTP_USERNAME)
- Spam/Junk folder (if not in inbox)
- HTML source view to inspect QR base64

---

That's it! QR code implementation fully documented for testing. 🎉
