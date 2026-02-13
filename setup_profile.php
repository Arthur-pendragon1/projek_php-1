<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['oauth_temp'])) {
    redirect('index.php');
}

$temp = $_SESSION['oauth_temp'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($name)) $errors['name'] = 'Name is required';
    if (empty($username)) $errors['username'] = 'Username is required';
    if (empty($password)) $errors['password'] = 'Password is required';
    if ($password !== $confirm_password) $errors['confirm_password'] = 'Passwords do not match';
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors['username'] = 'Username already taken';
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $temp['email'], $hashed_password);
        
        if ($stmt->execute()) {
            $new_user_id = $conn->insert_id;
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['email'] = $temp['email'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            
            // Clear temp data
            unset($_SESSION['oauth_temp']);
            
            flash('success', 'Profile setup complete! Welcome to Web Sunsal.');
            redirect('loading.php');
        } else {
            $errors['general'] = 'Failed to create account';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024px, initial-scale=1.0, user-scalable=no">
    <title>Complete Your Profile | Web Sunsal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: white;
            width: 100%;
            max-width: 420px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: #fff;
            background: rgba(255, 255, 255, 0.2);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #fff;
            color: #667eea;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn:hover {
            background: #f0f0f0;
        }

        .error-message {
            color: #ff6b6b;
            margin-top: 5px;
            font-size: 14px;
        }

        .info-message {
            color: #fff;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Complete Your Profile</h1>
            <p>Finish setting up your account</p>
        </div>
        
        <?php if ($message = flash('success')): ?>
            <div class="info-message">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <input type="text" name="name" class="form-control" placeholder="Full Name" value="<?php echo htmlspecialchars($temp['name']); ?>" required>
                <?php if (isset($errors['name'])): ?>
                    <div class="error-message"><?php echo $errors['name']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo htmlspecialchars($temp['username']); ?>" required>
                <?php if (isset($errors['username'])): ?>
                    <div class="error-message"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($errors['general'])): ?>
                <div class="error-message"><?php echo $errors['general']; ?></div>
            <?php endif; ?>
            
            <button type="submit" class="btn">Complete Setup</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="color: white; text-decoration: none;">Back to Login</a>
        </div>
    </div>
</body>
</html>