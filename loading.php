<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in, if not redirect to login
if (!isLoggedIn()) {
    redirect('index.php');
}

// Get user info
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';
$user_id = $_SESSION['user_id'] ?? null;

// Get user avatar path
$avatar_path = '';
if ($user_id) {
    $avatar_dir = 'uploads/avatars/';
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ($extensions as $ext) {
        $file = $avatar_dir . 'user_' . $user_id . '.' . $ext;
        if (file_exists($file)) {
            $avatar_path = $file;
            break;
        }
    }
}

// If user is logged in, redirect to dashboard after animation
if (isset($_SESSION['user_id'])) {
    // Add a delay for animation
    echo "<script>
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 3000); // 3 seconds delay
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        if ($role === 'admin') {
            echo "Welcome, Admin $username | Web Sunsal";
        } else {
            echo "Logging In, $username... | Web Sunsal";
        }
        ?>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            font-family: 'Montserrat', sans-serif;
            color: white;
            margin: 0;
        }

        .loading-container {
            text-align: center;
            animation: fadeIn 1s ease-in;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            margin-bottom: 20px;
            object-fit: cover;
            animation: slideUpBounce 1.2s ease-out;
        }

        .loading-spinner {
            width: 80px;
            height: 80px;
            border: 6px solid rgba(255, 255, 255, 0.3);
            border-top: 6px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .loading-text {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            animation: pulse 2s infinite;
        }

        .loading-subtext {
            font-size: 16px;
            opacity: 0.8;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .progress-bar {
            width: 300px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin: 20px auto;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #fff;
            border-radius: 2px;
            animation: fill 3s ease-in-out;
        }

        @keyframes fill {
            0% { width: 0%; }
            100% { width: 100%; }
        }

        @keyframes slideUpBounce {
            0% {
                opacity: 0;
                transform: translateY(50px) scale(0.8);
            }
            60% {
                opacity: 1;
                transform: translateY(-10px) scale(1.1);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <?php if ($avatar_path): ?>
            <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Profile Picture" class="profile-avatar">
        <?php endif; ?>
        <div class="loading-spinner"></div>
        <div class="loading-text">
            <?php
            if ($role === 'admin') {
                echo "Selamat datang, Admin $username!";
            } else {
                echo "Logging you in, $username...";
            }
            ?>
        </div>
        <div class="loading-subtext">
            <?php
            if ($role === 'admin') {
                echo "Preparing your admin dashboard";
            } else {
                echo "Please wait while we prepare your dashboard";
            }
            ?>
        </div>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
    </div>
</body>
</html>