-- Practical 12 : Event Management CRUD with Poster Upload
-- Run this in phpMyAdmin (http://localhost/phpmyadmin) before running login.php / index.php

CREATE DATABASE IF NOT EXISTS event_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE event_db;

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(150) NOT NULL,
    status ENUM('upcoming','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    poster VARCHAR(255) NULL COMMENT 'Stored file name inside uploads/',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data (posters added via Add Event form after setup)
INSERT INTO events (title, description, event_date, venue, status, poster) VALUES
('Tech Fest 2026', 'Annual technical festival with coding contests and robotics expo.', '2026-11-20', 'Main Auditorium', 'upcoming', NULL),
('Cultural Night', 'Music, dance and drama performances by student clubs.', '2026-12-05', 'Open Air Theatre', 'upcoming', NULL),
('AI Workshop', 'Hands-on workshop on machine learning basics with Python.', '2026-10-25', 'Seminar Hall B', 'completed', NULL),
('Sports Meet', 'Inter-college athletics and cricket tournament.', '2026-09-15', 'Sports Ground', 'cancelled', NULL);
