@echo off
REM Event Reminder Batch Script
REM This script runs the event reminder cron job

cd /d "c:\laragon\www\EventSite"

REM Create logs directory if not exists
if not exist "logs" mkdir logs

REM Run the cron job and log output
c:\xampp\php\php.exe cron\send_event_reminders.php >> logs\cron_reminder.log 2>&1

echo Event reminder job completed. Check logs\cron_reminder.log for details.
