<?php
// API/update_order_status.php
session_start();
header('Content-Type: application/json');

require_once '../DB_Conn/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update_order_status') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $deliveryNumber = trim($_POST['delivery_number'] ?? '');
    $newStatus = trim($_POST['status'] ?? '');

    // Validate inputs
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    if (empty($deliveryNumber)) {
        echo json_encode(['success' => false, 'message' => 'Delivery number is required']);
        exit;
    }

    $allowedStatuses = ['PENDING', 'PAID', 'CANCELLED'];
    if (!in_array($newStatus, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get current status before update
        $stmt = $pdo->prepare("SELECT status FROM for_deliveries WHERE id = :order_id AND delivery_number = :delivery_number");
        $stmt->execute([
            ':order_id' => $orderId,
            ':delivery_number' => $deliveryNumber
        ]);
        $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentOrder) {
            throw new Exception("Order not found");
        }
        
        $oldStatus = $currentOrder['status'];

        // Update status in for_deliveries table
        $stmt = $pdo->prepare("UPDATE for_deliveries SET status = :status WHERE id = :order_id AND delivery_number = :delivery_number");
        $result = $stmt->execute([
            ':status' => $newStatus,
            ':order_id' => $orderId,
            ':delivery_number' => $deliveryNumber
        ]);

        if (!$result || $stmt->rowCount() === 0) {
            throw new Exception("Order not found or no changes made");
        }

        // ==============================================
        // DEDUCT QUANTITY FROM INVENTORY WHEN STATUS IS PAID
        // ==============================================
        if ($newStatus === 'PAID' && $oldStatus !== 'PAID') {
            // Get all items from order_status_history for this delivery_number
            $stmt = $pdo->prepare("
                SELECT product_name, pieces 
                FROM order_status_history 
                WHERE delivery_number = :delivery_number
            ");
            $stmt->execute([':delivery_number' => $deliveryNumber]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($orderItems)) {
                throw new Exception("No items found for delivery number: " . $deliveryNumber);
            }
            
            $deductionErrors = [];
            
            // Deduct each product quantity
            foreach ($orderItems as $item) {
                $productName = $item['product_name'];
                $quantityToDeduct = intval($item['pieces']);
                
                // Check current stock
                $checkStmt = $pdo->prepare("
                    SELECT id, product_name, qty_on_hand 
                    FROM merchandise_inventory 
                    WHERE product_name = :product_name
                ");
                $checkStmt->execute([':product_name' => $productName]);
                $currentStock = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$currentStock) {
                    $deductionErrors[] = "Product '{$productName}' not found in inventory";
                    continue;
                }
                
                if ($currentStock['qty_on_hand'] < $quantityToDeduct) {
                    $deductionErrors[] = "Insufficient stock for '{$productName}'. Available: {$currentStock['qty_on_hand']}";
                    continue;
                }
                
                // Update inventory
                $updateStmt = $pdo->prepare("
                    UPDATE merchandise_inventory 
                    SET qty_on_hand = qty_on_hand - :quantity,
                        last_restocked = NOW()
                    WHERE product_name = :product_name
                ");
                
                $updateResult = $updateStmt->execute([
                    ':quantity' => $quantityToDeduct,
                    ':product_name' => $productName
                ]);
                
                if (!$updateResult || $updateStmt->rowCount() === 0) {
                    $deductionErrors[] = "Failed to deduct stock for '{$productName}'";
                }
            }
            
            // If there were errors, rollback the transaction
            if (!empty($deductionErrors)) {
                throw new Exception("Stock deduction failed: " . implode(", ", $deductionErrors));
            }
        }
        
        // ==============================================
        // RESTORE QUANTITY WHEN STATUS CHANGED FROM PAID TO CANCELLED
        // ==============================================
        if ($newStatus === 'CANCELLED' && $oldStatus === 'PAID') {
            // Get all items from order_status_history for this delivery_number
            $stmt = $pdo->prepare("
                SELECT product_name, pieces 
                FROM order_status_history 
                WHERE delivery_number = :delivery_number
            ");
            $stmt->execute([':delivery_number' => $deliveryNumber]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($orderItems)) {
                foreach ($orderItems as $item) {
                    $productName = $item['product_name'];
                    $quantityToRestore = intval($item['pieces']);
                    
                    $updateStmt = $pdo->prepare("
                        UPDATE merchandise_inventory 
                        SET qty_on_hand = qty_on_hand + :quantity,
                            last_restocked = NOW()
                        WHERE product_name = :product_name
                    ");
                    $updateStmt->execute([
                        ':quantity' => $quantityToRestore,
                        ':product_name' => $productName
                    ]);
                }
            }
        }

        // Update status in order_status_history table for all items with this delivery_number
        $stmt = $pdo->prepare("UPDATE order_status_history SET status = :status WHERE delivery_number = :delivery_number");
        $stmt->execute([
            ':status' => $newStatus,
            ':delivery_number' => $deliveryNumber
        ]);

        // Commit transaction
        $pdo->commit();

        // Prepare response message
        $message = "Order status updated to {$newStatus} successfully!";
        if ($newStatus === 'PAID' && $oldStatus !== 'PAID') {
            $message .= " Inventory has been deducted.";
        } elseif ($newStatus === 'CANCELLED' && $oldStatus === 'PAID') {
            $message .= " Inventory has been restored.";
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'status' => $newStatus,
            'old_status' => $oldStatus,
            'order_id' => $orderId,
            'delivery_number' => $deliveryNumber
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Update Order Status Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>