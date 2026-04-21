<?php
// change_password.php – Change Password for Villaruz Print Shop & General Merchandise

require_once __DIR__ . '/DB_Conn/config.php';
require_once __DIR__ . '/Mail/phpmailer/PHPMailerAutoload.php';

function sendPasswordChangeNotification($email, $fullname = '')
{
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Username = 'villaruzprintshop@gmail.com';
    $mail->Password = 'ydyu zfbg onec qmmu';
    $mail->setFrom('villaruzprintshop@gmail.com', 'Villaruz Print Shop');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Password Changed Successfully - Villaruz Print Shop";
    $mail->Body = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Password Changed Successfully</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 40px 20px;
            color: #1e293b;
        }
        
        .email-wrapper {
            max-width: 580px;
            margin: 0 auto;
        }
        
        .email-container {
            background: #ffffff;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .email-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-size: cover;
            opacity: 0.1;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        
        .logo-icon span {
            font-size: 36px;
        }
        
        .email-header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }
        
        .email-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin: 0;
        }
        
        .email-body {
            padding: 40px 35px;
        }
        
        .greeting {
            margin-bottom: 25px;
        }
        
        .greeting h2 {
            font-size: 24px;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        
        .greeting p {
            color: #64748b;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .success-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            border: 1px solid #bbf7d0;
        }
        
        .check-circle {
            width: 80px;
            height: 80px;
            background: #22c55e;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px -5px rgba(34, 197, 94, 0.3);
        }
        
        .check-circle svg {
            width: 45px;
            height: 45px;
            color: white;
        }
        
        .success-card h3 {
            font-size: 22px;
            font-weight: 700;
            color: #166534;
            margin: 0 0 10px 0;
        }
        
        .success-card p {
            color: #15803d;
            font-size: 14px;
            margin: 0;
        }
        
        .info-text {
            color: #475569;
            font-size: 15px;
            line-height: 1.6;
            margin: 20px 0;
        }
        
        .security-box {
            background: #fefce8;
            border-radius: 20px;
            padding: 25px;
            margin: 25px 0;
            border: 1px solid #fde047;
        }
        
        .security-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .security-icon {
            width: 40px;
            height: 40px;
            background: #eab308;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .security-header h4 {
            font-size: 18px;
            font-weight: 600;
            color: #854d0e;
            margin: 0;
        }
        
        .security-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .security-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            color: #713f12;
            font-size: 14px;
            border-bottom: 1px solid #fef08a;
        }
        
        .security-list li:last-child {
            border-bottom: none;
        }
        
        .security-list li::before {
            content: '✓';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            background: #eab308;
            border-radius: 50%;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .help-box {
            background: #eff6ff;
            border-radius: 20px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        
        .help-box p {
            margin: 0 0 15px 0;
            color: #1e40af;
            font-size: 14px;
        }
        
        .contact-btn {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e2e8f0, transparent);
            margin: 30px 0 20px;
        }
        
        .footer {
            background: #f8fafc;
            padding: 30px 35px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer p {
            color: #64748b;
            font-size: 12px;
            margin: 8px 0;
        }
        
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }
        
        .social-links a {
            color: #94a3b8;
            font-size: 18px;
            transition: color 0.2s ease;
        }
        
        .social-links a:hover {
            color: #3b82f6;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 20px 15px;
            }
            
            .email-body {
                padding: 30px 25px;
            }
            
            .footer {
                padding: 25px;
            }
            
            .success-card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class='email-wrapper'>
        <div class='email-container'>
            <div class='email-header'>
                <div class='logo-icon'>
                    <span>🖨️</span>
                </div>
                <h1>Password Updated</h1>
                <p>Your account security is our priority</p>
            </div>
            
            <div class='email-body'>
                <div class='greeting'>
                    <h2>Hello, " . htmlspecialchars($fullname ?: $email) . "</h2>
                    <p>We're writing to confirm that your password has been successfully changed.</p>
                </div>
                
                <div class='success-card'>
                    <div class='check-circle'>
                        <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M5 13l4 4L19 7' />
                        </svg>
                    </div>
                    <h3>Password Changed ✓</h3>
                    <p>Your password was updated successfully on " . date('F j, Y \a\t g:i A') . "</p>
                </div>
                
                <div class='info-text'>
                    This is a confirmation that the password for your <strong>Villaruz Print Shop</strong> account has been changed. If you made this change, you can safely ignore this email.
                </div>
                
                <div class='security-box'>
                    <div class='security-header'>
                        <div class='security-icon'>🔒</div>
                        <h4>Security Tips</h4>
                    </div>
                    <ul class='security-list'>
                        <li>If you did NOT make this change, contact us immediately</li>
                        <li>Never share your password with anyone, including support staff</li>
                        <li>Use a unique password that you don't use elsewhere</li>
                        <li>Consider enabling two-factor authentication</li>
                    </ul>
                </div>
                
                <div class='help-box'>
                    <p><strong>📱 Didn't request this change?</strong></p>
                    <p>If you didn't authorize this password change, please contact our support team right away to secure your account.</p>
                    <a href='mailto:villaruzprintshop@gmail.com' class='contact-btn'>Contact Support →</a>
                </div>
                
                <div class='divider'></div>
                
                <div class='info-text' style='font-size: 13px; text-align: center;'>
                    <strong>Need immediate assistance?</strong><br>
                    Call us or email our support team for urgent concerns.
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Villaruz Print Shop & General Merchandise</strong></p>
                <p>Quality prints & everyday goods, delivered with care.</p>
                <p>📍 Poblacion, Dasol, Pangasinan | 📞 +63 912 345 6789</p>
                <div class='social-links'>
                    <a href='#'>📘</a>
                    <a href='#'>📷</a>
                    <a href='#'>🐦</a>
                    <a href='#'>💬</a>
                </div>
                <div class='divider' style='margin: 20px 0 15px;'></div>
                <p>This email was sent to <strong>$email</strong></p>
                <p>© 2026 Villaruz Print Shop & General Merchandise. All rights reserved.</p>
                <p><a href='#'>Privacy Policy</a> | <a href='#'>Terms of Service</a></p>
            </div>
        </div>
    </div>
</body>
</html>
    ";

    return $mail->send();
}

