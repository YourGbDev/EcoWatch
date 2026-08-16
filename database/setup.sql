-- EcoWatch Database Setup Script
-- =====================================
-- This script creates the database schema and seed data for demonstration purposes.
-- All users, emails, and reports are fictional demo data.
-- Do not use these credentials in production environments.

-- Drop tables to start fresh
DROP TABLE IF EXISTS report_status_history;
DROP TABLE IF EXISTS environmental_reports;
DROP TABLE IF EXISTS users;

-- Create Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Reports Table
CREATE TABLE environmental_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tracking_token VARCHAR(9) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL,
    severity VARCHAR(20) DEFAULT 'low',
    barangay VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    description TEXT,
    status VARCHAR(20) DEFAULT 'submitted',
    photo_path VARCHAR(255),
    latitude FLOAT NULL,
    longitude FLOAT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_tracking_token ON environmental_reports(tracking_token);
CREATE INDEX idx_reports_user_status ON environmental_reports(user_id, status);
CREATE INDEX idx_reports_severity_created ON environmental_reports(severity, created_at DESC);
CREATE INDEX idx_reports_barangay ON environmental_reports(barangay);

-- Status History Table
CREATE TABLE report_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    old_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES environmental_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_history_report ON report_status_history(report_id, created_at DESC);

-- =====================================
-- DEMO SEED DATA (ALL FICTIONAL)
-- =====================================

-- Seed admin user: admin@ecowatch.test / DemoPass123!
-- Password hash generated for demo purposes only
INSERT INTO users (name, email, password, role) VALUES 
('System Administrator', 'admin@ecowatch.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Demo User', 'demo@ecowatch.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Seed demo reports (fictional locations in Ormoc City area)
INSERT INTO environmental_reports (user_id, tracking_token, category, severity, barangay, address, description, status, photo_path, latitude, longitude, created_at, updated_at) VALUES
(2, 'EW-A1B2C3', 'flooding', 'high', 'Barangay 1', 'Lakeview Heights, Main Road', 'Water accumulation after heavy rain, blocking access to school.', 'verified', NULL, 11.0100, 124.6100, NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 1 HOUR),
(2, 'EW-D4E5F6', 'illegal_dumping', 'low', 'Barangay 5', 'Riverside District, near bridge', 'Small amount of trash discarded near riverbank.', 'submitted', NULL, 11.0200, 124.6200, NOW() - INTERVAL 5 HOUR, NOW() - INTERVAL 5 HOUR),
(2, 'EW-G7H8I9', 'clogged_drainage', 'critical', 'Barangay 8', 'Mountain View Drive, cul-de-sac', 'Major blockage causing street flooding during rain.', 'assigned', NULL, 11.0300, 124.6300, NOW() - INTERVAL 8 HOUR, NOW() - INTERVAL 8 HOUR),
(2, 'EW-J0K1L2', 'uncollected_garbage', 'medium', 'Barangay 12', 'Oak Street residential area', 'Garbage collection missed, bags visible on sidewalk.', 'responding', NULL, 11.0400, 124.6400, NOW() - INTERVAL 12 HOUR, NOW() - INTERVAL 12 HOUR),
(2, 'EW-M3N4O5', 'flooding', 'low', 'Barangay 15', 'Cedar Lane neighborhood', 'Minor puddles after brief rainfall.', 'resolved', NULL, 11.0500, 124.6500, NOW() - INTERVAL 24 HOUR, NOW() - INTERVAL 24 HOUR);

-- Seed status history for demo reports
INSERT INTO report_status_history (report_id, old_status, new_status, changed_by, notes, created_at) VALUES
(1, NULL, 'submitted', 2, NULL, NOW() - INTERVAL 3 HOUR),
(1, 'submitted', 'verified', 1, 'Verified via satellite imagery and on-site inspection.', NOW() - INTERVAL 2 HOUR),
(2, NULL, 'submitted', 2, NULL, NOW() - INTERVAL 6 HOUR),
(3, NULL, 'submitted', 2, NULL, NOW() - INTERVAL 9 HOUR),
(3, 'submitted', 'verified', 1, 'Confirmed blockage location via drone survey.', NOW() - INTERVAL 8 HOUR),
(3, 'verified', 'assigned', 1, 'Assigned to Barangay 8 street cleaning crew.', NOW() - INTERVAL 7 HOUR),
(4, NULL, 'submitted', 2, NULL, NOW() - INTERVAL 13 HOUR),
(4, 'submitted', 'responding', 1, 'Waste collection team dispatched to location.', NOW() - INTERVAL 12 HOUR),
(5, NULL, 'submitted', 2, NULL, NOW() - INTERVAL 1 DAY),
(5, 'submitted', 'verified', 1, 'Minor issue, documentation complete.', NOW() - INTERVAL 25 HOUR),
(5, 'verified', 'resolved', 1, 'Issue resolved, street drainage clear.', NOW() - INTERVAL 24 HOUR);