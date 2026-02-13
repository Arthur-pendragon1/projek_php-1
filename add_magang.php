<?php
// add_magang.php
// Form sederhana untuk menambahkan anak magang ke tabel `karyawan` di database peminjaman_barang_db

require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    // only admins should add interns
    redirect('dashboard.php');
}

$invDb = 'peminjaman_barang_db';
$invConn = mysqli_connect('localhost', DB_USER, DB_PASS, $invDb);
if (!$invConn) {
    $error = 'Gagal koneksi ke database peminjaman (peminjaman_barang_db): ' . mysqli_connect_error();
}

// Ensure karyawan table has jam_mulai and jam_akhir columns
if ($invConn) {
    @mysqli_query($invConn, "ALTER TABLE karyawan ADD COLUMN IF NOT EXISTS jam_mulai TIME DEFAULT NULL");
    @mysqli_query($invConn, "ALTER TABLE karyawan ADD COLUMN IF NOT EXISTS jam_akhir TIME DEFAULT NULL");
}

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $id_card = trim($_POST['id_card'] ?? '');
    $uid_kartu = trim($_POST['uid_kartu'] ?? '');
    $divisi = trim($_POST['divisi'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? 'Magang');
    $jam_mulai = trim($_POST['jam_mulai'] ?? '');
    $jam_akhir = trim($_POST['jam_akhir'] ?? '');

    if ($nama === '' || $id_card === '') {
        $message = ['type'=>'error','text'=>'Nama dan NIS (ID Card) wajib diisi.'];
    } else {
        // check uniqueness (id_card or uid_kartu)
        $existsSql = "SELECT id_karyawan FROM karyawan WHERE id_card = '" . mysqli_real_escape_string($invConn, $id_card) . "' LIMIT 1";
        $res = @mysqli_query($invConn, $existsSql);
        if ($res && mysqli_num_rows($res) > 0) {
            $message = ['type'=>'error','text'=>'NIS (ID Card) sudah ada di sistem.'];
        } else if ($uid_kartu !== '') {
            $existsSql2 = "SELECT id_karyawan FROM karyawan WHERE uid_kartu = '" . mysqli_real_escape_string($invConn, $uid_kartu) . "' LIMIT 1";
            $r2 = @mysqli_query($invConn, $existsSql2);
            if ($r2 && mysqli_num_rows($r2) > 0) {
                $message = ['type'=>'error','text'=>'UID kartu sudah terdaftar. Hapus/ubah sebelum mencoba.'];
            }
        }

        if ($message === null) {
            $cols = ['nama','id_card','uid_kartu','divisi','jabatan'];
            $vals = [
                "'" . mysqli_real_escape_string($invConn, $nama) . "'",
                "'" . mysqli_real_escape_string($invConn, $id_card) . "'",
                ($uid_kartu !== '' ? "'" . mysqli_real_escape_string($invConn, $uid_kartu) . "'" : "NULL"),
                ($divisi !== '' ? "'" . mysqli_real_escape_string($invConn, $divisi) . "'" : "NULL"),
                "'" . mysqli_real_escape_string($invConn, $jabatan) . "'"
            ];
            
            // Tambah jam kerja jika ada
            if ($jam_mulai !== '') {
                $cols[] = 'jam_mulai';
                $vals[] = "'" . mysqli_real_escape_string($invConn, $jam_mulai) . ":00'";
            }
            if ($jam_akhir !== '') {
                $cols[] = 'jam_akhir';
                $vals[] = "'" . mysqli_real_escape_string($invConn, $jam_akhir) . ":00'";
            }

            $ins = "INSERT INTO karyawan (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ")";
            if (mysqli_query($invConn, $ins)) {
                // Get the inserted id from peminjaman_barang_db
                $insertedId = mysqli_insert_id($invConn);
                
                // Also insert to absensi_db dengan id_card sebagai identifikasi
                // Gunakan UPDATE jika sudah ada, INSERT jika belum ada
                $absConn = @mysqli_connect('localhost', DB_USER, DB_PASS, 'absensi_db');
                if ($absConn) {
                    // Cek apakah sudah ada dengan id_card yang sama
                    $checkAbsen = "SELECT id_karyawan FROM karyawan WHERE id_card = '" . mysqli_real_escape_string($absConn, $id_card) . "' LIMIT 1";
                    $resCheck = @mysqli_query($absConn, $checkAbsen);
                    
                    if ($resCheck && mysqli_num_rows($resCheck) > 0) {
                        // Sudah ada, update saja dengan jam kerja
                        $absRow = mysqli_fetch_assoc($resCheck);
                        $updateAbs = "UPDATE karyawan SET nama = '" . mysqli_real_escape_string($absConn, $nama) . "'";
                        if ($jam_mulai !== '') {
                            $updateAbs .= ", jam_mulai = '" . mysqli_real_escape_string($absConn, $jam_mulai) . ":00'";
                        }
                        if ($jam_akhir !== '') {
                            $updateAbs .= ", jam_akhir = '" . mysqli_real_escape_string($absConn, $jam_akhir) . ":00'";
                        }
                        $updateAbs .= " WHERE id_card = '" . mysqli_real_escape_string($absConn, $id_card) . "'";
                        @mysqli_query($absConn, $updateAbs);
                    } else {
                        // Belum ada, insert baru
                        $colsAbs = ['nama', 'id_card', 'uid_kartu'];
                        $valsAbs = [
                            "'" . mysqli_real_escape_string($absConn, $nama) . "'",
                            "'" . mysqli_real_escape_string($absConn, $id_card) . "'",
                            ($uid_kartu !== '' ? "'" . mysqli_real_escape_string($absConn, $uid_kartu) . "'" : "NULL")
                        ];
                        
                        if ($jam_mulai !== '') {
                            $colsAbs[] = 'jam_mulai';
                            $valsAbs[] = "'" . mysqli_real_escape_string($absConn, $jam_mulai) . ":00'";
                        }
                        if ($jam_akhir !== '') {
                            $colsAbs[] = 'jam_akhir';
                            $valsAbs[] = "'" . mysqli_real_escape_string($absConn, $jam_akhir) . ":00'";
                        }
                        
                        $insAbs = "INSERT INTO karyawan (`" . implode('`,`', $colsAbs) . "`) 
                                  VALUES (" . implode(',', $valsAbs) . ")";
                        @mysqli_query($absConn, $insAbs);
                    }
                    mysqli_close($absConn);
                }
                
                $message = ['type'=>'success','text'=>'Anak magang berhasil ditambahkan.'];
                // clear POST to avoid resubmission
                $_POST = [];
            } else {
                $message = ['type'=>'error','text'=>'Gagal menambahkan anak magang: ' . mysqli_error($invConn)];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tambah Anak Magang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{font-family:Segoe UI, Tahoma, sans-serif;background:#f3f4f6;color:#111;margin:0;padding:24px}
        .card{background:white;border-radius:10px;padding:20px;max-width:700px;margin:12px auto;box-shadow:0 6px 20px rgba(2,6,23,0.06)}
        label{display:block;margin-bottom:6px;font-weight:600}
        input[type=text], input[type=time]{width:100%;padding:10px;border-radius:8px;border:1px solid #e6edf3;margin-bottom:12px;box-sizing:border-box}
        .btn{background:#4f46e5;color:white;padding:10px 14px;border-radius:8px;border:none;cursor:pointer}
        .muted{color:#64748b}
        .msg{padding:10px;border-radius:8px;margin-bottom:12px}
        .msg.success{background:#dcfce7;color:#065f46}
        .msg.error{background:#fee2e2;color:#7f1d1d}
    </style>
</head>
<body>
    <div class="card">
        <h2>Tambah Anak Magang</h2>
        <p class="muted">Form ini menambah entri ke tabel <code>karyawan</code> pada database <code>peminjaman_barang_db</code>. Kolom wajib: Nama dan NIS (ID Card).</p>

        <?php if (isset($error)): ?>
            <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="msg <?php echo htmlspecialchars($message['type']); ?>"><?php echo htmlspecialchars($message['text']); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>" required>

            <label for="id_card">NIS / ID Card</label>
            <input type="text" id="id_card" name="id_card" value="<?php echo htmlspecialchars($_POST['id_card'] ?? ''); ?>" required>

            <label for="uid_kartu">UID Kartu (opsional)</label>
            <input type="text" id="uid_kartu" name="uid_kartu" value="<?php echo htmlspecialchars($_POST['uid_kartu'] ?? ''); ?>">

            <label for="divisi">Divisi (opsional)</label>
            <input type="text" id="divisi" name="divisi" value="<?php echo htmlspecialchars($_POST['divisi'] ?? ''); ?>">

            <label for="jabatan">Jabatan</label>
            <input type="text" id="jabatan" name="jabatan" value="<?php echo htmlspecialchars($_POST['jabatan'] ?? 'Magang'); ?>">

            <label for="jam_mulai">Jam Masuk (opsional)</label>
            <input type="time" id="jam_mulai" name="jam_mulai" value="<?php echo htmlspecialchars($_POST['jam_mulai'] ?? ''); ?>">

            <label for="jam_akhir">Jam Keluar (opsional)</label>
            <input type="time" id="jam_akhir" name="jam_akhir" value="<?php echo htmlspecialchars($_POST['jam_akhir'] ?? ''); ?>">

            <div style="margin-top:10px;display:flex;gap:8px">
                <button class="btn" type="submit">Tambah</button>
                <a href="absen_tanpa_rfid.php" style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;border-radius:8px;background:#eef2ff;color:#1e3a8a;text-decoration:none">Ke Absen</a>
            </div>
        </form>
    </div>

    <footer style="text-align:center;padding:20px;color:#666;font-size:12px;margin-top:40px;border-top:1px solid #eef2f6;max-width:700px;margin-left:auto;margin-right:auto">
        <p>&copy; 2024 - 2026 <strong>Web Sunsal</strong> by <strong>Muhammad Rifqi Andrian</strong>. All Rights Reserved. | Sistem Manajemen HR & Inventory</p>
    </footer>
</body>
</html>
