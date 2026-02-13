 <?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password']) && !isset($_POST['name'])) {
        // Login
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Prepare statement
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user';
            
            // Load avatar from database if exists
            if (!empty($user['avatar'])) {
                $_SESSION['avatar'] = $user['avatar'];
            }
            
            redirect('loading.php');
        } else {
            flash('login_error', 'Username atau password salah!');
        }
    } elseif (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password'])) {
        // Register
        $username = trim($_POST['name']); // Assuming name is username
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Validasi
        if (empty($username)) $errors['username'] = 'Username is required';
        if (empty($email)) $errors['email'] = 'Email is required';
        if (empty($password)) $errors['password'] = 'Password is required';
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'Email already registered';
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate confirmation code
            $confirmation_code = bin2hex(random_bytes(3));
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, confirmation_code) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $confirmation_code);
            
            if ($stmt->execute()) {
                // Send confirmation code to user's email
                require_once 'includes/send_mail.php';

                $mail_sent = sendConfirmationEmail($email, $confirmation_code);

                if ($mail_sent) {
                    // Store email in session for verification page
                    $_SESSION['email_to_verify'] = $email;
                    flash('success', 'Registration successful! Please check your email for confirmation code.');
                    redirect('verify_email.php');
                } else {
                    error_log("Failed to send email to $email");
                    $errors['general'] = 'Failed to send confirmation email. Please try again.';
                }
            } else {
                flash('error', 'Registration failed!');
            }
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
    <title>Modern Login Page | Web Sunsal</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body{
            background-color: #c9d6ff;
            background: linear-gradient(to right, #e2e2e2, #c9d6ff);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100vh;
        }

        .container{
            background-color: #fff;
            border-radius: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            width: 768px;
            max-width: 100%;
            min-height: 480px;
        }

        .container p{
            font-size: 14px;
            line-height: 20px;
            letter-spacing: 0.3px;
            margin: 20px 0;
        }

        .container span{
            font-size: 12px;
        }

        .container a{
            color: #333;
            font-size: 13px;
            text-decoration: none;
            margin: 15px 0 10px;
        }

        .container button{
            background-color: #512da8;
            color: #fff;
            font-size: 12px;
            padding: 10px 45px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 10px;
            cursor: pointer;
        }

        .container button.hidden{
            background-color: transparent;
            border-color: #fff;
        }

        .container form{
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            height: 100%;
        }

        .container input{
            background-color: #eee;
            border: none;
            margin: 8px 0;
            padding: 10px 15px;
            font-size: 13px;
            border-radius: 8px;
            width: 100%;
            outline: none;
        }

        .form-container{
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        .sign-in{
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .container.active .sign-in{
            transform: translateX(100%);
        }

        .sign-up{
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }

        .container.active .sign-up{
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: move 0.6s;
        }

        @keyframes move{
            0%, 49.99%{
                opacity: 0;
                z-index: 1;
            }
            50%, 100%{
                opacity: 1;
                z-index: 5;
            }
        }

        .social-icons{
            margin: 20px 0;
        }

        .social-icons a{
            border: 1px solid #ccc;
            border-radius: 20%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 3px;
            width: 40px;
            height: 40px;
        }

        .toggle-container{
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: all 0.6s ease-in-out;
            border-radius: 150px 0 0 100px;
            z-index: 1000;
        }

        .container.active .toggle-container{
            transform: translateX(-100%);
            border-radius: 0 150px 100px 0;
        }

        .toggle{
            background-color: #512da8;
            height: 100%;
            background: linear-gradient(to right, #5c6bc0, #512da8);
            color: #fff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .container.active .toggle{
            transform: translateX(50%);
        }

        .toggle-panel{
            position: absolute;
            width: 50%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 30px;
            text-align: center;
            top: 0;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .toggle-left{
            transform: translateX(-200%);
        }

        .container.active .toggle-left{
            transform: translateX(0);
        }

        .toggle-right{
            right: 0;
            transform: translateX(0);
        }

        .container.active .toggle-right{
            transform: translateX(200%);
        }

        .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: 5px;
        }

        .forgot-password-btn {
            background-color: #6c757d;
            color: white !important;
            padding: 12px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            text-decoration: none !important;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .forgot-password-btn:hover {
            background-color: #5a6268;
            transform: scale(1.05);
        }

        .forgot-password-btn:active {
            transform: scale(0.98);
        }
    </style>
</head>

<body>

    <div class="container" id="container">
        <div class="form-container sign-up">
            <form method="POST" action="">
                <h1>Create Account</h1>
                <div class="social-icons">
                    <a href="oauth_init.php?provider=google" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                    <a href="oauth_init.php?provider=github" class="icon"><i class="fa-brands fa-github"></i></a>
                </div>
                <span>or use your email for registration</span>
                <input type="text" name="name" placeholder="Name" required autocomplete="off">
                <input type="email" name="email" placeholder="Email" required autocomplete="off">
                <input type="password" name="password" placeholder="Password" required autocomplete="off">
                <?php if (isset($errors['username'])): ?>
                    <div class="error-message"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
                <?php if (isset($errors['general'])): ?>
                    <div class="error-message"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                <button type="submit">Sign Up</button>
            </form>
        </div>
        <div class="form-container sign-in">
            <form method="POST" action="">
                <h1>Sign In</h1>
                <div class="social-icons">
                    <a href="oauth_init.php?provider=google" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                    <a href="oauth_init.php?provider=github" class="icon"><i class="fa-brands fa-github"></i></a>
                </div>
                <span>or use your username password</span>
                <input type="text" name="username" placeholder="Username" required autocomplete="off">
                <input type="password" name="password" placeholder="Password" required autocomplete="off">
                <?php if ($message = flash('login_error')): ?>
                    <div class="error-message"><?php echo $message; ?></div>
                <?php endif; ?>
                <div style="display: flex; gap: 10px; width: 100%; margin-top: 15px;">
                    <button type="submit" style="flex: 1;">Sign In</button>
                    <a href="forgot_password.php" class="forgot-password-btn">Lupa Password?</a>
                </div>
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all of site features</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Friend!</h1>
                    <p>selamat datang di web sunsal DIT</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        registerBtn.addEventListener('click', () => {
            container.classList.add("active");
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
        });
    </script>
    <footer style="text-align: center; padding: 10px; background: rgba(255, 255, 255, 0.3); border-top: 1px solid rgba(255, 255, 255, 0.2); position: fixed; bottom: 0; width: 100%; z-index: 10; font-size: 12px; color: #f093fb;">
        <p>&copy; 2026 Muhammad Rifqi Andrian. All rights reserved.</p>
    </footer>
</body>

</html>
