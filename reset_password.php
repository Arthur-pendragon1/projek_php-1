<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$errors = [];
$success = '';

if (!isset($_SESSION['email_to_verify'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['email_to_verify'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmation_code = trim($_POST['confirmation_code']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($confirmation_code)) {
        $errors['confirmation_code'] = 'Kode konfirmasi harus diisi.';
    }
    if (empty($new_password)) {
        $errors['new_password'] = 'Password baru harus diisi.';
    }
    if ($new_password !== $confirm_password) {
        $errors['confirm_password'] = 'Konfirmasi password tidak cocok.';
    }

    if (empty($errors)) {
        // Verify confirmation code
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND confirmation_code = ?");
        $stmt->bind_param("ss", $email, $confirmation_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Update password and clear confirmation code
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password = ?, confirmation_code = NULL WHERE email = ?");
            $stmt_update->bind_param("ss", $hashed_password, $email);
            if ($stmt_update->execute()) {
                unset($_SESSION['email_to_verify']);
                $success = 'Password berhasil diubah. Silakan <a href="index.php">login</a> dengan password baru Anda.';
                echo "<script>alert('Code berhasil');</script>";
            } else {
                $errors['general'] = 'Gagal memperbarui password. Silakan coba lagi.';
            }
        } else {
            $errors['confirmation_code'] = 'Kode konfirmasi salah atau sudah digunakan.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=1024px, initial-scale=1, user-scalable=no" />
    <title>Reset Password | Web Sunsal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap">
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
            flex-direction: column;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: #fff;
            border-radius: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 550px;
            padding: 50px 40px;
            text-align: center;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #f0f0f0;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: #333;
            font-size: 20px;
        }

        .back-button:hover {
            background: #512da8;
            color: white;
            transform: scale(1.1);
        }

        h1 {
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
            font-size: 2em;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input.form-control {
            width: 100%;
            padding: 15px 20px;
            background: #f5f5f5;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            color: #333;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }

        input.form-control:focus {
            outline: none;
            border-color: #512da8;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(81, 45, 168, 0.1);
        }

        input.form-control::placeholder {
            color: #999;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #512da8 0%, #5c6bc0 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 15px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(81, 45, 168, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .error-text {
            color: #ef4444;
            font-size: 13px;
            margin-top: 8px;
            display: block;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #ef4444;
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #4ade80;
        }

        .success-message a {
            color: #166534;
            font-weight: 600;
            text-decoration: underline;
        }

        .success-message a:hover {
            color: #15803d;
        }

        .form-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #666;
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

        .icon-wrapper {
            font-size: 50px;
            margin-bottom: 15px;
            color: #512da8;
        }

        .password-requirement {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: left;
            font-size: 13px;
            color: #666;
        }

        .password-requirement ul {
            margin-left: 20px;
            line-height: 1.8;
        }

        .password-requirement li {
            margin: 5px 0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 40px 25px;
                max-width: 100%;
            }

            h1 {
                font-size: 1.6em;
            }

            input.form-control {
                padding: 12px 15px;
                font-size: 14px;
            }

            .btn {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button" title="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="icon-wrapper">
            <i class="fas fa-key"></i>
        </div>
        
        <h1>Reset Password</h1>
        
        <?php if (!empty($success)): ?>
            <p class="subtitle">Proses reset password selesai!</p>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php else: ?>
            <p class="subtitle">
                Masukkan kode konfirmasi yang telah dikirim ke email Anda, lalu buat password baru
            </p>

            <?php if (isset($errors['general'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="confirmation_code">Kode Konfirmasi</label>
                    <input 
                        type="text" 
                        id="confirmation_code" 
                        name="confirmation_code" 
                        class="form-control" 
                        placeholder="Masukkan 6 digit kode konfirmasi" 
                        required 
                        autocomplete="off"
                    >
                    <?php if (isset($errors['confirmation_code'])): ?>
                        <span class="error-text">
                            <i class="fas fa-times-circle"></i> <?php echo $errors['confirmation_code']; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-control" 
                        placeholder="Masukkan password baru (min. 6 karakter)" 
                        required 
                        autocomplete="off"
                    >
                    <?php if (isset($errors['new_password'])): ?>
                        <span class="error-text">
                            <i class="fas fa-times-circle"></i> <?php echo $errors['new_password']; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control" 
                        placeholder="Ulangi password baru Anda" 
                        required 
                        autocomplete="off"
                    >
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="error-text">
                            <i class="fas fa-times-circle"></i> <?php echo $errors['confirm_password']; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-redo"></i> Reset Password
                </button>
            </form>

            <div class="password-requirement">
                <strong style="color: #333;">💡 Tips Password Aman:</strong>
                <ul>
                    <li>Gunakan minimal 8 karakter</li>
                    <li>Campurkan huruf besar dan kecil</li>
                    <li>Sertakan angka dan simbol (!@#$%)</li>
                    <li>Jangan gunakan informasi pribadi</li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="form-footer">
            Ingin kembali? <a href="index.php">Ke Halaman Login</a>
        </div>
    </div>

    <footer style="text-align: center; padding: 10px; background: rgba(255, 255, 255, 0.3); border-top: 1px solid rgba(255, 255, 255, 0.2); position: fixed; bottom: 0; width: 100%; z-index: 10; font-size: 12px; color: #f093fb;">
        <p>&copy; 2026 Muhammad Rifqi Andrian. All rights reserved.</p>
    </footer>
</body>
</html>
