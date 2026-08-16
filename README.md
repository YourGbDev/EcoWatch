# EcoWatch 🌿

**Environmental Incident Monitoring & Response Platform**

EcoWatch is a web-based environmental incident monitoring system designed for municipalities. It provides citizens a digital channel to report environmental concerns (Stagnant Water, Illegal Dumping, Clogged Drainage, Uncollected Public Garbage, Drug-related Community Concern) while giving local authorities a structured operations dashboard to receive, triage, coordinate, and resolve those reports.

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
- Submit environmental incident reports across 5 categories: Stagnant Water, Illegal Dumping, Clogged Drainage, Uncollected Public Garbage, and Drug-related Community Concern
- Track report status publicly using a unique tracking token (e.g. `EW-XXXXXX`) — no login required
- View personal report history with full status timeline
- Update profile and change password

### Administrative Features
- Server-side authentication gate
- Operations dashboard with real-time report statistics
- Paginated report queue with search and filtering
- Full report detail review with status lifecycle management
- Complete audit trail of all status changes
- Geospatial data for identifying problem areas

---

## Status Lifecycle

Every submitted report moves through a structured lifecycle:

```
submitted → verified → assigned → responding → resolved
```

Each transition is timestamped and logged for full accountability.

---

## Quick Start

### Prerequisites
- XAMPP (Apache + MySQL)
- PHP 7.x or 8.x
- MySQL 5.7+

### Installation

1. Clone or copy the project to your XAMPP `htdocs` directory
2. Start Apache and MySQL in XAMPP Control Panel
3. Create database `smart_flood_waste` in phpMyAdmin
4. Import `database/setup.sql` into the database
5. Navigate to the application URL

### Demo Credentials

The database seed data includes demo accounts for testing:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ecowatch.test | DemoPass123! |
| User | demo@ecowatch.test | DemoPass123! |

⚠️ **Important**: These are demo accounts only. Always change credentials for production deployments.

---

## Configuration

### Environment Variables

Copy the example configuration and update with your production values:

```bash
cp .env.example .env
```

Key configuration options:
- Database connection settings
- Session secret keys
- Application base URL

---

## File Structure

```
├── admin/              # Administrative interface
├── api/                # REST API endpoints
├── config/             # Configuration files
├── database/           # Database schema and seed data
├── docs/               # Documentation
├── includes/           # Shared components
├── index.html          # Homepage
├── db_connection.php   # Database connection
├── dashboard.php       # User dashboard
├── submit_report.php   # Report submission
├── my_reports.php      # User's report history
├── profile.php         # Profile management
└── login.php           # Authentication redirect
```

---

## Security

- **Authentication**: Session-based with `password_hash()` and `password_verify()`
- **CSRF Protection**: All state-changing endpoints validate session-bound tokens
- **SQL Injection Prevention**: 100% PDO prepared statements
- **XSS Prevention**: Server-side `htmlspecialchars()` and client-side text escaping
- **Session Security**: `HttpOnly` and `SameSite=Strict` cookie flags
- **File Upload Security**: MIME validation, size limits, random filenames
- **Authorization**: Role-based access control for admin functions

---

## Documentation

- `docs/ER_Diagram.md` — Database schema and relationships
- `docs/Architecture.md` — System design and data flows
- `docs/Test_Cases.md` — QA test cases

---

## Configuration Files

The following files are excluded from the repository (see `.gitignore`):

- `.env` — Local environment configuration (not shared)
- `.env.example` — Template for environment configuration
- `.env.production` — Production environment template
- `Uploads/` — User-uploaded media files
- `.claude/` — Local AI development settings

---

## License

This is an open-source project for educational and community purposes.

---

## Disclaimer

This is a demonstration/capstone project. The following apply:

- All demo credentials are for testing only
- Demo data is fictional and for demonstration purposes
- Security features are implemented as educational examples
- Not recommended for production use without additional hardening
- User data and credentials should be properly managed in production deployments