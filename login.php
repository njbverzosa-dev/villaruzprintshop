<?php
// login.php – for Villaruz Print Shop

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

// If already logged in as Admin, log them out
if (isset($_SESSION['acc_number'])) {
    $stmt = $pdo->prepare("UPDATE admins SET status = 0 WHERE acc_number = ?");
    $stmt->execute([$_SESSION['acc_number']]);
    session_unset();
    session_destroy();
    session_start();
}

// If already logged in as Customer, log them out
if (isset($_SESSION['customer_id'])) {
    $stmt = $pdo->prepare("UPDATE customers SET status = 0 WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    session_unset();
    session_destroy();
    session_start();
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
    $password = $_POST['password'] ?? '';

    if (empty($identifier))
        $errors[] = 'Account number or Phone Number is required.';
    if (empty($password))
        $errors[] = 'Password cannot be empty.';

    if (empty($errors)) {
        // Clean identifier: remove any non-numeric characters (for phone number)
        $cleanIdentifier = preg_replace('/[^0-9]/', '', $identifier);
        
        $userType = null;
        $user = null;

        // Check in ADMINS table first
        $adminStmt = $pdo->prepare("SELECT id, password, acc_number, phone_number, f_name, role, status 
                                   FROM admins WHERE acc_number = ? OR phone_number = ?");
        $adminStmt->execute([$identifier, $cleanIdentifier]);
        $admin = $adminStmt->fetch();

        if ($admin) {
            // Verify admin password
            if (password_verify($password, $admin['password'])) {
                $userType = 'Admin';
                $user = $admin;
            }
        }

        // If not found in admins or password invalid, check CUSTOMERS table
        if (!$userType) {
            $customerStmt = $pdo->prepare("SELECT id, password, acc_number, phone_number, f_name, role, status 
                                          FROM customers WHERE acc_number = ? OR phone_number = ?");
            $customerStmt->execute([$identifier, $cleanIdentifier]);
            $customer = $customerStmt->fetch();

            if ($customer && password_verify($password, $customer['password'])) {
                $userType = 'Customer';
                $user = $customer;
            }
        }

        // Process based on user type
        if ($userType && $user) {
            date_default_timezone_set('Asia/Manila');
            $lastLoginDate = date('D, j M Y g:i A');

            if ($userType === 'Admin') {
                // Update admin login info
                $updateStmt = $pdo->prepare("UPDATE admins SET last_login_date = ?, status = 1 WHERE id = ?");
                $updateStmt->execute([$lastLoginDate, $user['id']]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['acc_number'] = $user['acc_number'];
                $_SESSION['phone_number'] = $user['phone_number'];
                $_SESSION['fullname'] = $user['f_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_type'] = 'Admin';

                // Redirect admin to web/all_products.php
                header('Location: web/all_products.php');
                exit;

            } else if ($userType === 'Customer') {
                // Update customer login info
                $updateStmt = $pdo->prepare("UPDATE customers SET last_login_date = ?, status = 1 WHERE id = ?");
                $updateStmt->execute([$lastLoginDate, $user['id']]);

                session_regenerate_id(true);
                $_SESSION['customer_id'] = $user['id'];
                $_SESSION['customer_acc_number'] = $user['acc_number'];
                $_SESSION['customer_phone'] = $user['phone_number'];
                $_SESSION['customer_name'] = $user['f_name'];
                $_SESSION['customer_role'] = $user['role'];
                $_SESSION['user_type'] = 'Customer';

                // Redirect customer to shop page
                header('Location: public/shop.php');
                exit;
            }

        } else {
            $errors[] = 'Invalid credentials. Please check your account number/phone number and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Villaruz Print Shop</title>
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
            max-width: 450px;
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

        .login-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }

        .login-hint i {
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
            <h2 class="auth-title">Welcome <span>Back</span></h2>
            <p class="auth-sub">Login to your account</p>

            <?php if ($logoutMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($logoutMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label>Account Number or Phone Number</label>
                    <input type="text" name="identifier" placeholder="Enter account number or 11-digit mobile number"
                        autocomplete="off" value="<?php echo htmlspecialchars($identifier); ?>" required>
                    <div class="login-hint">
                        <i class="fas fa-info-circle"></i> Use your account number (e.g., 4819) or 11-digit mobile
                        number (e.g., 09123456789)
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter your password"
                            autocomplete="current-password" required>
                        <i class="fas fa-eye-slash" id="togglePassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Login</button>

                <div class="auth-footer">
                    Don't have an account? <a href="registration.php">Register here</a>
                </div>
                <div class="auth-footer">
                    Forgot Password? <a href="change_password.php">Continue here</a>
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

        // Auto-format phone number to allow only numbers and limit to 11 digits (for better UX)
        const identifierInput = document.querySelector('input[name="identifier"]');
        identifierInput.addEventListener('input', function (e) {
            // Allow numbers only
            this.value = this.value.replace(/[^0-9]/g, '');
            // Limit to 11 digits
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });
    </script>
</body>

</html>