@echo off
echo ========================================
echo Testing Event Reminder System
echo ========================================
echo.

cd /d "c:\laragon\www\EventSite"

echo Creating logs directory if needed...
if not exist "logs" mkdir logs

echo.
echo Running reminder cron job...
echo ----------------------------------------
c:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe cron\send_event_reminders.php

echo.
echo ========================================
echo Test Complete!
echo ========================================
echo.
echo Check the output above for:
echo  - Number of events found
echo  - Number of participants
echo  - Email delivery status
echo.
echo Also check:
echo  1. logs\cron_reminder.log
echo  2. Database notifications table
echo.
pause
