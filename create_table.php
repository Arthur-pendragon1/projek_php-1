<?php
require_once 'includes/config.php';

// SQL untuk membuat tabel jadwal_konfirmasi
$sql = "
CREATE TABLE IF NOT EXISTS jadwal_konfirmasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    confirmed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_confirmation (schedule_id, email),
    INDEX idx_schedule_email (schedule_id, email)
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Tabel jadwal_konfirmasi berhasil dibuat!<br>";
    echo "Sekarang Anda dapat menggunakan fitur konfirmasi email jadwal.";
} else {
    echo "❌ Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>