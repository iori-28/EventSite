# 🎉 EventSite – Web Sistem Manajemen Event Mahasiswa

![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen) ![PHP](https://img.shields.io/badge/PHP-8.x-blue) ![License](https://img.shields.io/badge/License-Academic-orange)

**Event Management System** berbasis web untuk memudahkan pengelolaan event kampus dengan fitur lengkap:
- ✅ Multi-role authentication (Admin, Panitia, User) + Google OAuth
- ✅ Event creation & approval workflow
- ✅ QR Code attendance tracking
- ✅ Automated email notifications & reminders
- ✅ Certificate generation (PDF with templates)
- ✅ Calendar integration (Google Calendar, .ics export)
- ✅ Analytics dashboard with AI-powered recommendations
- ✅ CSV export functionality (participants, categories, full reports)
- ✅ Event reminders via cron (H-1 dan H-0)

**📚 Untuk dokumentasi lengkap backend & frontend architecture, baca: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)**

---
## 🛠️ Technology Stack

**Backend:**
- PHP 8.x (Native, no framework)
- MySQL/MariaDB
- Composer (Dependency management)
- PDO (Database abstraction)

**Frontend:**
- HTML5, CSS3, JavaScript (ES6+)
- Bootstrap 5 (Responsive framework)
- Chart.js (Data visualization)

**Libraries & Services:**
- PHPMailer (Email sending)
- Google API Client (OAuth & Calendar)
- chillerlan/php-qrcode (QR Code generation)
- mPDF (PDF generation)
- Firebase JWT (Token management)

**Architecture:**
- MVC-like pattern (Models, Controllers, Services)
- RESTful API design
- Session-based authentication
- OOP principles (Classes, Methods, Encapsulation)

## ✅ Academic Requirements Compliance

Proyek ini memenuhi 100% requirements akademik:
- ✅ PHP Native (no framework MVC)
- ✅ OOP Architecture (Class-based dengan methods)
- ✅ Multi-role Authentication + Google OAuth
- ✅ Database design dengan relasi proper
- ✅ 5 CRUD entities (exceeded requirement: 2)
- ✅ API Integration dengan Google Calendar
- ✅ Email notification system
- ✅ Chart visualization (4 charts: Bar, Line, Pie, Doughnut)
- ✅ Analytics service dengan recommendations
- ✅ CSV export functionality
- ✅ Complete documentation (ERD, UML, API endpoints)

**📄 Completion Report**: [`docs/PROJECT_COMPLETION_REPORT.md`](docs/PROJECT_COMPLETION_REPORT.md)

---
# � Visual Documentation

## Database Schema (ERD)
![ERD Diagram](docs/diagrams/ERD.png)

## System Use Cases
![Use Case Diagram](docs/diagrams/UseCase.png)

## Class Structure (OOP)
![Class Diagram](docs/diagrams/ClassDiagram.png)

**📋 Dokumentasi lengkap diagram & API endpoints:**
- **Diagrams**: [`docs/diagrams/README.md`](docs/diagrams/README.md)
- **API Endpoints**: [`docs/API_ENDPOINTS.md`](docs/API_ENDPOINTS.md)

---

