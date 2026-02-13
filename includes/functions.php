<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect to a page
function redirect($page) {
    header("Location: " . $page);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Flash message system
function flash($name = '', $message = '') {
    if (!empty($name)) {
        if (!empty($message)) {
            $_SESSION[$name] = $message;
        } else {
            if (isset($_SESSION[$name])) {
                $message = $_SESSION[$name];
                unset($_SESSION[$name]);
                return $message;
            }
        }
    }
}

// Generate a random confirmation code of given length
function generateConfirmationCode($length = 6) {
    try {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        } else {
            // Fallback to less secure method
            $characters = '0123456789abcdef';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }
    } catch (Exception $e) {
        // Fallback to less secure method on error
        $characters = '0123456789abcdef';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

// Send email using PHPMailer
function sendEmail($to, $subject, $message) {
    require_once __DIR__ . '/email_config.php';
    require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'error_log';
        
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
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
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
