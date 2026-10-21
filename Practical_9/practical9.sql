-- practical9.sql — Run this once in phpMyAdmin (Import) or MySQL console
-- Creates database + users table with UNIQUE constraints for duplicate check

CREATE DATABASE IF NOT EXISTS practical9_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE practical9_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    username VARCHAR(20)  NOT NULL UNIQUE,
    email    VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,   -- stores bcrypt hash from password_hash()
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
