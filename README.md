# EventSite – Web Sistem Manajemen Event Mahasiswa

Dokumentasi resmi untuk struktur project, alur kerja, dan hubungan antar komponen dalam aplikasi **EventSite**.

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
│   ├── NotificationService.php
│   ├── CertificateService.php
│   ├── CalendarService.php
│   └── GoogleCalendarService.php
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
│   ├── README.md
│   ├── BUG_FIXES_REPORT.md
│   ├── CHANGELOG_EVENT_COMPLETION.md
│   ├── WORKFLOW_IMPLEMENTATION.md
│   └── NOTIFICATION_SYSTEM_COMPLETE.md
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

* Mengirim email (PHPMailer / SMTP)
* Mencatat log ke model `Notification`
* Template email untuk berbagai jenis notifikasi

### **CertificateService.php**

* Generate sertifikat HTML dari template
* Convert HTML ke PDF (jika diperlukan)
* Menyimpan data sertifikat ke database

### **CalendarService.php**

* Abstraksi untuk integrasi kalender
* Wrapper untuk berbagai provider (Google Calendar, dll)

### **GoogleCalendarService.php**

* Sinkronisasi event ke Google Calendar API
* Digunakan ketika event dibuat/diupdate
* Export event ke format .ics

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

---

---

# 🔗 Diagram Alur Kerja (MVC + Services)

```
              ┌──────────────────────────┐
              │          User            │
              └────────────┬─────────────┘
                           │ HTTP Request
                           ▼
               ┌────────────────────────┐
               │  public/index.php or   │
               │     public/api/*.php   │
               └────────────┬──────────┘
                           ▼
                   ┌────────────────┐
                   │   Controller   │
                   └──────┬────────┘
                          │
          ┌───────────────┼────────────────┐
          ▼                               ▼
   ┌───────────────┐                ┌──────────────┐
   │     Model      │                │    Service    │
   └───────┬────────┘                └──────┬───────┘
           ▼                                ▼
   ┌───────────────┐                ┌──────────────┐
   │    Database    │                │ API Eksternal │
   └───────────────┘                └──────────────┘
```

---

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
