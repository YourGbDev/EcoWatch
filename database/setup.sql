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

-- Seed admin user: admin@ecowatch.gov / admin123
INSERT INTO users (name, email, password, role) VALUES ('System Administrator', 'admin@ecowatch.gov', '$2y$10$1BF.aU7nk0xGQAZfhiZFwegyj5FB5vIPlnKTnQGSWklP4CPjZVht.', 'admin');

-- Seed demo citizen user: demo@ecowatch.gov / demo1234
INSERT INTO users (name, email, password, role) VALUES ('Maria Santos', 'demo@ecowatch.gov', '$2y$10$DRPjc25.M.drRnVVV6Kf9eTS/PIHmayqFM0zUUfCEbAHLKcZpH4ci', 'user');

-- Seed demo reports (with sample lat/lng for Ormoc City area)
INSERT INTO environmental_reports (user_id, tracking_token, category, severity, barangay, address, description, status, latitude, longitude, created_at, updated_at) VALUES
(2, 'EW-A1B2C3', 'flooding', 'critical', 'Tambulilid', '123 Main St near Plaza', 'Flash flooding blocking main road, water level rising rapidly.', 'submitted', 11.0064, 124.6072, NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 2 HOUR),
(2, 'EW-D4E5F6', 'illegal_dumping', 'high', 'Biliboy', '45 Oak Avenue', 'Large pile of construction waste blocking sidewalk.', 'verified', 11.0150, 124.6150, NOW() - INTERVAL 5 HOUR, NOW() - INTERVAL 1 HOUR),
(2, 'EW-G7H8I9', 'clogged_drainage', 'high', 'Dolores', '78 Pine Street', 'Drainage blocked by plastic waste, water pooling on road.', 'assigned', 11.0200, 124.6200, NOW() - INTERVAL 8 HOUR, NOW() - INTERVAL 3 HOUR),
(2, 'EW-J0K1L2', 'uncollected_garbage', 'low', 'Curva', '12 Maple Road', 'Garbage not collected for 3 days, bags piling up.', 'responding', 10.9900, 124.5900, NOW() - INTERVAL 12 HOUR, NOW() - INTERVAL 4 HOUR),
(2, 'EW-M3N4O5', 'flooding', 'low', 'Tongonan', '56 Cedar Lane', 'Minor flooding in low-lying area after heavy rain.', 'resolved', 11.0300, 124.6300, NOW() - INTERVAL 24 HOUR, NOW() - INTERVAL 6 HOUR),
(2, 'EW-P6Q7R8', 'drug_concern', 'high', 'Labrador', '34 Elm Street', 'Suspected drug activity near community park, anonymous report.', 'submitted', 10.9800, 124.5800, NOW() - INTERVAL 3 HOUR, NOW() - INTERVAL 3 HOUR);

-- Seed status history
INSERT INTO report_status_history (report_id, old_status, new_status, changed_by, notes, created_at) VALUES
(1, NULL, 'submitted', 2, NULL, NOW() - INTERVAL 2 HOUR),
(2, NULL, 'submitted', 2, 'Photo evidence uploaded', NOW() - INTERVAL 5 HOUR),
(2, 'submitted', 'verified', 1, 'Verified via satellite imagery', NOW() - INTERVAL 1 HOUR),
(3, NULL, 'submitted', 2, NULL, NOW() - INTERVAL 8 HOUR),
(3, 'submitted', 'assigned', 1, 'Assigned to Dolores clean-up crew', NOW() - INTERVAL 3 HOUR),
(4, NULL, 'submitted', 2, NULL, NOW() - INTERVAL 12 HOUR),
(4, 'submitted', 'responding', 1, 'Dispatch team en route', NOW() - INTERVAL 4 HOUR),
(5, NULL, 'submitted', 2, NULL, NOW() - INTERVAL 24 HOUR),
(5, 'submitted', 'verified', 1, 'Confirmed by barangay captain', NOW() - INTERVAL 12 HOUR),
(5, 'verified', 'responding', 1, 'Clean-up crew dispatched', NOW() - INTERVAL 8 HOUR),
(5, 'responding', 'resolved', 1, 'Issue fully resolved and verified', NOW() - INTERVAL 6 HOUR),
(6, NULL, 'submitted', 2, 'Anonymous community report', NOW() - INTERVAL 3 HOUR);
