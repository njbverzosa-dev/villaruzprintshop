<?php
// error.php
session_start();

// Get the error code
$error_code = http_response_code();

// Custom error messages
$error_messages = [
    400 => 'Bad Request',
    401 => 'Unauthorized Access',
    403 => 'Forbidden',
    404 => 'Page Not Found',
    500 => 'Internal Server Error',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable'
];

$error_title = $error_messages[$error_code] ?? 'An Error Occurred';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo $error_code; ?> - Villaruz Print Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { 
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .error-container {
            text-align: center;
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 20px;
        }

        .error-icon {
            font-size: 80px;
            color: #ef4444;
            margin-bottom: 20px;
        }

        .error-code {
            font-size: 22px;
            font-weight: 100;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .error-title {
            font-size: 24px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 15px;
        }

        .error-message {
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .home-btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin: 5px;
        }

        .home-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .back-btn {
            display: inline-block;
            background: #e2e8f0;
            color: #475569;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin: 5px;
        }

        .back-btn:hover {
            background: #cbd5e1;
        }
         @media (max-width: 500px) {
           
            .logo img {
                width: 75px;
            }
        }
        .logo img {
            width: 300px;
            height: auto;
            object-fit: contain;
        }

    </style>
</head>
<body>
    <div class="error-container">
       <div class="logo">
            <img src="/logo/logo.jpeg" alt="Villaruz Print Shop Logo">
        </div>
        <div class="error-code">Error Code = {<?php echo $error_code; ?>}</div>
        <div class="error-message">
            <?php if ($error_code == 404): ?>
                The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
            <?php elseif ($error_code == 403): ?>
                You don't have permission to access this resource. Please log in or contact the administrator.
            <?php elseif ($error_code == 500): ?>
                Something went wrong on our end. Please try again later or contact support.
            <?php else: ?>
                An unexpected error occurred. Please try again or contact support if the problem persists.
            <?php endif; ?>
        </div>
        <div>
            <a href="javascript:history.back()" class="back-btn">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
            <a href="/" class="home-btn">
                <i class="fas fa-home"></i> Home Page
            </a>
        </div>
    </div>
</body>
</html>