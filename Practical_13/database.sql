-- Practical 13 : PHP JSON API + Fetch CRUD (reuses event_db.events from Practical 12)
-- Run once in phpMyAdmin (http://localhost/phpmyadmin) before opening index.html

CREATE DATABASE IF NOT EXISTS event_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE event_db;

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(150) NOT NULL,
    status ENUM('upcoming','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    poster VARCHAR(255) NULL COMMENT 'Unused in Practical 13 (JSON-only CRUD)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data (run only if table is empty)
INSERT INTO events (title, description, event_date, venue, status) VALUES
('Tech Fest 2026', 'Annual technical festival with coding contests and robotics expo.', '2026-11-20', 'Main Auditorium', 'upcoming'),
('Cultural Night', 'Music, dance and drama performances by student clubs.', '2026-12-05', 'Open Air Theatre', 'upcoming'),
('AI Workshop', 'Hands-on workshop on machine learning basics with Python.', '2026-10-25', 'Seminar Hall B', 'completed'),
('Sports Meet', 'Inter-college athletics and cricket tournament.', '2026-09-15', 'Sports Ground', 'cancelled');
