-- ========================================
-- SIMPLIFIED Database Schema for Peminjaman Barang
-- ========================================
-- This is a simplified version without views for easier import
-- ========================================

CREATE DATABASE IF NOT EXISTS peminjaman_barang_db;
USE peminjaman_barang_db;

-- Set character set
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table karyawan: employees with card/RFID information
DROP TABLE IF EXISTS karyawan;
CREATE TABLE karyawan (
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
DROP TABLE IF EXISTS kategori_barang;
CREATE TABLE kategori_barang (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table barang: list of available items
DROP TABLE IF EXISTS barang;
CREATE TABLE barang (
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
    INDEX idx_kode_barang (kode_barang),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table sn_history: history tracking for Serial Number verifications
DROP TABLE IF EXISTS sn_history;
CREATE TABLE sn_history (
    id_sn_history INT AUTO_INCREMENT PRIMARY KEY,
    id_barang INT NOT NULL,
    serial_number VARCHAR(100) NOT NULL,
    sn_verified TINYINT(1) NOT NULL DEFAULT 0,
    verified_by INT,
    verified_at DATETIME,
    action ENUM('created','verified','unverified','updated') NOT NULL,
    keterangan TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_barang (id_barang),
    INDEX idx_serial_number (serial_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table peminjaman: records of borrowed items
DROP TABLE IF EXISTS peminjaman;
CREATE TABLE peminjaman (
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
    INDEX idx_id_karyawan (id_karyawan),
    INDEX idx_id_barang (id_barang),
    INDEX idx_status (status),
    INDEX idx_tanggal_pinjam (tanggal_pinjam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table riwayat_peminjaman: history of all borrowing transactions
DROP TABLE IF EXISTS riwayat_peminjaman;
CREATE TABLE riwayat_peminjaman (
    id_riwayat INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT NOT NULL,
    id_karyawan INT NOT NULL,
    id_barang INT NOT NULL,
    action ENUM('created','approved','rejected','borrowed','returned','overdue') NOT NULL,
    tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    keterangan TEXT,
    INDEX idx_id_peminjaman (id_peminjaman),
    INDEX idx_id_karyawan (id_karyawan),
    INDEX idx_id_barang (id_barang),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table izin: leave/permission requests
DROP TABLE IF EXISTS izin;
CREATE TABLE izin (
    id_izin INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    jenis ENUM('izin_keluar','permintaan_pulang','langsung_pulang','terlambat') NOT NULL,
    alasan TEXT,
    status ENUM('pending','approved','rejected','failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_karyawan (id_karyawan),
    INDEX idx_status (status),
    INDEX idx_jenis (jenis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraints after all tables are created
ALTER TABLE barang ADD CONSTRAINT fk_barang_kategori FOREIGN KEY (id_kategori) REFERENCES kategori_barang(id_kategori) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE sn_history ADD CONSTRAINT fk_sn_history_barang FOREIGN KEY (id_barang) REFERENCES barang(id_barang) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE peminjaman ADD CONSTRAINT fk_peminjaman_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE peminjaman ADD CONSTRAINT fk_peminjaman_barang FOREIGN KEY (id_barang) REFERENCES barang(id_barang) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE riwayat_peminjaman ADD CONSTRAINT fk_riwayat_peminjaman FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE riwayat_peminjaman ADD CONSTRAINT fk_riwayat_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE riwayat_peminjaman ADD CONSTRAINT fk_riwayat_barang FOREIGN KEY (id_barang) REFERENCES barang(id_barang) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE izin ADD CONSTRAINT fk_izin_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert sample data
INSERT INTO kategori_barang (nama_kategori, deskripsi) VALUES
('Elektronik', 'Barang elektronik seperti laptop, monitor, dll'),
('Furniture', 'Meja, kursi, dan peralatan kantor'),
('Tools', 'Peralatan dan tools kerja'),
('Kendaraan', 'Mobil, motor, dan kendaraan dinas');

-- Insert sample karyawan
INSERT INTO karyawan (nama, id_card, divisi, jabatan) VALUES
('John Doe', 'EMP001', 'IT', 'Programmer'),
('Jane Smith', 'EMP002', 'HR', 'Manager'),
('Bob Johnson', 'EMP003', 'Finance', 'Accountant');

-- Insert sample barang
INSERT INTO barang (id_kategori, kode_barang, nama_barang, deskripsi, jumlah_total, jumlah_tersedia, lokasi_penyimpanan) VALUES
(1, '#00001', 'Laptop Dell', 'Laptop untuk development', 5, 5, 'Ruang IT'),
(1, '#00002', 'Monitor LG', 'Monitor 24 inch', 10, 10, 'Ruang IT'),
(2, '#00003', 'Kursi Kantor', 'Kursi ergonomis', 20, 20, 'Gudang'),
(3, '#00004', 'Obeng Set', 'Set obeng lengkap', 15, 15, 'Workshop');

COMMIT;