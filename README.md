# 🎉 EventSite – Web Sistem Manajemen Event Mahasiswa

**Event Management System** berbasis web untuk memudahkan pengelolaan event kampus dengan fitur lengkap:
- ✅ Multi-role authentication (Admin, Panitia, User)
- ✅ Event creation & approval workflow
- ✅ QR Code attendance tracking
- ✅ Automated email notifications
- ✅ Certificate generation (PDF)
- ✅ Calendar integration (Google Calendar, .ics)
- ✅ Analytics dashboard
- ✅ Event reminders (H-1 dan H-0)

**📚 Untuk dokumentasi lengkap backend & frontend architecture, baca: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)**

---

# 📁 Struktur Direktori

```
EventSite/
├── config/
│   ├── env.php
│   └── db.php
│
├── controllers/
│   ├── AuthController.php
│   ├── EventController.php
│   ├── ParticipantController.php
│   ├── NotificationController.php
│   └── CertificateController.php
│
├── models/
│   ├── User.php
│   ├── Event.php
│   ├── Participant.php
│   ├── Notification.php
│   └── Certificate.php
│
├── services/
│   ├── NotificationService.php (Email via PHPMailer)
│   ├── CertificateService.php (PDF generation)
│   ├── CalendarService.php (Google Calendar + .ics export)
│   └── QRCodeService.php (QR code generation)
│
├── views/
│   ├── admin_*.php (halaman admin)
│   ├── panitia_*.php (halaman panitia)
│   ├── user_*.php (halaman user)
│   ├── login.php
│   └── register.php
│
├── public/
│   ├── index.php
│   ├── dashboard.php
│   ├── logout.php
│   ├── api/ (REST API endpoints)
│   ├── components/ (reusable UI components)
│   ├── css/ (stylesheets)
│   └── certificates/ (generated certificate files)
│
├── templates/
│   └── emails/ (email templates)
│
├── cron/
│   └── send_event_reminders.php
│
├── docs/
│   ├── ARCHITECTURE.md ⭐ (Complete system documentation)
│   ├── CODE_DOCUMENTATION_GUIDE.md
│   ├── CODE_DOCUMENTATION_SUMMARY.md
│   ├── NOTIFICATION_SYSTEM_COMPLETE.md
│   ├── QR_CODE_ATTENDANCE.md
│   ├── WORKFLOW_IMPLEMENTATION.md
│   └── ... (other documentation files)
│
├── database/
│   └── migrations/
│       ├── README.md
│       ├── dump_db.sql
│       └── migration_*.sql
│
├── scripts/
│   ├── README.md
│   ├── run_migration.php
│   ├── verify_migration.php
│   └── *.bat (batch scripts)
│
├── composer.json
├── .env
├── .gitignore
└── vendor/
```

---

# 🔍 Penjelasan Folder & Hubungan Antar Komponen

## **1. config/**

#### `env.php`

* Memuat environment variable dari file `.env`.
* Berisi konfigurasi sensitif seperti kredensial database & API key.

#### `db.php`

* Menginisialisasi koneksi database menggunakan **PDO**.
* Dipanggil oleh *semua model*.

**Alur:**

```
Controller → Model → db.php → Database
```

---

## **2. models/**

Model merepresentasikan tabel database dan berisi fungsi CRUD.

### Model yang tersedia:

* **User** → data akun & autentikasi
* **Event** → data event
* **Participant** → pendaftaran peserta
* **Notification** → log pengiriman email
* **Certificate** → sertifikat peserta

**Relasi antar model:**

```
User 1—* Participant *—1 Event
Event 1—* Notification
Participant 1—1 Certificate
```

---

## **3. controllers/**

Menangani *logic aplikasi* dan request dari endpoint.

### **AuthController**

* Login, register, session handling
* Memanggil model `User`

### **EventController**

* CRUD event
* Approval event oleh admin
* Complete event workflow (waiting_completion → completed)
* Memanggil model `Event` & `CalendarService`

### **ParticipantController**

* Pendaftaran peserta event
* Konfirmasi kehadiran peserta
* Memanggil `Participant`, `Event`, dan `NotificationService`

### **NotificationController**

* Mengambil log notifikasi
* Mengirim notifikasi manual bila diperlukan
* Logging untuk debugging

