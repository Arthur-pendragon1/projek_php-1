<?php
// Manually include PHPMailer classes from PHPMailer-master folder
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendConfirmationEmail($to, $confirmation_code, $type = 'register') {
    $mail = new PHPMailer(true);

    try {
        // Debug level - set to 2 for debugging, 0 for production
        $mail->SMTPDebug = 0; // Set to 0 for production
        $mail->Debugoutput = 'error_log';

        //Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Additional settings for troubleshooting
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Character encoding
        $mail->CharSet = 'UTF-8';

        // Content
        $mail->isHTML(true);
        
        if ($type === 'reset_password') {
            $mail->Subject = 'Reset Password Confirmation Code';
            $mail->Body    = '
                <html>
                <body style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2 style="color: #4f46e5;">Reset Password</h2>
                    <p>Anda telah meminta untuk mereset password. Kode konfirmasi Anda adalah:</p>
                    <h3 style="background-color: #f3f4f6; padding: 10px; text-align: center; font-size: 24px;">' . $confirmation_code . '</h3>
                    <p>Masukkan kode ini untuk melanjutkan proses reset password.</p>
                    <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                </body>
                </html>';
            $mail->AltBody = "Kode konfirmasi reset password Anda adalah: " . $confirmation_code;
        } else {
            $mail->Subject = 'Email Confirmation Code';
            $mail->Body    = '
                <html>
                <body style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2 style="color: #4f46e5;">Email Verification</h2>
                    <p>Terima kasih telah mendaftar! Kode konfirmasi Anda adalah:</p>
                    <h3 style="background-color: #f3f4f6; padding: 10px; text-align: center; font-size: 24px;">' . $confirmation_code . '</h3>
                    <p>Masukkan kode ini untuk memverifikasi email Anda.</p>
                    <p>Jika Anda tidak mendaftar di sistem kami, abaikan email ini.</p>
                </body>
                </html>';
            $mail->AltBody = "Kode konfirmasi Anda adalah: " . $confirmation_code;
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log detailed error information
        error_log("SMTP ERROR - Sending to: " . $to);
        error_log("Error Message: " . $e->getMessage());
        error_log("Mailer Error: " . $mail->ErrorInfo);
        error_log("Debug Output: " . print_r($mail->SMTPDebug, true));
        
        // Fallback to PHP mail() function
        if ($type === 'reset_password') {
            $subject = 'Reset Password Confirmation Code';
            $message = "Kode konfirmasi reset password Anda adalah: " . $confirmation_code . "\n\nMasukkan kode ini untuk melanjutkan proses reset password.\n\nJika Anda tidak meminta reset password, abaikan email ini.";
        } else {
            $subject = 'Email Confirmation Code';
            $message = "Kode konfirmasi Anda adalah: " . $confirmation_code . "\n\nMasukkan kode ini untuk memverifikasi email Anda.\n\nJika Anda tidak mendaftar di sistem kami, abaikan email ini.";
        }
        
        $headers = "From: Modern Auth System <noreply@yourdomain.com>\r\n";
        $headers .= "Reply-To: noreply@yourdomain.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return sendEmail($to, $subject, $message, $headers);
    }
}

function sendScheduleNotification($to, $scheduleData) {
    $mail = new PHPMailer(true);

    try {
        // Debug level - set to 2 for debugging, 0 for production
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'error_log';

        //Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Additional settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Character encoding
        $mail->CharSet = 'UTF-8';

        // Content
        $mail->isHTML(true);
        $mail->Subject = '⏰ Pengingat Jadwal Kegiatan - Web Sunsal';
        $mail->Body    = '
            <html>
            <body style="font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;">
                <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <h2 style="color: #e74c3c; text-align: center;">⏰ Pengingat Jadwal Kegiatan</h2>
                    <p style="color: #666; font-size: 16px; line-height: 1.6;">
                        Halo,<br><br>
                        Ini adalah pengingat untuk jadwal kegiatan yang akan datang di sistem Web Sunsal.
                    </p>
                    <div style="background-color: #fff3cd; border: 2px solid #f39c12; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        <h3 style="color: #2c3e50; margin-top: 0;">' . htmlspecialchars($scheduleData['nama_kegiatan']) . '</h3>
                        <p style="margin: 5px 0;"><strong>📅 Tanggal:</strong> ' . htmlspecialchars($scheduleData['tanggal']) . '</p>
                        <p style="margin: 5px 0;"><strong>⏰ Waktu:</strong> ' . htmlspecialchars($scheduleData['waktu']) . '</p>
                        <p style="margin: 5px 0;"><strong>📝 Deskripsi:</strong> ' . nl2br(htmlspecialchars($scheduleData['deskripsi'])) . '</p>
                    </div>
                    <p style="color: #666; font-size: 14px;">
                        Pastikan Anda mempersiapkan segala sesuatu yang diperlukan untuk kegiatan ini.
                    </p>
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="http://localhost/web_sunsal" style="background-color: #e74c3c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">Lihat Detail Lengkap</a>
                    </div>
                    <p style="color: #999; font-size: 12px; text-align: center; margin-top: 30px;">
                        Email ini dikirim secara otomatis oleh sistem Web Sunsal sebagai pengingat.<br>
                        Jika Anda tidak ingin menerima pengingat ini, silakan hubungi administrator.
                    </p>
                </div>
            </body>
            </html>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

function sendCompletionNotification($to, $scheduleData, $scheduleId = null) {
    $mail = new PHPMailer(true);

    // Generate unique token for confirmation
    $token = bin2hex(random_bytes(32));

    try {
        // Debug level - set to 2 for debugging, 0 for production
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'error_log';

        //Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Additional settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Character encoding
        $mail->CharSet = 'UTF-8';

        // Content
        $mail->isHTML(true);
        $mail->Subject = '✅ Jadwal Kegiatan Telah Selesai - Web Sunsal';
        $confirmationUrl = "http://localhost/web_sunsal/confirm_schedule.php?token=" . urlencode($token) . "&email=" . urlencode($to) . "&id=" . ($scheduleId ?? '');
        $mail->Body    = '
            <html>
            <body style="font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;">
                <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <h2 style="color: #27ae60; text-align: center;">✅ Jadwal Kegiatan Telah Selesai</h2>
                    <p style="color: #666; font-size: 16px; line-height: 1.6;">
                        Halo,<br><br>
                        Kami ingin memberitahu bahwa jadwal kegiatan berikut telah berhasil diselesaikan.
                    </p>
                    <div style="background-color: #d4edda; border: 2px solid #27ae60; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        <h3 style="color: #155724; margin-top: 0;">' . htmlspecialchars($scheduleData['nama_kegiatan']) . '</h3>
                        <p style="margin: 5px 0;"><strong>📅 Tanggal:</strong> ' . htmlspecialchars($scheduleData['tanggal']) . '</p>
                        <p style="margin: 5px 0;"><strong>⏰ Waktu:</strong> ' . htmlspecialchars($scheduleData['waktu']) . '</p>
                        <p style="margin: 5px 0;"><strong>📝 Deskripsi:</strong> ' . nl2br(htmlspecialchars($scheduleData['deskripsi'])) . '</p>
                        <p style="margin: 10px 0; color: #155724; font-weight: bold;"><strong>✅ Status: SELESAI</strong></p>
                    </div>
                    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
                        <h3 style="color: #2c3e50; margin: 0 0 10px 0;">Terima Kasih! 🙏</h3>
                        <p style="color: #666; margin: 0; font-size: 16px;">
                            Atas partisipasi dan kontribusi Anda dalam menyelesaikan kegiatan ini.<br>
                            Semoga kegiatan ini memberikan manfaat yang besar bagi kita semua.
                        </p>
                    </div>
                    <div style="background-color: #fff3cd; border: 2px solid #f39c12; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
                        <h3 style="color: #8b4513; margin: 0 0 15px 0;">📋 Konfirmasi Penerimaan</h3>
                        <p style="color: #856404; margin: 0 0 20px 0; font-size: 14px;">
                            Jika Anda tidak dapat mengakses web, silakan klik tombol di bawah ini untuk mengkonfirmasi bahwa Anda telah menerima notifikasi ini.
                        </p>
                        <a href="' . $confirmationUrl . '" style="background-color: #f39c12; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">✅ Konfirmasi Diterima</a>
                    </div>
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="http://localhost/web_sunsal" style="background-color: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">Lihat Jadwal Lainnya</a>
                    </div>
                    <p style="color: #999; font-size: 12px; text-align: center; margin-top: 30px;">
                        Email ini dikirim secara otomatis oleh sistem Web Sunsal.<br>
                        Jika Anda memiliki pertanyaan, silakan hubungi administrator.
                    </p>
                </div>
            </body>
            </html>';

        $mail->send();
        return ['success' => true, 'token' => $token];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
