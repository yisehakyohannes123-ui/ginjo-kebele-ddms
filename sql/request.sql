-- Create request_types table
CREATE TABLE IF NOT EXISTS request_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL,
    type_category VARCHAR(50) NOT NULL
);

-- Insert request types
INSERT IGNORE INTO request_types (type_name, type_category) VALUES
('New ID Card', 'id'),
('ID Card Renewal', 'id'),
('Birth Certificate', 'certificate'),
('Death Certificate', 'certificate'),
('Marriage Certificate', 'certificate'),
('Divorce Certificate', 'certificate'),
('Resident Registration', 'registration'),
('Clearance Letter', 'clearance'),
('Court Case Filing', 'court');

-- Create requests table
CREATE TABLE IF NOT EXISTS requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    request_number VARCHAR(50) UNIQUE NOT NULL,
    resident_name VARCHAR(100) NOT NULL,
    resident_phone VARCHAR(20) NOT NULL,
    resident_email VARCHAR(100),
    request_type_id INT NOT NULL,
    request_details TEXT,
    status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    assigned_to INT,
    requested_by INT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_date DATETIME,
    notes TEXT,
    FOREIGN KEY (request_type_id) REFERENCES request_types(type_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id),
    FOREIGN KEY (requested_by) REFERENCES users(user_id) -- For community users
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Add email column to customers if not exists
ALTER TABLE customers ADD COLUMN IF NOT EXISTS email VARCHAR(100);
ALTER TABLE customers ADD COLUMN IF NOT EXISTS phone_secondary VARCHAR(20);