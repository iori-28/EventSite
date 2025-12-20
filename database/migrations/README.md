# Database Migrations

This folder contains SQL migration files for database schema changes.

## 📋 Migration Files

### Active Migrations
- **migration_event_completion_workflow.sql** - Event completion workflow with admin approval
  - Adds `waiting_completion` status
  - Adds tracking fields: `completed_by`, `completed_at`, `approved_by`, `approved_at`
  - Adds foreign key constraints
  - Date: 2025-12-15

- **migration_add_email_reminders_toggle.sql** - Email reminders user preference
  - Adds `email_reminders_enabled` column to users table
  - Adds index for query performance
  - Default: enabled (1)
  - Date: 2025-12-20

- **migration_completed_status.sql** - Basic completed status for events
  - Adds `completed` status to events table
  - Date: Earlier version (superseded by workflow migration)

### Database Dumps
- **dump_db.sql** - Full database backup/dump
  - ⚠️ **DO NOT commit to Git** (contains production data)
  - Used for local backup and restoration
  - Update .gitignore to exclude this file

## 🚀 How to Run Migrations

### Using PHP Script (Recommended)
```bash
cd c:\laragon\www\EventSite
php scripts/run_migration.php
```

### Manual MySQL
```bash
mysql -u root eventsite_db < database/migrations/migration_event_completion_workflow.sql
```

### Verify Migration
```bash
php scripts/verify_migration.php
```

## ✅ Migration Checklist

Before running migrations:
- [ ] Backup database: `php scripts/backup_database.php`
- [ ] Review SQL file for conflicts
- [ ] Check current database schema
- [ ] Test in development first

After running migrations:
- [ ] Verify tables updated: `DESCRIBE events;`
- [ ] Check foreign keys: `SHOW CREATE TABLE events;`
- [ ] Test application features
- [ ] Update documentation if needed

## 🔄 Migration Order

Run migrations in this order:
1. `migration_completed_status.sql` (if starting fresh)
2. `migration_event_completion_workflow.sql` (latest)

## ⚠️ Important Notes

- Always backup database before running migrations
- Migrations are **one-way** - create rollback scripts if needed
- Test migrations in development environment first
- Some migrations may take time on large tables
- Check for existing data conflicts before running

---

**Last Updated:** December 16, 2025
