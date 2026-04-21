<?php
// Customer_API/add_to_cart.php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../DB_Conn/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token missing from request']);
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token not found in session']);
    exit();
}

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    if ($action !== 'add_to_cart') {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }
    
    $productId = $_POST['product_id'] ?? 0;
    $quantity = intval($_POST['quantity'] ?? 0);
    $userAccNumber = $_POST['acc_number'] ?? '';
    
    if (empty($userAccNumber)) {
        echo json_encode(['success' => false, 'message' => 'User account number is required']);
        exit();
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
        exit();
    }
    
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM merchandise_inventory WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }
    
    // ==============================================
    // FIXED: Get or create order number for today
    // ==============================================
    $todayDate = date('D, j M Y g:i A');
    $orderNumber = null;
    
    // Check for existing cart items for this user today
    $stmt = $pdo->prepare("
        SELECT DISTINCT order_number 
        FROM cart 
        WHERE acc_number = ? 
        AND order_number LIKE ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$userAccNumber, $userAccNumber . '_%_' . $todayDate]);
    $existingOrder = $stmt->fetch();
    
    if ($existingOrder) {
        // Use existing order number
        $orderNumber = $existingOrder['order_number'];
    } else {
        // Generate new order number
        // Find the highest sequence number used today
        $stmt = $pdo->prepare("
            SELECT order_number 
            FROM cart 
            WHERE order_number LIKE ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute([$userAccNumber . '_%_' . $todayDate]);
        $lastOrder = $stmt->fetch();
        
        $sequence = 1;
        if ($lastOrder) {
            // Extract sequence number from existing order
            preg_match('/' . preg_quote($userAccNumber, '/') . '_(\d+)_/', $lastOrder['order_number'], $matches);
            if (isset($matches[1])) {
                $sequence = intval($matches[1]) + 1;
            }
        }
        
        $sequencePadded = str_pad($sequence, 5, '0', STR_PAD_LEFT);
        $orderNumber = $userAccNumber . '_' . $sequencePadded . '_' . $todayDate;
    }
    
    $dateTimeAdd = date('D, j M Y g:i A');
    $totalAmount = $product['selling_price'] * $quantity;
    
    // Check if product already exists in cart with this order_number
    $stmt = $pdo->prepare("
        SELECT * FROM cart 
        WHERE order_number = ? AND product_name = ?
    ");
    $stmt->execute([$orderNumber, $product['product_name']]);
    $existingItem = $stmt->fetch();
    
    if ($existingItem) {
        // Update existing cart item - increment pieces
        $newPieces = $existingItem['pieces'] + $quantity;
        $newTotalAmount = $product['selling_price'] * $newPieces;
        
        $stmt = $pdo->prepare("
            UPDATE cart 
            SET pieces = ?, total_amount = ?, date_time_add = ? 
            WHERE id = ?
        ");
        $stmt->execute([$newPieces, $newTotalAmount, $dateTimeAdd, $existingItem['id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => "Updated {$product['product_name']} quantity to {$newPieces}",
            'order_number' => $orderNumber
        ]);
    } else {
        // Insert new cart item
        $stmt = $pdo->prepare("
            INSERT INTO cart (acc_number, order_number, product_name, unit, selling_price, pieces, total_amount, date_time_add) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userAccNumber, 
            $orderNumber, 
            $product['product_name'], 
            $product['unit'], 
            $product['selling_price'], 
            $quantity, 
            $totalAmount, 
            $dateTimeAdd
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => "Added {$quantity} × {$product['product_name']} to cart",
            'order_number' => $orderNumber
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>