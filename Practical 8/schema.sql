-- ============================================================
-- Practical 8: StudentHub Database Schema
-- Database: studenthub
-- Tables  : students, events, registrations
-- Run in phpMyAdmin (SQL tab) or MySQL CLI:
--   CREATE DATABASE studenthub; USE studenthub; SOURCE schema.sql;
-- ============================================================

CREATE DATABASE IF NOT EXISTS studenthub
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

-- ---------- 1. STUDENTS (parent table) ----------
CREATE TABLE IF NOT EXISTS students (
  student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(100) NOT NULL UNIQUE,
  phone      VARCHAR(15),
  department VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- 2. EVENTS (parent table) ----------
CREATE TABLE IF NOT EXISTS events (
  event_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(150) NOT NULL,
  description TEXT,
  event_date  DATE NOT NULL,
  venue       VARCHAR(100) NOT NULL,
  max_seats   INT UNSIGNED NOT NULL DEFAULT 100,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_max_seats CHECK (max_seats > 0)
) ENGINE=InnoDB;

-- ---------- 3. REGISTRATIONS (child / junction table) ----------
-- Resolves the Many-to-Many relationship:
--   One student can register for many events.
--   One event can have many students.
CREATE TABLE IF NOT EXISTS registrations (
  reg_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id        INT UNSIGNED NOT NULL,
  event_id          INT UNSIGNED NOT NULL,
  registration_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status            ENUM('registered','cancelled') NOT NULL DEFAULT 'registered',

  -- One student cannot register twice for the same event
  UNIQUE KEY uq_student_event (student_id, event_id),

  CONSTRAINT fk_reg_student FOREIGN KEY (student_id)
    REFERENCES students (student_id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_reg_event FOREIGN KEY (event_id)
    REFERENCES events (event_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Helpful indexes for JOINs used by the PHP app
CREATE INDEX idx_reg_student ON registrations (student_id);
CREATE INDEX idx_reg_event   ON registrations (event_id);

-- ---------- Sample data (for testing the PHP app) ----------
INSERT INTO students (name, email, phone, department) VALUES
  ('Aarav Sharma', 'aarav@college.edu', '9876543210', 'BCA'),
  ('Diya Patel',   'diya@college.edu',  '9876543211', 'BTech'),
  ('Kabir Singh',  'kabir@college.edu', '9876543212', 'MCA')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO events (title, description, event_date, venue, max_seats) VALUES
  ('Tech Fest 2026',  'Annual coding and robotics fest.', '2026-11-15', 'Main Auditorium', 200),
  ('Cultural Night',  'Music, dance and drama evening.',  '2026-12-05', 'Open Air Theatre', 300),
  ('Placement Drive', 'Campus recruitment drive.',         '2026-10-20', 'Seminar Hall A', 150);

-- Sample registrations (student 1 -> event 1, student 2 -> event 1, student 1 -> event 2)
INSERT IGNORE INTO registrations (student_id, event_id, status) VALUES
  (1, 1, 'registered'),
  (2, 1, 'registered'),
  (1, 2, 'registered');
