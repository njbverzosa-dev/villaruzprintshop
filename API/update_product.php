<?php
// API/update_product.php
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

// Handle update product
if ($action === 'update_product') {
    if (!isset($_POST['product_id']) || !isset($_POST['product_name']) || !isset($_POST['selling_price'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $productId = intval($_POST['product_id']);
    $productName = trim($_POST['product_name']);
    $unit = trim($_POST['unit'] ?? 'Pcs');
    $quantity = intval($_POST['quantity'] ?? 0);
    $sellingPrice = floatval($_POST['selling_price'] ?? 0);

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

    try {
        // Set timezone to Asia/Manila
        date_default_timezone_set('Asia/Manila');
        $currentDateTime = date('Y-m-d H:i:s');

        // Update product
        $stmt = $pdo->prepare("UPDATE merchandise_inventory SET product_name = :product_name, unit = :unit, qty_on_hand = :qty_on_hand, selling_price = :selling_price, last_restocked = :last_restocked WHERE id = :id");
        $result = $stmt->execute([
            ':product_name' => $productName,
            ':unit' => $unit,
            ':qty_on_hand' => $quantity,
            ':selling_price' => $sellingPrice,
            ':last_restocked' => $currentDateTime,
            ':id' => $productId
        ]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Product updated successfully',
                'product_id' => $productId,
                'last_updated' => $currentDateTime
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update product']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>