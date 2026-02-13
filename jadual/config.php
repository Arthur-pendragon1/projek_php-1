<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cihuy';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Ensure nomor_ustadz column exists in jadwal table
@mysqli_query($conn, "ALTER TABLE jadwal ADD COLUMN IF NOT EXISTS nomor_ustadz VARCHAR(20) DEFAULT NULL");
?>