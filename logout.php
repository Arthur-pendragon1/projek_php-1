<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Destroy session
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out | Web Sunsal</title>
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
        <div class="loading-spinner"></div>
        <div class="loading-text">
            Logging you out...
        </div>
        <div class="loading-subtext">
            Please wait while we securely log you out
        </div>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 3000); // 3 seconds delay
    </script>
</body>
</html>