session_start();
$errors = [];
$success = '';

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Password validation
    $passwordValid = true;
    if (strlen($password) < 8)
        $passwordValid = false;
    if (!preg_match('/[A-Z]/', $password))
        $passwordValid = false;
    if (!preg_match('/[0-9]/', $password))
        $passwordValid = false;
    if (!preg_match('/[^a-zA-Z0-9]/', $password))
        $passwordValid = false;

    if (!$passwordValid) {
        $errors[] = 'Password must be at least 8 characters with uppercase, number, and special character.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email exists in database
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, f_name, email FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errors[] = 'Email address not found in our records.';
        }
    }

    // Update password if no errors
    if (empty($errors)) {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("UPDATE customers SET password = ? WHERE email = ?");
            $result = $stmt->execute([$hashedPassword, $email]);

            if ($result) {
                // Send password change notification email
                $fullname = $user['f_name'] ?? '';
                $emailSent = sendPasswordChangeNotification($email, $fullname);

                if ($emailSent) {
                    $_SESSION['success'] = 'You can Login Now';
                    header('Location: login');
                    exit;
                } else {
                    $success = 'Password changed successfully! However, we could not send a confirmation email.';
                    error_log("Failed to send password change notification email to: $email");
                }

                // Clear form
                $_POST = array();
            } else {
                $errors[] = 'Database error. Please try again.';
            }
        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            $errors[] = 'Database error. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Villaruz Print Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .logo img {
            width: 100px;
            height: auto;
            object-fit: contain;
        }

        .nav-link {
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-link:hover {
            color: #3b82f6;
        }

        .auth-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 28px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.05);
        }

        .auth-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
            text-align: center;
            color: #0f172a;
        }

        .auth-title span {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .auth-sub {
            text-align: center;
            color: #64748b;
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
            color: #475569;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            color: #1e293b;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        .form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #ffffff;
        }

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            flex: 1;
            padding-right: 45px;
        }

        .password-wrapper i {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.3s;
            font-size: 18px;
        }

        .password-wrapper i:hover {
            color: #3b82f6;
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(145deg, #3b82f6, #6366f1);
            border: none;
            padding: 14px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 16px;
            color: white;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .auth-footer {
            text-align: center;
            margin-top: 25px;
            color: #64748b;
            font-size: 14px;
        }

        .auth-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        footer {
            background: #ffffff;
            padding: 20px 5%;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .password-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }

        .password-hint ul {
            margin-left: 20px;
            margin-top: 5px;
        }

        .password-hint li {
            margin: 3px 0;
        }

        @media (max-width: 500px) {
            .auth-card {
                padding: 30px 25px;
            }

            .logo img {
                width: 75px;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="logo">
            <img src="logo/logo.jpeg" alt="Villaruz Print Shop Logo">
        </div>
        <div>
            <a href="index.php" class="nav-link">Home</a>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Change <span>Password</span></h2>
            <p class="auth-sub">Enter your email and new password</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Your Email</label>
                    <input type="email" name="email" placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter new password" required>
                        <i class="fas fa-eye-slash" id="togglePassword"></i>
                    </div>
                    <div class="password-hint">
                        <small>Password must contain:</small>
                        <ul>
                            <li>At least 8 characters</li>
                            <li>At least 1 uppercase letter (A-Z)</li>
                            <li>At least 1 number (0-9)</li>
                            <li>At least 1 special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password"
                            placeholder="Confirm new password" required>
                        <i class="fas fa-eye-slash" id="toggleConfirmPassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>

                <div class="auth-footer">
                    Remember your password? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>© 2026 Villaruz Print Shop & General Merchandise. All rights reserved.</p>
    </footer>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPassword = document.getElementById('confirm_password');

        toggleConfirmPassword.addEventListener('click', function () {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Optional: Real-time password match validation
        const confirmInput = document.getElementById('confirm_password');
        const passwordInput = document.getElementById('password');

        function validatePasswordMatch() {
            if (passwordInput.value !== confirmInput.value && confirmInput.value !== '') {
                confirmInput.style.borderColor = '#dc2626';
            } else {
                confirmInput.style.borderColor = '#e2e8f0';
            }
        }

        passwordInput.addEventListener('input', validatePasswordMatch);
        confirmInput.addEventListener('input', validatePasswordMatch);
    </script>
</body>

</html>