### **CertificateController**

* Generate sertifikat HTML untuk peserta
* Download sertifikat dalam format PDF/HTML
* Memanggil `CertificateService` dan model `Certificate`

**Alur umum:**

```
API → Controller → Model → DB
                   ↳ Service (opsional)
```

---

## **4. services/**

Layer yang menangani integrasi eksternal dan business logic kompleks.

### **NotificationService.php**

* Mengirim email via PHPMailer (SMTP)
* Support HTML templates untuk berbagai jenis notifikasi
* Mencatat log ke model `Notification`
* Auto-update delivery status (sent/failed)

### **QRCodeService.php**

* Generate QR code untuk attendance tracking
* Library: chillerlan/php-qrcode v5.0
* Output format: Base64 image, HTML img tag, atau PNG file
* Digunakan untuk registrasi event & check-in participants

### **CertificateService.php**

* Generate sertifikat HTML dari template
* Convert HTML ke PDF
* Menyimpan data sertifikat ke database
* Support bulk generation untuk semua participants

### **CalendarService.php**

* Generate Google Calendar "Add Event" URL
* Generate iCalendar (.ics) file untuk Outlook/Apple Calendar
* Timezone conversion (Asia/Jakarta → UTC)
* Format sesuai RFC 5545 (iCalendar spec)

**Flow Service:**

```
Controller → Service → API Eksternal / File System
                        ↓
                     Model Log
```

---

## **5. views/**

Berisi file tampilan (UI) yang di-render oleh `public/index.php` berdasarkan role user.

### **Role-based Views:**

* **admin_*.php** — Dashboard & fitur admin (approval event, manage users, analytics, event completion)
* **panitia_*.php** — Dashboard & fitur panitia (create event, manage participants, attendance)
* **user_*.php** — Dashboard & fitur user biasa (browse events, my events, certificates)
* **login.php / register.php** — Autentikasi

### **Routing:**

```
public/index.php?page=admin_dashboard → views/admin_dashboard.php
public/index.php?page=user_my_events → views/user_my_events.php
```

---

## **6. public/**

File yang bisa diakses langsung oleh browser.

### File utama:

* `index.php` — Router utama (whitelist pages, routing ke views/)
* `dashboard.php` — Redirect ke dashboard sesuai role
* `logout.php` — Logout & destroy session

### **public/api/** (REST API Endpoints)

Semua AJAX request dari frontend dikirim ke sini.

| Endpoint                      | Controller yang dipanggil |
| ----------------------------- | ------------------------- |
| `auth.php`                    | AuthController            |
| `events.php`                  | EventController           |
| `participants.php`            | ParticipantController     |
| `participants_attendance.php` | ParticipantController     |
| `notifications.php`           | NotificationController    |
| `certificates.php`            | CertificateController     |
| `admin_event_completion.php`  | EventController           |
| `event_approval.php`          | EventController           |
| `users.php`                   | AuthController            |

### **public/components/**

Reusable UI components (sidebar, navbar, dll) yang di-include di views.

### **public/certificates/**

Folder untuk menyimpan file sertifikat yang di-generate (.html).

**Flow lengkap request browser:**

```
Browser → public/api/events.php → EventController → Event Model → DB
```

---

## **7. templates/**

Berisi template untuk konten dinamis.

### **templates/emails/**

* Template HTML untuk email notifikasi
* Digunakan oleh `NotificationService`
* Variabel placeholder di-replace saat runtime

---

## **8. cron/**

Berisi script untuk scheduled tasks / cron jobs.

### **send_event_reminders.php**

* Mengirim reminder email otomatis sebelum event dimulai
* Dijalankan via cron job atau Task Scheduler
* Mengirim ke semua peserta yang sudah registered

**Setup (Windows Task Scheduler):**

```bash
php C:\laragon\www\EventSite\cron\send_event_reminders.php
```

---

## **9. docs/**

Dokumentasi project (changelog, workflow, bug fixes).

* **README.md** — Panduan dokumentasi
* **BUG_FIXES_REPORT.md** — Laporan bug yang sudah diperbaiki
* **CHANGELOG_EVENT_COMPLETION.md** — Log perubahan workflow event completion
* **WORKFLOW_IMPLEMENTATION.md** — Dokumentasi implementasi workflow
* **NOTIFICATION_SYSTEM_COMPLETE.md** — Dokumentasi sistem notifikasi

