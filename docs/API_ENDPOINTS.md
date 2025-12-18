# 📋 API Routing & Endpoints Documentation

Dokumentasi lengkap semua endpoint API dan routing dalam sistem EventSite.

---

## 🗺️ Routing Structure

EventSite menggunakan **query parameter routing** melalui `index.php`:
```
URL: index.php?page={PAGE_NAME}
```

---

## 🔐 Authentication Endpoints

### **1. Login/Register/Logout**

| Method | Endpoint                        | Description       | Auth Required | Role   |
| ------ | ------------------------------- | ----------------- | ------------- | ------ |
| POST   | `/api/auth.php?action=login`    | User login        | ❌ No          | Public |
| POST   | `/api/auth.php?action=register` | User registration | ❌ No          | Public |
| POST   | `/api/auth.php?action=logout`   | User logout       | ✅ Yes         | Any    |

**Request Body (Login)**:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response**:
- Success: `"LOGIN_SUCCESS"`
- Failed: `"LOGIN_FAILED"`

---

### **2. Google OAuth**

| Method | Endpoint                   | Description              | Auth Required |
| ------ | -------------------------- | ------------------------ | ------------- |
| GET    | `/api/google-login.php`    | Redirect to Google OAuth | ❌ No          |
| GET    | `/api/google-callback.php` | Handle OAuth callback    | ❌ No          |

---

## 📅 Event Management Endpoints

### **3. Event CRUD**

| Method | Endpoint                         | Description                   | Auth Required | Role          |
| ------ | -------------------------------- | ----------------------------- | ------------- | ------------- |
| GET    | `/api/events.php`                | Get all events (with filters) | ❌ No          | Public        |
| GET    | `/api/events.php?id={id}`        | Get event by ID               | ❌ No          | Public        |
| POST   | `/api/events.php?action=create`  | Create new event              | ✅ Yes         | Panitia       |
| POST   | `/api/events.php?action=update`  | Update event                  | ✅ Yes         | Panitia/Admin |
| POST   | `/api/events.php?action=delete`  | Delete event                  | ✅ Yes         | Admin         |
| POST   | `/api/events.php?action=approve` | Approve event                 | ✅ Yes         | Admin         |
| POST   | `/api/events.php?action=reject`  | Reject event                  | ✅ Yes         | Admin         |

**Query Parameters (GET all)**:
- `status`: Filter by status (approved, pending, rejected)
- `category`: Filter by category
- `search`: Search by title/description
- `limit`: Limit results (default: 10)
- `offset`: Pagination offset

---

### **4. Event Participants**

| Method | Endpoint                                | Description           | Auth Required | Role          |
| ------ | --------------------------------------- | --------------------- | ------------- | ------------- |
| POST   | `/api/participants.php?action=register` | Register to event     | ✅ Yes         | User          |
| POST   | `/api/participants.php?action=cancel`   | Cancel registration   | ✅ Yes         | User          |
| GET    | `/api/participants.php?event_id={id}`   | Get participants list | ✅ Yes         | Panitia/Admin |

**Request Body (Register)**:
```json
{
  "event_id": 1,
  "user_id": 5
}
```

**Response**:
- `"REGISTER_SUCCESS"`: Registration successful
- `"ALREADY_REGISTERED"`: User already registered
- `"EVENT_FULL"`: Event capacity reached
- `"EVENT_NOT_APPROVED"`: Event not approved yet

---

## ✅ Attendance & QR Code

### **5. QR Code Check-in**

| Method | Endpoint                            | Description          | Auth Required | Role          |
| ------ | ----------------------------------- | -------------------- | ------------- | ------------- |
| POST   | `/api/qr_checkin.php`               | Check-in via QR code | ✅ Yes         | User          |
| POST   | `/api/attendance.php?action=verify` | Verify QR token      | ✅ Yes         | Panitia/Admin |
| POST   | `/api/attendance.php?action=manual` | Manual check-in      | ✅ Yes         | Panitia/Admin |

**Request Body (QR Check-in)**:
```json
{
  "qr_token": "abc123xyz456"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Check-in berhasil!",
  "participant": {...}
}
```

---

## 📧 Notification Endpoints

### **6. Notifications**

