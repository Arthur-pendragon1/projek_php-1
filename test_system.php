<?php
require_once 'includes/config.php';

// Test query to check if jadwal_konfirmasi table exists
$testQuery = "SHOW TABLES LIKE 'jadwal_konfirmasi'";
$result = mysqli_query($conn, $testQuery);

if (mysqli_num_rows($result) > 0) {
    echo "✅ Tabel jadwal_konfirmasi sudah ada.<br>";
} else {
    echo "❌ Tabel jadwal_konfirmasi belum ada. <a href='create_table.php'>Buat tabel</a><br>";
}

// Test query to check if jadwal table exists
$jadwalQuery = "SHOW TABLES LIKE 'jadwal'";
$jadwalResult = mysqli_query($conn, $jadwalQuery);

if (mysqli_num_rows($jadwalResult) > 0) {
    echo "✅ Tabel jadwal sudah ada.<br>";
} else {
    echo "❌ Tabel jadwal belum ada.<br>";
}

mysqli_close($conn);
?>

<h2>Test Links</h2>
<p>Klik link di bawah untuk test halaman konfirmasi:</p>
<ul>
    <li><a href="confirm_schedule.php">Halaman Konfirmasi (tanpa parameter)</a></li>
    <li><a href="confirm_schedule.php?token=test123&email=test@example.com&id=1">Halaman Konfirmasi (dengan parameter test)</a></li>
</ul>