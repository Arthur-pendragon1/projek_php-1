-- SQL Dump for Web Sunsal - HR & Inventory Management System
-- Created on 2025-12-04
-- Database: cihuy (Primary Database)
--
-- ========================================
-- Database Schema for Cihuy (Main System)
-- ========================================
-- Main Tables:
-- 1. users - User accounts with role system
-- 2. password_resets - Password reset tokens
-- 3. email_verifications - Email verification tokens
-- 4. jadwal - Activity/schedule management
-- 5. avatar - User profile photos (optional, for extensions)
--
-- Related Databases:
-- - peminjaman_barang_db: Inventory & borrowing (see peminjaman_barang.sql)
-- - absensi_db: Attendance & employee records (see absensi_db.sql)
--
-- Authentication & Security:
-- - Session-based authentication with role system (user|admin)
-- - Password hashing (bcrypt via PHP password_hash)
-- - Email verification for new accounts
-- - Token-based password reset mechanism
-- ========================================

CREATE DATABASE IF NOT EXISTS cihuy;
USE cihuy;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    confirmation_code VARCHAR(10),
    verified TINYINT(1) DEFAULT 0,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(255),  -- Path to profile photo (stored in uploads/avatars/)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk reset password
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel untuk verifikasi email
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- Tabel jadwal untuk fitur jadual
CREATE TABLE IF NOT EXISTS jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kegiatan VARCHAR(100) NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    deskripsi TEXT,
    status ENUM('belum','selesai') DEFAULT 'belum'
);

-- Tabel konfirmasi jadwal untuk tracking konfirmasi email
CREATE TABLE IF NOT EXISTS jadwal_konfirmasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    confirmed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_confirmation (schedule_id, email),
    FOREIGN KEY (schedule_id) REFERENCES jadwal(id) ON DELETE CASCADE
);

-- ========================================
-- Serial Number System Documentation
-- ========================================
-- Reference: peminjaman_barang.sql contains the actual SN implementation
--
-- Table: barang (in peminjaman_barang_db)
-- - serial_number VARCHAR(100)    -- Menyimpan SN barang
-- - sn_verified TINYINT(1) DEFAULT 0 -- Flag verifikasi
--
-- Table: sn_history (in peminjaman_barang_db)
-- - id_sn_history INT AUTO_INCREMENT PRIMARY KEY
-- - id_barang INT NOT NULL
-- - serial_number VARCHAR(100) NOT NULL
-- - sn_verified TINYINT(1) NOT NULL DEFAULT 0
-- - verified_by INT             -- User ID yang verifikasi
-- - verified_at DATETIME        -- Waktu verifikasi
-- - action ENUM('created','verified','unverified','updated')
-- - keterangan TEXT
-- - created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
-- - FOREIGN KEY (id_barang) REFERENCES barang(id_barang) ON DELETE CASCADE
--
-- Serial Number Logic:
-- - If serial_number is empty: auto-generate kode_barang (#00001, #00002, etc)
-- - If serial_number exists: use SN or custom code (no auto-generation)
-- - sn_verified: manual verification flag set by admin after physical inspection
-- - sn_history: audit trail tracking all SN changes and verifications
-- ========================================
