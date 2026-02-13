<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['email_to_verify'])) {
    redirect('index.php');
}

$errors = [];
$email = $_SESSION['email_to_verify'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_code = trim($_POST['confirmation_code']);

    if (empty($input_code)) {
        $errors['confirmation_code'] = 'Confirmation code is required';
    } else {
        // Check code in database
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND confirmation_code = ? AND verified = 0");
        $stmt->bind_param("ss", $email, $input_code);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            // Update verified to 1
            $stmt_update = $conn->prepare("UPDATE users SET verified = 1, confirmation_code = NULL WHERE email = ?");
            $stmt_update->bind_param("s", $email);
            if ($stmt_update->execute()) {
                unset($_SESSION['email_to_verify']);
                flash('register_success', 'Email confirmed successfully! You can now login.');
                echo "<script>alert('Code berhasil'); window.location.href='index.php';</script>";
                exit;
            } else {
                $errors['general'] = 'Failed to update confirmation status. Please try again.';
            }
        } else {
            $errors['confirmation_code'] = 'Invalid confirmation code or email already confirmed.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024px, initial-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap">
    <title>Email Verification | Web Sunsal</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #c9d6ff;
            background: linear-gradient(to right, #e2e2e2, #c9d6ff);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .verification-container {
            background-color: #fff;
            border-radius: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            position: relative;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f0f0f0;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: #333;
            font-size: 18px;
        }

        .back-button:hover {
            background-color: #e0e0e0;
            transform: translateX(-5px);
        }

        .icon-wrapper {
            text-align: center;
            margin-bottom: 30px;
        }

        .icon-wrapper i {
            font-size: 50px;
            color: #512da8;
        }

        .verification-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }

        .verification-container > p {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
            background-color: #f5f5f5;
        }

        .form-group input:focus {
            outline: none;
            border-color: #512da8;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(81, 45, 168, 0.1);
        }

        .form-group input::placeholder {
            color: #999;
        }

        .error-text {
            color: #ef4444;
            font-size: 12px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .error-message {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 16px;
        }

        .success-message {
            background-color: #dcfce7;
            border-left: 4px solid #22c55e;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message i {
            font-size: 16px;
        }

        .verification-code-tips {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            color: #92400e;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .verification-code-tips i {
            margin-right: 8px;
            color: #f59e0b;
        }

        .verification-code-tips strong {
            display: block;
            margin-bottom: 5px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #512da8 0%, #5c6bc0 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(81, 45, 168, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 13px;
        }

        .form-footer a {
            color: #512da8;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .form-footer a:hover {
            color: #5c6bc0;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .verification-container {
                padding: 30px 20px;
            }

            .verification-container h1 {
                font-size: 24px;
            }

            .back-button {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }

            .icon-wrapper i {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <button class="back-button" onclick="window.location.href='index.php'" title="Back to Login">
        <i class="fas fa-arrow-left"></i>
    </button>

    <div class="verification-container">
        <div class="icon-wrapper">
            <i class="fas fa-envelope-open-text"></i>
        </div>

        <h1>Email Verification</h1>
        <p>Enter the 6-digit code sent to your email<br><strong><?php echo htmlspecialchars($email); ?></strong></p>

        <form action="" method="POST">
            <div class="verification-code-tips">
                <i class="fas fa-lightbulb"></i>
                <strong>💡 Cek Email Anda</strong>
                Periksa inbox atau folder spam untuk kode konfirmasi.
            </div>

            <?php if (isset($errors['general'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="confirmation_code">Confirmation Code</label>
                <input 
                    type="text" 
                    id="confirmation_code" 
                    name="confirmation_code" 
                    placeholder="Enter 6-character code" 
                    required 
                    autocomplete="off"
                    maxlength="6"
                />
                <?php if (isset($errors['confirmation_code'])): ?>
                    <div class="error-text">
                        <i class="fas fa-times-circle"></i>
                        <?php echo htmlspecialchars($errors['confirmation_code']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn">Verify Email</button>
        </form>

        <div class="form-footer">
            Back to Login? <a href="index.php">Sign In Here</a>
        </div>
    </div>
</body>
</html>
