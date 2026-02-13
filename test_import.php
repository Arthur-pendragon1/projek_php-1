<?php
// Script untuk test dan import database peminjaman_barang.sql
echo "<h1>Test Import Database Peminjaman Barang</h1>";

// Koneksi ke database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'peminjaman_barang_db';

$conn = mysqli_connect($host, $username, $password);

if (!$conn) {
    die("<p style='color: red;'>❌ Koneksi database gagal: " . mysqli_connect_error() . "</p>");
}

echo "<p style='color: green;'>✅ Koneksi database berhasil</p>";

// Buat database jika belum ada
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($conn, $sql_create_db)) {
    echo "<p style='color: green;'>✅ Database '$database' berhasil dibuat/ditemukan</p>";
} else {
    echo "<p style='color: red;'>❌ Gagal membuat database: " . mysqli_error($conn) . "</p>";
}

// Pilih database
if (mysqli_select_db($conn, $database)) {
    echo "<p style='color: green;'>✅ Database '$database' berhasil dipilih</p>";
} else {
    echo "<p style='color: red;'>❌ Gagal memilih database: " . mysqli_error($conn) . "</p>";
}

// Baca file SQL
$sql_file = __DIR__ . '/database/peminjaman_barang.sql';
if (file_exists($sql_file)) {
    echo "<p style='color: green;'>✅ File SQL ditemukan: $sql_file</p>";

    $sql_content = file_get_contents($sql_file);

    // Hapus baris-baris yang tidak perlu untuk import
    $sql_content = preg_replace('/--.*$/m', '', $sql_content); // Hapus komentar
    $sql_content = preg_replace('/^\s*$/m', '', $sql_content); // Hapus baris kosong
    $sql_content = trim($sql_content);

    // Split berdasarkan semicolon
    $sql_statements = array_filter(array_map('trim', explode(';', $sql_content)));

    $success_count = 0;
    $error_count = 0;
    $errors = [];

    foreach ($sql_statements as $statement) {
        if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE)/i', $statement)) {
            if (mysqli_query($conn, $statement)) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Error: " . mysqli_error($conn) . "<br>Query: " . substr($statement, 0, 100) . "...";
            }
        }
    }

    echo "<h2>Hasil Import:</h2>";
    echo "<p style='color: green;'>✅ Statement berhasil: $success_count</p>";
    echo "<p style='color: red;'>❌ Statement gagal: $error_count</p>";

    if (!empty($errors)) {
        echo "<h3>Detail Error:</h3>";
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    }

    // Test koneksi ke tabel yang dibuat
    echo "<h2>Test Tabel:</h2>";
    $tables = ['karyawan', 'kategori_barang', 'barang', 'peminjaman', 'izin'];
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "<p style='color: green;'>✅ Tabel '$table' berhasil dibuat</p>";
        } else {
            echo "<p style='color: red;'>❌ Tabel '$table' tidak ditemukan</p>";
        }
    }

} else {
    echo "<p style='color: red;'>❌ File SQL tidak ditemukan: $sql_file</p>";
}

mysqli_close($conn);

echo "<hr>";
echo "<h2>Cara Import Manual:</h2>";
echo "<ol>";
echo "<li>Buka phpMyAdmin (http://localhost/phpmyadmin)</li>";
echo "<li>Buat database baru dengan nama: <code>peminjaman_barang_db</code></li>";
echo "<li>Klik tab 'Import' di phpMyAdmin</li>";
echo "<li>Pilih file: <code>database/peminjaman_barang.sql</code></li>";
echo "<li>Klik 'Go' untuk import</li>";
echo "</ol>";

echo "<h2>Cara Import via Command Line:</h2>";
echo "<pre>";
echo "mysql -u root -p peminjaman_barang_db < database/peminjaman_barang.sql";
echo "</pre>";
?>