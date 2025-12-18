# Auth Files Structure - Penjelasan

## 📁 Struktur File Auth di EventSite

Sekarang ada **2 file auth dengan fungsi BERBEDA**:

---

## 1️⃣ **config/AuthMiddleware.php** (Session Manager)

**Fungsi**: Middleware untuk refresh session dari database setiap page load

**Type**: PHP Class

**Methods**:
```php
Auth::check('admin')     // Check auth + refresh session from DB
Auth::guest()            // Check if user is NOT logged in
Auth::user()             // Get current user from session
Auth::logout()           // Logout current user
```

**Usage Example**:
```php
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('admin');

// Now $_SESSION['user'] has fresh data from DB including profile_picture!
echo $_SESSION['user']['name'];
echo $_SESSION['user']['profile_picture'];
```

**Purpose**: 
- ✅ Refresh session data from database every page load
- ✅ Ensure profile_picture, name, email always up-to-date
- ✅ Security: Detect deleted users, role changes
- ✅ Single source of truth (database)

**Called by**: 
- All protected pages (25+ files in views/)
- Dashboard pages
- Profile pages
- Event management pages

---

## 2️⃣ **public/api/auth.php** (API Endpoint)

**Fungsi**: API endpoint untuk handle login, register, logout requests

**Type**: API Endpoint (receives POST requests)

**Actions**:
```php
POST /api/auth.php
  action=login      → Handle login (email + password)
  action=register   → Handle registration (name + email + password)
  action=logout     → Handle logout
```

**Usage Example**:
```javascript
// Frontend JavaScript (AJAX call)
fetch('api/auth.php', {
    method: 'POST',
    body: new FormData(loginForm)
})
.then(response => response.text())
.then(result => {
    if (result === 'LOGIN_SUCCESS') {
        window.location.href = 'index.php?page=admin_dashboard';
    } else {
        alert('Login failed!');
    }
});
```

**Purpose**:
- ✅ Handle user authentication requests
- ✅ Validate credentials
- ✅ Create/destroy sessions
- ✅ Return status strings (LOGIN_SUCCESS, LOGIN_FAILED, etc)

**Called by**:
- Frontend login form (views/login.php)
- Frontend register form (views/register.php)
- Logout button (via AJAX)

---

## 🔄 Bagaimana Mereka Bekerja Sama

### **Login Flow**:
```
1. User submit login form
   ↓
2. AJAX call → public/api/auth.php (action=login)
   ↓
3. AuthController validates credentials
   ↓
4. If valid: Set $_SESSION['user'] with user data
   ↓
5. Return "LOGIN_SUCCESS"
   ↓
6. Redirect to dashboard
   ↓
7. Dashboard page calls Auth::check() from config/AuthMiddleware.php
   ↓
8. Auth::check() refreshes session from database
   ↓
9. User sees updated profile picture, name, etc.
```

### **Every Page Load**:
```
1. User visits any protected page
   ↓
2. Page calls Auth::check('role')
   ↓
3. Auth::check() queries database:
   SELECT id, name, email, role, profile_picture FROM users WHERE id = ?
   ↓
4. Update $_SESSION['user'] with fresh data
   ↓
5. Page displays updated info (profile_picture, name, etc)
```

---

## ⚡ Kenapa Dipisah?

### **Separation of Concerns**:

**public/api/auth.php** = **Authentication** (Login/Register/Logout)
- Handle user credentials
- Create initial session
- One-time actions

**config/AuthMiddleware.php** = **Session Management** (Refresh & Validate)
- Maintain session freshness
- Refresh data from database
- Every page load

### **Benefits**:
1. ✅ **Clarity**: Each file has single responsibility
2. ✅ **Maintainability**: Easy to update login logic vs session logic
3. ✅ **Security**: Middleware always checks if user still exists in DB
4. ✅ **Performance**: API endpoint doesn't need to load middleware
5. ✅ **Flexibility**: Can add more auth methods (OAuth, SSO) without touching middleware

---

## 📊 File Usage Statistics

**config/AuthMiddleware.php**: Called on **EVERY protected page load** (25+ files)
**public/api/auth.php**: Called only on **login/register/logout actions** (3 API calls)

---

## 🎯 Summary

| File                          | Purpose                      | Type         | Called By       | Frequency       |
| ----------------------------- | ---------------------------- | ------------ | --------------- | --------------- |
| **config/AuthMiddleware.php** | Session refresh & validation | PHP Class    | Protected pages | Every page load |
| **public/api/auth.php**       | Handle auth requests         | API Endpoint | Frontend AJAX   | On user action  |

**Nama berbeda sekarang** = **Tidak confusing lagi!** ✅
