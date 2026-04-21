<?php
// access_sessions.php – Session validation + user data + earnings + daily bonus

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../DB_Conn/config.php';

// Redirect if not logged in or if user is Admin
if (!isset($_SESSION['acc_number']) || ($_SESSION['role'] ?? '') === 'Customer') {
    header('Location: login.php');
    exit();
}

// Fetch full user data
$stmt = $pdo->prepare("SELECT * FROM admins WHERE acc_number = ?");
$stmt->execute([$_SESSION['acc_number']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

 $currentDateTime = date('D, j M Y g:i A');

// Helper for collected_rewards
date_default_timezone_set('Asia/Manila');

// Daily login bonus
$storedDate = $user['last_login_date'] ?? '';
if ($storedDate !== $currentDateTime) {
  
    $updateStmt = $pdo->prepare("UPDATE admins SET last_login_date = ? WHERE acc_number = ?");
    $updateStmt->execute([$currentDateTime, $_SESSION['acc_number']]);

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE acc_number = ?");
    $stmt->execute([$_SESSION['acc_number']]);
    $user = $stmt->fetch();
}

// Update status to online
$stmt = $pdo->prepare("UPDATE admins SET status = 1 WHERE acc_number = ?");
$stmt->execute([$_SESSION['acc_number']]);
