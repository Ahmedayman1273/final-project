# 📚 Final Project — HTI Student Management System

## 🧠 Project Overview

A Laravel-based backend system for managing students and graduates. It supports:

* User authentication (students, graduates, admin)
* News & Events publishing
* Student service requests (e.g., certificate or enrollment proof)
* Admin dashboard for full control

---

## 🧑‍💻 Project Roles

* **📱 Mobile App (Flutter):** Used by students and graduates
* **🖥️ Admin Panel (Web):** Used by admins only

---

## 🔐 Authentication

* Only admins can create users (students or graduates)
* Students & graduates login with email and password
* No self-registration

---

## 🌍 API Base URL

```
https://YOUR_DOMAIN/api
```

---

## 📦 Key API Endpoints

### 🔑 Auth

* `POST /login` — login with email & password
* `POST /logout` — logout (requires token)

### 👤 Profile

* `GET /profile` — fetch current user profile
* `POST /profile/photo` — upload profile photo
* `DELETE /profile/photo` — delete profile photo

### 📰 News

* `GET /news` — list all news items

### 📅 Events

* `GET /events` — list all events

### 🧾 Student Requests

* `GET /student-requests`
* `POST /student-requests`
* `DELETE /student-requests/{id}`

### 🔔 Notifications

* `GET /notifications`
* `GET /notifications/unread-count`
* `POST /notifications/{id}/read`

---

## 📥 Headers (After Login)

```
Authorization: Bearer <TOKEN>
Accept: application/json
```

---

## 🧪 Test Credentials (Optional)

```
email: test@student.com
password: 123456
```

---

## 🛠 Admin Notes

* Admin can create users one-by-one or by importing an Excel sheet
* Admin can approve/reject student requests
* Admin creates news & events that appear for all users

---

## 🧱 Database Structure (Simplified)

```
users               → students, graduates, and admins
news                → news articles created by admin
events              → events created by admin
student_requests    → requests submitted by users
requests            → master list of request types
notifications       → user notifications
```

---

## 🖼 Image Handling

* All images (profile/news/events) are uploaded to: `storage/app/public`
* URLs returned as:

```
https://YOUR_DOMAIN/storage/{image_path}
```

---

## 📡 HTTP Status Codes

* 200 → OK / Success
* 201 → Created
* 400 / 422 → Validation error
* 401 → Unauthorized
* 403 → Forbidden
* 404 → Not found

---

## 📤 File Uploads (if any)

If an endpoint supports file uploads (e.g., uploading a request attachment), send the request as:

```
Content-Type: multipart/form-data
```

With:

* `file`: actual file
* other form fields (text)

---

## 🔐 User Permissions

* **Student/Graduate**: Can login, view news/events, submit and delete their requests.
* **Admin**: Full access. Manage users, news, events, requests, and approvals.

---

*This file is meant to guide the frontend & Flutter teams on how to interact with the backend APIs.*
