<?php
session_start();
require_once 'config.php';
require_once '../includes/functions.php';
require_once '../includes/send_mail.php';

// Debug session
// error_log('Ajax Session data: ' . print_r($_SESSION, true));

// Cek apakah user sudah login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit;
}

// Cek apakah user adalah admin untuk aksi yang membutuhkan hak admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Debug role
// error_log('Is Admin: ' . ($isAdmin ? 'true' : 'false'));
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'get':
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
            break;
        }
        $id = intval($_GET['id']);
        $query = "SELECT * FROM jadwal WHERE id = $id";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            echo json_encode($row);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
        break;

    case 'add':
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Hanya admin yang dapat menambah jadwal']);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
            break;
        }
        $nama_kegiatan = mysqli_real_escape_string($conn, $_POST['nama_kegiatan'] ?? '');
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? '');
        $waktu = mysqli_real_escape_string($conn, $_POST['waktu'] ?? '');
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
        $nomor_ustadz = mysqli_real_escape_string($conn, $_POST['nomor_ustadz'] ?? '');

        if (!$nama_kegiatan || !$tanggal || !$waktu) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            break;
        }

        $query = "INSERT INTO jadwal (nama_kegiatan, tanggal, waktu, deskripsi, nomor_ustadz) 
                  VALUES ('$nama_kegiatan', '$tanggal', '$waktu', '$deskripsi', '$nomor_ustadz')";

        if (mysqli_query($conn, $query)) {
            $scheduleId = mysqli_insert_id($conn);
            
            // Kirim notifikasi email PERTAMA ke semua user (langsung saat pembuatan)
            $scheduleData = [
                'nama_kegiatan' => $nama_kegiatan,
                'tanggal' => $tanggal,
                'waktu' => $waktu,
                'deskripsi' => $deskripsi
            ];
            
            // Query semua email user dari database yang sama
            $emailQuery = "SELECT email FROM users WHERE email != '' AND email IS NOT NULL";
            $emailResult = mysqli_query($conn, $emailQuery);
            $reminderScheduled = 0;
            
            if ($emailResult) {
                while ($user = mysqli_fetch_assoc($emailResult)) {
                    // KIRIM EMAIL PERTAMA (langsung saat pembuatan jadwal)
                    try {
                        sendScheduleNotification($user['email'], $scheduleData);
                    } catch (Exception $e) {
                        error_log("Failed to send initial email to " . $user['email'] . ": " . $e->getMessage());
                    }
                    
                    // SIMPAN JADWAL PENGINGAT KEDUA untuk dikirim pada waktu yang ditentukan
                    $reminderDateTime = $tanggal . ' ' . $waktu;
                    
                    // Cek apakah tabel jadwal_reminder ada
                    $tableCheckQuery = "SHOW TABLES LIKE 'jadwal_reminder'";
                    $tableResult = mysqli_query($conn, $tableCheckQuery);
                    
                    if (!$tableResult || mysqli_num_rows($tableResult) === 0) {
                        // Buat tabel jika belum ada
                        $createTableQuery = "CREATE TABLE IF NOT EXISTS jadwal_reminder (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            schedule_id INT NOT NULL,
                            email VARCHAR(100) NOT NULL,
                            nama_kegiatan VARCHAR(255) NOT NULL,
                            tanggal DATE NOT NULL,
                            waktu TIME NOT NULL,
                            reminder_datetime DATETIME NOT NULL,
                            sent_at TIMESTAMP NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY unique_reminder (schedule_id, email),
                            KEY idx_reminder_datetime (reminder_datetime),
                            KEY idx_sent (sent_at)
                        )";
                        mysqli_query($conn, $createTableQuery);
                    }
                    
                    // Insert reminder
                    $insertReminderQuery = "INSERT INTO jadwal_reminder (schedule_id, email, nama_kegiatan, tanggal, waktu, reminder_datetime)
                                           VALUES (" . intval($scheduleId) . ", '" . mysqli_real_escape_string($conn, $user['email']) . "', 
                                           '$nama_kegiatan', '$tanggal', '$waktu', '$reminderDateTime')
                                           ON DUPLICATE KEY UPDATE reminder_datetime = '$reminderDateTime'";
                    
                    if (mysqli_query($conn, $insertReminderQuery)) {
                        $reminderScheduled++;
                    }
                }
            }
            
            $message = '✅ Jadwal berhasil ditambahkan! Email pertama dikirim 📧 | Pengingat ke-2 dijadwalkan (' . $reminderScheduled . ' user)';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan jadwal: ' . mysqli_error($conn)]);
        }
        break;

    case 'update':
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Hanya admin yang dapat mengubah jadwal']);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        $nama_kegiatan = mysqli_real_escape_string($conn, $_POST['nama_kegiatan'] ?? '');
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? '');
        $waktu = mysqli_real_escape_string($conn, $_POST['waktu'] ?? '');
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
        $nomor_ustadz = mysqli_real_escape_string($conn, $_POST['nomor_ustadz'] ?? '');

        if (!$id || !$nama_kegiatan || !$tanggal || !$waktu) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            break;
        }

        $query = "UPDATE jadwal SET 
                  nama_kegiatan = '$nama_kegiatan', 
                  tanggal = '$tanggal', 
                  waktu = '$waktu', 
                  deskripsi = '$deskripsi', 
                  nomor_ustadz = '$nomor_ustadz' 
                  WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            // Kirim notifikasi email PERTAMA ke semua user (langsung saat update)
            $scheduleData = [
                'nama_kegiatan' => $nama_kegiatan,
                'tanggal' => $tanggal,
                'waktu' => $waktu,
                'deskripsi' => $deskripsi
            ];
            
            // Query semua email user dari database yang sama
            $emailQuery = "SELECT email FROM users WHERE email != '' AND email IS NOT NULL";
            $emailResult = mysqli_query($conn, $emailQuery);
            $reminderScheduled = 0;
            
            if ($emailResult) {
                while ($user = mysqli_fetch_assoc($emailResult)) {
                    // KIRIM EMAIL PERTAMA (langsung saat update jadwal)
                    try {
                        sendScheduleNotification($user['email'], $scheduleData);
                    } catch (Exception $e) {
                        error_log("Failed to send update email to " . $user['email'] . ": " . $e->getMessage());
                    }
                    
                    // UPDATE JADWAL PENGINGAT KEDUA untuk dikirim pada waktu yang ditentukan
                    $reminderDateTime = $tanggal . ' ' . $waktu;
                    
                    // Cek apakah tabel jadwal_reminder ada
                    $tableCheckQuery = "SHOW TABLES LIKE 'jadwal_reminder'";
                    $tableResult = mysqli_query($conn, $tableCheckQuery);
                    
                    if (!$tableResult || mysqli_num_rows($tableResult) === 0) {
                        // Buat tabel jika belum ada
                        $createTableQuery = "CREATE TABLE IF NOT EXISTS jadwal_reminder (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            schedule_id INT NOT NULL,
                            email VARCHAR(100) NOT NULL,
                            nama_kegiatan VARCHAR(255) NOT NULL,
                            tanggal DATE NOT NULL,
                            waktu TIME NOT NULL,
                            reminder_datetime DATETIME NOT NULL,
                            sent_at TIMESTAMP NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY unique_reminder (schedule_id, email),
                            KEY idx_reminder_datetime (reminder_datetime),
                            KEY idx_sent (sent_at)
                        )";
                        mysqli_query($conn, $createTableQuery);
                    }
                    
                    // Update atau insert reminder
                    $updateReminderQuery = "INSERT INTO jadwal_reminder (schedule_id, email, nama_kegiatan, tanggal, waktu, reminder_datetime)
                                           VALUES (" . intval($id) . ", '" . mysqli_real_escape_string($conn, $user['email']) . "', 
                                           '$nama_kegiatan', '$tanggal', '$waktu', '$reminderDateTime')
                                           ON DUPLICATE KEY UPDATE 
                                           nama_kegiatan = '$nama_kegiatan',
                                           tanggal = '$tanggal',
                                           waktu = '$waktu',
                                           reminder_datetime = '$reminderDateTime',
                                           sent_at = NULL";
                    
                    if (mysqli_query($conn, $updateReminderQuery)) {
                        $reminderScheduled++;
                    }
                }
            }
            
            $message = '✅ Jadwal berhasil diperbarui! Email pertama dikirim 📧 | Pengingat ke-2 dijadwalkan ulang (' . $reminderScheduled . ' user)';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui jadwal: ' . mysqli_error($conn)]);
        }
        break;

    case 'delete':
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Hanya admin yang dapat menghapus jadwal']);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
            break;
        }
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
            break;
        }

        $query = "DELETE FROM jadwal WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Jadwal berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus jadwal: ' . mysqli_error($conn)]);
        }
        break;

    case 'done':
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Hanya admin yang dapat menandai jadwal selesai']);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
            break;
        }
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
            break;
        }

        // Ambil data jadwal sebelum update untuk notifikasi email
        $scheduleQuery = "SELECT nama_kegiatan, tanggal, waktu, deskripsi FROM jadwal WHERE id = $id";
        $scheduleResult = mysqli_query($conn, $scheduleQuery);
        
        if (!$scheduleResult || mysqli_num_rows($scheduleResult) == 0) {
            echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan']);
            break;
        }
        
        $scheduleData = mysqli_fetch_assoc($scheduleResult);

        $query = "UPDATE jadwal SET status='selesai' WHERE id=$id";

        if (mysqli_query($conn, $query)) {
            // Kirim notifikasi email ke semua user bahwa jadwal telah selesai
            // Query semua email user dari database yang sama
            $emailQuery = "SELECT email FROM users WHERE email != '' AND email IS NOT NULL";
            $emailResult = mysqli_query($conn, $emailQuery);
            if ($emailResult) {
                $confirmationTokens = [];
                while ($user = mysqli_fetch_assoc($emailResult)) {
                    // Kirim email (jangan biarkan gagal menghentikan proses)
                    try {
                        $result = sendCompletionNotification($user['email'], $scheduleData, $id);
                        if ($result['success']) {
                            $confirmationTokens[] = [
                                'email' => $user['email'],
                                'token' => $result['token']
                            ];
                        }
                    } catch (Exception $e) {
                        error_log("Failed to send completion email to " . $user['email'] . ": " . $e->getMessage());
                    }
                }

                // Simpan token konfirmasi ke database
                if (!empty($confirmationTokens)) {
                    foreach ($confirmationTokens as $tokenData) {
                        $insertQuery = "INSERT INTO jadwal_konfirmasi (schedule_id, email, token)
                                       VALUES (?, ?, ?)
                                       ON DUPLICATE KEY UPDATE token = VALUES(token)";
                        $stmt = mysqli_prepare($conn, $insertQuery);
                        mysqli_stmt_bind_param($stmt, "iss", $id, $tokenData['email'], $tokenData['token']);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => '✅ Jadwal berhasil ditandai selesai dan ucapan terima kasih dikirim 📧']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal update: ' . mysqli_error($conn)]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
        break;
}

mysqli_close($conn);
