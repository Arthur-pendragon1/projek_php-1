CREATE DATABASE IF NOT EXISTS peminjaman_barang_db;
USE peminjaman_barang_db;

-- ========================================
-- Database Schema for Peminjaman Barang
-- ========================================
-- Main Tables:
-- 1. karyawan - Employee/staff master data
-- 2. kategori_barang - Item categories
-- 3. barang - Inventory items (with serial_number & sn_verified)
-- 4. sn_history - Serial Number verification audit trail
-- 5. peminjaman - Borrowing records
-- 6. riwayat_peminjaman - Transaction history
-- 7. izin - Permission/leave requests
--
-- Serial Number System:
-- - If serial_number is empty: auto-generate kode_barang (#00001, #00002, etc)
-- - If serial_number exists: use SN or custom code (no auto-generation)
-- - sn_verified: manual verification flag set by admin after physical inspection
-- - sn_history: audit trail tracking all SN changes and verifications
-- ========================================

-- Set SQL mode and character set
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Table karyawan: employees with card/RFID information
CREATE TABLE IF NOT EXISTS karyawan (
    id_karyawan INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    id_card VARCHAR(50) NOT NULL UNIQUE,
    uid_kartu VARCHAR(255) UNIQUE,
    divisi VARCHAR(100),
    jabatan VARCHAR(100),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table kategori_barang: categories for items
CREATE TABLE IF NOT EXISTS kategori_barang (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table barang: list of available items (primary table)
CREATE TABLE IF NOT EXISTS barang (
    id_barang INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT,
    kode_barang VARCHAR(50) NOT NULL UNIQUE,
    nama_barang VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100),
    sn_verified TINYINT(1) DEFAULT 0,
    deskripsi TEXT,
    jumlah_total INT NOT NULL DEFAULT 1,
    jumlah_tersedia INT NOT NULL DEFAULT 1,
    lokasi_penyimpanan VARCHAR(100),
    kondisi ENUM('baik', 'rusak_ringan', 'rusak_berat') NOT NULL DEFAULT 'baik',
    status ENUM('tersedia','dipinjam','dalam_pemeliharaan') NOT NULL DEFAULT 'tersedia',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori_barang(id_kategori)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table sn_history: history tracking for Serial Number verifications
CREATE TABLE IF NOT EXISTS sn_history (
    id_sn_history INT AUTO_INCREMENT PRIMARY KEY,
    id_barang INT NOT NULL,
    serial_number VARCHAR(100) NOT NULL,
    sn_verified TINYINT(1) NOT NULL DEFAULT 0,
    verified_by INT,
    verified_at DATETIME,
    action ENUM('created','verified','unverified','updated') NOT NULL,
    keterangan TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create an `item` table as an alias/duplicate of `barang` so code that expects
-- a table named `item` will work. If you already have an `item` table, this
-- statement will do nothing due to IF NOT EXISTS — keep schemas synchronized manually.
CREATE TABLE IF NOT EXISTS item (
    id_barang INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT,
    kode_barang VARCHAR(50) NOT NULL UNIQUE,
    nama_barang VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100),
    sn_verified TINYINT(1) DEFAULT 0,
    deskripsi TEXT,
    jumlah_total INT NOT NULL DEFAULT 1,
    jumlah_tersedia INT NOT NULL DEFAULT 1,
    lokasi_penyimpanan VARCHAR(100),
    kondisi ENUM('baik', 'rusak_ringan', 'rusak_berat') NOT NULL DEFAULT 'baik',
    status ENUM('tersedia','dipinjam','dalam_pemeliharaan') NOT NULL DEFAULT 'tersedia',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table peminjaman: records of borrowed items
CREATE TABLE IF NOT EXISTS peminjaman (
    id_peminjaman INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    id_barang INT DEFAULT NULL,
    tanggal_pinjam DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tanggal_kembali_rencana DATETIME,
    tanggal_kembali_aktual DATETIME,
    tujuan_peminjaman TEXT,
    lokasi_penggunaan VARCHAR(100),
    status ENUM('pending','approved','rejected','dipinjam','dikembalikan','terlambat') NOT NULL DEFAULT 'pending',
    catatan TEXT,
    approved_by INT,
    approved_at DATETIME,
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table riwayat_peminjaman: history of all borrowing transactions
CREATE TABLE IF NOT EXISTS riwayat_peminjaman (
    id_riwayat INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT NOT NULL,
    id_karyawan INT NOT NULL,
    id_barang INT NOT NULL,
    action ENUM('created','approved','rejected','borrowed','returned','overdue') NOT NULL,
    tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    keterangan TEXT,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table izin: leave/permission requests for karyawan (used by absensi system)
CREATE TABLE IF NOT EXISTS izin (
    id_izin INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    jenis ENUM('izin_keluar','permintaan_pulang','langsung_pulang','terlambat') NOT NULL,
    alasan TEXT,
    status ENUM('pending','approved','rejected','failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create views after all tables are created
DROP VIEW IF EXISTS active_borrowings;
CREATE VIEW active_borrowings AS
SELECT
    p.*,
    k.nama AS nama_karyawan,
    k.id_card,
    k.uid_kartu,
    k.divisi,
    k.jabatan,
    b.kode_barang,
    b.nama_barang,
    b.lokasi_penyimpanan,
    b.kondisi
FROM peminjaman p
JOIN karyawan k ON p.id_karyawan = k.id_karyawan
JOIN barang b ON p.id_barang = b.id_barang
WHERE p.status = 'dipinjam'
    AND p.tanggal_kembali_aktual IS NULL;

DROP VIEW IF EXISTS employee_borrow_history;
CREATE VIEW employee_borrow_history AS
SELECT
    k.id_karyawan,
    k.nama AS nama_karyawan,
    k.id_card,
    k.divisi,
    k.jabatan,
    COUNT(p.id_peminjaman) AS total_peminjaman,
    COUNT(CASE WHEN p.status = 'dipinjam' THEN 1 END) AS peminjaman_aktif,
    COUNT(CASE WHEN p.status = 'terlambat' THEN 1 END) AS peminjaman_terlambat,
    MAX(p.tanggal_pinjam) AS peminjaman_terakhir
FROM karyawan k
LEFT JOIN peminjaman p ON k.id_karyawan = p.id_karyawan
GROUP BY k.id_karyawan, k.nama, k.id_card, k.divisi, k.jabatan;

-- Insert sample data (optional - uncomment if needed)
-- INSERT INTO kategori_barang (nama_kategori, deskripsi) VALUES
-- ('Elektronik', 'Barang elektronik seperti laptop, monitor, dll'),
-- ('Furniture', 'Meja, kursi, dan peralatan kantor'),
-- ('Tools', 'Peralatan dan tools kerja');

COMMIT;