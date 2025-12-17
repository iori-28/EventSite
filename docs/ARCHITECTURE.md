# 🏗️ EventSite - Architecture Documentation

**Dokumentasi Lengkap Arsitektur Backend & Frontend**  
*Untuk Academic Review & Team Understanding*

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Architecture Pattern](#architecture-pattern)
4. [Backend Architecture](#backend-architecture)
5. [Frontend Architecture](#frontend-architecture)
6. [Database Schema](#database-schema)
7. [Authentication System](#authentication-system)
8. [Notification System](#notification-system)
9. [QR Code System](#qr-code-system)
10. [Certificate System](#certificate-system)
11. [Calendar Integration](#calendar-integration)
12. [API Endpoints](#api-endpoints)
13. [Security Implementations](#security-implementations)
14. [File Structure](#file-structure)

---

## 🎯 Project Overview

**EventSite** adalah sistem manajemen event berbasis web yang memungkinkan:
- **Admin**: Kelola semua event, user, approval, completion, dan analytics
- **Panitia**: Buat dan kelola event, track participants, dan konfirmasi kehadiran
- **User**: Browse event, daftar, check-in via QR code, dan download sertifikat

### Key Features
- ✅ Multi-role authentication (Admin, Panitia, User)
- ✅ Event creation & approval workflow
- ✅ QR Code attendance tracking
- ✅ Automated email notifications (PHPMailer)
- ✅ Certificate generation (PDF)
- ✅ Calendar integration (Google Calendar, .ics export)
- ✅ Event reminders (H-1 dan H-0)
- ✅ Analytics dashboard
- ✅ Responsive design

---

## 🛠️ Technology Stack

### Backend
- **Language**: PHP 8.x
- **Database**: MySQL 8.x with PDO
- **Email**: PHPMailer 6.9 (SMTP)
- **QR Code**: chillerlan/php-qrcode 5.0
- **Composer**: Dependency management

### Frontend
- **HTML5 + CSS3**: Semantic markup, CSS Variables
- **JavaScript**: Vanilla JS (no framework)
- **Chart.js**: Analytics visualization
- **Google Fonts**: Inter typeface

### Development Tools
- **Laragon**: Local development environment (Apache + MySQL + PHP)
- **VS Code**: Code editor
- **Git**: Version control

---

## 🏛️ Architecture Pattern

EventSite menggunakan **MVC Pattern** (Model-View-Controller) dengan tambahan **Service Layer**.

```
┌─────────────────────────────────────────────────────────────┐
│                        PRESENTATION LAYER                    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │   Views     │  │ Components  │  │   Public Assets     │ │
│  │  (HTML/PHP) │  │   (Reuse)   │  │   (CSS/JS/Images)   │ │
│  └─────────────┘  └─────────────┘  └─────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                      CONTROLLER LAYER                        │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Routing, Input Validation, Session Management       │   │
│  │  AuthController, EventController, etc.               │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                       SERVICE LAYER                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Business Logic, External APIs, Email, QR, Calendar  │   │
│  │  NotificationService, QRCodeService, etc.            │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                        MODEL LAYER                           │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Database Operations, Data Validation                │   │
│  │  User, Event, Participant, Notification, Certificate │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                       DATABASE LAYER                         │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  MySQL Database with PDO Connection                  │   │
│  │  Tables: users, events, participants, notifications  │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Why MVC + Service Layer?

1. **Separation of Concerns**: Setiap layer punya tanggung jawab yang jelas
2. **Reusability**: Service layer bisa dipakai di multiple controllers
3. **Testability**: Mudah untuk unit testing setiap component
4. **Maintainability**: Mudah debug dan extend features
5. **Scalability**: Gampang tambah fitur baru tanpa ganggu existing code

---

## 🔧 Backend Architecture

### 1. **Config Layer** (`config/`)

#### `db.php` - Database Connection Manager
```php
class Database {
    public static function connect()
    // Singleton pattern untuk PDO connection
    // Return: PDO instance dengan error mode exception
}
```

**Purpose**: Centralized database connection dengan error handling dan UTF-8 support.

#### `env.php` - Environment Configuration
```php
// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Email Settings
define('FROM_EMAIL', 'noreply@eventsite.com');
define('FROM_NAME', 'EventSite Team');

// Event Reminder Configuration
define('EVENT_REMINDER_ENABLED', true);
define('REMINDER_DAYS_BEFORE', 1);
```

**Purpose**: Store sensitive credentials dan app configuration (tidak di-commit ke Git).

---

### 2. **Model Layer** (`models/`)

Models handle semua database operations dan data validation.

#### `User.php` - User Management
```php
class User {
    // Cari user by email (untuk login)
    public static function findByEmail($email)
    
    // Create new user dengan hashed password
    public static function create($name, $email, $password, $role = 'user')
}
```

**Database Table**: `users`
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255),  -- bcrypt hash
    role ENUM('admin', 'panitia', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `Event.php` - Event Management
```php
class Event {
    // Create event (status: draft/pending)
    public static function create($data)
    
    // Get approved events (public listing)
    public static function getApproved()
    
    // Update event status (admin approval)
    public static function updateStatus($id, $status)
    
    // Mark event as completed
    public static function markAsCompleted($id)
}
```

**Database Table**: `events`
```sql
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    start_at DATETIME,
    end_at DATETIME,
    capacity INT DEFAULT 0,
    category VARCHAR(50),  -- Workshop, Seminar, Webinar, etc.
    status ENUM('draft','pending','approved','rejected','cancelled','completed','waiting_completion'),
    created_by INT,  -- FK to users.id
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Event Status Workflow**:
```
draft → pending → approved → waiting_completion → completed
               ↓
            rejected
```

#### `Participant.php` - Registration Management
```php
class Participant {
    // Register user ke event dengan QR token
    public static function register($user_id, $event_id)
    
    // Cancel registration
    public static function cancel($user_id, $event_id)
    
    // Update attendance status (check-in via QR)
    public static function updateStatus($id, $status)
}
```

**Database Table**: `participants`
```sql
CREATE TABLE participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,  -- FK to users.id
    event_id INT NOT NULL,  -- FK to events.id
    status ENUM('registered','checked_in','cancelled') DEFAULT 'registered',
    qr_token VARCHAR(255) UNIQUE,  -- Untuk QR code attendance
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    checked_in_at TIMESTAMP NULL
);
```

#### `Notification.php` - Notification Logging
```php
class Notification {
    // Create notification record
    public static function create($user_id, $type, $payload, $status = 'pending')
    
    // Update delivery status
    public static function updateStatus($id, $status)
    
    // Get notifications by user
    public static function getByUser($user_id, $limit = 50)
}
```

**Database Table**: `notifications`
```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,  -- FK to users.id
    type VARCHAR(100),  -- event_approved, registration_success, etc.
    payload JSON,  -- Event details, email content, etc.
    status ENUM('sent','failed','pending') DEFAULT 'pending',
    send_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Notification Types**:
- `event_approved` - Event disetujui admin
- `event_rejected` - Event ditolak admin
- `registration_success` - Berhasil daftar event
- `event_reminder` - Reminder H-1 dan H-0
- `certificate_issued` - Sertifikat ready untuk download

#### `Certificate.php` - Certificate Management
```php
class Certificate {
    // Create certificate record
    public static function create($participant_id, $file_path)
    
    // Get certificate by participant
    public static function getByParticipant($participant_id)
    
    // Get all certificates for a user
    public static function getByUser($user_id)
}
```

**Database Table**: `certificates`
```sql
CREATE TABLE certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participant_id INT NOT NULL,  -- FK to participants.id
    file_path VARCHAR(255),  -- Path ke PDF file
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### 3. **Controller Layer** (`controllers/`)

Controllers handle request processing dan orchestrate antara Models dan Services.

#### `AuthController.php` - Authentication
```php
class AuthController {
    // Login dengan email & password
    // Return: true/false
    public static function login($email, $password)
    
    // Logout dan destroy session
    public static function logout()
    
    // Register new user
    // Return: SUCCESS / error message
    public static function register($name, $email, $password)
}
```

**Flow Login**:
1. User submit form (email + password)
2. `AuthController::login()` dipanggil
3. Cek user di database via `User::findByEmail()`
4. Verify password dengan `password_verify()`
5. Set session `$_SESSION['user']` jika berhasil
6. Redirect ke dashboard sesuai role

#### `EventController.php` - Event Operations
```php
class EventController {
    // Create new event
    public static function create($data)
    
    // Get approved events (public)
    public static function getApproved()
    
    // Approve event (admin only)
    public static function approve($event_id, $admin_id)
    
    // Reject event (admin only)
    public static function reject($event_id, $admin_id, $reason)
}
```

#### `ParticipantController.php` - Registration
```php
class ParticipantController {
    // Register user ke event + generate QR token
    public static function register($user_id, $event_id)
    
    // Confirm attendance via QR scan
    public static function confirmAttendance($qr_token)
}
```

#### `NotificationController.php` - Email Orchestration
```php
class NotificationController {
    // Create notification + send email
    // Return: ['success', 'db_id', 'email_sent']
    public static function createAndSend($user_id, $type, $payload, $subject, $body)
    
    // Get unread count (untuk badge)
    public static function getUnreadCount($user_id)
    
    // Get latest notifications (untuk dropdown)
    public static function getLatest($user_id, $limit = 5)
}
```

**Flow Notification**:
1. Event trigger (approval, registration, etc.)
2. Call `NotificationController::createAndSend()`
3. Create record di database (status: pending)
4. Try send email via `NotificationService::sendEmail()`
5. Update status (sent/failed)
6. User lihat notifikasi di dashboard

#### `CertificateController.php` - Certificate Generation
```php
class CertificateController {
    // Generate certificate PDF untuk participant
    public static function generate($participant_id)
    
    // Bulk generate untuk semua participants di event
    public static function bulkGenerate($event_id)
}
```

---

### 4. **Service Layer** (`services/`)

Services handle external integrations dan business logic yang complex.

#### `NotificationService.php` - Email Delivery
```php
class NotificationService {
    // Send email via PHPMailer (SMTP)
    // Return: true/false
    public static function sendEmail($to, $subject, $body, $name = '')
    
    // Send HTML email dengan template
    public static function sendHTMLEmail($to, $subject, $html_body, $name = '')
}
```

**PHPMailer Configuration**:
- Host: `smtp.gmail.com`
- Port: `587` (STARTTLS)
- Auth: Username + App Password
- Charset: UTF-8

**Email Templates** (`templates/emails/`):
- `event_approved.php` - Notifikasi approval
- `registration_success.php` - Konfirmasi registrasi dengan QR code
- `event_reminder.php` - Reminder H-1/H-0

#### `QRCodeService.php` - QR Code Generation
```php
class QRCodeService {
    // Generate QR code base64 image
    // Return: base64 string
    public static function generateBase64($data, $size = 250)
    
    // Generate QR code as HTML img tag
    // Return: <img src="data:image/png;base64,...">
    public static function generateImageTag($data, $size = 250, $alt = 'QR Code')
    
    // Save QR code as PNG file
    // Return: file path
    public static function saveAsFile($data, $file_path, $size = 300)
}
```

**Library**: chillerlan/php-qrcode v5.0
- Output format: PNG
- Error correction: Level H (30%)
- Size: Configurable (default 250-300px)

**QR Code Data Format**:
```
event:{event_id}|participant:{participant_id}|token:{qr_token}
```

#### `CertificateService.php` - PDF Generation
```php
class CertificateService {
    // Generate certificate PDF
    // Return: file path atau false
    public static function generate($participant_id)
}
```

**Implementation**: HTML to PDF conversion
- Template: HTML + inline CSS
- Output: PDF file di `public/certificates/`
- Filename: `cert_{event_id}_{user_id}_{timestamp}.pdf`

#### `CalendarService.php` - Calendar Integration
```php
class CalendarService {
    // Generate Google Calendar URL
    // Return: URL string
    public static function generateGoogleCalendarUrl($event)
    
    // Generate iCalendar (.ics) file content
    // Return: .ics file content
    public static function generateICalendar($event)
}
```

**Google Calendar URL Format**:
```
https://calendar.google.com/calendar/render?action=TEMPLATE
&text=Event+Title
&dates=20240120T100000Z/20240120T120000Z
&details=Description
&location=Location
```

**iCalendar Format** (RFC 5545):
```ics
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//EventSite//Event Calendar//EN
BEGIN:VEVENT
UID:event-123@eventsite.com
DTSTART:20240120T100000Z
DTEND:20240120T120000Z
SUMMARY:Event Title
DESCRIPTION:Description
LOCATION:Location
END:VEVENT
END:VCALENDAR
```

---

### 5. **API Endpoints** (`public/api/`)

RESTful-like API endpoints untuk AJAX operations.

#### `auth.php` - Authentication API
```
POST /api/auth.php?action=login
POST /api/auth.php?action=register
POST /api/auth.php?action=logout
```

#### `events.php` - Event Operations
```
POST /api/events.php?action=create
POST /api/events.php?action=list
POST /api/events.php?action=approve
POST /api/events.php?action=reject
POST /api/events.php?action=delete
```

#### `event_approval.php` - Admin Event Approval
```
POST /api/event_approval.php?action=approve
POST /api/event_approval.php?action=reject
POST /api/event_approval.php?action=delete
```

#### `participants.php` - Registration API
```
POST /api/participants.php?action=register
POST /api/participants.php?action=cancel
POST /api/participants.php?action=my-events
```

#### `participants_attendance.php` - Attendance Management
```
POST /api/participants_attendance.php?action=update_status
POST /api/participants_attendance.php?action=verify_qr
```

#### `certificates.php` - Certificate Operations
```
GET  /api/certificates.php?action=download&id=123
GET  /api/certificates.php?action=view&id=123
POST /api/certificates.php?action=confirm_attendance
```

#### `admin_event_completion.php` - Event Completion Workflow
```
POST /api/admin_event_completion.php?action=request_completion
POST /api/admin_event_completion.php?action=approve_completion
POST /api/admin_event_completion.php?action=generate_certificates
```

#### `analytics.php` - Analytics Data
```
GET /api/analytics.php?type=summary
GET /api/analytics.php?type=events_by_month
GET /api/analytics.php?type=events_by_category
GET /api/analytics.php?type=users_by_role
```

#### `export_calendar.php` - Calendar Export
```
GET /api/export_calendar.php?event_id=123
```

#### `notifications.php` - Notification Testing
```
POST /api/notifications.php?action=test-create
```

---

## 🎨 Frontend Architecture

### 1. **View Layer** (`views/`)

Views adalah halaman-halaman yang user lihat. Organized by role.

#### **Public Pages** (No authentication required)
- `home.php` - Landing page dengan featured events
- `events.php` - Public event listing dengan filter
- `event-detail.php` - Event details dengan registrasi button
- `login.php` - Login form
- `register.php` - Registration form

#### **User Pages** (Role: user)
- `user_dashboard.php` - Dashboard dengan stats dan recent events
- `user_browse_events.php` - Browse dan filter available events
- `user_my_events.php` - Event yang sudah didaftar (upcoming & past)
- `user_certificates.php` - List sertifikat yang bisa didownload
- `user_notifications.php` - Notification history
- `user_profile.php` - Edit profile

#### **Panitia Pages** (Role: panitia)
- `panitia_dashboard.php` - Dashboard dengan event stats
- `panitia_create_event.php` - Form create event baru
- `panitia_edit_event.php` - Edit event yang sudah dibuat
- `panitia_my_events.php` - List event yang dibuat
- `panitia_participants.php` - List participants per event + konfirmasi kehadiran
- `panitia_notifications.php` - Notification history
- `panitia_profile.php` - Edit profile

#### **Admin Pages** (Role: admin)
- `admin_dashboard.php` - Dashboard dengan global stats
- `admin_analytics.php` - Charts dan analytics (Chart.js)
- `admin_manage_events.php` - Manage all events dengan bulk actions
- `adm_apprv_event.php` - Event approval queue
- `admin_edit_event.php` - Edit any event
- `admin_event_completion.php` - Event completion workflow + certificate generation
- `admin_confirm_attendance.php` - Konfirmasi kehadiran participants
- `admin_manage_users.php` - User management (CRUD, change role)
- `admin_notifications.php` - Notification history
- `admin_reports.php` - Detailed reports dan statistics

#### **Utility Pages**
- `seed_admin.php` - Create/reset admin account
- `seed_panitia.php` - Create/reset panitia account
- `reset_password.php` - Reset password utility
- `check_users.php` - Verify user database

---

### 2. **Component Layer** (`public/components/`)

Reusable components untuk DRY principle.

#### `navbar.php` - Public Navigation Bar
- Logo dan brand
- Navigation menu (Home, Events, About, Contact)
- Login/Register buttons (if not logged in)
- Profile dropdown (if logged in)
- Responsive mobile menu

#### `sidebar.php` - Dashboard Sidebar
- Logo dan brand
- Role-based menu items
- Active state highlighting
- User profile info di footer
- Mobile responsive dengan overlay

#### `dashboard_header.php` - Dashboard Header
- Page title dan breadcrumb
- Notification bell dengan dropdown
- Badge count untuk unread notifications
- "Lihat Semua" link ke notification page
- Role-based notification routing

#### `footer.php` - Page Footer
- Brand info
- Quick links
- Contact info
- Copyright notice

#### `calendar_button.php` - Add to Calendar Button
- Google Calendar link
- Download .ics button
- Dropdown menu untuk pilih platform
- Reusable function: `renderCalendarButton($event)`

---

### 3. **Asset Layer** (`public/css/`, `public/js/`)

#### CSS Architecture

**`main.css`** - Global styles dan utility classes
```css
:root {
    /* CSS Variables */
    --primary-color: #6366f1;
    --secondary-color: #8b5cf6;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --text-dark: #1f2937;
    --text-muted: #6b7280;
    --border-color: #e5e7eb;
}
```

**Components**:
- `.btn` - Button styles (primary, secondary, success, danger)
- `.card` - Card container dengan shadow
- `.badge` - Badge untuk status dan counts
- `.form-group` - Form input styling
- `.table` - Table styling

**`dashboard.css`** - Dashboard-specific styles
- `.dashboard-container` - Flexbox layout untuk sidebar + content
- `.sidebar` - Sidebar styling dengan fixed position
- `.main-content` - Content area dengan padding
- `.stat-card` - Card untuk dashboard statistics

**`auth.css`** - Authentication page styles
- `.auth-container` - Centered login/register form
- `.logo` - Animated logo
- `.btn-submit` - Auth button dengan hover effects

#### JavaScript

**Inline Scripts** (per page)
- Form validation
- AJAX requests
- Modal dialogs
- Dropdown menus
- Chart rendering (Chart.js)

**Common Patterns**:
```javascript
// AJAX Form Submission
fetch('/EventSite/public/api/events.php', {
    method: 'POST',
    body: formData
})
.then(res => res.text())
.then(data => {
    if (data === 'SUCCESS') {
        alert('Berhasil!');
        location.reload();
    } else {
        alert('Error: ' + data);
    }
});
```

---

### 4. **Routing System** (`public/index.php`)

EventSite menggunakan **query parameter routing**.

```php
$page = $_GET['page'] ?? 'home';

// Whitelist allowed pages
$allowed_pages = [
    'home', 'events', 'event-detail', 'login', 'register',
    'user_dashboard', 'user_browse_events', // ... etc
];

// Security: Prevent directory traversal
$page = basename($page);

if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Include the requested view
require_once "views/{$page}.php";
```

**URL Format**:
```
http://localhost/EventSite/public/index.php?page=user_dashboard
http://localhost/EventSite/public/index.php?page=event-detail&id=5
http://localhost/EventSite/public/index.php?page=panitia_my_events
```

**Logout Handling**:
- Logout adalah special case yang di-handle di `index.php`
- Clear session, destroy session, redirect ke home
- Menggunakan meta refresh untuk compatibility

---

## 🗄️ Database Schema

### Entity Relationship Diagram

```
┌─────────────┐
│    users    │
├─────────────┤
│ id (PK)     │
│ name        │
│ email       │◄─────────┐
│ password    │          │
│ role        │          │
│ created_at  │          │
└─────────────┘          │
       │                 │
       │ created_by      │
       │                 │
       ▼                 │
┌─────────────┐          │
│   events    │          │
├─────────────┤          │
│ id (PK)     │◄──┐      │
│ title       │   │      │
│ description │   │      │
│ location    │   │      │
│ start_at    │   │      │
│ end_at      │   │      │
│ capacity    │   │      │
│ category    │   │      │
│ status      │   │      │
│ created_by  │───┘      │
│ created_at  │          │
└─────────────┘          │
       │                 │
       │ event_id        │
       │                 │
       ▼                 │
┌──────────────┐         │
│ participants │         │
├──────────────┤         │
│ id (PK)      │◄──┐     │
│ user_id      │───┼─────┘
│ event_id     │───┘
│ status       │
│ qr_token     │
│ registered_at│
│ checked_in_at│
└──────────────┘
       │
       │ participant_id
       │
       ▼
┌──────────────┐
│ certificates │
├──────────────┤
│ id (PK)      │
│ participant_id│───┘
│ file_path    │
│ issued_at    │
└──────────────┘

┌──────────────┐
│notifications │
├──────────────┤
│ id (PK)      │
│ user_id      │───► users.id
│ type         │
│ payload (JSON)│
│ status       │
│ send_at      │
│ created_at   │
└──────────────┘
```

### Table Descriptions

#### `users` - User Accounts
- Stores all user data (admin, panitia, user)
- Password hashed dengan bcrypt
- Role-based access control

#### `events` - Event Information
- All event details (title, description, location, datetime)
- Category untuk filtering (Workshop, Seminar, Webinar, etc.)
- Status workflow tracking
- Foreign key ke `users` (creator)

#### `participants` - Event Registrations
- Many-to-many relationship antara `users` dan `events`
- QR token untuk attendance tracking
- Status: registered, checked_in, cancelled
- Timestamps untuk registered dan checked_in

#### `certificates` - Generated Certificates
- Links to participant record
- File path ke PDF
- Issued timestamp

#### `notifications` - Email Notification Log
- Track semua email yang dikirim
- Payload (JSON) contains email details
- Status untuk monitoring delivery
- send_at bisa NULL (scheduled/immediate)

---

## 🔐 Authentication System

### Session-Based Authentication

EventSite menggunakan **PHP Session** untuk authentication.

#### Registration Flow
```
User fills form → POST to auth.php
                    ↓
    AuthController::register($name, $email, $password)
                    ↓
    Validation (email unique, password strength)
                    ↓
    Password hash dengan bcrypt
                    ↓
    User::create() → INSERT ke database
                    ↓
    Return SUCCESS/error message
```

#### Login Flow
```
User fills form → POST to auth.php
                    ↓
    AuthController::login($email, $password)
                    ↓
    User::findByEmail($email)
                    ↓
    password_verify($password, $user['password'])
                    ↓
    Set $_SESSION['user'] = [id, name, email, role]
                    ↓
    Redirect to role-based dashboard
```

#### Session Data Structure
```php
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'role' => 'user'  // admin | panitia | user
];
```

#### Role-Based Access Control

**Every protected page checks**:
```php
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
```

**Dashboard Routing by Role**:
- `admin` → `admin_dashboard`
- `panitia` → `panitia_dashboard`
- `user` → `user_dashboard`

#### Logout Flow
```
User clicks logout → index.php?page=logout
                        ↓
    $_SESSION = array()  // Clear session data
                        ↓
    session_destroy()    // Destroy session
                        ↓
    Redirect to home
```

#### Security Measures
- ✅ Password hashing dengan bcrypt (cost factor 10)
- ✅ Session regeneration untuk prevent session fixation
- ✅ Role-based access control di setiap protected page
- ✅ Input sanitization dan validation
- ✅ SQL injection prevention dengan prepared statements
- ✅ XSS prevention dengan `htmlspecialchars()`

---

## 📧 Notification System

### Architecture

```
Event Trigger → NotificationController → NotificationService → PHPMailer
     ↓                 ↓                      ↓                     ↓
  (Approval)     (Create record)      (SMTP Connection)      (Send Email)
     ↓                 ↓                      ↓                     ↓
  Database       (status=pending)     (Try send email)     (Update status)
```

### Flow Detail

1. **Event Trigger**
   - User registers for event
   - Admin approves event
   - Event reminder H-1/H-0
   - Certificate issued

2. **Controller Layer**
   ```php
   NotificationController::createAndSend(
       $user_id,
       'registration_success',
       ['event_title' => 'Workshop PHP', 'qr_code' => '...'],
       'Pendaftaran Berhasil',
       '<html>...</html>'
   );
   ```

3. **Create Database Record**
   ```php
   Notification::create($user_id, $type, $payload, 'pending')
   ```

4. **Send Email via SMTP**
   ```php
   NotificationService::sendEmail($to, $subject, $body)
   ```

5. **Update Status**
   ```php
   Notification::updateStatus($id, 'sent' | 'failed')
   ```

### Notification Types

#### `event_approved` - Event Disetujui
**To**: Panitia (event creator)
**Content**: "Event '{title}' telah disetujui admin dan sekarang dapat dilihat user"

#### `event_rejected` - Event Ditolak
**To**: Panitia (event creator)
**Content**: "Event '{title}' ditolak admin"
**Payload**: Rejection reason

#### `registration_success` - Registrasi Berhasil
**To**: User (participant)
**Content**: "Anda berhasil mendaftar event '{title}'"
**Includes**: QR code untuk attendance

#### `event_reminder` - Reminder H-1 atau H-0
**To**: All registered participants
**Content**: "Event '{title}' akan dimulai pada {date}"
**Cron**: Automated via `cron/send_event_reminders.php`

#### `certificate_issued` - Sertifikat Ready
**To**: Participant yang hadir
**Content**: "Sertifikat event '{title}' telah tersedia untuk diunduh"

### Email Templates

Located di `templates/emails/`:
- `event_approved.php`
- `registration_success.php`
- `event_reminder.php`
- `certificate_issued.php`

**Template Structure**:
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Subject</title>
    <style>/* Inline CSS */</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>EventSite</h1>
        </div>
        <div class="content">
            <!-- Dynamic content here -->
        </div>
        <div class="footer">
            <!-- Links, contact info -->
        </div>
    </div>
</body>
</html>
```

### SMTP Configuration

File: `config/env.php`
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);  // STARTTLS
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'app-password');  // Not regular password!
```

**Gmail Setup**:
1. Enable 2-Factor Authentication
2. Generate App Password
3. Use App Password di SMTP_PASSWORD

### Cron Job - Event Reminders

File: `cron/send_event_reminders.php`

**Runs**: Setiap hari via Task Scheduler (Windows) atau crontab (Linux)

**Logic**:
1. Query events yang akan dimulai H-1 atau H-0
2. Get all participants yang registered
3. Send reminder email dengan QR code
4. Log ke notifications table
5. Mark reminder as sent (prevent duplicate)

**Windows Task Scheduler**:
```batch
php C:\laragon\www\EventSite\cron\send_event_reminders.php
```

**Linux Crontab**:
```bash
0 8 * * * php /path/to/EventSite/cron/send_event_reminders.php
```

---

## 📱 QR Code System

### Purpose
QR Code digunakan untuk **attendance tracking** - confirm kehadiran participant di event.

### Architecture

```
Registration → Generate QR Token → Display QR Code
                     ↓                   ↓
              (Unique per participant)  (Email + Dashboard)
                     ↓                   ↓
              Save to database    User shows QR at event
                     ↓                   ↓
              participants.qr_token  Panitia scans QR
                                         ↓
                                 Verify token + Update status
                                         ↓
                                    checked_in!
```

### Implementation

#### 1. **Generate QR Token** (Registration)
```php
// In Participant::register()
$qr_token = bin2hex(random_bytes(16));  // 32-char unique token
```

#### 2. **Generate QR Code Image**
```php
// In QRCodeService
$qr_data = "event:{$event_id}|participant:{$participant_id}|token:{$qr_token}";
$qr_image = QRCodeService::generateBase64($qr_data, 250);
```

#### 3. **Display QR Code**

**Email Template**:
```html
<img src="<?= $qr_base64 ?>" alt="QR Code" style="width:250px;height:250px;"/>
<p>Tunjukkan QR code ini saat check-in di event</p>
```

**User Dashboard**:
```html
<div class="qr-code-container">
    <img src="<?= $qr_image ?>" alt="QR Code"/>
    <p>Screenshot QR code ini</p>
</div>
```

#### 4. **Scan & Verify**

**Panitia Interface** (`panitia_participants.php`):
```html
<input type="text" id="qr-scanner" placeholder="Scan QR code...">
```

**AJAX Verification**:
```javascript
fetch('/EventSite/public/api/participants_attendance.php', {
    method: 'POST',
    body: JSON.stringify({
        action: 'verify_qr',
        qr_token: scanned_token
    })
});
```

**Backend Verification**:
```php
// Find participant by qr_token
$participant = Participant::findByQRToken($qr_token);

if ($participant && $participant['event_id'] == $event_id) {
    // Update status to checked_in
    Participant::updateStatus($participant['id'], 'checked_in');
    return 'SUCCESS';
}
```

### Security Considerations

- ✅ QR token adalah random 32-char hex string (virtually impossible to guess)
- ✅ One token per participant per event (no reuse)
- ✅ Verify event_id matches (prevent QR code dari event lain)
- ✅ Check-in timestamp recorded untuk audit trail

---

## 🎓 Certificate System

### Workflow

```
Event Completed → Admin Request Completion → Check Attendance
                         ↓                         ↓
              (waiting_completion)    (All participants checked in?)
                         ↓                         ↓
              Admin Approve Completion      Generate Certificates
                         ↓                         ↓
              (status = completed)     Create PDF for each participant
                                              ↓
                                    Save to public/certificates/
                                              ↓
                                    Create certificate record
                                              ↓
                                    Send notification email
                                              ↓
                                    User download via dashboard
```

### Implementation

#### 1. **Event Completion Request**

**Panitia** request completion:
```php
// In Event::requestCompletion()
UPDATE events 
SET status = 'waiting_completion', 
    completion_requested_at = NOW() 
WHERE id = ?
```

#### 2. **Admin Approval**

**Admin** checks:
- All participants have `checked_in` status
- Event sudah lewat (end_at < NOW())
- Attendance rate > threshold (optional)

```php
// In admin_event_completion.php
if ($can_complete) {
    Event::markAsCompleted($event_id);
    // status = 'completed'
}
```

#### 3. **Certificate Generation**

**Bulk Generate**:
```php
CertificateController::bulkGenerate($event_id);
```

**Per Participant**:
```php
foreach ($participants as $p) {
    if ($p['status'] === 'checked_in') {
        $file_path = CertificateService::generate($p['id']);
        Certificate::create($p['id'], $file_path);
    }
}
```

#### 4. **PDF Generation**

**Method**: HTML to PDF
```php
// In CertificateService
$html = self::generateHTML([
    'participant_name' => $name,
    'event_title' => $title,
    'event_date' => $date,
    'certificate_number' => $cert_number
]);

// Save as PDF file
file_put_contents($file_path, $html);
```

**Template**: Certificate HTML template dengan:
- Header logo
- Certificate title
- Participant name (large, centered)
- Event details
- Date issued
- Signature (digital atau image)
- Certificate number

#### 5. **User Download**

**User Dashboard** (`user_certificates.php`):
```html
<a href="/EventSite/public/api/certificates.php?action=download&id=<?= $cert_id ?>" 
   class="btn btn-primary" download>
    📥 Download Certificate
</a>
```

**API Handler**:
```php
// In certificates.php
$file_path = Certificate::getById($cert_id)['file_path'];
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="certificate.pdf"');
readfile($file_path);
```

---

## 📅 Calendar Integration

### Purpose
Allow users to add event ke personal calendar mereka (Google Calendar, Outlook, Apple Calendar).

### Implementation

#### 1. **Google Calendar**

**Generate URL**:
```php
CalendarService::generateGoogleCalendarUrl($event);
```

**URL Format**:
```
https://calendar.google.com/calendar/render?action=TEMPLATE
&text=Workshop%20PHP
&dates=20240120T100000Z/20240120T120000Z
&details=Description%20here
&location=Jakarta
&sf=true
&output=xml
```

**User Flow**:
1. User click "Add to Google Calendar" button
2. Opens Google Calendar dengan pre-filled event details
3. User click "Save" di Google Calendar

#### 2. **iCalendar (.ics Export)**

**Generate .ics File**:
```php
$ics_content = CalendarService::generateICalendar($event);
```

**Format** (RFC 5545):
```
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//EventSite//Event Calendar//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
UID:event-123@eventsite.com
DTSTAMP:20240115T120000Z
DTSTART:20240120T100000Z
DTEND:20240120T120000Z
SUMMARY:Workshop PHP
DESCRIPTION:Learn PHP programming...
LOCATION:Jakarta Convention Center
STATUS:CONFIRMED
SEQUENCE:0
END:VEVENT
END:VCALENDAR
```

**Download Handler**:
```php
// In export_calendar.php
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="event.ics"');
echo $ics_content;
```

**User Flow**:
1. User click "Download .ics"
2. File downloaded ke computer
3. Double-click file → Opens default calendar app
4. Event imported ke calendar

#### 3. **Timezone Handling**

EventSite uses **Asia/Jakarta** timezone, but calendar exports use **UTC**.

**Conversion**:
```php
$dt = new DateTime($event['start_at'], new DateTimeZone('Asia/Jakarta'));
$dt->setTimezone(new DateTimeZone('UTC'));
$utc_time = $dt->format('Ymd\THis\Z');
```

#### 4. **Component - Add to Calendar Button**

Reusable component di `components/calendar_button.php`:

```php
require_once 'components/calendar_button.php';
renderCalendarButton($event);
```

**Renders**:
```html
<div class="calendar-dropdown">
    <button>📅 Add to Calendar</button>
    <div class="dropdown-menu">
        <a href="[Google Calendar URL]">Google Calendar</a>
        <a href="api/export_calendar.php?event_id=123" download>
            Outlook / Apple Calendar (.ics)
        </a>
    </div>
</div>
```

---

## 🔒 Security Implementations

### 1. **SQL Injection Prevention**

**Always use prepared statements**:
```php
// ❌ BAD: Vulnerable to SQL injection
$stmt = $db->query("SELECT * FROM users WHERE email = '$email'");

// ✅ GOOD: Safe with prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### 2. **XSS Prevention**

**Escape output**:
```php
// ❌ BAD: XSS vulnerable
echo $user_input;

// ✅ GOOD: Escaped
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

### 3. **Password Security**

**Hashing**:
```php
// Hash password dengan bcrypt (cost factor 10)
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verify password
$is_valid = password_verify($input_password, $stored_hash);
```

### 4. **Session Security**

**Session configuration**:
```php
// Regenerate session ID untuk prevent session fixation
if (empty($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}
```

### 5. **CSRF Protection**

**Current implementation**: Basic form validation
**Recommendation**: Add CSRF tokens untuk POST forms

```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Verify token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

### 6. **File Upload Security**

**Certificate file naming**:
```php
// Prevent path traversal
$filename = basename($filename);

// Validate extension
$allowed = ['pdf'];
$ext = pathinfo($filename, PATHINFO_EXTENSION);
if (!in_array($ext, $allowed)) {
    die('Invalid file type');
}
```

### 7. **Input Validation**

**Example**:
```php
// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return 'INVALID_EMAIL';
}

// Validate integer
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

// Sanitize string
$title = trim(strip_tags($title));
```

### 8. **Directory Traversal Prevention**

**Routing**:
```php
// Prevent ../../../etc/passwd
$page = basename($page);

// Whitelist allowed pages
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}
```

### 9. **Error Handling**

**Production mode**:
```php
// Don't expose detailed errors to users
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log errors instead
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
```

---

## 📁 File Structure

```
EventSite/
├── config/                      # Configuration files
│   ├── db.php                   # Database connection (PDO)
│   └── env.php                  # Environment variables (SMTP, settings)
│
├── controllers/                 # Business logic controllers
│   ├── AuthController.php       # Login, register, logout
│   ├── CertificateController.php # Certificate generation
│   ├── EventController.php      # Event CRUD operations
│   ├── NotificationController.php # Email orchestration
│   └── ParticipantController.php # Registration management
│
├── cron/                        # Scheduled tasks
│   ├── send_event_reminders.php # Daily reminder cron job
│   └── README_REMINDER.md       # Cron setup guide
│
├── database/                    # Database scripts
│   └── migrations/
│       ├── dump_db.sql          # Full database schema
│       ├── migration_*.sql      # Schema updates
│       └── README.md
│
├── docs/                        # Documentation
│   ├── ARCHITECTURE.md          # This file!
│   ├── CODE_DOCUMENTATION_GUIDE.md
│   ├── QR_CODE_ATTENDANCE.md
│   ├── NOTIFICATION_SYSTEM_COMPLETE.md
│   └── ... (other docs)
│
├── models/                      # Database models
│   ├── Certificate.php          # Certificate operations
│   ├── Event.php                # Event operations
│   ├── Notification.php         # Notification logging
│   ├── Participant.php          # Registration operations
│   └── User.php                 # User authentication
│
├── public/                      # Web-accessible directory
│   ├── index.php                # Main router
│   ├── dashboard.php            # Developer dashboard
│   ├── logout.php               # Logout handler
│   │
│   ├── api/                     # API endpoints
│   │   ├── auth.php             # Authentication API
│   │   ├── events.php           # Event operations API
│   │   ├── participants.php     # Registration API
│   │   ├── certificates.php     # Certificate download API
│   │   ├── notifications.php    # Notification testing API
│   │   ├── analytics.php        # Analytics data API
│   │   └── ... (other APIs)
│   │
│   ├── components/              # Reusable UI components
│   │   ├── navbar.php           # Public navigation
│   │   ├── sidebar.php          # Dashboard sidebar
│   │   ├── dashboard_header.php # Dashboard header
│   │   ├── footer.php           # Page footer
│   │   └── calendar_button.php  # Add to calendar button
│   │
│   ├── css/                     # Stylesheets
│   │   ├── main.css             # Global styles
│   │   ├── dashboard.css        # Dashboard styles
│   │   └── auth.css             # Auth page styles
│   │
│   └── certificates/            # Generated PDF files (auto-created)
│
├── scripts/                     # Utility scripts
│   ├── run_migration.php        # Database migration runner
│   ├── verify_migration.php     # Migration verification
│   └── ... (other utilities)
│
├── services/                    # External integrations
│   ├── CalendarService.php      # Google Calendar + .ics export
│   ├── CertificateService.php   # PDF generation
│   ├── NotificationService.php  # Email sending (PHPMailer)
│   └── QRCodeService.php        # QR code generation
│
├── templates/                   # Email templates
│   └── emails/
│       ├── event_approved.php
│       ├── registration_success.php
│       ├── event_reminder.php
│       └── certificate_issued.php
│
├── vendor/                      # Composer dependencies
│   ├── autoload.php
│   ├── phpmailer/
│   └── chillerlan/
│
├── views/                       # Page views
│   ├── home.php                 # Landing page
│   ├── login.php                # Login form
│   ├── register.php             # Registration form
│   ├── events.php               # Public event listing
│   ├── event-detail.php         # Event details
│   │
│   ├── user_*.php               # User role pages
│   ├── panitia_*.php            # Panitia role pages
│   ├── admin_*.php              # Admin role pages
│   │
│   └── seed_*.php               # Database seeding utilities
│
├── .gitignore                   # Git ignore rules
├── composer.json                # Composer dependencies
├── composer.lock                # Locked dependency versions
├── PROJECT_STRUCTURE.md         # File structure overview
└── README.md                    # Project overview & setup guide
```

---

## 🎓 Academic Notes

### Design Patterns Used

1. **MVC Pattern** - Separation of concerns
2. **Singleton Pattern** - Database connection
3. **Service Layer Pattern** - External integrations
4. **Repository Pattern** - Data access abstraction
5. **Factory Pattern** - Object creation (QR codes, certificates)

### Best Practices Implemented

1. ✅ **DRY** (Don't Repeat Yourself) - Reusable components
2. ✅ **SOLID Principles** - Single responsibility per class
3. ✅ **Security First** - Input validation, output escaping, prepared statements
4. ✅ **Documentation** - PHPDoc comments di semua methods
5. ✅ **Error Handling** - Try-catch blocks dan logging
6. ✅ **Code Organization** - Clear folder structure
7. ✅ **Responsive Design** - Mobile-friendly UI

### Technologies Learned

- **Backend**: PHP OOP, PDO, Sessions, Composer
- **Database**: MySQL, SQL queries, normalization
- **Email**: SMTP, PHPMailer, HTML templates
- **Security**: Password hashing, SQL injection prevention, XSS
- **APIs**: RESTful design, AJAX, JSON
- **Frontend**: HTML5, CSS3, JavaScript, responsive design
- **Tools**: Git, Laragon, VS Code

---

## 📝 Conclusion

EventSite adalah aplikasi web full-stack yang mengimplementasikan:
- ✅ Multi-role authentication dan authorization
- ✅ Complete event management workflow
- ✅ Real-time notification system dengan email
- ✅ QR code-based attendance tracking
- ✅ Automated certificate generation
- ✅ Calendar integration
- ✅ Analytics dan reporting
- ✅ Security best practices

**Cocok untuk**:
- Final project / Tugas Akhir
- Academic portfolio
- Real-world event management needs
- Learning modern web development

---

**Developed by**: EventSite Team  
**Date**: December 2025  
**Version**: 1.0.0  
**License**: MIT (for educational purposes)

---

*Dokumentasi ini dibuat untuk memudahkan review oleh dosen dan team members. Jika ada pertanyaan, silakan refer ke code atau documentation files lainnya di folder `docs/`.*
