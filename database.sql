CREATE DATABASE IF NOT EXISTS after_class_db;
USE after_class_db;

-- USERS TABLE (Checklist §10: Database Design)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') DEFAULT 'student',
    profile_image VARCHAR(255) DEFAULT NULL,
    level INT DEFAULT 1,
    xp INT DEFAULT 0,
    high_score INT DEFAULT 0,
    games_played INT DEFAULT 0,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ACTIVITY LOGS (Checklist §18: Activity Logs)
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- PASSWORD RESETS (Checklist §7: Forgot Password)
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TEST ACCOUNTS
-- Password for all accounts: Password123!
-- ============================================

-- Admin Account
INSERT INTO users (username, email, password_hash, role) VALUES 
('admin', 'admin@afterclass.com', '$2y$10$YourHashHere', 'admin');

-- Student Account
INSERT INTO users (username, email, password_hash, role) VALUES 
('student', 'student@afterclass.com', '$2y$10$YourHashHere', 'student');

-- Teacher Account
INSERT INTO users (username, email, password_hash, role) VALUES 
('teacher', 'teacher@afterclass.com', '$2y$10$YourHashHere', 'teacher');

-- Add email column if it doesn't exist
ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER username;

-- Update existing users with an email (replace with real values)
UPDATE users SET email = 'admin@afterclass.com' WHERE username = 'admin';
UPDATE users SET email = 'student@afterclass.com' WHERE username = 'student';
UPDATE users SET email = 'teacher@afterclass.com' WHERE username = 'teacher';