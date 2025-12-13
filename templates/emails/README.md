# Email Templates - Documentation

## 📧 Template Email yang Tersedia

Sistem EventSite menggunakan template email HTML profesional untuk semua notifikasi.

### 1. Registration Confirmation
**File**: `templates/emails/registration_confirmation_template.php`
**Digunakan saat**: User mendaftar ke event
**Subject**: "✅ Pendaftaran Event Berhasil - [Event Title]"

### 2. Event Approved
**File**: `templates/emails/event_approved_template.php`
**Digunakan saat**: Admin menyetujui event panitia
**Subject**: "✅ Event Disetujui - [Event Title]"

### 3. Event Rejected
**File**: `templates/emails/event_rejected_template.php`
**Digunakan saat**: Admin menolak event panitia
**Subject**: "❌ Event Ditolak - [Event Title]"

### 4. Event Reminder
**File**: `templates/emails/event_reminder_template.php`
**Digunakan saat**: 24 jam sebelum event dimulai (via cron job)
**Subject**: "⏰ Reminder: Event [Event Title] Besok!"

---

## 🎨 Fitur Template

✅ **Responsive Design** - Tampil bagus di desktop & mobile
✅ **Professional Layout** - Gradient header, card design
✅ **Color-coded** - Setiap template punya warna berbeda
✅ **Icons & Emoji** - Visual yang menarik
✅ **CTA Buttons** - Call-to-action yang jelas
✅ **Footer** - Informasi kontak & copyright

---

## 📝 Cara Menggunakan

Template sudah terintegrasi otomatis di:
- `public/api/event_approval.php` - Event approval/rejection
- `public/api/event_approval.php` - Registration confirmation
- `cron/send_event_reminders.php` - Event reminders

### Manual Usage

```php
// Load template
$template = file_get_contents('templates/emails/registration_confirmation_template.php');

// Replace placeholders
$template = str_replace('{{participant_name}}', $user['name'], $template);
$template = str_replace('{{event_title}}', $event['title'], $template);
$template = str_replace('{{event_location}}', $event['location'], $template);
$template = str_replace('{{event_datetime}}', date('l, d F Y - H:i', strtotime($event['start_at'])) . ' WIB', $template);
$template = str_replace('{{event_description}}', $event['description'], $template);
$template = str_replace('{{event_detail_url}}', 'http://localhost/...', $template);

// Send email
NotificationService::sendEmail($user_id, $email, $subject, $template);
```

---

## 🔧 Placeholder Variables

### Registration Confirmation Template
- `{{participant_name}}` - Nama peserta
- `{{event_title}}` - Judul event
- `{{event_location}}` - Lokasi event
- `{{event_datetime}}` - Waktu event
- `{{event_description}}` - Deskripsi event
- `{{event_detail_url}}` - Link ke detail event

### Event Approved Template
- `{{organizer_name}}` - Nama panitia
- `{{event_title}}` - Judul event
- `{{event_location}}` - Lokasi event
- `{{event_datetime}}` - Waktu event
- `{{event_capacity}}` - Kapasitas event
- `{{event_manage_url}}` - Link kelola event
- `{{event_detail_url}}` - Link detail event

### Event Rejected Template
- `{{organizer_name}}` - Nama panitia
- `{{event_title}}` - Judul event
- `{{rejection_reason}}` - Alasan penolakan
- `{{edit_event_url}}` - Link edit event

### Event Reminder Template
- `{{participant_name}}` - Nama peserta
- `{{event_title}}` - Judul event
- `{{event_datetime}}` - Waktu event
- `{{event_location}}` - Lokasi event
- `{{event_description}}` - Deskripsi event
- `{{event_detail_url}}` - Link detail event

---

## 🎨 Customization

Untuk mengubah desain template:

1. Edit file template di `templates/emails/`
2. Ubah warna gradient di header
3. Ubah teks footer
4. Tambah/hapus section sesuai kebutuhan

**Contoh ubah warna:**
```html
<!-- Purple gradient -->
<td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">

<!-- Green gradient -->
<td style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">

<!-- Orange gradient -->
<td style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
```

---

## ✅ Testing

Untuk test email template:

1. **Daftar ke event** → Akan terima registration confirmation
2. **Submit event sebagai panitia** → Admin approve/reject → Terima email
3. **Jalankan cron job** → `php cron/send_event_reminders.php`

---

## 📊 Email Analytics

Email yang terkirim dicatat di tabel `notifications`:
```sql
SELECT * FROM notifications WHERE type = 'registration' ORDER BY created_at DESC;
```

Status email:
- `sent` - Berhasil terkirim
- `failed` - Gagal terkirim
- `pending` - Menunggu pengiriman
