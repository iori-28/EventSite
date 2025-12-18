# 📧 Email Configuration Guide

**Current Status**: Temporary Academic Setup  
**Last Updated**: December 18, 2025

---

## 🎓 Current Setup (Academic/Testing)

### Email Configuration:
- **Display Name**: `EventSite Support`
- **From Email**: Your Gmail account (configured in `.env`)
- **Reply-To**: `support@eventsite.com` (placeholder)
- **Status**: ✅ Working untuk academic submission

### How It Works:
```
Email dikirim dari: Your Gmail
Display nama: "EventSite Support"
Reply diterima di: Your Gmail
```

**Files Configured:**
- `services/NotificationService.php` (line 172-174)
- `.env` file (MAIL_* configurations)

---

## 🚀 Future Production Setup

### Option 1: Google Workspace (RECOMMENDED) 💼

**Cost**: ~$6/month per user  
**Setup Time**: 30 minutes

**Steps:**

#### 1. Buy Domain
```
Domain: eventsite.com (or available variant)
Provider: Niagahoster, Domainesia, Namecheap
Cost: ~Rp 150,000/year
```

#### 2. Sign Up Google Workspace
```
URL: https://workspace.google.com
Plan: Business Starter ($6/user/month)
Features:
- Custom email (@eventsite.com)
- 30GB storage per user
- 24/7 support
- Professional & reliable
```

#### 3. Verify Domain Ownership
```
1. Add TXT record ke DNS domain
2. Wait propagation (15 min - 48 hours)
3. Verify in Google Admin Console
```

#### 4. Setup MX Records
```
| Priority | Mail Server      |
| -------- | ---------------- |
| 1        | smtp.google.com  |
| 5        | smtp2.google.com |
| 5        | smtp3.google.com |
| 10       | smtp4.google.com |
```

#### 5. Create Email Accounts
```
Primary Emails:
- support@eventsite.com (customer support)
- admin@eventsite.com (admin notifications)
- noreply@eventsite.com (automated emails)
```

#### 6. Update .env File
```env
# Google Workspace SMTP
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=support@eventsite.com
MAIL_PASSWORD=[App Password - bukan password Gmail]
MAIL_FROM_NAME=EventSite Support
MAIL_ENCRYPTION=tls
```

#### 7. Generate App Password
```
1. Google Account > Security
2. Enable 2-Step Verification
3. App Passwords > Generate
4. Copy ke .env MAIL_PASSWORD
```

**Pros:**
- ✅ Professional (@eventsite.com)
- ✅ Reliable (99.9% uptime)
- ✅ Spam filter & security
- ✅ Support & backup
- ✅ Easy management

**Cons:**
- ❌ Monthly cost ($6/month)
- ❌ Requires domain purchase

---

### Option 2: cPanel Email (Budget-Friendly) 💰

**Cost**: Included with hosting (~Rp 300k/year)  
**Setup Time**: 15 minutes

**Steps:**

#### 1. Buy Hosting + Domain
```
Provider: Niagahoster, Hostinger, Rumahweb
Package: Basic Hosting + Domain
Cost: ~Rp 300,000/year
Includes:
- Domain .com
- Hosting space
- Unlimited email accounts
- cPanel access
```

#### 2. Create Email in cPanel
```
1. Login ke cPanel
2. Email Accounts
3. Create New Account:
   - Email: support@eventsite.com
   - Password: [Strong password]
   - Storage: Unlimited
```

#### 3. Setup SMTP in cPanel
```
SMTP Server: mail.eventsite.com (or your domain)
Port: 587 (TLS) or 465 (SSL)
Authentication: Required
Username: support@eventsite.com
Password: [Your email password]
```

#### 4. Update .env File
```env
# cPanel SMTP
MAIL_HOST=mail.eventsite.com
MAIL_PORT=587
MAIL_USERNAME=support@eventsite.com
MAIL_PASSWORD=[Your email password]
MAIL_FROM_NAME=EventSite Support
MAIL_ENCRYPTION=tls
```

**Pros:**
- ✅ Cheap (bundled with hosting)
- ✅ Unlimited email accounts
- ✅ Custom domain
- ✅ Easy cPanel management

**Cons:**
- ⚠️ Reliability depends on hosting quality
- ⚠️ Might have delivery issues (spam)
- ⚠️ Limited support

---

### Option 3: SendGrid/Mailgun (Developer-Friendly) 🔧

**Cost**: FREE tier available (up to 100 emails/day)  
**Setup Time**: 20 minutes

**Steps:**

#### 1. Sign Up SendGrid
```
URL: https://sendgrid.com
Plan: Free (100 emails/day)
```

#### 2. Verify Sender Email
```
1. Add sender: support@eventsite.com
2. Click verification link di email
```

#### 3. Generate API Key
```
1. Settings > API Keys
2. Create API Key
3. Copy key (save securely!)
```

