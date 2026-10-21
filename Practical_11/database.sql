-- Practical 11 : Student Management CRUD with Search and Filter
-- Run this in phpMyAdmin (http://localhost/phpmyadmin) before running index.php

CREATE DATABASE IF NOT EXISTS student_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE student_db;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    course VARCHAR(50) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data (matches screenshot style)
INSERT INTO students (name, email, phone, course, status) VALUES
('Sneha Prajapati', 'sneha.prajapati@example.com', '9876500022', 'BBA', 'active'),
('Neel Amin', 'neel.amin@example.com', '9876500021', 'B.Tech', 'active'),
('Pooja Barot', 'pooja.barot@example.com', '9876500020', 'BCA', 'active'),
('Om Raval', 'om.raval@example.com', '9876500019', 'MCA', 'active'),
('Tara Dave', 'tara.dave@example.com', '9876500018', 'B.Sc', 'active'),
('Manav Kapadia', 'manav.kapadia@example.com', '9876500017', 'M.Tech', 'active'),
('Nidhi Pandya', 'nidhi.pandya@example.com', '9876500016', 'BBA', 'active'),
('Harsh Vora', 'harsh.vora@example.com', '9876500015', 'BCA', 'active');