| Method | Endpoint                                      | Description            | Auth Required | Role |
| ------ | --------------------------------------------- | ---------------------- | ------------- | ---- |
| GET    | `/api/notifications.php`                      | Get user notifications | ✅ Yes         | Any  |
| POST   | `/api/notifications.php?action=mark_read`     | Mark as read           | ✅ Yes         | Any  |
| POST   | `/api/notifications.php?action=mark_all_read` | Mark all as read       | ✅ Yes         | Any  |
| GET    | `/api/notifications.php?unread_count=1`       | Get unread count       | ✅ Yes         | Any  |

---

## 📊 Analytics Endpoints

### **7. Analytics Data**

| Method | Endpoint                               | Description               | Auth Required | Role  |
| ------ | -------------------------------------- | ------------------------- | ------------- | ----- |
| GET    | `/api/analytics.php?type=summary`      | Get summary metrics       | ✅ Yes         | Admin |
| GET    | `/api/analytics.php?type=participants` | Participants per event    | ✅ Yes         | Admin |
| GET    | `/api/analytics.php?type=category`     | Category popularity       | ✅ Yes         | Admin |
| GET    | `/api/analytics.php?type=trend`        | Registration trend        | ✅ Yes         | Admin |
| GET    | `/api/analytics.php?type=event_status` | Event status distribution | ✅ Yes         | Admin |

**Response Example**:
```json
{
  "success": true,
  "data": [
    {"title": "Workshop PHP", "participant_count": 45},
    {"title": "Seminar AI", "participant_count": 38}
  ]
}
```

---

### **8. CSV Export**

| Method | Endpoint                                      | Description             | Auth Required | Role  |
| ------ | --------------------------------------------- | ----------------------- | ------------- | ----- |
| GET    | `/api/export_analytics.php?type=participants` | Export participants CSV | ✅ Yes         | Admin |
| GET    | `/api/export_analytics.php?type=category`     | Export category CSV     | ✅ Yes         | Admin |
| GET    | `/api/export_analytics.php?type=full`         | Export full report      | ✅ Yes         | Admin |

---

## 🎓 Certificate Endpoints

### **9. Certificates**

| Method | Endpoint                                          | Description           | Auth Required | Role  |
| ------ | ------------------------------------------------- | --------------------- | ------------- | ----- |
| GET    | `/api/certificates.php?participant_id={id}`       | Get certificate       | ✅ Yes         | User  |
| GET    | `/api/download_certificate.php?id={cert_id}`      | Download PDF          | ✅ Yes         | User  |
| POST   | `/api/admin_event_completion.php?action=complete` | Generate certificates | ✅ Yes         | Admin |

---

## 👥 User Management

### **10. User CRUD**

| Method | Endpoint                            | Description      | Auth Required | Role  |
| ------ | ----------------------------------- | ---------------- | ------------- | ----- |
| GET    | `/api/users.php`                    | Get all users    | ✅ Yes         | Admin |
| GET    | `/api/users.php?id={id}`            | Get user by ID   | ✅ Yes         | Admin |
| POST   | `/api/users.php?action=update`      | Update user      | ✅ Yes         | Admin |
| POST   | `/api/users.php?action=delete`      | Delete user      | ✅ Yes         | Admin |
| POST   | `/api/users.php?action=change_role` | Change user role | ✅ Yes         | Admin |

---

## 📆 Calendar Integration

### **11. Calendar Export**

| Method | Endpoint                                             | Description         | Auth Required |
| ------ | ---------------------------------------------------- | ------------------- | ------------- |
| GET    | `/api/calendar_export.php?event_id={id}&type=google` | Google Calendar URL | ❌ No          |
| GET    | `/api/calendar_export.php?event_id={id}&type=ics`    | Download .ics file  | ❌ No          |

---

## 🌐 Page Routing (Frontend)

### **Public Pages**

| Route                                 | File               | Description    | Auth Required |
| ------------------------------------- | ------------------ | -------------- | ------------- |
| `index.php`                           | `home.php`         | Homepage       | ❌ No          |
| `index.php?page=login`                | `login.php`        | Login page     | ❌ No          |
| `index.php?page=register`             | `register.php`     | Register page  | ❌ No          |
| `index.php?page=events`               | `events.php`       | Events listing | ❌ No          |
| `index.php?page=event-detail&id={id}` | `event-detail.php` | Event detail   | ❌ No          |

---

### **Admin Pages**