---

## **10. database/**

Berisi file terkait database.

### **database/migrations/**

* SQL migration files untuk update schema
* `dump_db.sql` — Full database schema
* `migration_*.sql` — Incremental migrations
* Dijalankan via `scripts/run_migration.php`

---

## **11. scripts/**

Utility scripts untuk maintenance dan testing.

* **run_migration.php** — Menjalankan database migrations
* **verify_migration.php** — Validasi status migration
* **pashash.php** — Generate password hash
* **run_event_reminders.bat** — Batch script untuk cron
* **test_reminder.bat** — Test reminder system

---

## **12. vendor/**

Folder hasil **Composer**. Berisi library seperti:

* Google API Client
* PHPMailer
* Dotenv Loader

---

## **13. composer.json & .env**

### **composer.json**

* Dependency management
* Autoload configuration

### **.env**

Berisi configuration:

* DB_USERNAME, DB_PASSWORD
* SMTP_SERVER
* GOOGLE_API_KEY
* APP_URL

Dipanggil oleh `config/env.php`.

### **.gitignore**

* Exclude vendor/, .env, certificates/, node_modules/
* Exclude sensitive files dan generated content
🎯 Fitur Utama

## **1. Multi-Role System**
- **Admin**: Full control (approve events, manage users, analytics, event completion)
- **Panitia**: Create & manage events, track participants, confirm attendance
- **User**: Browse events, register, check-in via QR, download certificates

## **2. Event Management**
- CRUD operations dengan approval workflow
- Event categories (Workshop, Seminar, Webinar, Competition, etc.)
- Capacity management dan registration limits
- Status tracking: draft → pending → approved → completed

## **3. QR Code Attendance System** 🆕
- Generate unique QR code per participant saat registrasi
- Embed QR code di email confirmation dan dashboard
- Panitia scan QR untuk confirm kehadiran
- Track attendance dengan timestamp

## **4. Notification System**
- Automated email via PHPMailer (SMTP)
- Notification types: approval, registration, reminder, certificate
- Event reminder H-1 dan H-0 (automated cron job)
- Notification history & status tracking

## **5. Certificate Generation**
- Auto-generate PDF certificates setelah event completed
- Custom template dengan participant name & event details
- Bulk generation untuk semua attendees
- Download via user dashboard

## **6. Calendar Integration**
- Add to Google Calendar (one-click)
- Export .ics file (Outlook, Apple Calendar, etc.)
- Automatic timezone conversion
- Event details pre-filled

## **7. Analytics Dashboard**
- Event statistics (by month, category, status)
- User statistics (by role)
- Padraft/pending]
        ↓
Admin Approve/Reject ────────┐
        ↓                    │
   [approved]           [rejected]
        ↓
Event berlangsung + User Register
        ↓
Participants Check-in (QR Code)
        ↓
Panitia Request Completion
        ↓
[waiting_completion]
        ↓
Admin Verify Attendance
        ↓
Admin Approve Completion
        ↓
Auto-Generate Certificates
        ↓
Send Email Notifications
        ↓
   [completed] ✅
```

---

# 🔐 QR Code Attendance Flow 🆕

```
User Register Event
        ↓
Generate Unique QR Token
   Prerequisites
- PHP 8.x
- MySQL 8.x
- Composer
- Laragon (recommended) atau XAMPP/WAMP

## Installation Steps

### 1. Clone repository

```bash
git clone <repository-url>
cd EventSite
```

### 2. Install dependencies

```bash
composer install
```

**Dependencies yang di-install:**
- `phpmailer/phpmailer` - Email sending
- `chillerlan/php-qrcode` - QR code generation
- `vlucas/phpdotenv` - Environment configuration (optional)

### 3. Configure environment

