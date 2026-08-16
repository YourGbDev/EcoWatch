# Entity-Relationship Diagram

## Users Table

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique user identifier |
| name | VARCHAR(100) | NOT NULL | Full name of the user |
| email | VARCHAR(100) | NOT NULL, UNIQUE | Login email address |
| password | VARCHAR(255) | NOT NULL | Bcrypt password hash |
| role | ENUM('user', 'admin') | DEFAULT 'user' | Access level |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Registration timestamp |

## Environmental Reports Table

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique report identifier |
| user_id | INT | NOT NULL, FK → users(id) | Citizen who submitted |
| tracking_token | VARCHAR(9) | NOT NULL, UNIQUE | Public tracking reference (EW-XXXXXX) |
| category | VARCHAR(50) | NOT NULL | flooding, illegal_dumping, clogged_drainage, uncollected_garbage, drug_concern |
| severity | VARCHAR(20) | DEFAULT 'low' | low, high, critical |
| barangay | VARCHAR(100) | NOT NULL | Barangay classification |
| address | TEXT | NOT NULL | Exact location description |
| description | TEXT | NULL | Additional details |
| status | VARCHAR(20) | DEFAULT 'submitted' | Lifecycle state |
| photo_path | VARCHAR(255) | NULL | Uploaded evidence file path |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Submission time |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last modification time |

## Report Status History Table

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique history entry |
| report_id | INT | NOT NULL, FK → environmental_reports(id) ON DELETE CASCADE | Related report |
| old_status | VARCHAR(20) | NULL | Previous status |
| new_status | VARCHAR(20) | NOT NULL | Updated status |
| changed_by | INT | NULL, FK → users(id) ON DELETE SET NULL | Admin who updated |
| notes | TEXT | NULL | Admin annotations |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Change timestamp |

## Relationships

```
users (1) ──────── (N) environmental_reports
    │                     │
    │                     │ (1)
    │                     │
    └────────── (N) ──────┘
              report_status_history
```

### One-to-Many: Users → Environmental Reports
- One citizen can submit many reports
- Foreign key: `environmental_reports.user_id` references `users.id`
- Delete behavior: If user is deleted, reports remain (NOT NULL FK prevents user deletion)

### One-to-Many: Environmental Reports → Report Status History
- One report can have many status changes
- Foreign key: `report_status_history.report_id` references `environmental_reports.id`
- Delete behavior: If report is deleted, history is also deleted (CASCADE)

### Optional One-to-Many: Users → Report Status History
- One admin can make many status changes
- Foreign key: `report_status_history.changed_by` references `users.id`
- Delete behavior: If admin is deleted, history entry remains with NULL changed_by (SET NULL)
