<?php
// admin_portal.php – Admin login

session_start();
require_once 'DB_Conn/config.php';

$logoutMessage = '';
if (isset($_SESSION['logout_message'])) {
    $logoutMessage = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

$successMessage = '';
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

$errors = [];
$identifier = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier)) $errors[] = 'Account number is required.';
    if (empty($password)) $errors[] = 'Password cannot be empty.';

    if (empty($errors)) {
        // Query admin table – only one placeholder needed
        $stmt = $pdo->prepare("SELECT id, password, acc_number FROM admin WHERE acc_number = ?");
        $stmt->execute([$identifier]);
        $admin = $stmt->fetch();

        if ($admin) {
            // Add pepper to the entered password
            $pepperedPassword = $password . PEPPER; // PEPPER constant must be defined in config.php

            if (password_verify($pepperedPassword, $admin['password'])) {
                // Login successful – regenerate session ID
                session_regenerate_id(true);
                // Set admin-specific session variables
                $_SESSION['admin_id']         = $admin['id'];
                $_SESSION['admin_acc_number'] = $admin['acc_number'];
                $_SESSION['role']             = 'admin';

                // Redirect to admin dashboard
                header('Location: Admin/Dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid account number or password.';
            }
        } else {
            $errors[] = 'Invalid account number or password.';
        }
    }
}
// If there were errors, the form will be shown again (you need to add HTML form below)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameEarn · Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: #0b0e1a;
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            background: rgba(10, 15, 30, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid #f5b34230;
        }
        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(145deg, #f5b342, #ffdd88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .auth-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        .auth-card {
            background: #1a1f30;
            border-radius: 30px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border: 2px solid #2a2f40;
            box-shadow: 0 20px 30px rgba(0,0,0,0.5);
        }
        .auth-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
            text-align: center;
        }
        .auth-title span {
            color: #f5b342;
        }
        .auth-sub {
            text-align: center;
            color: #b0b8d4;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #f5b342;
        }
        .form-group input {
            width: 100%;
            padding: 15px;
            background: #0f1425;
            border: 2px solid #2a2f40;
            border-radius: 15px;
            color: #fff;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
        }
        .form-group input:focus {
            border-color: #f5b342;
        }
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            flex: 1;
            padding-right: 40px;
        }
        .password-wrapper i {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #b0b8d4;
            transition: color 0.3s;
            font-size: 18px;
        }
        .password-wrapper i:hover {
            color: #f5b342;
        }
        .forgot-password {
            text-align: right;
            margin-top: 5px;
        }
        .forgot-password a {
            color: #b0b8d4;
            font-size: 14px;
            text-decoration: none;
        }
        .forgot-password a:hover {
            color: #f5b342;
        }
        .btn-primary {
            width: 100%;
            background: #f5b342;
            border: none;
            padding: 15px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 18px;
            color: #0b0e1a;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 10px 20px #f5b34240;
            margin-top: 10px;
        }
        .btn-primary:hover {
            background: #ffcc66;
            transform: translateY(-2px);
        }
        .auth-footer {
            text-align: center;
            margin-top: 25px;
            color: #b0b8d4;
        }
        .auth-footer a {
            color: #f5b342;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        footer {
            background: #0a0f1a;
            padding: 20px 5%;
            text-align: center;
            border-top: 2px solid #2a2f40;
        }
        .copyright {
            color: #b0b8d4;
            font-size: 14px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">GAME<span style="color:#fff; -webkit-text-fill-color: initial;">EARN</span></div>
    </nav>

    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Welcome <span>ADMIN</span></h2>
            <p class="auth-sub">Sign in to continue earning</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($logoutMessage)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($logoutMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group">
                    <label>Account Number</label>
                    <input type="text" name="identifier" placeholder="GE-123456 " value="<?php echo htmlspecialchars($identifier); ?>" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                        <i class="fas fa-eye" onclick="togglePassword('password', this)"></i>
                    </div>
                    <div class="forgot-password">
                        <a href="forgot_password.php">Forgot password?</a>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Sign In</button>

                <div class="auth-footer">
                    Don't have an account? <a href="registration.php">Register now</a>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="copyright">
            <p>© 2025 GameEarn. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function togglePassword(fieldId, icon) {
            const field = document.getElementById(fieldId);
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>