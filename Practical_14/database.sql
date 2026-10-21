-- Practical 14 : Admin Dashboard with RBAC + Audit Log
-- Run once in phpMyAdmin (http://localhost/phpmyadmin).
-- Demo users (admin/admin123, staff/staff123) are auto-seeded by config.php
-- on first page load, so no password hashes are hardcoded here.

CREATE DATABASE IF NOT EXISTS dashboard_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE dashboard_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT '',
    action VARCHAR(50) NOT NULL,
    details VARCHAR(255) NULL,
    ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- Reads data created by earlier practicals (same MySQL server):
--   student_db.students  (Practical 11)
--   event_db.events      (Practical 12)
-- If those DBs are missing, the dashboard shows "—" for those cards.
