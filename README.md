# 🔐 PHP User Authentication System

A full-featured user authentication system built with **PHP**, **MySQL (PDO)**, and **Google OAuth 2.0** — designed to run on a local **XAMPP** stack.

---

## ✨ Features

- **User Registration** — full name, email, username, and password with server-side validation
- **User Login** — supports login via email *or* username
- **Google OAuth 2.0** — "Sign in with Google" one-click authentication
- **Session Management** — secure PHP sessions to track logged-in users
- **Protected Dashboard** — only accessible to authenticated users
- **Password Hashing** — passwords are hashed with `password_hash()` (bcrypt) and never stored in plain text
- **CSRF Protection** — state token validation on the Google OAuth flow
- **SQL Injection Prevention** — all database queries use PDO prepared statements

---

## 📁 Project Structure

```
form/
├── login.php              → Login page (email/username + password, Google OAuth)
├── register.php           → Registration page
├── dashboard.php          → Protected user dashboard
├── logout.php             → Destroys session and redirects to login
├── db.php                 → PDO database connection
├── google-oauth.php       → Google OAuth 2.0 helper functions
├── google-callback.php    → Google OAuth 2.0 callback handler
├── style.css              → Application styles
└── .env                   → Google OAuth credentials (not committed to git)
```

---

## ⚙️ Requirements

| Requirement | Version |
|---|---|
| XAMPP (Apache + MySQL) | 7.x / 8.x |
| PHP | 7.4+ |
| MySQL | 5.7+ |
| PHP extensions | `pdo_mysql`, `curl`, `openssl` |

---

## 🚀 Setup & Installation

### 1. Clone / Copy the Project

Place the `form/` folder inside your XAMPP web root:

```
C:\xampp\htdocs\form\
```

### 2. Create the Database

Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and run the following SQL:

```sql
CREATE DATABASE IF NOT EXISTS user_auth CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE user_auth;

CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    username    VARCHAR(50)   NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL DEFAULT '',
    google_id   VARCHAR(50)   DEFAULT NULL,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);
```

### 3. Configure the Database Connection

Edit `db.php` if your MySQL credentials differ from the XAMPP defaults:

```php
$host     = "localhost";
$db_name  = "user_auth";
$username = "root";
$password = "";       // Default XAMPP password is empty
```

### 4. Set Up Google OAuth (Optional)

> Skip this step if you only need email/password authentication.

1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project and navigate to **APIs & Services → Credentials**.
3. Click **Create Credentials → OAuth 2.0 Client ID**.
4. Set the **Authorized redirect URI** to:
   ```
   http://localhost/form/google-callback.php
   ```
5. Copy your **Client ID** and **Client Secret**.
6. Create a `.env` file in the project root:

```env
GOOGLE_CLIENT_ID=your-client-id-here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret-here
GOOGLE_REDIRECT_URI=http://localhost/form/google-callback.php
```

### 5. Start XAMPP & Visit the App

1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Open your browser and navigate to:

```
http://localhost/form/login.php
```

---

## 🗺️ Page Flow

```
Register (register.php)
    ↓  (on success)
Login (login.php)  ←──── Google OAuth ────→ google-callback.php
    ↓  (on success)
Dashboard (dashboard.php)
    ↓  (logout)
Login (login.php)
```

---

## 🔒 Security Notes

| Concern | How It is Handled |
|---|---|
| Password storage | `password_hash()` with `PASSWORD_DEFAULT` (bcrypt) |
| SQL injection | PDO prepared statements with named placeholders |
| XSS output | `htmlspecialchars()` on all dynamic output |
| OAuth CSRF | Random `state` token stored in session and verified on callback |
| Session hijacking | `session_start()` with server-side session storage |

---

## 📄 File Reference

| File | Purpose |
|---|---|
| `login.php` | Handles login form and initiates Google OAuth |
| `register.php` | Validates and inserts new user records |
| `dashboard.php` | Displays user info; access-controlled by session |
| `logout.php` | Destroys session and redirects to login |
| `db.php` | Creates a shared `$pdo` database connection |
| `google-oauth.php` | Loads `.env`, builds OAuth URL, exchanges tokens |
| `google-callback.php` | Handles Google redirect, creates/logs in user |
| `style.css` | Shared stylesheet for all pages |

---

## 🛠️ Development Notes

- The `.env` file is excluded from version control via `.gitignore` to keep secrets safe.
- The `google_id` column in the `users` table allows accounts created via Google OAuth to be matched on future logins without a password.
- Google OAuth users are inserted with an empty `password` field (`''`), so they cannot log in via the password form.
