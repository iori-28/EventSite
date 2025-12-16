# Utility Scripts

This folder contains utility scripts for development, maintenance, and automation tasks.

## 🔧 Available Scripts

### Database Migration Tools
- **run_migration.php** - Execute database migrations
  - Runs SQL files from `database/migrations/`
  - Handles errors and rollback
  - Logs execution progress
  
  ```bash
  php scripts/run_migration.php
  ```

- **verify_migration.php** - Verify migration success
  - Checks table structure
  - Validates foreign keys
  - Displays migration status
  
  ```bash
  php scripts/verify_migration.php
  ```

### Event Reminder System
- **run_event_reminders.bat** - Execute event reminder cron job
  - Sends reminder emails 24 hours before events
  - Logs to `logs/cron_reminder.log`
  - For production use (Task Scheduler)
  
  ```bash
  scripts\run_event_reminders.bat
  ```

- **test_reminder.bat** - Test reminder system
  - Interactive testing mode
  - Shows output in console
  - For development/debugging
  
  ```bash
  scripts\test_reminder.bat
  ```

### Development Utilities
- **pashash.php** - Generate password hashes
  - Creates bcrypt hashes for passwords
  - Used for manual user creation
  
  ```bash
  php scripts/pashash.php
  ```

## 📝 Usage Examples

### Running Migrations
```bash
# Step 1: Review migration file
cat database/migrations/migration_event_completion_workflow.sql

# Step 2: Run migration
php scripts/run_migration.php

# Step 3: Verify success
php scripts/verify_migration.php
```

### Testing Reminders
```bash
# Test reminder system
scripts\test_reminder.bat

# Check logs
Get-Content logs\cron_reminder.log -Tail 20
```

### Generate Password Hash
```php
// Edit scripts/pashash.php
$password = "your_password_here";

// Run script
php scripts/pashash.php

// Copy hash to database
```

## ⚠️ Important Notes

### Security
- **Never commit** pashash.php with real passwords
- Keep sensitive scripts in .gitignore
- Use environment variables for production

### Paths
Scripts assume these paths:
- Root: `c:\laragon\www\EventSite`
- PHP: `c:\laragon\bin\php\*\php.exe`
- Logs: `logs/`

Update paths in batch files if your setup differs.

### Windows Task Scheduler Setup
For automated reminders:
1. Open Task Scheduler
2. Create Basic Task
3. Trigger: Daily at specific time
4. Action: Start Program
5. Program: `c:\laragon\www\EventSite\scripts\run_event_reminders.bat`

## 🔄 Script Maintenance

When updating scripts:
- [ ] Update paths if project moves
- [ ] Test in development first
- [ ] Update documentation
- [ ] Check logs for errors
- [ ] Verify cron jobs work

---

**Last Updated:** December 16, 2025