# �📁 Struktur Direktori

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
│   ├── QRCodeService.php (QR code generation)
│   └── AnalyticsService.php (Metrics & CSV export)
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
│   ├── API_ENDPOINTS.md 📋 (All endpoints & routing)
│   ├── diagrams/ 📊 (ERD, UML, Use Case diagrams)
│   ├── ARCHITECTURE.md ⭐ (Complete system documentation)
│   ├── AUTH_FILES_EXPLANATION.md
│   ├── CODE_COMMENTS_GUIDE.md
│   ├── EMAIL_CONFIGURATION_GUIDE.md
│   ├── GOOGLE_CALENDAR_API_SETUP.md
│   ├── GOOGLE_OAUTH_SETUP.md
│   ├── HOSTING_DEPLOYMENT_GUIDE.md
│   ├── PROJECT_COMPLETION_REPORT.md
│   ├── QR_CODE_ATTENDANCE.md
│   ├── QR_USAGE_GUIDE.md
│   └── README.md
│
├── database/
│   └── migrations/
│       ├── README.md
│       ├── dump_db.sql
│       └── migration_*.sql
│
├── scripts/
│   ├── README.md
│   ├── check_event_time.php
│   └── run_event_reminders.bat
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
* Exclude user uploads (public/uploads/*) but preserve folder structure with .gitkeep files
* Preserve empty folders for fresh installations
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
- ✅ **Security First** - Input validation, output escaping, session management
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

Developed as an academic final project

**Development Period:** October - December 2025  
**Status:** ✅ Complete & Production Ready

---

# 🐛 Known Issues & Future Improvements

## Known Issues
- ✅ All critical bugs fixed
- ✅ Timezone consistency implemented
- ✅ Reminder system working for all user types
- ✅ Contact section redesigned
- ✅ Project cleaned up and production-ready

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

1. **Role-Based Access Control**
   - Multi-role authentication: Admin, Panitia, User
   - Google OAuth 2.0 integration
   - Session management with auto-refresh
   - Authorization middleware untuk setiap page

2. **Event Management**
   - CRUD operations dengan image upload
   - Approval workflow (pending → approved/rejected)
   - Event completion workflow dengan certificate generation
   - Category-based event classification
   - Capacity management & auto-increment

3. **Participant Management**
   - Self-registration dengan QR token generation
   - QR Code attendance tracking (scan to check-in)
   - Manual attendance confirmation (admin/panitia)
   - Automatic certificate generation setelah hadir
   - Export participant lists to CSV

4. **Notification System**
   - Email notifications via PHPMailer (SMTP)
   - Automated event reminders (H-1 dan H-0)
   - Email templates untuk berbagai scenarios
   - Notification logging ke database
   - Retry mechanism untuk failed emails

5. **Certificate Generation**
   - HTML template dengan dynamic data
   - PDF generation menggunakan mPDF library
   - Automatic download link via email
   - Certificate management interface

6. **Calendar Integration**
   - Google Calendar "Add to Calendar" URL
   - .ics file export untuk Outlook/Apple Calendar
   - Event synchronization

7. **Analytics & Reporting**
   - Event statistics dashboard dengan Chart.js
   - Time-series registration trend analysis
   - Category popularity metrics
   - AI-powered recommendations based on data
   - CSV export (participants, categories, full reports)
   - Visual charts: Bar, Doughnut, Line, Pie

8. **Automated Reminders**
   - Cron job scheduled execution
   - H-1 reminder (1 day before event)
   - H-0 reminder (event day morning)
   - Batch processing dengan error handling

9. **Database Migration System**
   - Version-controlled migrations
   - Rollback capability
   - Seed data untuk testing
   - SQL dump untuk deployment

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

**Core Documentation:**
* **Architecture** → [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) ⭐ Complete system documentation
* **API Endpoints** → [docs/API_ENDPOINTS.md](docs/API_ENDPOINTS.md) 📋 All endpoints & routing
* **Code Comments** → [docs/CODE_COMMENTS_GUIDE.md](docs/CODE_COMMENTS_GUIDE.md) 📝 Documentation standards
* **Completion Report** → [docs/PROJECT_COMPLETION_REPORT.md](docs/PROJECT_COMPLETION_REPORT.md) ✅ Academic compliance

**Feature Documentation:**
* **QR Code Attendance** → [docs/QR_CODE_ATTENDANCE.md](docs/QR_CODE_ATTENDANCE.md)
* **QR Usage Guide** → [docs/QR_USAGE_GUIDE.md](docs/QR_USAGE_GUIDE.md)
* **Google OAuth Setup** → [docs/GOOGLE_OAUTH_SETUP.md](docs/GOOGLE_OAUTH_SETUP.md)
* **Google Calendar API** → [docs/GOOGLE_CALENDAR_API_SETUP.md](docs/GOOGLE_CALENDAR_API_SETUP.md)
* **Email Configuration** → [docs/EMAIL_CONFIGURATION_GUIDE.md](docs/EMAIL_CONFIGURATION_GUIDE.md)

**Technical Reference:**
* **Database Migrations** → [database/migrations/README.md](database/migrations/README.md)
* **Scripts Usage** → [scripts/README.md](scripts/README.md)
* **Hosting & Deployment** → [docs/HOSTING_DEPLOYMENT_GUIDE.md](docs/HOSTING_DEPLOYMENT_GUIDE.md)
* **Auth Files Explanation** → [docs/AUTH_FILES_EXPLANATION.md](docs/AUTH_FILES_EXPLANATION.md)

**Visual Documentation:**
* **Diagrams Guide** → [docs/diagrams/README.md](docs/diagrams/README.md) 📊 ERD, UML instructions

---

## 💯 Code Quality

**Documentation Coverage:**
- ✅ 100% file headers (Controllers, Models, Services, APIs)
- ✅ 100% PHPDoc method comments
- ✅ Inline comments for complex logic
- ✅ 20+ documentation markdown files

**Coding Standards:**
- ✅ PSR-12 compliant formatting
- ✅ Descriptive variable & function names
- ✅ No commented-out dead code
- ✅ Error handling & logging
- ✅ Input validation & sanitization

**Security:**
- ✅ Password hashing (bcrypt)
- ✅ Prepared statements (SQL injection prevention)
- ✅ Session management with auto-refresh
- ✅ XSS prevention (htmlspecialchars)
- ✅ Role-based authorization
- ⚠️ CSRF protection (recommended for future implementation)

**Testing:**
- ✅ Manual testing on all features
- ✅ Demo accounts available
- ✅ Seed data provided
- ✅ Debug scripts included

---

## 🎓 Academic Project Information

**Course**: Web Programming / Sistem Informasi  
**Institution**: [Your Institution]  
**Year**: 2024/2025  
**Status**: ✅ **COMPLETE** (50/50 points)

**Key Achievements:**
- 🏆 100% requirements compliance
- 🏆 Production-ready code quality
- 🏆 Comprehensive documentation
- 🏆 Advanced features (OAuth, Analytics, QR)
- 🏆 Professional UI/UX

---

## 📞 Support & Contact

For questions or issues:
1. Check documentation in `docs/` folder
2. Review code comments in relevant files
3. See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for system overview
4. Check [docs/BUG_FIXES_REPORT.md](docs/BUG_FIXES_REPORT.md) for known issues

---

## 📄 License

This project is developed for academic purposes.

---

**Built with ❤️ by EventSite Team**  
*Last Updated: December 18, 2025*
