# EcoWatch 🌿

### A Web-Based Environmental Incident Monitoring and Response Coordination Platform for Ormoc City

**Capstone Project — Western Leyte College of Ormoc | BSIT | A.Y. 2026–2027**
**Submitted by:** Gilbert M. Bulado Jr.

EcoWatch is a web-based environmental incident monitoring system designed specifically for Ormoc City. It gives citizens a formal digital channel to report environmental concerns — flooding, illegal dumping, clogged drainage, uncollected garbage, and drug-related community issues — while giving the **City Disaster Risk Reduction and Management Office (CDRRMO)** a structured operations dashboard to receive, triage, coordinate, and resolve those reports.

Before EcoWatch, residents relied on informal channels like Facebook posts and group chats to report incidents. Reports were lost, ignored, or left unresolved with no accountability. EcoWatch bridges that gap — making local environmental governance more transparent, accountable, and data-driven.

---

## Tech Stack

- **Backend**: PHP 7.x / 8.x (XAMPP-compatible)
- **Database**: MySQL with PDO
- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript, Lucide Icons
- **Security**: CSRF protection, HttpOnly/SameSite session cookies, `password_hash()`

---

## Features

### Citizen Features
- User registration and authentication
- Submit environmental incident reports with photo evidence and exact location details
- Track report status publicly using a unique tracking token (e.g. `EW-XXXXXX`) — no login required
- View personal report history with full status timeline
- Anonymous reporting channel for drug-related community concerns
- Update profile and change password

### CDRRMO Admin Features
- Server-side authentication gate
- Operations dashboard with real-time report statistics
- Paginated report queue with search and filtering by status, barangay, severity, and category
- Full report detail review with status lifecycle management and admin notes
- Complete audit trail — every status change is logged with timestamps and CDRRMO notes
- Barangay-level data for identifying recurring problem areas and resource allocation

---

## Status Lifecycle

Every submitted report moves through a structured lifecycle managed by the CDRRMO:

```
submitted → verified → assigned → responding → resolved
```

Each transition is timestamped and logged with CDRRMO notes for full accountability.

---

## Quick Start

### Prerequisites
- XAMPP (Apache + MySQL)
- PHP 7.x or 8.x
- MySQL 5.7+

### Installation

1. Clone or copy the project to `C:\xampp\htdocs\ecowatch\`
2. Start Apache and MySQL in XAMPP Control Panel
3. Create database `smart_flood_waste` in phpMyAdmin
4. Import `database/setup.sql` into the database
5. Navigate to `http://localhost/ecowatch`

### Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| CDRRMO Admin | `admin@ecowatch.gov` | `admin123` |
| Citizen | `demo@ecowatch.gov` | `demo1234` |

---

## File Structure

```
├── admin/
│   └── index.php                  # CDRRMO admin control panel (PHP-gated)
├── api/
│   ├── admin_fetch.php            # Admin report list API
│   ├── admin_update.php           # Admin status update API
│   ├── change_password.php        # Password change API
│   ├── csrf_token.php             # Public CSRF token endpoint
│   ├── get_report_detail.php      # Report details API
│   ├── login_action.php           # Login API
│   ├── logout.php                 # Logout API
│   ├── register.php               # Registration API
│   ├── submit_report.action.php   # Report submission API
│   ├── track_report.php           # Public tracking token API
│   └── update_profile.php         # Profile update API
├── database/
│   └── setup.sql                  # Schema + seed data
├── docs/
│   ├── ER_Diagram.md              # Database schema documentation
│   ├── Architecture.md            # System architecture
│   └── Test_Cases.md              # QA test cases
├── Includes/
│   └── csrf.php                   # Session security + CSRF helpers
├── index.html                     # Homepage with modals
├── db_connection.php              # PDO database connection
├── dashboard.php                  # Citizen dashboard
├── submit_report.php              # Report submission page
├── my_reports.php                 # Citizen report list
├── profile.php                    # Profile settings
└── login.php                      # Redirect to index.html
```

---

## Security

- **Authentication**: Session-based with `password_hash()` and `password_verify()`
- **CSRF Protection**: All state-changing endpoints validate session-bound tokens
- **SQL Injection Prevention**: 100% PDO prepared statements
- **XSS Prevention**: Server-side `htmlspecialchars()` and client-side text escaping
- **Session Security**: `HttpOnly` and `SameSite=Strict` cookie flags
- **File Upload Security**: MIME validation, 5MB limit, random filenames

---

## Documentation

- `docs/ER_Diagram.md` — Database schema and relationships
- `docs/Architecture.md` — System design and data flows
- `docs/Test_Cases.md` — QA test results

---

## License

Capstone Project — Bachelor of Science in Information Technology
Western Leyte College of Ormoc | A.Y. 2026–2027