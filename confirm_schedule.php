<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Cek apakah ada token konfirmasi
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$schedule_id = intval($_GET['id'] ?? 0);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    // Proses konfirmasi
    if (!empty($token) && !empty($email) && $schedule_id > 0) {
        // Cek apakah tabel jadwal_konfirmasi ada
        $tableCheckQuery = "SHOW TABLES LIKE 'jadwal_konfirmasi'";
        $tableResult = mysqli_query($conn, $tableCheckQuery);
        
        if (!$tableResult || mysqli_num_rows($tableResult) === 0) {
            // Buat tabel jika belum ada
            $createTableQuery = "CREATE TABLE IF NOT EXISTS jadwal_konfirmasi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                schedule_id INT NOT NULL,
                email VARCHAR(100) NOT NULL,
                token VARCHAR(255) NOT NULL,
                confirmed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_confirmation (schedule_id, email, token)
            )";
            
            if (!mysqli_query($conn, $createTableQuery)) {
                $message = '❌ Kesalahan sistem: tidak dapat membuat tabel konfirmasi.';
                $messageType = 'error';
            } else {
                // Lanjutkan dengan insert
                $query = "INSERT INTO jadwal_konfirmasi (schedule_id, email, token, confirmed_at)
                          VALUES (?, ?, ?, NOW())
                          ON DUPLICATE KEY UPDATE confirmed_at = NOW()";

                $stmt = mysqli_prepare($conn, $query);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "iss", $schedule_id, $email, $token);

                    if (mysqli_stmt_execute($stmt)) {
                        $message = '✅ Terima kasih! Konfirmasi Anda telah diterima.';
                        $messageType = 'success';
                    } else {
                        $message = '❌ Terjadi kesalahan saat memproses konfirmasi: ' . mysqli_error($conn);
                        $messageType = 'error';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $message = '❌ Terjadi kesalahan dalam preparasi query: ' . mysqli_error($conn);
                    $messageType = 'error';
                }
            }
        } else {
            // Tabel sudah ada, lanjutkan dengan insert
            $query = "INSERT INTO jadwal_konfirmasi (schedule_id, email, token, confirmed_at)
                      VALUES (?, ?, ?, NOW())
                      ON DUPLICATE KEY UPDATE confirmed_at = NOW()";

            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "iss", $schedule_id, $email, $token);

                if (mysqli_stmt_execute($stmt)) {
                    $message = '✅ Terima kasih! Konfirmasi Anda telah diterima.';
                    $messageType = 'success';
                } else {
                    $message = '❌ Terjadi kesalahan saat memproses konfirmasi: ' . mysqli_error($conn);
                    $messageType = 'error';
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = '❌ Terjadi kesalahan dalam preparasi query: ' . mysqli_error($conn);
                $messageType = 'error';
            }
        }
    } else {
        $message = '❌ Data konfirmasi tidak valid.';
        $messageType = 'error';
    }
} elseif (!empty($token) && !empty($email) && $schedule_id > 0) {
    // Cek apakah sudah dikonfirmasi sebelumnya
    $checkQuery = "SELECT id FROM jadwal_konfirmasi WHERE schedule_id = ? AND email = ? AND token = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iss", $schedule_id, $email, $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $message = 'ℹ️ Anda sudah mengkonfirmasi penyelesaian jadwal ini sebelumnya.';
            $messageType = 'info';
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = '❌ Terjadi kesalahan dalam preparasi query.';
        $messageType = 'error';
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024px, initial-scale=1.0, user-scalable=no">
    <title>Konfirmasi Penyelesaian Jadwal - Web Sunsal</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 2rem;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 10px;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.6);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .schedule-info {
            background: rgba(102, 126, 234, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }

        .schedule-info h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .schedule-info p {
            margin: 5px 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Konfirmasi Penyelesaian Jadwal</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($token) && !empty($email) && $schedule_id > 0 && $messageType !== 'success' && $messageType !== 'info'): ?>
            <div class="schedule-info">
                <h3>Detail Jadwal</h3>
                <p><strong>ID Jadwal:</strong> <?php echo $schedule_id; ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            </div>

            <p style="color: #666; margin-bottom: 30px; line-height: 1.6;">
                Terima kasih telah menerima notifikasi penyelesaian jadwal.<br>
                Silakan konfirmasi bahwa Anda telah menerima informasi ini.
            </p>

            <form method="POST">
                <button type="submit" name="confirm" class="btn btn-confirm">
                    ✅ Konfirmasi Diterima
                </button>
            </form>
        <?php elseif ($messageType === 'success' || $messageType === 'info'): ?>
            <div style="margin-top: 30px;">
                <a href="http://localhost/web_sunsal" class="btn btn-back">Kembali ke Web Sunsal</a>
            </div>
        <?php else: ?>
            <div class="message error">
                ❌ Link konfirmasi tidak valid atau telah kedaluwarsa.
            </div>
            <div style="margin-top: 30px;">
                <a href="http://localhost/web_sunsal" class="btn btn-back">Kembali ke Web Sunsal</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>