**Buat file `config/env.php`:**
```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'eventsite');
define('DB_USER', 'root');
define('DB_PASS', '');

// SMTP Configuration (untuk email notifications)
define('SMTP_HOST', 'smtp.gmail.com');
de🧪 Testing

## Manual Testing Flow

### 1. **Admin Flow**
```
Login as admin → Dashboard
├── Approve pending events
├── View analytics & statistics
├── Manage users (create, edit, delete, change role)
├── Event completion workflow
│   ├── Verify attendance
│   ├── Approve completion
│   └── Generate certificates
└── View system reports
```

### 2. **Panitia Flow**
```
Login as panitia → Dashboard
├── Create new event
├── Wait for admin approval
├── View event participants
├── Confirm attendance (QR scan)
├── Request event completion
└── View notifications
```

### 3. **User Flow**
```
Register account → Login → Dashboard
├── Browse available events
├── Register for event
├── Receive confirmation email (with QR code)
├── Check-in at event (show QR)
├── Receive certificate (after event completed)
├── Download certificate
└── Add event to calendar (Google/Outlook)
```

## API Testing

**Test endpoints via Postman or browser:**
```bash
# Test notification (create dummy notification)
POST http://localhost/EventSite/public/api/notifications.php
Body: action=test-create

# Test QR code generation
GET http://localhost/EventSite/public/api/participants.php?action=my-events

# Test calendar export
GET http://localhost/EventSite/public/api/export_calendar.php?event_id=1
```

---

# 📚 Dokumentasi Lengkap

## 📖 Main Documentation
- **[ARCHITECTURE.md](docs/ARCHITECTURE.md)** ⭐ - **Complete backend & frontend architecture** (3000+ lines)
  - MVC + Service Layer pattern
  - Database schema & ER diagram
  - Authentication system
  - Notification system flow
  - QR Code system
  - Certificate generation
  - Security best practices
  - API documentation

## 📝 Additional Documentation
- **[CODE_DOCUMENTATION_GUIDE.md](docs/CODE_DOCUMENTATION_GUIDE.md)** - Coding standards & PHPDoc guide
- **[CODE_DOCUMENTATION_SUMMARY.md](docs/CODE_DOCUMENTATION_SUMMARY.md)** - Documentation summary
- **[NOTIFICATION_SYSTEM_COMPLETE.md](docs/NOTIFICATION_SYSTEM_COMPLETE.md)** - Email notification details
- **[QR_CODE_ATTENDANCE.md](docs/QR_CODE_ATTENDANCE.md)** - QR code implementation
- **[WORKFLOW_IMPLEMENTATION.md](docs/WORKFLOW_IMPLEMENTATION.md)** - Event completion workflow
- **[Database Migrations README](database/migrations/README.md)** - Migration guide
- **[Scripts README](scripts/README.md)** - Utility scripts usage

---

# 🛠️ Technology Stack

## Backend
- **PHP 8.x** - Server-side language
- **MySQL 8.x** - Database with PDO
- **Composer** - Dependency management

## Frontend
- **HTML5 + CSS3** - Semantic markup, CSS Variables
- **JavaScript (Vanilla)** - No framework dependency
- **Chart.js** - Analytics visualization

## Libraries
- **PHPMailer 6.9** - SMTP email sending
- **chillerlan/php-qrcode 5.0** - QR code generation
- **Google Fonts (Inter)** - Typography

## Tools
- **Laragon** - Local development environment
- **Git** - Version control
- **VS Code** - Code editor

---

# 🎓 Academic Notes

## Design Patterns
- ✅ **MVC Pattern** - Separation of concerns
- ✅ **Service Layer** - External integrations
- ✅ **Singleton** - Database connection
- ✅ **Repository Pattern** - Data access abstraction

## Best Practices
- ✅ **DRY** - Reusable components
- ✅ **SOLID Principles** - Single responsibility
- ✅ **Security First** - Input validation, output escaping
- ✅ **Documentation** - PHPDoc comments
- ✅ **Code Organization** - Clear folder structure

## Skills Demonstrated
- Backend: PHP OOP, PDO, Sessions, Composer
- Database: MySQL, SQL queries, normalization
- Email: SMTP, PHPMailer, HTML templates
- Security: Password hashing, SQL injection prevention
- APIs: RESTful design, AJAX, JSON
- Frontend: HTML5, CSS3, JavaScript, responsive design

---

# 📄 License

This project is developed for **academic purposes** (Final Project / Tugas Akhir).

**Usage:**
- ✅ For learning and educational purposes
- ✅ For academic presentations and reviews
- ✅ As portfolio material
- ❌ Not for commercial use without permission

---

# 👥 Contributors

**EventSite Team** - December 2025

---

# 🐛 Known Issues & Future Improvements

## Known Issues
- None reported (all critical bugs fixed)

## Future Improvements
- [ ] Advanced analytics dengan more charts
- [ ] Export reports to Excel/PDF
- [ ] WhatsApp notification integration
- [ ] Mobile app (React Native)
- [ ] Payment gateway integration
- [ ] Multi-language support (ID/EN)
- [ ] Advanced search & filtering
- [ ] Event categories with images
- [ ] Social media sharing
- [ ] Event feedback & rating system

---

# 📞 Support & Contact

Untuk pertanyaan atau issue, silakan:
1. Check dokumentasi di `docs/ARCHITECTURE.md`
2. Review code comments (PHPDoc)
3. Contact team members

---

**⭐ Star this project if you find it useful for learning!**
```

