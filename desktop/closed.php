<?php
// logout.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include PDO config
require_once __DIR__ . '/DB_Conn/config.php';

// If the user is logged in, update status to offline (0)
if (isset($_SESSION['acc_number'])) {
    $stmt = $pdo->prepare("UPDATE admins SET status = 0 WHERE acc_number = ?");
    $stmt->execute([$_SESSION['acc_number']]);
}

// Destroy the session completely
session_unset();
session_destroy();

// Start a fresh session to store the logout message
session_start();
$_SESSION['logout_message'] = 'Sign-Out Successfully';

// Redirect to login page
header('Location: login.php');
exit;