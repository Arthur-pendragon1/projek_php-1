<?php
/**
 * Script untuk mengirim email pengingat jadwal pada waktu yang ditentukan
 * Jalankan script ini secara berkala menggunakan cronjob atau scheduler
 * 
 * Contoh cronjob (setiap 5 menit):
 * */5 * * * * curl -s http://localhost/web_sunsal/send_schedule_reminders.php > /dev/null 2>&1
 */

require_once 'jadual/config.php';
require_once 'includes/functions.php';
require_once 'includes/send_mail.php';

// Header untuk memastikan output JSON
header('Content-Type: application/json');

// Cek parameter authentication (optional, untuk keamanan)
$secretKey = isset($_GET['key']) ? $_GET['key'] : '';
// Ganti dengan key yang aman
$validKey = 'your-secret-key-for-reminders';

// Jika ada parameter key, validasi
if (!empty($_GET['key']) && $secretKey !== $validKey) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Cek apakah tabel jadwal_reminder ada, jika tidak buat
    $checkTableQuery = "SHOW TABLES LIKE 'jadwal_reminder'";
    $tableResult = mysqli_query($conn, $checkTableQuery);
    
    if (!$tableResult || mysqli_num_rows($tableResult) === 0) {
        // Buat tabel jadwal_reminder
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
        
        if (!mysqli_query($conn, $createTableQuery)) {
            throw new Exception("Gagal membuat tabel jadwal_reminder: " . mysqli_error($conn));
        }
    }
    
    // Ambil semua pengingat yang belum dikirim dan sudah saatnya dikirim
    $currentDateTime = date('Y-m-d H:i:s');
    $query = "SELECT * FROM jadwal_reminder 
              WHERE sent_at IS NULL 
              AND reminder_datetime <= '$currentDateTime'
              ORDER BY reminder_datetime ASC
              LIMIT 50";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Gagal query jadwal_reminder: " . mysqli_error($conn));
    }
    
    $sentCount = 0;
    $failedCount = 0;
    $errors = [];
    
    if (mysqli_num_rows($result) > 0) {
        while ($reminder = mysqli_fetch_assoc($result)) {
            try {
                // Data jadwal untuk email
                $scheduleData = [
                    'nama_kegiatan' => $reminder['nama_kegiatan'],
                    'tanggal' => $reminder['tanggal'],
                    'waktu' => $reminder['waktu'],
                    'deskripsi' => '(Notifikasi pengingat pada waktu yang ditentukan)'
                ];
                
                // Kirim email pengingat
                $emailSent = sendScheduleReminderEmail($reminder['email'], $scheduleData);
                
                if ($emailSent) {
                    // Update record bahwa email sudah dikirim
                    $updateQuery = "UPDATE jadwal_reminder SET sent_at = NOW() WHERE id = " . intval($reminder['id']);
                    
                    if (mysqli_query($conn, $updateQuery)) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Gagal update record ID " . $reminder['id'];
                    }
                } else {
                    $failedCount++;
                    $errors[] = "Gagal kirim email ke " . $reminder['email'];
                }
                
            } catch (Exception $e) {
                $failedCount++;
                $errors[] = "Error untuk " . $reminder['email'] . ": " . $e->getMessage();
            }
        }
    }
    
    $response = [
        'success' => true,
        'message' => "Pengingat jadwal terkirim ($sentCount sent, $failedCount failed)",
        'sent' => $sentCount,
        'failed' => $failedCount,
        'errors' => $errors,
        'current_time' => $currentDateTime
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);

/**
 * Fungsi untuk mengirim email pengingat jadwal
 */
function sendScheduleReminderEmail($to, $scheduleData) {
    require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
    require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
    require_once __DIR__ . '/includes/email_config.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'error_log';
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        
        $mail->Subject = '🔔 PENGINGAT JADWAL - ' . htmlspecialchars($scheduleData['nama_kegiatan']);
        $mail->Body = '
            <html>
            <body style="font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;">
                <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <h2 style="color: #e74c3c; text-align: center;">🔔 PENGINGAT JADWAL KEGIATAN</h2>
                    <p style="color: #666; font-size: 16px; line-height: 1.6; text-align: center;">
                        <strong>Waktu kegiatan sudah tiba!</strong>
                    </p>
                    <div style="background-color: #ffe5e5; border: 3px solid #e74c3c; padding: 25px; border-radius: 8px; margin: 25px 0;">
                        <h3 style="color: #c0392b; margin-top: 0; font-size: 18px;">📌 ' . htmlspecialchars($scheduleData['nama_kegiatan']) . '</h3>
                        <hr style="border: none; border-top: 2px solid #e74c3c; margin: 15px 0;">
                        <p style="margin: 10px 0; font-size: 16px;"><strong>📅 Tanggal:</strong> ' . htmlspecialchars($scheduleData['tanggal']) . '</p>
                        <p style="margin: 10px 0; font-size: 16px;"><strong>⏰ Waktu:</strong> ' . htmlspecialchars($scheduleData['waktu']) . '</p>
                        <p style="margin: 10px 0; font-size: 16px;"><strong>📝 Status:</strong> Kegiatan dimulai sekarang!</p>
                    </div>
                    <div style="background-color: #e8f4f8; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3498db;">
                        <p style="margin: 0; color: #2c3e50;">
                            Pengingat ini dikirim pada waktu yang telah ditentukan oleh administrator. 
                            Pastikan Anda siap dan hadir untuk mengikuti kegiatan ini.
                        </p>
                    </div>
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="http://localhost/web_sunsal" style="background-color: #e74c3c; color: white; padding: 14px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Buka Web Sunsal</a>
                    </div>
                    <p style="color: #999; font-size: 12px; text-align: center; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
                        Email ini dikirim secara otomatis oleh sistem Web Sunsal pada waktu yang ditentukan.<br>
                        Jika ada pertanyaan, silakan hubungi administrator.
                    </p>
                </div>
            </body>
            </html>';

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Reminder email failed to: " . $to . " Error: " . $e->getMessage());
        return false;
    }
}
?>
