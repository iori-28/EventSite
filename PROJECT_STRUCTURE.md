# 📁 EventSite - Project Structure

Updated project structure with organized folders for better maintainability.

## 🗂️ Current Structure

```
EventSite/
├── docs/                          ← 📄 Documentation
│   ├── README.md
│   ├── API_ENDPOINTS.md
│   ├── ARCHITECTURE.md
│   ├── AUTH_FILES_EXPLANATION.md
│   ├── CODE_COMMENTS_GUIDE.md
│   ├── EMAIL_CONFIGURATION_GUIDE.md
│   ├── GOOGLE_CALENDAR_API_SETUP.md
│   ├── GOOGLE_OAUTH_SETUP.md
│   ├── HOSTING_DEPLOYMENT_GUIDE.md
│   ├── PROJECT_COMPLETION_REPORT.md
│   ├── QR_CODE_ATTENDANCE.md
│   ├── QR_USAGE_GUIDE.md
│   └── diagrams/
│
├── database/
│   └── migrations/                ← 🗄️ SQL Migrations
│       ├── README.md
│       ├── migration_completed_status.sql
│       ├── migration_event_completion_workflow.sql
│       └── dump_db.sql           (gitignored)
│
├── scripts/                       ← 🔧 Utility Scripts
│   ├── README.md
│   ├── pashash.php               (gitignored)
│   ├── run_migration.php
│   ├── verify_migration.php
│   ├── run_event_reminders.bat
│   └── test_reminder.bat
│
├── config/                        ← ⚙️ Configuration
│   ├── AuthMiddleware.php
│   ├── db.php
│   └── env.php
│
├── controllers/                   ← 🎮 Business Logic
│   ├── AuthController.php
│   ├── EventController.php
│   ├── ParticipantController.php
│   ├── CertificateController.php
│   └── NotificationController.php
│
├── models/                        ← 📊 Data Models
│   ├── User.php
│   ├── Event.php
│   ├── Participant.php
│   ├── Certificate.php
│   └── Notification.php
│
├── services/                      ← 🛠️ Services
│   ├── AnalyticsService.php
│   ├── CalendarService.php
│   ├── CertificateService.php
│   ├── NotificationService.php
│   └── QRCodeService.php
│
├── views/                         ← 🎨 UI Templates
│   ├── admin_*.php              (Admin pages)
│   ├── panitia_*.php            (Panitia pages)
│   ├── user_*.php               (User pages)
│   └── components/              (Reusable components)
│
├── public/                        ← 🌐 Public Assets
│   ├── index.php                (Main router)
│   ├── dashboard.php
│   ├── css/
│   ├── api/                     (API endpoints)
│   ├── certificates/            (Generated certificates)
│   ├── uploads/                 (User uploads - gitignored)
│   │   ├── .gitkeep            (Preserves folder structure)
│   │   └── events/              (Event photos)
│   │       └── .gitkeep        (Preserves folder structure)
│   └── components/
│
├── cron/                          ← ⏰ Scheduled Tasks
│   ├── README_REMINDER.md
│   └── send_event_reminders.php
│
├── logs/                          ← 📝 Log Files (gitignored)
│   └── cron_reminder.log
│
├── vendor/                        ← 📦 Dependencies (gitignored)
│
├── .env                           ← 🔐 Environment Config (gitignored)
├── .gitignore                     ← 🚫 Git Ignore Rules
│                                    • Excludes uploads/* but preserves .gitkeep
│                                    • Preserves folder structure for fresh clones
├── composer.json                  ← 📋 PHP Dependencies
└── README                         ← 📖 Original Documentation
```

## 📚 Folder Purposes

### `docs/`
PrAPI endpoint documentation
- System architecture
- Authentication system guide
- Email configuration
- Google OAuth & Calendar setup
- Hosting and deployment guides
- QR code attendance system
- Code comments guide
- Project completion reports
- Diagrams and visualizationss
- System documentation

### `database/migrations/`
SQL migration files for schema changes:
- Version-controlled database changes
- Rollback capabilities
- Migration history

### `scripts/`
Utility scripts for development and maintenance:
- Database migration tools
- Authentication middleware
- Event reminder automation
- Development utilities
- Password hash generators

### `config/`
Application configuration:
- Database connection
- Environment variables
- App settings

### `controllers/`
Business logic layer:
- Request handling
- Validation
- Coordination between models and views

### `models/`
Data access layer:
- Database queries
- Data validation
- Entity representation

### `services/`
ReAnalytics and reporting
- Calendar integration
- Certificate generation
- Email notifications
- QR code generation and validation
- External API integrations

### `views/`
User interface templates:
- Admin dashboard pages
- Panitia management pages
- User pages
- Reusable components

### `public/`
Publicly accessible files:
- Entry point (index.php)
- Static assets (CSS, JS)
- API endpoints
- Generated files
- User uploads (event photos)
  - **uploads/**: Gitignored dynamic content
  - **.gitkeep**: Preserves folder structure in git

### `cron/`
Scheduled background tasks:
- Event reminders
- Automated emails
- Cleanup jobs

## 🚀 Quick Start

### Running Migrations
```bash
php scripts/run_migration.php
php scripts/verify_migration.php
```

### Testing Reminders
```bash
scripts\test_reminder.bat
```

### Development Server
Access via: `http://localhost/EventSite/public/`

## API Endpoints:** See [docs/API_ENDPOINTS.md](docs/API_ENDPOINTS.md)
- **Architecture:** See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- **Authentication:** See [docs/AUTH_FILES_EXPLANATION.md](docs/AUTH_FILES_EXPLANATION.md)
- **Code Comments:** See [docs/CODE_COMMENTS_GUIDE.md](docs/CODE_COMMENTS_GUIDE.md)
- **Email Setup:** See [docs/EMAIL_CONFIGURATION_GUIDE.md](docs/EMAIL_CONFIGURATION_GUIDE.md)
- **Google Calendar:** See [docs/GOOGLE_CALENDAR_API_SETUP.md](docs/GOOGLE_CALENDAR_API_SETUP.md)
- **Google OAuth:** See [docs/GOOGLE_OAUTH_SETUP.md](docs/GOOGLE_OAUTH_SETUP.md)
- **Deployment:** See [docs/HOSTING_DEPLOYMENT_GUIDE.md](docs/HOSTING_DEPLOYMENT_GUIDE.md)
- **Project Report:** See [docs/PROJECT_COMPLETION_REPORT.md](docs/PROJECT_COMPLETION_REPORT.md)
- **QR Attendance:** See [docs/QR_CODE_ATTENDANCE.md](docs/QR_CODE_ATTENDANCE.md)
- **QR Usage:** See [docs/QR_USAGE_GUIDE.md](docs/QR_USAGE_GUIDE
- **Notifications:** See [docs/NOTIFICATION_SYSTEM_COMPLETE.md](docs/NOTIFICATION_SYSTEM_COMPLETE.md)
- **Changelog:** See [docs/CHANGELOG_EVENT_COMPLETION.md](docs/CHANGELOG_EVENT_COMPLETION.md)

## 🔧 Maintenance

### Database Backups
```bash
mysqldump -u root eventsite_db > database/migrations/backup_$(date +%Y%m%d).sql
```

### Logs
```bash
# View cron logs
Get-Content logs\cron_reminder.log -Tail 20

# Clear logs
Remove-Item logs\*.log
```

## 🤝 Contributing

When adding new features:
1. Create migration files i20`database/migrations/`
2. Update documentation in `docs/`
3. Add utility scripts to `scripts/`
4. Follow existing folder structure
5. Update .gitignore for sensitive files

---

**Last Updated:** December 16, 2025
