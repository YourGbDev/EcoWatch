# System Architecture

## Overview

EcoWatch is a PHP-based web application that follows a traditional client-server architecture. All processing happens server-side; the browser renders HTML/CSS and communicates with PHP APIs via HTTP requests.

## Technology Stack

- **Frontend**: HTML5, Tailwind CSS (CDN), Lucide Icons, Vanilla JavaScript
- **Backend**: PHP 7.x (XAMPP-compatible)
- **Database**: MySQL with PDO
- **Security**: CSRF tokens, HttpOnly/SameSite session cookies, password_hash()

## High-Level Architecture

```
┌─────────────────────────────────────────┐
│         Browser (HTML + JS)             │
│   Tailwind CSS + Lucide Icons           │
└─────────────────────────────────────────┘
                  │ HTTP/HTTPS
                  ▼
┌─────────────────────────────────────────┐
│     Apache Web Server (XAMPP)           │
│  ┌───────────────────────────────────┐  │
│  │   PHP Pages + API Endpoints       │  │
│  │   - index.html                    │  │
│  │   - dashboard.php                 │  │
│  │   - admin/index.php               │  │
│  │   - api/*.php                     │  │
│  └───────────────────────────────────┘  │
│                                          │
│  ┌───────────────────────────────────┐  │
│  │   Security Layer                  │  │
│  │   - Includes/csrf.php             │  │
│  │   - Session validation            │  │
│  │   - Role checks                   │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
                  │ PDO
                  ▼
┌─────────────────────────────────────────┐
│         MySQL Database                  │
│  ┌─────────────┐ ┌──────────────┐      │
│  │ users        │ │ environmental│      │
│  │             │ │ _reports     │      │
│  └─────────────┘ └──────────────┘      │
│  ┌─────────────┐                       │
│  │ report_status│                      │
│  │ _history     │                       │
│  └─────────────┘                       │
└─────────────────────────────────────────┘
```

## Authentication Flow

1. User clicks "Login" or "Register" on homepage
2. Modal opens with CSRF token loaded from `api/csrf_token.php`
3. Form submits via AJAX to `api/login_action.php` or `api/register.php`
4. Server validates CSRF token, credentials, and email uniqueness
5. On success, server sets session variables: `user_id`, `role`, `user_name`
6. Client receives JSON response with `redirect` URL and navigates
7. Session cookies are set with `HttpOnly` and `SameSite=Strict`

## Report Submission Flow

1. Citizen clicks "Submit Report" from dashboard
2. Form loads with category buttons, barangay dropdown, severity radios
3. User selects category, fills location, adds description, optionally uploads photo
4. Client validates CSRF token and required fields
5. AJAX POST to `api/submit_report.action.php` with FormData
6. Server validates:
   - CSRF token
   - Active session
   - Category whitelist
   - Severity whitelist
   - Photo MIME type and size
7. Server generates unique tracking token (`EW-XXXXXX`)
8. Transactional INSERT into `environmental_reports` + `report_status_history`
9. Response includes tracking token for display

## Tracking Flow

1. Citizen or public user clicks "Track Report" on homepage
2. Modal prompts for tracking token
3. GET request to `api/track_report.php?token=EW-XXXXXX`
4. Server queries `environmental_reports` by token
5. Server fetches status history from `report_status_history`
6. Client renders timeline with color-coded status dots

## Admin Workflow

1. Admin logs in via same modal, redirected to `admin/index.php`
2. PHP gate verifies `$_SESSION['role'] === 'admin'`
3. Dashboard renders 5 summary cards with COUNT queries
4. `api/admin_fetch.php` returns paginated, searchable, filterable report list
5. Admin clicks report row → `openAdminDetail()` modal
6. Modal fetches full report via `api/get_report_detail.php?id=X`
7. Admin reviews details, photo, timeline
8. Admin selects new status, optionally adds notes
9. POST to `api/admin_update.php` with status + notes
10. Server uses `SELECT ... FOR UPDATE` row lock, updates status, inserts history entry

## Security Architecture

- **CSRF**: Every mutating endpoint validates a session-bound token
- **SQL Injection**: 100% PDO prepared statements with bound parameters
- **XSS**: `htmlspecialchars()` on server output; DOM `textContent` escaping on client
- **Authentication**: Session-based with role separation
- **Authorization**: API endpoints enforce admin role; user endpoints enforce ownership
- **File Upload**: MIME validation, size limit, random filename, .htaccess blocking
- **Password Storage**: `password_hash(PASSWORD_DEFAULT)` + `password_verify()`

## Database Design Principles

- **Normalization**: 3NF — no repeating groups, atomic values
- **Referential Integrity**: Foreign keys with appropriate ON DELETE actions
- **Audit Trail**: `report_status_history` captures all state changes
- **Indexing**: Composite and single-column indexes for common query patterns
- **Upgrade Safety**: `setup.sql` includes migration paths for existing databases