**Gmail SMTP Setup:**
1. Enable 2-Factor Authentication di Google Account
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use App Password di SMTP_PASSWORD (bukan password biasa)

### 4. Create database

```sql
CREATE DATABASE eventsite CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 5. Run migrations

```bash
php scripts/run_migration.php
php scripts/verify_migration.php
```

**Migrations akan create tables:**
- users
- events
- participants
- notifications
- certificates

### 6. Seed admin account

Akses via browser:
```
http://localhost/EventSite/views/seed_admin.php
```

**Default credentials:**
- Email: `admin@example.com`
- Password: `password`

### 7. Access aplikasi

```
http://localhost/EventSite/public/index.php
```

### 8. Setup cron job (optional)

**Windows Task Scheduler:**
```
Program: C:\laragon\bin\php\php-8.3.0\php.exe
Arguments: C:\laragon\www\EventSite\cron\send_event_reminders.php
Schedule: Daily at 06:00 AM
```

**Linux Crontab:**
```bash
crontab -e

# Add this line:
0 6 * * * php /path/to/EventSite/cron/send_event_reminders.php

# 🔄 Event Status Workflow

```
Panitia Create Event
        ↓
   [pending]
        ↓
Admin Approve/Reject
        ↓
   [approved] ──→ Event berlangsung
        ↓
Panitia Mark Complete
        ↓
[waiting_completion]
        ↓
Admin Approve Completion + Generate Certificates
        ↓
   [completed]
```

---

# 🎯 Fitur Utama

1. **Role-Based Access Control** (Admin, Panitia, User)
2. **Event Management** (CRUD, Approval, Completion Workflow)
3. **Participant Management** (Registration, Attendance, Certificates)
4. **Notification System** (Email via PHPMailer, Logging)
5. **Certificate Generation** (HTML template → PDF)
6. **Calendar Integration** (Google Calendar, .ics export)
7. **Analytics Dashboard** (Event statistics, participation rates)
8. **Automated Reminders** (Cron job untuk event reminders)
9. **Migration System** (Database versioning)

---

# 🚀 Setup & Installation

### 1. Clone repository

```bash
git clone <repository-url>
cd EventSite
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
# Edit .env dengan kredensial database & API keys
```

### 4. Run migrations

```bash
php scripts/run_migration.php
php scripts/verify_migration.php
```

### 5. Setup cron job (optional)

**Windows Task Scheduler:**

```
Program: C:\laragon\bin\php\php-8.x\php.exe
Arguments: C:\laragon\www\EventSite\cron\send_event_reminders.php
Schedule: Daily at 06:00
```

---

# 📚 Dokumentasi Lanjutan

Lihat folder `docs/` untuk dokumentasi lengkap:

* **Workflow Implementation** → [docs/WORKFLOW_IMPLEMENTATION.md](docs/WORKFLOW_IMPLEMENTATION.md)
* **Bug Fixes Report** → [docs/BUG_FIXES_REPORT.md](docs/BUG_FIXES_REPORT.md)
* **Notification System** → [docs/NOTIFICATION_SYSTEM_COMPLETE.md](docs/NOTIFICATION_SYSTEM_COMPLETE.md)
* **Database Migrations** → [database/migrations/README.md](database/migrations/README.md)
* **Scripts Usage** → [scripts/README.md](scripts/README.md)
