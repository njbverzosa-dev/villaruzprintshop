<?php
// Customer_API/cart_operations.php
session_start();
require_once __DIR__ . '/../DB_Conn/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'increment':
            $cartId = $_POST['cart_id'] ?? 0;

            $stmt = $pdo->prepare("SELECT * FROM cart WHERE id = ?");
            $stmt->execute([$cartId]);
            $cartItem = $stmt->fetch();

            if (!$cartItem) {
                echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                exit();
            }

            $newPieces = $cartItem['pieces'] + 1;
            $newTotal = $cartItem['selling_price'] * $newPieces;

            $stmt = $pdo->prepare("UPDATE cart SET pieces = ?, total_amount = ? WHERE id = ?");
            $stmt->execute([$newPieces, $newTotal, $cartId]);

            echo json_encode(['success' => true, 'message' => 'Quantity increased']);
            break;

        case 'decrement':
            $cartId = $_POST['cart_id'] ?? 0;

            $stmt = $pdo->prepare("SELECT * FROM cart WHERE id = ?");
            $stmt->execute([$cartId]);
            $cartItem = $stmt->fetch();

            if (!$cartItem) {
                echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                exit();
            }

            if ($cartItem['pieces'] > 1) {
                $newPieces = $cartItem['pieces'] - 1;
                $newTotal = $cartItem['selling_price'] * $newPieces;

                $stmt = $pdo->prepare("UPDATE cart SET pieces = ?, total_amount = ? WHERE id = ?");
                $stmt->execute([$newPieces, $newTotal, $cartId]);

                echo json_encode(['success' => true, 'message' => 'Quantity decreased']);
            } else {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
                $stmt->execute([$cartId]);

                echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            }
            break;

        case 'remove':
            $cartId = $_POST['cart_id'] ?? 0;

            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
            $stmt->execute([$cartId]);

            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            break;

        case 'clear_cart':
            $userAccNumber = $_POST['acc_number'] ?? '';

            if (!empty($userAccNumber)) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE acc_number = ?");
                $stmt->execute([$userAccNumber]);
                $deletedCount = $stmt->rowCount();
                echo json_encode(['success' => true, 'message' => "$deletedCount item(s) cleared from cart"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Account number is required']);
            }
            break;

        case 'checkout':
            $customerName = $_POST['customer_name'] ?? '';
            $deliveryAddress = $_POST['delivery_address'] ?? '';
            $city = $_POST['city'] ?? '';
            $barangay = $_POST['barangay'] ?? '';
            $deliveryDateRaw = $_POST['delivery_date'] ?? '';
            $userAccNumber = $_POST['acc_number'] ?? '';

            if (empty($customerName) || empty($deliveryAddress) || empty($deliveryDateRaw)) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit();
            }

            if (empty($userAccNumber)) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                exit();
            }

            // Format delivery date from YYYY-MM-DD to "Sat, 23 March 2026" format
            $deliveryDateFormatted = '';
            if (!empty($deliveryDateRaw)) {
                $timestamp = strtotime($deliveryDateRaw);
                $deliveryDateFormatted = date('D, j F Y', $timestamp);
            }

            // Get cart items for this user
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE acc_number = ?");
            $stmt->execute([$userAccNumber]);
            $cartItems = $stmt->fetchAll();

            if (empty($cartItems)) {
                echo json_encode(['success' => false, 'message' => 'Cart is empty']);
                exit();
            }

            // Calculate subtotal amount from cart items
            $subtotalAmount = 0;
            foreach ($cartItems as $item) {
                $subtotalAmount += $item['total_amount'];
            }

            // Check location table for delivery charge based on barangay
            // If subtotal is 500 or more, delivery charge is 0 (free delivery)
            $deliveryCharge = 0;
            if ($subtotalAmount < 500) {
                if (!empty($barangay)) {
                    $stmt = $pdo->prepare("SELECT charge FROM location WHERE delivery_location = ?");
                    $stmt->execute([$barangay]);
                    $location = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($location) {
                        $deliveryCharge = floatval($location['charge']);
                    }
                }
            } else {
                // Subtotal is 500 or more - free delivery
                $deliveryCharge = 0;
            }

            // Calculate total amount with delivery charge
            $totalAmount = $subtotalAmount + $deliveryCharge;

            // Generate delivery number
            date_default_timezone_set('Asia/Manila');
            $dateCode = date('Ymd');

            $stmt = $pdo->prepare("SELECT delivery_number FROM for_deliveries WHERE delivery_number LIKE :prefix ORDER BY delivery_number DESC LIMIT 1");
            $stmt->execute([':prefix' => 'DEL-' . $dateCode . '-%']);
            $lastDelivery = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lastDelivery) {
                if (preg_match('/DEL-' . $dateCode . '-(\d+)/', $lastDelivery['delivery_number'], $matches)) {
                    $sequence = intval($matches[1]) + 1;
                } else {
                    $sequence = 1;
                }
                $deliveryNumber = 'DEL-' . $dateCode . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            } else {
                $deliveryNumber = 'DEL-' . $dateCode . '-0001';
            }

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = $protocol . $host;
            $qrCodeLink = $baseUrl . '/delivery_receipt.php?delivery_number=' . urlencode($deliveryNumber);
            $dateTimeSold = date('D, j M Y g:i A');

            // Start transaction
            $pdo->beginTransaction();

            // Insert into for_deliveries with total amount (including delivery charge)
            $stmt = $pdo->prepare("INSERT INTO for_deliveries (acc_number, ordered_by, delivery_number, delivery_address, city, barangay, total_amount, charge, status, date_time_sold, qr_code, delivery_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?, ?, ?)");
            $result1 = $stmt->execute([
                $userAccNumber,
                $customerName,
                $deliveryNumber,
                $deliveryAddress,
                $city,
                $barangay,
                $totalAmount,
                $deliveryCharge,
                $dateTimeSold,
                $qrCodeLink,
                $deliveryDateFormatted
            ]);

            if (!$result1) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to create delivery record']);
                exit();
            }

            $orderId = $pdo->lastInsertId();

            // Insert items into order_status_history
            $successCount = 0;
            foreach ($cartItems as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_status_history (acc_number, order_id, delivery_address, delivery_number, product_name, selling_price, status, pieces, unit, total_amount, date_time_sold, delivery_date, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $result2 = $stmt->execute([
                    $userAccNumber,
                    $orderId,
                    $deliveryAddress,
                    $deliveryNumber,
                    $item['product_name'],
                    $item['selling_price'],  // Added: selling_price value
                    'PENDING',                // Added: status value
                    $item['pieces'],
                    $item['unit'],
                    $item['total_amount'],
                    $dateTimeSold,
                    $deliveryDateFormatted,
                    $qrCodeLink
                ]);

                if ($result2) {
                    $successCount++;
                }
            }

            if ($successCount === 0) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'No items were inserted']);
                exit();
            }

            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE acc_number = ?");
            $stmt->execute([$userAccNumber]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Order placed successfully! Delivery #: ' . $deliveryNumber,
                'delivery_number' => $deliveryNumber,
                'order_id' => $orderId,
                'items_count' => $successCount,
                'subtotal_amount' => $subtotalAmount,
                'delivery_charge' => $deliveryCharge,
                'total_amount' => $totalAmount,
                'delivery_date' => $deliveryDateFormatted
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Cart Operations Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>