<?php
session_start();
require_once 'jadual/config.php';
require_once 'includes/functions.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Debug session
// error_log('Session data: ' . print_r($_SESSION, true));

// Ambil role user dari session
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024px, initial-scale=1.0, user-scalable=no">
    <title>Pengaturan Jadwal Kegiatan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="60" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="60" cy="30" r="1.5" fill="rgba(255,255,255,0.1)"/></svg>');
            pointer-events: none;
            z-index: -1;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.8s ease-out;
        }

        h1 {
            color: white;
            margin: 0;
            font-weight: 700;
            font-size: 2.2rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .form-container h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 700;
            text-align: center;
            font-size: 1.8rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
            font-size: 14px;
        }

        input,
        textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            font-family: inherit;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .table-container h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 700;
            text-align: center;
            font-size: 1.8rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        td {
            padding: 18px 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }

        .action-btn {
            padding: 8px 16px;
            margin-right: 8px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .success-msg {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: none;
            border-left: 5px solid #1e8449;
            position: relative;
            animation: slideIn 0.5s ease-out;
            box-shadow: 0 4px 20px rgba(46, 204, 113, 0.3);
        }

        .success-msg::before {
            content: '📧';
            font-size: 24px;
            margin-right: 15px;
            animation: bounce 1s infinite;
        }

        .error-msg {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: none;
            border-left: 5px solid #a93226;
            box-shadow: 0 4px 20px rgba(231, 76, 60, 0.3);
        }

        .notification-dot {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            margin: 0 auto;
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.6);
            animation: pulse 2s infinite;
        }

        .notification-dot.sent {
            background-color: #2ecc71;
        }

        .notification-dot.not-sent {
            background-color: #e74c3c;
            box-shadow: 0 0 8px rgba(231, 76, 60, 0.6);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-5px);
            }
            60% {
                transform: translateY(-3px);
            }
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 8px rgba(46, 204, 113, 0.6);
            }
            50% {
                box-shadow: 0 0 16px rgba(46, 204, 113, 0.8);
            }
            100% {
                box-shadow: 0 0 8px rgba(46, 204, 113, 0.6);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
                margin-bottom: 20px;
            }

            h1 {
                font-size: 1.8rem;
                margin-bottom: 15px;
            }

            .form-container,
            .table-container {
                padding: 20px;
                margin-bottom: 20px;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                font-size: 14px;
            }

            th,
            td {
                padding: 12px 8px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <button class="btn btn-back" onclick="window.history.back()">Kembali</button>
            <h1>Pengaturan Jadwal Kegiatan</h1>
            <?php if ($isAdmin): ?>
            <div style="display: flex; gap: 10px; margin-top: 10px; justify-content: center;">
                <a href="SCHEDULE_REMINDERS_QUICKSTART.html" class="btn btn-primary" style="padding: 10px 15px; font-size: 12px; text-decoration: none;">📧 Email Reminder Setup</a>
            </div>
            <?php endif; ?>
        </div>

        <div id="successMsg" class="success-msg"></div>
        <div id="errorMsg" class="error-msg"></div>

        <?php if ($isAdmin): ?>
        <div class="form-container">
            <h2>Tambah/Edit Jadwal</h2>
            <form id="scheduleForm">
                <input type="hidden" id="id" name="id">
                <div class="form-group">
                    <label for="nama_kegiatan">Nama Kegiatan</label>
                    <input type="text" id="nama_kegiatan" name="nama_kegiatan" required>
                </div>
                <div class="form-group">
                    <label for="tanggal">Tanggal</label>
                    <input type="date" id="tanggal" name="tanggal" required>
                </div>
                <div class="form-group">
                    <label for="waktu">Waktu</label>
                    <input type="time" id="waktu" name="waktu" required>
                </div>
                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi"></textarea>
                </div>
                <div class="form-group">
                    <label for="nomor_ustadz">Nomor Ustadz/Ustadzah (Kontak)</label>
                    <input type="text" id="nomor_ustadz" name="nomor_ustadz" placeholder="Contoh: 08123456789">
                </div>
                <button type="submit" class="btn btn-primary"><span style="margin-right: 8px;">💾</span>Simpan</button>
                <button type="button" class="btn btn-danger" onclick="resetForm()"><span style="margin-right: 8px;">🔄</span>Reset</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <h2>Daftar Jadwal</h2>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kegiatan</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Deskripsi</th>
                        <th>Nomor Ustadz</th>
                        <th>Status</th>
                        <th>Notifikasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="scheduleTable">
                    <?php
                    $query = "SELECT * FROM jadwal ORDER BY tanggal, waktu";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_kegiatan']) . "</td>";
                            echo "<td>" . $row['tanggal'] . "</td>";
                            echo "<td>" . substr($row['waktu'], 0, 5) . "</td>";
                            echo "<td>" . htmlspecialchars($row['deskripsi']) . "</td>";
                            echo "<td>";
                            if (isset($row['nomor_ustadz']) && $row['nomor_ustadz']) {
                                echo "<a href='tel:" . htmlspecialchars($row['nomor_ustadz']) . "' style='color: #667eea; text-decoration: none; font-weight: 600;'><i class='fas fa-phone' style='margin-right: 5px;'></i>" . htmlspecialchars($row['nomor_ustadz']) . "</a>";
                            } else {
                                echo "<span style='color: #999;'>-</span>";
                            }
                            echo "</td>";
                            // Status selesai/tidak
                            echo "<td>";
                            if (isset($row['status']) && $row['status'] == 'selesai') {
                                echo "<span style='color:#2ecc71;font-weight:bold;'><span style='margin-right: 5px;'>✅</span>Selesai</span>";
                            } else {
                                if ($isAdmin) {
                                    echo "<button class='btn btn-primary action-btn' onclick='markDone(" . $row['id'] . ")'><span style='margin-right: 5px;'>✔️</span>Tandai Selesai</button>";
                                } else {
                                    echo "<span style='color:#f39c12;font-weight:bold;'><span style='margin-right: 5px;'>⏳</span>Belum Selesai</span>";
                                }
                            }
                            echo "</td>";
                            // Kolom Notifikasi - hijau karena dikirim saat save
                            echo "<td><span class='notification-dot sent' title='Pengingat dikirim'></span></td>";
                            echo "<td>";
                            if ($isAdmin) {
                                echo "<button class='btn btn-primary action-btn' onclick='editSchedule(" . $row['id'] . ")'><span style='margin-right: 5px;'>✏️</span>Edit</button>";
                                echo "<button class='btn btn-danger action-btn' onclick='deleteSchedule(" . $row['id'] . ")'><span style='margin-right: 5px;'>🗑️</span>Hapus</button>";
                            } else {
                                echo "<span class='text-muted'>-</span>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center;'>Tidak ada jadwal yang ditemukan</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <!-- Tombol Export Excel (hanya untuk admin) -->
            <?php if ($isAdmin): ?>
            <div style="text-align: center; margin-top: 30px;">
                <button class="btn btn-primary" onclick="exportExcel()" style="position: relative; overflow: hidden;">
                    <span style="display: inline-block; margin-right: 8px;">📊</span>
                    Export ke Excel
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showMessage(type, message) {
            if (type === 'success') {
                const msgElement = document.getElementById('successMsg');
                msgElement.style.display = 'block';
                msgElement.textContent = message;
                setTimeout(() => {
                    msgElement.style.display = 'none';
                }, 3000);
            } else {
                const msgElement = document.getElementById('errorMsg');
                msgElement.style.display = 'block';
                msgElement.textContent = message;
                setTimeout(() => {
                    msgElement.style.display = 'none';
                }, 3000);
            }
        }

        function resetForm() {
            document.getElementById('scheduleForm').reset();
            document.getElementById('id').value = '';
        }

        function editSchedule(id) {
            fetch('jadual/ajax.php?action=get&id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('id').value = data.id;
                    document.getElementById('nama_kegiatan').value = data.nama_kegiatan;
                    document.getElementById('tanggal').value = data.tanggal;
                    document.getElementById('waktu').value = data.waktu;
                    document.getElementById('deskripsi').value = data.deskripsi;
                    document.getElementById('nomor_ustadz').value = data.nomor_ustadz || '';
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                })
                .catch(error => {
                    showMessage('error', 'Gagal mengambil data jadwal');
                });
        }

        function deleteSchedule(id) {
            if (confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
                const formData = new FormData();
                formData.append('id', id);
                fetch('jadual/ajax.php?action=delete', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage('success', data.message);
                            setTimeout(() => {
                                location.reload();
                            }, 500);
                        } else {
                            showMessage('error', data.message);
                        }
                    })
                    .catch(error => {
                        showMessage('error', 'Gagal menghapus jadwal');
                    });
            }
        }

        function markDone(id) {
            fetch('jadual/ajax.php?action=done', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(id)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(() => showMessage('error', 'Gagal menandai selesai'));
        }

        function exportExcel() {
            window.location.href = 'jadual/export_excel.php';
        }

        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const action = formData.get('id') ? 'update' : 'add';
            fetch('jadual/ajax.php?action=' + action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    showMessage('error', 'Terjadi kesalahan saat menyimpan data');
                });
        });

        // Set today's date as default
        const tanggalInput = document.getElementById('tanggal');
        if (tanggalInput) {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            tanggalInput.value = `${yyyy}-${mm}-${dd}`;
        }
    </script>
    <footer style="text-align: center; padding: 20px; background: rgba(255, 255, 255, 0.8); border-top: 1px solid rgba(255, 255, 255, 0.3); margin-top: 50px;">
        <p style="color: #667eea; font-weight: 500;">&copy; 2026 Muhammad Rifqi Andrian. All rights reserved.</p>
    </footer>
</body>

</html>