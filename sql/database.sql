-- Create database
CREATE DATABASE IF NOT EXISTS ginjo_kebele_ddms;
USE ginjo_kebele_ddms;

-- Create roles table
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
);

-- Insert roles
INSERT INTO roles (role_name) VALUES 
('System_Administrator'),
('Kebele_Manager'),
('Record_Officer');

-- Create users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    gender ENUM('Male', 'Female', 'Other'),
    birth_date DATE,
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Create user_contacts table
CREATE TABLE user_contacts (
    contact_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    phone_number VARCHAR(20),
    contact_type VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Create kebeles table
CREATE TABLE kebeles (
    kebele_id INT PRIMARY KEY AUTO_INCREMENT,
    kebele_name VARCHAR(100) NOT NULL,
    zone VARCHAR(100)
);

-- Insert default kebele
INSERT INTO kebeles (kebele_name, zone) VALUES ('Ginjo Guduru', 'Jimma');

-- Create jobs table
CREATE TABLE jobs (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    job_title VARCHAR(100) NOT NULL
);

-- Insert sample jobs
INSERT INTO jobs (job_title) VALUES 
('Farmer'), 
('Teacher'), 
('Merchant'), 
('Student'), 
('Government Employee'),
('Private Employee'),
('Other');

-- Create houses table
CREATE TABLE houses (
    house_id INT PRIMARY KEY AUTO_INCREMENT,
    house_number VARCHAR(50) NOT NULL,
    kebele_id INT,
    zone VARCHAR(100),
    FOREIGN KEY (kebele_id) REFERENCES kebeles(kebele_id)
);

-- Create customers (residents) table
CREATE TABLE customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50),
    gender ENUM('Male', 'Female', 'Other'),
    birth_date DATE,
    house_id INT,
    job_id INT,
    registration_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (house_id) REFERENCES houses(house_id),
    FOREIGN KEY (job_id) REFERENCES jobs(job_id)
);

-- Create customer contacts table
CREATE TABLE customer_contacts (
    contact_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    phone_number VARCHAR(20),
    is_primary BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);

-- Create ids table
CREATE TABLE ids (
    id_number INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    issued_date DATE NOT NULL,
    expiry_date DATE,
    issued_by INT,
    is_renewed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (issued_by) REFERENCES users(user_id)
);

-- Create renewed_ids table
CREATE TABLE renewed_ids (
    renew_id INT PRIMARY KEY AUTO_INCREMENT,
    original_id_number INT,
    customer_id INT,
    renew_date DATE NOT NULL,
    renewed_by INT,
    FOREIGN KEY (original_id_number) REFERENCES ids(id_number),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (renewed_by) REFERENCES users(user_id)
);

-- Create certificate_types table
CREATE TABLE certificate_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL
);

-- Insert certificate types
INSERT INTO certificate_types (type_name) VALUES 
('Birth Certificate'),
('Death Certificate'),
('Marriage Certificate'),
('Divorce Certificate');

-- Create certificates table
CREATE TABLE certificates (
    certificate_id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    type_id INT,
    issue_date DATE NOT NULL,
    issued_by INT,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (type_id) REFERENCES certificate_types(type_id),
    FOREIGN KEY (issued_by) REFERENCES users(user_id)
);

-- Create social_court_cases table
CREATE TABLE social_court_cases (
    case_id INT PRIMARY KEY AUTO_INCREMENT,
    case_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    case_type VARCHAR(100),
    description TEXT,
    verdict TEXT,
    hearing_date DATE,
    decision_date DATE,
    recorded_by INT,
    status ENUM('Pending', 'Decided', 'Closed') DEFAULT 'Pending',
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
);

-- Create clearances table
CREATE TABLE clearances (
    clearance_id INT PRIMARY KEY AUTO_INCREMENT,
    clearance_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    clearance_type ENUM('Give', 'Receive') NOT NULL,
    source_kebele VARCHAR(100),
    destination_kebele VARCHAR(100),
    issue_date DATE NOT NULL,
    issued_by INT,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (issued_by) REFERENCES users(user_id)
);

-- Create messages table
CREATE TABLE messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT,
    receiver_id INT,
    subject VARCHAR(200),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES users(user_id)
);

-- Create news table
CREATE TABLE news (
    news_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    posted_by INT,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (posted_by) REFERENCES users(user_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, phone, gender, role_id) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'admin@ginjo.gov.et', '0912345678', 'Male', 1);

-- Note: Replace the password hash with actual hash using password_hash('admin123', PASSWORD_DEFAULT)