#### 4. Update Code to Use SendGrid API
```php
// Install SendGrid library
composer require sendgrid/sendgrid

// In NotificationService.php, add method:
public static function sendEmailViaSendGrid($to, $subject, $message) {
    $email = new \SendGrid\Mail\Mail();
    $email->setFrom("support@eventsite.com", "EventSite Support");
    $email->setSubject($subject);
    $email->addTo($to);
    $email->addContent("text/html", $message);
    
    $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
    
    try {
        $response = $sendgrid->send($email);
        return $response->statusCode() == 202;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}
```

#### 5. Update .env
```env
SENDGRID_API_KEY=[Your API Key]
```

**Pros:**
- ✅ FREE tier (100 emails/day)
- ✅ High deliverability
- ✅ Analytics & tracking
- ✅ API-based (modern)

**Cons:**
- ❌ Requires code changes
- ❌ Daily limit on free tier
- ❌ Need custom domain for production

---

## 🔧 Quick Migration Guide

### When You Want to Switch to Production Email:

**Step 1**: Choose email provider (Google Workspace recommended)

**Step 2**: Update `.env` file:
```env
# Change these values only:
MAIL_HOST=[New SMTP host]
MAIL_PORT=[New port]
MAIL_USERNAME=[New email]
MAIL_PASSWORD=[New password]
```

**Step 3**: Update `NotificationService.php` if needed:
```php
// Line 172 - Update display name if needed
$mail->setFrom(MAIL_USERNAME, 'EventSite Support');

// Line 174 - Update reply-to email
$mail->addReplyTo('support@eventsite.com', 'EventSite Support');
```

**Step 4**: Test email sending:
```php
// Test script
require_once 'services/NotificationService.php';

$test = NotificationService::sendEmail(
    1, 
    'test@youremail.com', 
    'Test Email', 
    '<h1>Test dari EventSite</h1><p>Email working!</p>'
);

echo $test ? "✅ Email sent!" : "❌ Failed";
```

**Step 5**: Update contact info di homepage:
```php
// views/home.php - Line 238 (email card)
<p style="color: var(--primary-color); ...">support@eventsite.com</p>

// Update to your real email
<p style="color: var(--primary-color); ...">your-real-email@eventsite.com</p>
```

---

## 📋 Checklist: Academic → Production

### Current (Academic):
- ✅ Gmail SMTP configured
- ✅ Display name: "EventSite Support"
- ✅ Reply-to: support@eventsite.com (placeholder)
- ✅ Working untuk testing

### Production Ready:
- ⬜ Domain purchased (eventsite.com)
- ⬜ Email hosting setup (Google Workspace/cPanel)
- ⬜ Custom email created (support@eventsite.com)
- ⬜ SMTP credentials updated in .env
- ⬜ Email tested & verified
- ⬜ Contact info updated in homepage
- ⬜ DNS records configured (MX, SPF, DKIM)

---

## 🎯 Recommendation

**For Academic Submission:**
- ✅ Current setup is PERFECT
- ✅ No changes needed
- ✅ Email akan dikirim dengan display name "EventSite Support"

**For Future Production:**
- 🥇 **Best**: Google Workspace ($6/month) - Professional & reliable
- 🥈 **Budget**: cPanel Email (Rp 300k/year) - Cheap but ok
- 🥉 **Developer**: SendGrid (Free 100/day) - API-based, modern

**Time to Upgrade:**
- Saat project live & user real
- Saat butuh professional image
- Saat daily email > 100

---

## 📞 Current Contact Info (Academic)

**Display in Website:**
```
Email: support@eventsite.com
Phone: +62 (021) 1234-5678
GitHub: https://github.com/iori-28/EventSite
```

**Actual Email (Backend):**
```
Sent from: Your Gmail account
Display as: "EventSite Support"
```

**Note:** Penerima email ga tau kalau dikirim dari Gmail pribadi karena display name "EventSite Support" 😊

---

## ⚡ Quick Commands

### Test Email Send:
```bash
# Via browser
http://localhost/EventSite/index.php?page=admin_manage_events
# Approve event → email akan terkirim

# Via script
php -r "require 'services/NotificationService.php'; 
echo NotificationService::sendEmail(1, 'test@email.com', 'Test', '<h1>Hi</h1>') 
? '✅ OK' : '❌ Failed';"
```

### Check .env Config:
```bash
# Windows PowerShell
Get-Content .env | Select-String "MAIL_"

# Output should show:
# MAIL_HOST=smtp.gmail.com
# MAIL_PORT=587
# MAIL_USERNAME=your-gmail@gmail.com
# MAIL_PASSWORD=your-app-password
```

---

## 🎓 Summary

**Current Setup:**
- ✅ Ready for academic submission
- ✅ Email working dengan display "EventSite Support"
- ✅ Professional appearance
- ✅ No cost, no hassle

**Future Upgrade Path:**
1. Finish academic project ✅
2. Graduate & plan production 🎓
3. Buy domain (~Rp 150k/year)
4. Choose email provider (Google/cPanel)
5. Update 2 files (.env + home.php)
6. Test & launch 🚀

**Migration Time:** ~30 minutes when ready! 💪

---

*Documentation by EventSite Team*  
*Ready for Academic Submission ✨*
