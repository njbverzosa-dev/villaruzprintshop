<?php
// API/add_product.php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}
header('Content-Type: application/json');

require_once __DIR__ . '/../DB_Conn/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token missing from request']);
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token not found in session']);
    exit;
}

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid CSRF token. Please refresh the page and try again.'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add_product') {
    $productName = trim($_POST['product_name']);
    $unit = trim($_POST['unit']);
    $quantity = intval($_POST['quantity']);
    $sellingPrice = floatval($_POST['selling_price']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Validation
    if (empty($productName)) {
        echo json_encode(['success' => false, 'message' => 'Product name is required']);
        exit;
    }
    
    if ($sellingPrice <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unit cost must be greater than 0']);
        exit;
    }
    
    if ($quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity cannot be negative']);
        exit;
    }
    
    // Check if product already exists
    $checkStmt = $pdo->prepare("SELECT id FROM merchandise_inventory WHERE product_name = :product_name");
    $checkStmt->execute([':product_name' => $productName]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product already exists!']);
        exit;
    }
    
    try {
        date_default_timezone_set('Asia/Manila');
        $currentDateTime = date('D, j M Y g:i A');
        
        $stmt = $pdo->prepare("INSERT INTO merchandise_inventory (product_name, unit, qty_on_hand, selling_price, description, last_restocked) VALUES (:product_name, :unit, :qty_on_hand, :selling_price, :description, :last_restocked)");
        $result = $stmt->execute([
            ':product_name' => $productName,
            ':unit' => $unit,
            ':qty_on_hand' => $quantity,
            ':selling_price' => $sellingPrice,
            ':description' => $description,
            ':last_restocked' => $currentDateTime
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Product added successfully',
                'product_id' => $pdo->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>