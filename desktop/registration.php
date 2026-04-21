<?php
// registration.php – Registration for Villaruz Print Shop & General Merchandise

require_once 'DB_Conn/config.php';
require_once 'Mail/email.php';

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
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE acc_number = ?");
    $stmt->execute([$baseAccNumber]);

    if (!$stmt->fetch()) {
        return $baseAccNumber;
    }

    // If exists, append random digits
    $maxAttempts = 10;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        $newAccNumber = $baseAccNumber . mt_rand(10, 99);
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE acc_number = ?");
        $stmt->execute([$newAccNumber]);
        if (!$stmt->fetch()) {
            return $newAccNumber;
        }
    }

    // Final fallback with timestamp
    return $baseAccNumber . time();
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
    $phone_number = trim($_POST['phone_number'] ?? '');

    // Full name validation - only letters and spaces
    if (empty($fullname)) {
        $errors[] = 'Full name is required.';
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $fullname)) {
        $errors[] = 'Full name must contain only letters and spaces (no dots or special characters).';
    } elseif (strlen($fullname) < 3) {
        $errors[] = 'Full name must be at least 3 characters.';
    }

    // Phone validation - accept 11 digits (Philippine mobile number)
    if (empty($phone_number)) {
        $errors[] = 'Phone number is required.';
    } else {
        // Remove any non-digit characters
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone_number);
        
        // Check if phone number is exactly 11 digits and starts with 09
        if (!preg_match('/^09[0-9]{9}$/', $cleanPhone)) {
            $errors[] = 'Please enter a valid 11-digit Philippine mobile number starting with 09 (e.g., 09123456789)';
        } else {
            $phone_number = $cleanPhone; // Store clean 11-digit number
        }
    }

    // Password strength rules
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

    // Check phone uniqueness in admins table
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE phone_number = ?");
        $stmt->execute([$phone_number]);
        if ($stmt->fetch()) {
            $errors[] = 'Phone number already registered.';
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        // Generate acc_number from phone number's last 4 digits
        $accNumber = generateAccNumberFromPhone($pdo, $phone_number);

        date_default_timezone_set('Asia/Manila');
        $registrationDate = date('D, j M Y g:i A');

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO admins 
            (password, registered_at, acc_number, f_name, phone_number) 
            VALUES (?, ?, ?, ?, ?)");

        try {
            if ($stmt->execute([$hashedPassword, $registrationDate, $accNumber, $fullname, $phone_number])) {
                $_SESSION['success'] = 'Registration successful! Your account number is: ' . $accNumber . ' (last 4 digits of your phone number). You can now log in.';
                header('Location: login.php');
                exit;
            } else {
                $errors[] = 'Database error. Please try again.';
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                if (strpos($e->errorInfo[2], 'acc_number') !== false) {
                    $errors[] = 'Account number conflict. Please try again.';
                } else {
                    $errors[] = 'Duplicate information. Please try again.';
                }
            } else {
                error_log("Registration insert error: " . $e->getMessage());
                $errors[] = 'Database error. Please try again.';
            }
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

        .form-group input::placeholder {
            color: #94a3b8;
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
            transition: 0.3s;
        }

        .auth-footer a:hover {
            color: #2563eb;
            text-decoration: underline;
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

        .phone-hint i {
            color: #3b82f6;
            margin-right: 5px;
        }

        @media (max-width: 500px) {
            .auth-card {
                padding: 30px 25px;
            }

            .auth-title {
                font-size: 28px;
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
            <p class="auth-sub">Join To Villaruz Print Shop & General Merchandise today</p>

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
                    <input type="text" name="fullname" id="fullname" placeholder="Enter your full name"
                        autocomplete="off"
                        value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" placeholder="09123456789"
                        autocomplete="off" pattern="09[0-9]{9}" maxlength="11"
                        value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                        required>
                    <div class="phone-hint">
                        <i class="fas fa-info-circle"></i> Enter 11-digit mobile number (e.g., 09123456789). We use your number to inform you about your order.
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter password"
                            autocomplete="new-password" required>
                        <i class="fas fa-eye-slash" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password"
                            placeholder="Confirm password" autocomplete="new-password" required>
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
        <div class="copyright">
            <p>© 2026 Villaruz Print Shop & General Merchandise. All rights reserved.</p>
        </div>
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

        // Auto-format phone number to allow only numbers and limit to 11 digits
        const phoneInput = document.getElementById('phone_number');
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 11) {
                value = value.slice(0, 11); // Limit to 11 digits
            }
            e.target.value = value;
        });
    </script>
</body>

</html>