# Event Email Reminder - Setup Guide

## 📋 Fitur

Sistem ini akan mengirim email reminder otomatis ke peserta event **24 jam sebelum event dimulai**.

**Note:** Hanya mengirim ke user yang mengaktifkan email reminders di dashboard (`email_reminders_enabled = 1`).

## 🔧 Konfigurasi

### 1. Setup Email (SMTP)

Edit file `.env` dan isi dengan kredensial email Anda:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_NAME=EventSite

EVENT_REMINDER_ENABLED=true
EVENT_REMINDER_HOURS=24
```

**Untuk Gmail:**
1. Buka [Google Account Security](https://myaccount.google.com/security)
2. Aktifkan **2-Step Verification**
3. Buat **App Password** untuk aplikasi ini
4. Gunakan App Password tersebut di `MAIL_PASSWORD`

### 2. Update Database Schema

Jalankan query berikut untuk menambahkan kolom `is_read`:

```sql
ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER status;
```

Atau import ulang `dump_db.sql` yang sudah diperbarui.

## 🚀 Cara Menjalankan

### Manual Testing

Untuk testing, jalankan script secara manual dari command line:

```bash
cd c:\xampp\htdocs\EventSite-main\EventSite-main
php cron/send_event_reminders.php
```

Script akan:
1. Mencari event yang akan dimulai dalam 24 jam
2. Mengambil semua peserta yang terdaftar
3. Mengirim email reminder ke setiap peserta
4. Mencatat log pengiriman

### Otomatis dengan Windows Task Scheduler

Untuk menjalankan secara otomatis setiap jam:

#### Langkah 1: Buat Batch File

Buat file `run_event_reminders.bat` di folder `EventSite-main`:

```batch
@echo off
cd /d "c:\xampp\htdocs\EventSite-main\EventSite-main"
c:\xampp\php\php.exe cron\send_event_reminders.php >> logs\cron_reminder.log 2>&1
```

#### Langkah 2: Setup Task Scheduler

1. Buka **Task Scheduler** (tekan Win + R, ketik `taskschd.msc`)
2. Klik **Create Basic Task**
3. Isi detail:
   - **Name**: Event Reminder Sender
   - **Description**: Send email reminders for upcoming events
4. **Trigger**: Daily
   - Start: Hari ini
   - Recur every: 1 days
   - **Advanced settings**: Repeat task every **1 hour**, for a duration of **1 day**
5. **Action**: Start a program
   - Program/script: `c:\xampp\htdocs\EventSite-main\EventSite-main\run_event_reminders.bat`
6. **Finish**

#### Langkah 3: Test Task

1. Klik kanan pada task yang baru dibuat
2. Pilih **Run**
3. Cek log di `logs/cron_reminder.log`

## 📝 Monitoring

### Cek Log

Log akan tersimpan di:
- Console output (jika dijalankan manual)
- `logs/cron_reminder.log` (jika menggunakan batch file)
- Tabel `notifications` di database

### Cek Database

```sql
-- Lihat semua reminder yang terkirim
SELECT * FROM notifications 
WHERE type = 'event_reminder' 
ORDER BY created_at DESC;

-- Lihat statistik pengiriman
SELECT 
    status,
    COUNT(*) as total
FROM notifications
WHERE type = 'event_reminder'
GROUP BY status;
```

## ⚙️ Kustomisasi

### Ubah Waktu Reminder

Edit file `.env`:

```env
# Kirim 12 jam sebelum event
EVENT_REMINDER_HOURS=12

# Kirim 48 jam (2 hari) sebelum event
EVENT_REMINDER_HOURS=48
```

### Nonaktifkan Reminder

Edit file `.env`:

```env
EVENT_REMINDER_ENABLED=false
```

### Edit Template Email

Edit file `templates/emails/event_reminder_template.php` untuk mengubah desain atau konten email.

## 🐛 Troubleshooting

### Email tidak terkirim

1. **Cek konfigurasi SMTP** di `.env`
2. **Cek log error** di console atau log file
3. **Test koneksi SMTP** dengan script test sederhana
4. **Pastikan App Password** sudah benar (untuk Gmail)

### Reminder terkirim duplikat

Script sudah dilengkapi dengan **duplicate prevention**. Jika masih terjadi duplikat:
1. Cek tabel `notifications` untuk entry duplikat
2. Pastikan cron job tidak berjalan terlalu sering (minimal 1 jam sekali)

### Event tidak terdeteksi

1. **Cek status event** - harus `approved`
2. **Cek waktu event** - harus dalam window 24-25 jam dari sekarang
3. **Cek timezone** - pastikan sesuai dengan `Asia/Jakarta`

## 📧 Format Email

Email yang dikirim akan berisi:
- ⏰ Alert bahwa event akan dimulai dalam 24 jam
- 📅 Tanggal dan waktu event
- 📍 Lokasi event
- 📝 Deskripsi event
- 🔗 Link untuk melihat detail event

## 🔐 Keamanan

- **Jangan commit** file `.env` ke Git
- **Gunakan App Password**, bukan password email asli
- **Batasi akses** ke file cron dan konfigurasi
- **Monitor log** untuk aktivitas mencurigakan
