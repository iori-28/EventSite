# 📊 Diagrams & Visual Documentation

Folder ini berisi diagram-diagram untuk dokumentasi visual sistem EventSite.

---

## 📁 File Structure

```
diagrams/
├── ERD.png                    - Entity Relationship Diagram
├── UseCase.png               - Use Case Diagram
├── ClassDiagram.png          - Class Diagram
├── ActivityDiagram.png       - Activity Diagram (Event Flow)
├── SequenceDiagram.png       - Sequence Diagram (API Calls)
└── SystemArchitecture.png    - System Architecture Overview
```

---

## 🎯 Diagram Details

### 1. **ERD (Entity Relationship Diagram)**
**File**: `ERD.png`

**Purpose**: Menggambarkan struktur database dan relationships antar tabel

**Entities**:
- **users** (id, name, email, password, role, profile_picture, oauth_provider, google_id)
- **events** (id, title, description, location, start_at, end_at, max_participants, status, category, image, created_by)
- **participants** (id, user_id, event_id, status, registered_at, qr_token)
- **certificates** (id, participant_id, certificate_code, issued_at, file_path)
- **notifications** (id, user_id, type, title, message, status, sent_at)

**Relationships**:
- users → events (1:N) - One user creates many events
- users → participants (1:N) - One user joins many events
- events → participants (1:N) - One event has many participants
- participants → certificates (1:1) - One participant gets one certificate
- users → notifications (1:N) - One user receives many notifications

---

### 2. **Use Case Diagram**
**File**: `UseCase.png`

**Purpose**: Menggambarkan interaksi antara actors dan sistem

**Actors**:
- **Admin**: Approve events, manage users, view analytics, confirm attendance
- **Panitia**: Create events, manage participants, download certificates
- **User**: Browse events, register, check-in via QR, download certificate

**Use Cases**:
- Login/Logout (All)
- Browse Events (All)
- Register for Event (User)
- Create Event (Panitia)
- Approve Event (Admin)
- QR Check-in (User)
- View Analytics (Admin)
- Generate Certificate (System)
- Send Notifications (System)

---

### 3. **Class Diagram**
**File**: `ClassDiagram.png`

**Purpose**: Menggambarkan struktur OOP dan relationships antar class

**Classes**:

**Controllers**:
- AuthController (login, register, logout)
- EventController (create, update, delete, approve)
- ParticipantController (register, checkIn, cancel)
- CertificateController (generate, download)
- NotificationController (send, mark as read)

**Models**:
- User (findByEmail, create, update)
- Event (getAll, getById, create, update, delete)
- Participant (register, checkIn, getByUser)
- Certificate (generate, getByParticipant)
- Notification (create, send)

**Services**:
- NotificationService (sendEmail, sendReminder)
- CertificateService (generatePDF)
- CalendarService (generateGoogleUrl, generateICalendar)
- QRCodeService (generate, verify)
- AnalyticsService (getMetrics, exportCSV)

---

### 4. **Activity Diagram**
**File**: `ActivityDiagram.png`

**Purpose**: Menggambarkan alur proses bisnis

**Flows**:
1. **Event Creation Flow**:
   - Panitia create event → Admin approve/reject → Notification sent
   
2. **Event Registration Flow**:
   - User browse events → Register → QR code generated → Email sent
   
3. **Check-in Flow**:
   - User scan QR → System verify → Mark attendance → Certificate eligible
   
4. **Event Completion Flow**:
   - Admin mark event complete → Generate certificates → Email sent to attendees

---

### 5. **Sequence Diagram**
**File**: `SequenceDiagram.png`

**Purpose**: Menggambarkan sequence API calls dan interactions

**Sequences**:
1. **Login Sequence**:
   - User → Frontend → api/auth.php → AuthController → Database → Session
   
2. **Event Registration Sequence**:
   - User → Frontend → api/participants.php → ParticipantController → Database → QRCodeService → NotificationService
   
3. **QR Check-in Sequence**:
   - User → QR Scanner → api/attendance.php → ParticipantController → Database → Response

---

### 6. **System Architecture**
**File**: `SystemArchitecture.png`

**Purpose**: High-level overview arsitektur sistem

**Layers**:
```
┌─────────────────────────────────────┐
│     Presentation Layer              │
│  (HTML/CSS/JS - Bootstrap/Tailwind) │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│     Application Layer               │
│   (Controllers + Views + Routing)   │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│     Business Logic Layer            │
│   (Models + Services + Validation)  │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│     Data Layer                      │
│        (MySQL Database)             │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│     External Services               │
│  (Google API, PHPMailer, Chart.js)  │
└─────────────────────────────────────┘
```

---

## 🛠️ Tools untuk Generate Diagrams

### Recommended Tools:
1. **draw.io** (https://app.diagrams.net/) - Free, web-based
2. **dbdiagram.io** (https://dbdiagram.io/) - ERD specialist
3. **PlantUML** (https://plantuml.com/) - Code-based diagrams
4. **Lucidchart** (https://www.lucidchart.com/) - Professional tool
5. **MySQL Workbench** - Auto-generate ERD from database

---

## 📝 How to Add Diagrams

### Option 1: Export dari Laporan
```bash
1. Buka dokumen laporan (Word/PDF)
2. Screenshot atau export diagram
3. Save as PNG/JPG
4. Copy ke folder ini dengan nama sesuai
```

### Option 2: Create New with draw.io
```bash
1. Go to https://app.diagrams.net/
2. Pilih template (ERD, UML, etc)
3. Design diagram
4. Export as PNG (File → Export as → PNG)
5. Save to this folder
```

### Option 3: Generate ERD from Database
```bash
1. Open MySQL Workbench
2. Database → Reverse Engineer
3. Select EventSite database
4. Export as PNG
5. Save as ERD.png
```

---

## 🎨 Diagram Guidelines

### File Naming Convention:
- Use PascalCase: `ERD.png`, `ClassDiagram.png`
- Include version if multiple: `ERD_v2.png`
- Use descriptive names: `EventRegistrationFlow.png`

### Image Requirements:
- **Format**: PNG (preferred) or JPG
- **Resolution**: Minimum 1920x1080px
- **File Size**: < 5MB per image
- **Clarity**: Text harus readable saat di-zoom

### Diagram Standards:
- Use consistent colors and shapes
- Label all entities/classes/actors clearly
- Include legends if necessary
- Add timestamps or version numbers

---

## 📚 Reference in Documentation

Untuk reference diagram di README atau documentation:

```markdown
## Database Schema

![ERD Diagram](docs/diagrams/ERD.png)

## System Architecture

![Architecture](docs/diagrams/SystemArchitecture.png)
```

---

## ✅ Checklist

Untuk memenuhi requirement dosen, pastikan ada:

- [ ] **ERD.png** - Entity Relationship Diagram
- [ ] **UseCase.png** - Use Case Diagram
- [ ] **ClassDiagram.png** - Class Diagram (OOP structure)
- [ ] **ActivityDiagram.png** - Activity/Flow Diagram
- [ ] **SequenceDiagram.png** - Sequence Diagram

**Optional but recommended**:
- [ ] SystemArchitecture.png
- [ ] DeploymentDiagram.png
- [ ] DataFlowDiagram.png

---

## 📞 Need Help?

Jika butuh bantuan generate diagram:
1. Baca tutorial di https://app.diagrams.net/
2. Lihat contoh UML di PlantUML website
3. Export dari laporan yang sudah ada

---

*Last Updated: December 18, 2025*
*For EventSite Academic Project*