| Route                                     | File                           | Auth | Role  |
| ----------------------------------------- | ------------------------------ | ---- | ----- |
| `index.php?page=admin_dashboard`          | `admin_dashboard.php`          | ✅    | Admin |
| `index.php?page=admin_manage_events`      | `admin_manage_events.php`      | ✅    | Admin |
| `index.php?page=admin_edit_event&id={id}` | `admin_edit_event.php`         | ✅    | Admin |
| `index.php?page=adm_apprv_event`          | `adm_apprv_event.php`          | ✅    | Admin |
| `index.php?page=admin_manage_users`       | `admin_manage_users.php`       | ✅    | Admin |
| `index.php?page=admin_analytics`          | `admin_analytics.php`          | ✅    | Admin |
| `index.php?page=admin_reports`            | `admin_reports.php`            | ✅    | Admin |
| `index.php?page=admin_confirm_attendance` | `admin_confirm_attendance.php` | ✅    | Admin |
| `index.php?page=admin_event_completion`   | `admin_event_completion.php`   | ✅    | Admin |
| `index.php?page=admin_notifications`      | `admin_notifications.php`      | ✅    | Admin |
| `index.php?page=admin_profile`            | `admin_profile.php`            | ✅    | Admin |

---

### **Panitia Pages**

| Route                                               | File                        | Auth | Role    |
| --------------------------------------------------- | --------------------------- | ---- | ------- |
| `index.php?page=panitia_dashboard`                  | `panitia_dashboard.php`     | ✅    | Panitia |
| `index.php?page=panitia_create_event`               | `panitia_create_event.php`  | ✅    | Panitia |
| `index.php?page=panitia_my_events`                  | `panitia_my_events.php`     | ✅    | Panitia |
| `index.php?page=panitia_edit_event&id={id}`         | `panitia_edit_event.php`    | ✅    | Panitia |
| `index.php?page=panitia_participants&event_id={id}` | `panitia_participants.php`  | ✅    | Panitia |
| `index.php?page=panitia_notifications`              | `panitia_notifications.php` | ✅    | Panitia |
| `index.php?page=panitia_profile`                    | `panitia_profile.php`       | ✅    | Panitia |

---

### **User Pages**

| Route                               | File                     | Auth | Role |
| ----------------------------------- | ------------------------ | ---- | ---- |
| `index.php?page=user_dashboard`     | `user_dashboard.php`     | ✅    | User |
| `index.php?page=user_browse_events` | `user_browse_events.php` | ✅    | User |
| `index.php?page=user_my_events`     | `user_my_events.php`     | ✅    | User |
| `index.php?page=user_certificates`  | `user_certificates.php`  | ✅    | User |
| `index.php?page=user_notifications` | `user_notifications.php` | ✅    | User |
| `index.php?page=user_profile`       | `user_profile.php`       | ✅    | User |

---

## 🔒 Authentication & Authorization

### Auth Middleware
Semua protected pages menggunakan `AuthMiddleware.php`:
```php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';
Auth::check('admin'); // Check role
```

### Session Management
- Session key: `$_SESSION['user']`
- Session data: id, name, email, role, profile_picture, oauth_provider, google_id
- Session refresh: Otomatis via AuthMiddleware setiap page load

---

## 📝 HTTP Status Codes

| Code | Meaning               | Usage                                      |
| ---- | --------------------- | ------------------------------------------ |
| 200  | OK                    | Successful request                         |
| 201  | Created               | Resource created successfully              |
| 400  | Bad Request           | Invalid request parameters                 |
| 401  | Unauthorized          | Not authenticated                          |
| 403  | Forbidden             | Insufficient permissions                   |
| 404  | Not Found             | Resource not found                         |
| 409  | Conflict              | Duplicate entry (e.g., already registered) |
| 500  | Internal Server Error | Server-side error                          |

---

## 🧪 Testing Examples

### cURL Examples:

**Login**:
```bash
curl -X POST http://localhost/EventSite/public/api/auth.php \
  -d "action=login&email=admin@example.com&password=admin123"
```

**Get Events**:
```bash
curl http://localhost/EventSite/public/api/events.php?status=approved&limit=5
```

**Register to Event**:
```bash
curl -X POST http://localhost/EventSite/public/api/participants.php \
  -d "action=register&event_id=1" \
  --cookie "PHPSESSID=abc123"
```

---

## 📚 Notes

- All API responses di encode sebagai JSON (kecuali auth.php yang return text)
- File uploads menggunakan multipart/form-data
- Date format: MySQL DATETIME (YYYY-MM-DD HH:mm:ss)
- Timezone: Asia/Jakarta (UTC+7)

---

*Last Updated: December 18, 2025*
*EventSite API Documentation v1.0*
