<?php
// API/update_sold_products.php
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

// ==============================================
// ACTION 1: UPDATE STATUS (PENDING, PAID, CANCELLED)
// ==============================================
if ($action === 'update_status') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $deliveryNumber = trim($_POST['delivery_number'] ?? '');
    $newStatus = trim($_POST['status'] ?? '');

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    $allowedStatuses = ['PENDING', 'PAID', 'CANCELLED'];
    if (!in_array($newStatus, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get current status and product details
        $stmt = $pdo->prepare("SELECT status, product_name, pieces FROM order_status_history WHERE id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentOrder) {
            throw new Exception("Order not found");
        }

        $oldStatus = $currentOrder['status'];
        $productName = $currentOrder['product_name'];
        $quantity = intval($currentOrder['pieces']);

        // ==============================================
        // CASE 1: Changing to PAID (deduct inventory)
        // ==============================================
        if ($newStatus === 'PAID' && $oldStatus !== 'PAID') {
            // Check if product exists in inventory
            $checkStmt = $pdo->prepare("SELECT id, qty_on_hand FROM merchandise_inventory WHERE product_name = :product_name");
            $checkStmt->execute([':product_name' => $productName]);
            $inventory = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$inventory) {
                throw new Exception("Product '{$productName}' not found in inventory");
            }

            if ($inventory['qty_on_hand'] < $quantity) {
                throw new Exception("Insufficient stock for '{$productName}'. Available: {$inventory['qty_on_hand']}, Required: {$quantity}");
            }

            // Deduct from inventory
            $updateStmt = $pdo->prepare("
                UPDATE merchandise_inventory 
                SET qty_on_hand = qty_on_hand - :quantity,
                    last_restocked = NOW()
                WHERE product_name = :product_name
            ");
            $updateStmt->execute([
                ':quantity' => $quantity,
                ':product_name' => $productName
            ]);
        }

        // ==============================================
        // CASE 2: Changing from PAID to CANCELLED (restore inventory)
        // ==============================================
        if ($newStatus === 'CANCELLED' && $oldStatus === 'PAID') {
            $updateStmt = $pdo->prepare("
                UPDATE merchandise_inventory 
                SET qty_on_hand = qty_on_hand + :quantity,
                    last_restocked = NOW()
                WHERE product_name = :product_name
            ");
            $updateStmt->execute([
                ':quantity' => $quantity,
                ':product_name' => $productName
            ]);
        }

        // ==============================================
        // CASE 3: Changing from PAID to PENDING (restore inventory)
        // ==============================================
        if ($newStatus === 'PENDING' && $oldStatus === 'PAID') {
            $updateStmt = $pdo->prepare("
                UPDATE merchandise_inventory 
                SET qty_on_hand = qty_on_hand + :quantity,
                    last_restocked = NOW()
                WHERE product_name = :product_name
            ");
            $updateStmt->execute([
                ':quantity' => $quantity,
                ':product_name' => $productName
            ]);
        }

        // ==============================================
        // CASE 4: Changing from CANCELLED to PENDING (no inventory change needed)
        // ==============================================
        // No inventory action needed for CANCELLED → PENDING

        // Update the order status
        $stmt = $pdo->prepare("UPDATE order_status_history SET status = :status WHERE id = :order_id");
        $stmt->execute([
            ':status' => $newStatus,
            ':order_id' => $orderId
        ]);

        $pdo->commit();

        $message = "Order status updated to {$newStatus} successfully!";
        if ($newStatus === 'PAID' && $oldStatus !== 'PAID') {
            $message .= " Inventory has been deducted.";
        } elseif (($newStatus === 'CANCELLED' || $newStatus === 'PENDING') && $oldStatus === 'PAID') {
            $message .= " Inventory has been restored.";
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'status' => $newStatus,
            'old_status' => $oldStatus,
            'order_id' => $orderId
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Update Status Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==============================================
// ACTION 2: RESTORE ORDER (DELETE AND RESTORE INVENTORY)
// ==============================================
if ($action === 'restore_order') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $productName = trim($_POST['product_name'] ?? '');
    $pieces = intval($_POST['pieces'] ?? 0);

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get order details to verify it exists and is PAID
        $stmt = $pdo->prepare("SELECT status, product_name, pieces FROM order_status_history WHERE id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Order not found");
        }

        if ($order['status'] !== 'PAID') {
            throw new Exception("Only PAID orders can be restored");
        }

        // Restore quantity to inventory
        $updateStmt = $pdo->prepare("
            UPDATE merchandise_inventory 
            SET qty_on_hand = qty_on_hand + :quantity,
                last_restocked = NOW()
            WHERE product_name = :product_name
        ");
        $updateStmt->execute([
            ':quantity' => $pieces,
            ':product_name' => $productName
        ]);

        // Delete the order record
        $deleteStmt = $pdo->prepare("DELETE FROM order_status_history WHERE id = :order_id");
        $deleteStmt->execute([':order_id' => $orderId]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Order restored successfully! {$pieces} piece(s) of '{$productName}' added back to inventory."
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Restore Order Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==============================================
// ACTION 3: UPDATE TO PENDING ONLY (NEW FEATURE)
// ==============================================
if ($action === 'update_to_pending') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $deliveryNumber = trim($_POST['delivery_number'] ?? '');

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get current status and product details
        $stmt = $pdo->prepare("SELECT status, product_name, pieces FROM order_status_history WHERE id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentOrder) {
            throw new Exception("Order not found");
        }

        $oldStatus = $currentOrder['status'];
        $productName = $currentOrder['product_name'];
        $quantity = intval($currentOrder['pieces']);

        // If current status is PAID, restore inventory when changing to PENDING
        if ($oldStatus === 'PAID') {
            $updateStmt = $pdo->prepare("
                UPDATE merchandise_inventory 
                SET qty_on_hand = qty_on_hand + :quantity,
                    last_restocked = NOW()
                WHERE product_name = :product_name
            ");
            $updateStmt->execute([
                ':quantity' => $quantity,
                ':product_name' => $productName
            ]);
        }

        // Update status to PENDING
        $stmt = $pdo->prepare("UPDATE order_status_history SET status = 'PENDING' WHERE id = :order_id");
        $stmt->execute([':order_id' => $orderId]);

        $pdo->commit();

        $message = "Order status updated to PENDING successfully!";
        if ($oldStatus === 'PAID') {
            $message .= " Inventory has been restored.";
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'status' => 'PENDING',
            'old_status' => $oldStatus,
            'order_id' => $orderId
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Update to PENDING Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>