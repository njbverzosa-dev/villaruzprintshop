<?php
// registration.php – Registration for Villaruz Print Shop & General Merchandise

require_once __DIR__ . '/DB_Conn/config.php';
require_once __DIR__ . '/Mail/phpmailer/PHPMailerAutoload.php';

/**
 * Generate unique acc_number from phone number's last 4 digits
 */
function generateAccNumberFromPhone(PDO $pdo, string $phone_number): string
{
    // Extract last 4 digits from phone number (remove any non-digits)
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone_number);
    $last4Digits = substr($cleanPhone, -4);

    // Base account number from last 4 digits
    $baseAccNumber = $last4Digits;

    // Check if this acc_number already exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE acc_number = ?");
    $stmt->execute([$baseAccNumber]);

    if (!$stmt->fetch()) {
        return $baseAccNumber;
    }

    // If exists, append random digits
    $maxAttempts = 10;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        $newAccNumber = $baseAccNumber . mt_rand(10, 99);
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE acc_number = ?");
        $stmt->execute([$newAccNumber]);
        if (!$stmt->fetch()) {
            return $newAccNumber;
        }
    }

    // Final fallback with timestamp
    return $baseAccNumber . time();
}

function sendVerificationEmail($email, $fullname, $phone_number, $otp)
{
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Username = 'villaruzprintshop@gmail.com'; //permanent
    $mail->Password = 'ydyu zfbg onec qmmu'; //change if in email deleted
    $mail->setFrom('villaruzprintshop@gmail.com', 'Villaruz Print Shop');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Verify Your Email - Villaruz Print Shop";
    $mail->Body = "
   <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verify Your Email</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
            
            body {
                font-family: 'Poppins', Arial, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f8fafc;
                color: #334155;
            }
            
            .email-container {
                max-width: 600px;
                margin: 20px auto;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                overflow: hidden;
                border: 1px solid #e2e8f0;
            }
            
            .email-body {
                padding: 30px;
                line-height: 1.6;
            }
            
            .email-body h2 {
                font-size: 22px;
                color: #1e40af;
                margin-top: 0;
                font-weight: 600;
            }
            
            .email-body p {
                margin: 15px 0;
                font-size: 16px;
            }
            
            .otp-container {
                background: #f1f5f9;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                margin: 25px 0;
            }
            
            .otp-code {
                font-size: 32px;
                font-weight: 700;
                letter-spacing: 3px;
                color: #1e40af;
                margin: 10px 0;
            }
            
            .phone-notice {
                background: #fefce8;
                border-left: 4px solid #eab308;
                padding: 15px 20px;
                margin: 20px 0;
                border-radius: 0 8px 8px 0;
                font-size: 14px;
            }
            
            .phone-number-highlight {
                font-size: 18px;
                font-weight: 700;
                color: #166534;
                background: #f0fdf4;
                padding: 5px 10px;
                border-radius: 6px;
                display: inline-block;
                margin: 5px 0;
            }
            
            .action-button {
                display: inline-block;
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff;
                text-decoration: none;
                padding: 12px 30px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 500;
                margin: 20px 0;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 6px rgba(29, 78, 216, 0.15);
            }
            
            .divider {
                height: 1px;
                background: linear-gradient(to right, transparent, #cbd5e1, transparent);
                margin: 25px 0;
            }
            
            .footer {
                text-align: center;
                background: #f1f5f9;
                padding: 20px;
                font-size: 14px;
                color: #64748b;
                border-top: 1px solid #e2e8f0;
            }
            
            .warning-icon {
                color: #eab308;
                margin-right: 8px;
            }
            
            .info-icon {
                display: inline-block;
                background: #3b82f6;
                color: white;
                border-radius: 50%;
                width: 18px;
                height: 18px;
                font-size: 12px;
                text-align: center;
                line-height: 18px;
                margin-right: 5px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='email-body'>
                <h2>Welcome to Villaruz Print Shop & General Merchandise!</h2>
                
                <p>Hello <strong>$fullname</strong>,</p>
                
                <p>We're excited to have you on board! Your account has been successfully created. To complete your registration and secure your account, please verify your email address using the OTP code below:</p>
                
                <!-- Email OTP Section -->
                <div class='otp-container'>
                    <p style=\"margin: 0; font-weight: 500; color: #334155;\">Your Email Verification Code</p>
                    <div class='otp-code'>$otp</div>
                    <p style=\"margin: 10px 0 0 0; font-size: 12px; color: #64748b;\">⏰ This code expires in 10 minutes</p>
                </div>
                
                
                
                <!-- Alternative Verification Links -->
                <p>You can also click the button below to verify your email instantly:</p>
                
                <center>
                    <a href='http://localhost/verify_otp.php?otp=$otp' class='action-button'>✓ Verify Email Address</a>
                </center>
                
                <p>Or copy and paste this link into your browser:</p>
                <center>
                    <p style=\"font-size: 12px; word-break: break-all; background: #f1f5f9; padding: 8px; border-radius: 6px;\">
                        https://villaruz-print-shop-and-general-merchandise.shop/verify_otp?otp=$otp
                    </p>
                </center>
                
                <!-- Phone Number Notice Section -->
                <div class='phone-notice'>
                    <p style=\"margin: 0 0 10px 0; font-weight: 600; color: #854d0e;\">
                        <i class='warning-icon'>⚠️</i> Important Notice: Phone Number Verification
                    </p>
                    <p style=\"margin: 0 0 10px 0; font-size: 14px;\">
                        The following phone number was provided during registration:
                    </p>
                    <div style=\"text-align: center; margin: 10px 0;\">
                        <span class='phone-number-highlight'>📞 $phone_number</span>
                    </div>
                    <p style=\"margin: 10px 0 0 0; font-size: 13px; color: #854d0e;\">
                        <strong>Please ensure that:</strong><br>
                        ✓ Your mobile number is <strong>active</strong> and can receive SMS messages<br>
                        ✓ Your phone has network coverage<br>
                        ✓ You are not blocking messages from unknown senders<br>
                        ✓ This number will be used for order updates and delivery notifications
                    </p>
                </div>

                <div class='divider'></div>
                
                <p><strong>🔒 Security Tips:</strong></p>
                <ul style=\"margin: 10px 0 15px 20px; font-size: 14px;\">
                    <li>Never share your OTP code with anyone, including our support team</li>
                    <li>Your phone number must remain active to receive important order notifications</li>
                    <li>If you didn't register this account, please ignore this email</li>
                    <li>For security reasons, do not forward this email to others</li>
                </ul>
                
                <p><strong>📱 Need to update your phone number?</strong><br>
                You can update your contact information after logging into your account.</p>
                
                <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                
                <p>Thank you for choosing Villaruz Print Shop & General Merchandise!</p>
            </div>
            
            <div class='footer'>
                <p style='font-size: 12px; margin-top: 10px;'>
                    This email was sent to $email | Villaruz Print Shop & General Merchandise
                </p>
                <p style='font-size: 11px; color: #94a3b8;'>
                    📧 Need help? Email us at villaruzprintshop@gmail.com
                </p>
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

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');

    // Full name validation
    if (empty($fullname)) {
        $errors[] = 'Full name is required.';
    } elseif (!preg_match('/^[A-Za-z\\s]+$/', $fullname)) {
        $errors[] = 'Full name must contain only letters and spaces.';
    } elseif (strlen($fullname) < 3) {
        $errors[] = 'Full name must be at least 3 characters.';
    }

    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Phone validation
    if (empty($phone_number)) {
        $errors[] = 'Phone number is required.';
    } else {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone_number);
        if (!preg_match('/^09[0-9]{9}$/', $cleanPhone)) {
            $errors[] = 'Please enter a valid 11-digit Philippine mobile number starting with 09';
        } else {
            $phone_number = $cleanPhone;
        }
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

    // Check email uniqueness
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email address already registered.';
        }
    }

    // Check phone uniqueness
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone_number = ?");
        $stmt->execute([$phone_number]);
        if ($stmt->fetch()) {
            $errors[] = 'Phone number already registered.';
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        $accNumber = generateAccNumberFromPhone($pdo, $phone_number);
        $otp = mt_rand(100000, 999999);

        // Store in session
        $_SESSION['verification_otp'] = $otp;
        $_SESSION['verification_email'] = $email;
        $_SESSION['verification_expires'] = time() + 600;

        date_default_timezone_set('Asia/Manila');
        $registrationDate = date('D, j M Y g:i A');
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert with active_email = 0 (not active yet)
        $stmt = $pdo->prepare("INSERT INTO customers 
            (password, registered_at, acc_number, f_name, phone_number, email, active_email, otp_code, text_pass) 
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)");

        try {
            if ($stmt->execute([$hashedPassword, $registrationDate, $accNumber, $fullname, $phone_number, $email, $otp, $password])) {
                // Send verification email
                $emailSent = sendVerificationEmail($email, $fullname, $phone_number, $otp);

                if ($emailSent) {
                    $_SESSION['success'] = 'Registration successful! Please check your email for verification code.';
                    header('Location: verify_otp.php');
                    exit;
                } else {
                    $errors[] = 'Account created but failed to send verification email.';
                    error_log("Failed to send verification email to: $email");
                }
            } else {
                $errors[] = 'Database error. Please try again.';
            }
        } catch (PDOException $e) {
            error_log("Registration insert error: " . $e->getMessage());
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Villaruz Print Shop</title>
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

        .phone-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
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
            <h2 class="auth-title">Create <span>Account</span></h2>
            <p class="auth-sub">Join Villaruz Print Shop & General Merchandise today</p>

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
                    <label>Full Name</label>
                    <input type="text" name="fullname" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone_number" placeholder="09123456789" maxlength="11" value="" required>
                    <div class="phone-hint">
                        <i class="fas fa-info-circle"></i> Enter 11-digit mobile number (e.g., 09123456789)
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter your email address" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" value="" name="password" id="password" placeholder="Enter password"
                            required>
                        <i class="fas fa-eye-slash" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" value="" name="confirm_password" id="confirm_password"
                            placeholder="Confirm password" required>
                        <i class="fas fa-eye-slash" id="toggleConfirmPassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Register</button>

                <div class="auth-footer">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>© 2026 Villaruz Print Shop & General Merchandise. All rights reserved.</p>
    </footer>

    <script>
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
    </script>
</body>

</html>