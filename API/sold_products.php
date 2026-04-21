<?php
// API/sold_products.php
session_start();
header('Content-Type: application/json');

require_once '../DB_Conn/config.php';  // Or use __DIR__ . '/../DB_Conn/config.php'

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$action = $_POST['action'] ?? '';

// Handle sell product (record to order_status_history only - no inventory deduction)
if ($action === 'sell_product') {
    if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $productId = intval($_POST['product_id']);
    $quantityToSell = intval($_POST['quantity']);
    // We still accept product_name from POST for reference, but will use DB value
    $submittedProductName = $_POST['product_name'] ?? '';
    $submittedSellingPrice = floatval($_POST['selling_price'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');

    if ($quantityToSell <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
        exit;
    }

    if (empty($purpose)) {
        echo json_encode(['success' => false, 'message' => 'Please select a purpose for this sale']);
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Set timezone to Asia/Manila
        date_default_timezone_set('Asia/Manila');
        $currentDateTime = date('D, j M Y g:i A');

        // Get product details including unit and official selling_price
        $stmt = $pdo->prepare("SELECT * FROM merchandise_inventory WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception('Product not found');
        }

        // Use the official selling price from the database
        $officialSellingPrice = floatval($product['selling_price']);
        $totalAmount = $officialSellingPrice * $quantityToSell;
        $unit = $product['unit'] ?? 'Pcs';
        
        // Determine status based on purpose
        // If purpose is "In Use", status becomes PAID automatically
        // Otherwise, status is PENDING
        $status = ($purpose === 'In Use') ? 'PAID' : 'PENDING';

        // Insert into order_status_history with note/purpose column
        $stmt = $pdo->prepare("INSERT INTO order_status_history 
            (order_id, product_name, status, pieces, unit, selling_price, total_amount, date_time_sold, note) 
            VALUES 
            (:order_id, :product_name, :status, :pieces, :unit, :selling_price, :total_amount, :date_time_sold, :note)");
        
        $result = $stmt->execute([
            ':order_id' => $productId,
            ':product_name' => $product['product_name'],
            ':status' => $status,
            ':pieces' => $quantityToSell,
            ':unit' => $unit,
            ':selling_price' => $officialSellingPrice,
            ':total_amount' => $totalAmount,
            ':date_time_sold' => $currentDateTime,
            ':note' => $purpose
        ]);

        if (!$result) {
            throw new Exception('Failed to insert into order_status_history');
        }

        // Get the auto-generated ID of the inserted record
        $recordId = $pdo->lastInsertId();

        // If status is PAID (because purpose is "In Use"), deduct from inventory
        if ($status === 'PAID') {
            // Check current stock
            $stmt = $pdo->prepare("SELECT qty_on_hand FROM merchandise_inventory WHERE id = :product_id");
            $stmt->execute([':product_id' => $productId]);
            $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($quantityToSell > $inventory['qty_on_hand']) {
                throw new Exception("Insufficient stock for {$product['product_name']}. Available: {$inventory['qty_on_hand']}, Requested: {$quantityToSell}");
            }

            // Deduct from inventory
            $newQtyOnHand = $inventory['qty_on_hand'] - $quantityToSell;
            $stmt = $pdo->prepare("UPDATE merchandise_inventory SET qty_on_hand = :qty_on_hand WHERE id = :product_id");
            $stmt->execute([
                ':qty_on_hand' => $newQtyOnHand,
                ':product_id' => $productId
            ]);
            
            error_log("Inventory deducted for In Use - Product: {$product['product_name']}, Quantity: {$quantityToSell}, New Stock: {$newQtyOnHand}");
        }

        // Build the full URL to the sales receipt page
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
        // Remove the "API" part from the path to go back to root
        $basePath = str_replace('/API', '', $basePath);
        $receiptUrl = $protocol . $host . $basePath . 'sales_invoice.php?order_id=' . $recordId;

        // Update the newly inserted row with the QR code URL
        $updateStmt = $pdo->prepare("UPDATE order_status_history SET qr_code = :qr_code WHERE id = :id");
        $updateStmt->execute([
            ':qr_code' => $receiptUrl,
            ':id' => $recordId
        ]);

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => $status === 'PAID' 
                ? 'Sale recorded and inventory deducted for In Use purpose!' 
                : 'Sale recorded successfully (inventory not deducted)',
            'pieces' => $quantityToSell,
            'unit' => $unit,
            'selling_price' => $officialSellingPrice,
            'total_amount' => $totalAmount,
            'product_name' => $product['product_name'],
            'date_time_sold' => $currentDateTime,
            'display_text' => $quantityToSell . ' ' . $unit,
            'qr_code' => $receiptUrl,
            'record_id' => $recordId,
            'purpose' => $purpose,
            'status' => $status
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>