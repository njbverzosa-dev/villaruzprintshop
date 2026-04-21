<?php
// cart.php 
require_once 'access_sessions.php';

$userAccNumber = $user['acc_number'] ?? '';

// Fetch ALL cart items for current user, ordered by id
$stmt = $pdo->prepare("SELECT * FROM cart WHERE acc_number = ? ORDER BY id ASC");
$stmt->execute([$userAccNumber]);
$cartItems = $stmt->fetchAll();

// Get unique order numbers (for display purposes)
$currentOrderNumber = !empty($cartItems) ? $cartItems[0]['order_number'] : '';

// Calculate cart totals
$totalItems = 0;
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalItems += $item['pieces'];
    $totalAmount += $item['total_amount'];
}

// Fetch all delivery locations from location table
$stmt = $pdo->prepare("SELECT delivery_location, charge FROM location");
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create a JavaScript-friendly array of locations with charges
$locationData = [];
foreach ($locations as $loc) {
    $locationData[strtolower(trim($loc['delivery_location']))] = floatval($loc['charge']);
}

// Check if subtotal is 500 or more (free delivery)
$isFreeDelivery = $totalAmount >= 500;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Cart | Villaruz Print Shop & General Merchandise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .app-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }

        /* ========== BURGER BUTTON (FIXED) ========== */
        .burger-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 48px;
            height: 48px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1001;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .burger-btn:hover {
            background: #f8fafc;
            transform: scale(1.02);
        }

        .burger-btn i {
            font-size: 24px;
            color: #3b82f6;
        }

        /* ========== RIGHT SIDE MENU (OVERLAY) ========== */
        .side-menu {
            position: fixed;
            top: 0;
            right: -320px;
            width: 280px;
            height: 100vh;
            background: #ffffff;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.1);
            z-index: 1002;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #e2e8f0;
        }

        .side-menu.open {
            right: 0;
        }

        /* Menu header with user info */
        .menu-header {
            padding: 25px 20px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .menu-header .user-name {
            font-weight: 700;
            font-size: 18px;
            color: #0f172a;
            margin-top: 8px;
        }

        .menu-header .user-greeting {
            font-size: 13px;
            color: #64748b;
        }

        .menu-header i {
            font-size: 40px;
            color: #3b82f6;
        }

        /* Menu navigation list */
        .menu-nav {
            flex: 1;
            padding: 20px;
        }

        .menu-nav .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 12px;
            border-radius: 14px;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 8px;
        }

        .menu-nav .nav-item i {
            width: 24px;
            font-size: 20px;
            color: #3b82f6;
        }

        .menu-nav .nav-item span {
            font-size: 15px;
            font-weight: 500;
        }

        .menu-nav .nav-item:hover {
            background: #eff6ff;
            color: #1e293b;
        }

        .menu-nav .nav-item.active {
            background: #eff6ff;
            color: #3b82f6;
            border-left: 3px solid #3b82f6;
        }

        /* Overlay background when menu is open */
        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 1000;
            display: none;
        }

        .menu-overlay.active {
            display: block;
        }

        /* Cart Grid Layout */
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 30px;
        }

        /* Cart Items */
        .cart-items {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .cart-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 20px;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            transition: 0.2s;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item:hover {
            background: #f8fafc;
        }

        .item-details h3 {
            font-size: 18px;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .item-unit {
            font-size: 13px;
            color: #64748b;
            background: #f1f5f9;
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .item-price {
            font-size: 20px;
            font-weight: 700;
            color: #3b82f6;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8fafc;
            padding: 8px 16px;
            border-radius: 40px;
        }

        .quantity-btn {
            background: white;
            border: 1px solid #e2e8f0;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            color: #3b82f6;
            transition: 0.2s;
        }

        .quantity-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .quantity-value {
            font-size: 16px;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
        }

        .remove-item {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .remove-item:hover {
            background: #fee2e2;
        }

        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .cart-summary h3 {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            color: #475569;
        }

        .summary-row.total {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
            margin-top: 10px;
            padding-top: 20px;
        }

        .summary-row.total span:last-child {
            color: #3b82f6;
        }

        .delivery-fee-row {
            color: #f59e0b;
            font-weight: 600;
        }

        .delivery-fee-row span:last-child {
            color: #f59e0b;
        }

        .free-delivery-badge {
            background: #d1fae5;
            color: #059669;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            margin: 10px 0;
        }

        .checkout-inputs {
            margin: 20px 0;
        }

        .checkout-inputs input,
        .checkout-inputs select {
            width: 100%;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
        }

        .checkout-inputs input:focus,
        .checkout-inputs select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.2s;
        }

        .checkout-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .checkout-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .clear-cart-btn {
            width: 100%;
            background: white;
            color: #ef4444;
            border: 2px solid #ef4444;
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.2s;
        }

        .clear-cart-btn:hover {
            background: #ef4444;
            color: white;
        }

        .empty-cart {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-cart i {
            font-size: 80px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-cart h3 {
            font-size: 24px;
            color: #475569;
            margin-bottom: 10px;
        }

        .empty-cart p {
            color: #94a3b8;
            margin-bottom: 30px;
        }

        .shop-now-btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 40px;
            font-weight: 600;
            transition: 0.2s;
        }

        .shop-now-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        /* Toast */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }

        .toast-success {
            background: #10b981;
        }

        .toast-error {
            background: #ef4444;
        }

        /* Custom Alert Modal */
        .custom-alert {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 3000;
            justify-content: center;
            align-items: center;
        }

        .custom-alert-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: modalSlideIn 0.3s ease;
        }

        .custom-alert-content i {
            font-size: 60px;
            color: #10b981;
            margin-bottom: 20px;
        }

        .custom-alert-content h3 {
            font-size: 24px;
            color: #0f172a;
            margin-bottom: 15px;
        }

        .custom-alert-content p {
            color: #475569;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .custom-alert-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .custom-alert-btn:hover {
            background: #2563eb;
            transform: scale(1.02);
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .loading-spinner i {
            font-size: 40px;
            color: #3b82f6;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Simple Footer */
        footer {
            background: #ffffff;
            padding: 20px 5%;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .cart-grid {
                grid-template-columns: 1fr;
            }

            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }

            .quantity-control {
                justify-content: center;
            }
        }

        .info-banner {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #0369a1;
        }

        .info-banner i {
            font-size: 20px;
        }

        .info-banner span {
            flex: 1;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="app-wrapper">

        <!-- Burger Button (Fixed) -->
        <div class="burger-btn" id="burgerBtn">
            <i class="fas fa-bars"></i>
        </div>

        <!-- Overlay for menu background -->
        <div class="menu-overlay" id="menuOverlay"></div>

        <!-- Right Side Menu -->
        <div class="side-menu" id="sideMenu">
            <div class="menu-header">
                <i class="fas fa-store"></i>
                <div class="user-greeting">Logged in as</div>
                <div class="user-name"><?php echo htmlspecialchars($user['acc_number'] ?? 'User'); ?></div>
            </div>
            <div class="menu-nav">
                <a href="shop.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Shop</span>
                </a>
                <a href="cart.php" class="nav-item active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Cart</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-truck"></i>
                    <span>Order</span>
                </a>
                <a href="closed.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <div class="cart-grid">
            <div class="cart-items" id="cartItemsContainer">
                <?php if (empty($cartItems)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-bag"></i>
                        <h4>Your cart is empty</h4>
                        <p>Looks like you haven't added any items to your cart yet.</p>
                        <a href="shop.php" class="shop-now-btn">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" data-id="<?php echo $item['id']; ?>">
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <span class="item-unit"><?php echo htmlspecialchars($item['unit'] ?? 'Pcs'); ?></span>
                                <?php if ($item['order_number']): ?>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 5px;">
                                        Order #: <?php echo htmlspecialchars($item['order_number']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="item-price">₱ <?php echo number_format($item['selling_price'], 2); ?></div>
                            <div class="quantity-control">
                                <button class="quantity-btn decrement" data-id="<?php echo $item['id']; ?>">-</button>
                                <span class="quantity-value"
                                    id="qty-<?php echo $item['id']; ?>"><?php echo $item['pieces']; ?></span>
                                <button class="quantity-btn increment" data-id="<?php echo $item['id']; ?>">+</button>
                                <button class="remove-item" data-id="<?php echo $item['id']; ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="cart-summary" id="cartSummary">
                <h3>Delivery Details</h3>
                <div class="checkout-inputs" id="checkoutInputs">
                    <input type="text" id="customerName" placeholder="Fullname *"  required autocomplete="off">
                    <select id="city" required>
    <option value="">Select City/Municipality</option>
    <option value="Dasol">Dasol</option>
    <option value="Burgos">Burgos</option>
    <option value="Mabini">Mabini</option>
    <!-- New cities added below -->
    <option value="Alaminos">Alaminos</option>
    <option value="Lingayen">Lingayen</option>
    <option value="Urdaneta">Urdaneta</option>
    <option value="San Carlos">San Carlos</option>
    <option value="Calasiao">Calasiao</option>
    <option value="Binmaley">Binmaley</option>
    <option value="Mangaldan">Mangaldan</option>
    <option value="San Fabian">San Fabian</option>
    <option value="Sual">Sual</option>
    <option value="Labrador">Labrador</option>
</select>
                    <select id="barangay" required>
                        <option value="">Select Barangay</option>
                    </select>
                    <input type="text" id="deliveryAddress" placeholder="Street, Purok, House No. *" required autocomplete="off">
                    <span>Select Delivery Date</span>
                    <input type="date" id="deliveryDate" required>
                </div>

                <h3>Order Summary</h3>
                <div class="info-banner">
                    <i class="fas fa-info-circle"></i>
                    <span>For orders below ₱500, We add some delivery fee depends on your location</span>
                </div>

                <?php if ($isFreeDelivery && !empty($cartItems)): ?>
                    <div class="free-delivery-badge">
                        <i class="fas fa-gift"></i> Free Delivery! (Orders ₱500 and above)
                    </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Total Items:</span>
                    <span id="totalItems"><?php echo $totalItems; ?></span>
                </div>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">₱ <?php echo number_format($totalAmount, 2); ?></span>
                </div>
                <div class="summary-row delivery-fee-row" id="deliveryFeeRow" style="display: none;">
                    <span>Delivery Fee:</span>
                    <span id="deliveryFeeAmount">₱ 0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="totalAmountDisplay">₱ <?php echo number_format($totalAmount, 2); ?></span>
                </div>


                <button class="checkout-btn" id="checkoutBtn" <?php echo empty($cartItems) ? 'disabled' : ''; ?>>
                    Proceed to Checkout <i class="fas fa-arrow-right"></i>
                </button>
                <button class="clear-cart-btn" id="clearCartBtn" <?php echo empty($cartItems) ? 'disabled' : ''; ?>>
                    <i class="fas fa-trash-alt"></i> Clear Cart
                </button>
            </div>
        </div>

        <!-- Custom Alert Modal -->
        <div id="customAlert" class="custom-alert">
            <div class="custom-alert-content">
                <i class="fas fa-check-circle"></i>
                <h3>Order Confirmed!</h3>
                <p>Thank you for your order! We will send a text message once your order is ready for delivery.</p>
                <button class="custom-alert-btn" onclick="closeCustomAlert()">OK</button>
            </div>
        </div>

        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner">
                <i class="fas fa-spinner"></i>
                <p>Processing...</p>
            </div>
        </div>
    </div>

    <!-- Simple Footer -->
    <footer>
        <div class="copyright">
            <p>© 2026 Villaruz Print Shop & General Merchandise — Quality prints & everyday goods, delivered with care.
            </p>
        </div>
    </footer>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const accNum = '<?php echo htmlspecialchars($user['acc_number'] ?? ''); ?>';
        const subtotalAmount = <?php echo $totalAmount; ?>;
        const hasItems = <?php echo json_encode(!empty($cartItems)); ?>;
        const isFreeDelivery = <?php echo json_encode($isFreeDelivery); ?>;

        // Location data from database
        const locationCharges = <?php echo json_encode($locationData); ?>;

        // Barangay data organized by city/municipality
        const barangayByCity = {
    'Dasol': [
        'Alilao', 'Amalbalan', 'Bobonot', 'Eguia', 'Gais-Guipe',
        'Hermosa', 'Macalang', 'Magsaysay', 'Malacapas', 'Malimpin',
        'Osmeña', 'Petal', 'Poblacion', 'San Vicente', 'Tambac',
        'Tambobong', 'Uli', 'Viga'
    ],
    'Burgos': [
        'Poblacion', 'Bayambang', 'Cabacaraan', 'Dompoc', 'Don Matias',
        'Ilio-ilio', 'Kita-kita', 'San Pascual', 'San Mateo', 'San Miguel'
    ],
    'Mabini': [
        'Poblacion', 'Barlo', 'Caabiangaan', 'Cabili', 'Caculangan',
        'Calayucay', 'Capataan', 'Cawaynian', 'Gayagayaan', 'Lungal',
        'Mabini', 'Nalvo', 'Pangascasan', 'San Juan', 'Tagudin', 'Toritori'
    ],
    // New cities
    'Alaminos': [
        'Poblacion', 'Baleyadaan', 'Bisocol', 'Bolaney', 'Cacabugaoan',
        'Lambayan', 'Mabalbalino', 'Magsaysay', 'Pangapisan', 'Poblacion East',
        'Poblacion West', 'San Antonio', 'San Jose', 'San Vicente', 'Tangcarang'
    ],
    'Lingayen': [
        'Poblacion', 'Aliwekwek', 'Basing', 'Batang', 'Bogtong', 'Bued',
        'Domalandan Center', 'Domalandan East', 'Domalandan West', 'Dorongan',
        'Libtong', 'Malimpuec', 'Maniboc', 'Matalava', 'Naguelguel', 'Namolan',
        'Pangapisan North', 'Pangapisan South', 'Poblacion', 'Quibaol', 'Rosario',
        'Sabangan', 'Tombor'
    ],
    'Urdaneta': [
        'Poblacion', 'Anonas', 'Bactad East', 'Bactad Proper', 'Bactad West',
        'Bayaoas', 'Bolaoen', 'Cabaruan', 'Cabuloan', 'Camantiles', 'Casantaan',
        'Catablan', 'Cayambanan', 'Consolacion', 'Dilan', 'Don Matias', 'Dr. Pedro T. Orata',
        'Lunec', 'Nancamaliran East', 'Nancamaliran West', 'Palguyod', 'Pias', 'Poblacion',
        'San Jose', 'San Vicente', 'Santa Lucia', 'Santo Domingo', 'Sugcong', 'Tayug'
    ],
    'San Carlos': [
        'Poblacion', 'Ableg', 'Bacnar', 'Balangobong', 'Balayang', 'Bani',
        'Basing', 'Batang', 'Bogtong', 'Bolingit', 'Bued', 'Cabawatan',
        'Cacandongan', 'Camanga', 'Coliling', 'Dulacac', 'Dumalay', 'Guesang',
        'Lepa', 'Libsong East', 'Libsong West', 'Mabalbalino', 'Magtaking',
        'Malacañang', 'Mamalingling', 'Nagbinalegan', 'Naguilayan', 'Nancamaliran',
        'Pangapisan', 'Pugal', 'Rancas', 'Real', 'San Juan', 'San Pedro', 'Taloy'
    ],
    'Calasiao': [
        'Poblacion', 'Ambalangan-Dalig', 'Bued', 'Buenlag', 'Cabilocaan',
        'Dinalaoan', 'Doyong', 'Gabon', 'Lasip', 'Longos', 'Lumbang',
        'Macabito', 'Malabago', 'Mancup', 'Nagsaing', 'Nalsian', 'Poblacion East',
        'Poblacion West', 'Quesban', 'San Miguel', 'San Vicente', 'Songkoy', 'Talibaew'
    ],
    'Binmaley': [
        'Poblacion', 'Amancoro', 'Balogo', 'Basing', 'Baybay Lopez', 'Baybay Polong',
        'Biec', 'Bugayong', 'Cabuayangan', 'Caloocan Norte', 'Caloocan Sur',
        'Camaley', 'Canan Norte', 'Canan Sur', 'Canaoalan', 'Dulacac', 'Gayaman',
        'Linoc', 'Lomboy', 'Malindong', 'Mapolopolo', 'Mayombo', 'Namolan', 'Naguilayan',
        'Payar', 'Poblacion', 'San Isidro Norte', 'San Isidro Sur', 'Santa Catalina',
        'Tombor', 'Vacante'
    ],
    'Mangaldan': [
        'Poblacion', 'Alitaya', 'Amansabina', 'Anolid', 'Banaoang', 'Bantayan',
        'Bari', 'Batang', 'Buenlag', 'Dasmariñas', 'Gueguesangen', 'Luzong',
        'Navaluan', 'Nibaliw', 'Palua', 'Poblacion', 'Pogo', 'Salay', 'San Jose',
        'San Miguel', 'San Vicente', 'Santo Niño', 'Tebag', 'Talospatang'
    ],
    'San Fabian': [
        'Poblacion', 'Alacan', 'Ambalangan-Dalig', 'Angio', 'Anonang', 'Aramal',
        'Bigbiga', 'Binday', 'Bolasi', 'Cabalitian', 'Capeñahan', 'Ganao',
        'Lipit-Tomeeng', 'Longos', 'Lubleo', 'Mabilao', 'Palapad', 'Poblacion',
        'Rosario', 'San Pedro', 'Sobra', 'Songkoy', 'Tebuel'
    ],
    'Sual': [
        'Poblacion', 'Baquioen', 'Baybay Norte', 'Baybay Sur', 'Bongalon',
        'Cabili', 'Canaoalan', 'Dalong', 'Gorak', 'Lasip', 'Makinabang',
        'Poblacion', 'Pogo', 'Pozo', 'San Isidro', 'San Pascual', 'Tombor',
        'Vacante', 'Victoria'
    ],
    'Labrador': [
        'Poblacion', 'Bolo', 'Bongalon', 'Dulig', 'Carot', 'Guerero', 'Magsaysay',
        'Poblacion', 'San Gonzalo', 'San Jose', 'San Roque', 'San Vicente', 'Santa Maria',
        'Tobuan', 'Uyong'
    ]
};

        // DOM Elements
        const checkoutBtn = document.getElementById('checkoutBtn');
        const customerNameInput = document.getElementById('customerName');
        const citySelect = document.getElementById('city');
        const barangaySelect = document.getElementById('barangay');
        const deliveryAddressInput = document.getElementById('deliveryAddress');
        const deliveryDateInput = document.getElementById('deliveryDate');
        const deliveryFeeRow = document.getElementById('deliveryFeeRow');
        const deliveryFeeAmountSpan = document.getElementById('deliveryFeeAmount');
        const totalAmountDisplaySpan = document.getElementById('totalAmountDisplay');

        let currentDeliveryFee = 0;
        let currentTotalAmount = subtotalAmount;

        // Function to populate barangay based on selected city
        function populateBarangay() {
            const selectedCity = citySelect.value;
            
            // Clear current barangay options
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            if (selectedCity && barangayByCity[selectedCity]) {
                const barangays = barangayByCity[selectedCity];
                barangays.forEach(barangay => {
                    const option = document.createElement('option');
                    option.value = barangay;
                    option.textContent = barangay;
                    barangaySelect.appendChild(option);
                });
                barangaySelect.disabled = false;
            } else {
                barangaySelect.disabled = true;
            }
            
            // Reset delivery fee calculation
            updateDeliveryFee();
            validateDeliveryInputs();
        }

        // Function to check barangay and update delivery fee
        function updateDeliveryFee() {
            // If free delivery (subtotal >= 500), always hide delivery fee
            if (isFreeDelivery) {
                currentDeliveryFee = 0;
                if (deliveryFeeRow) deliveryFeeRow.style.display = 'none';
                currentTotalAmount = subtotalAmount;
                if (totalAmountDisplaySpan) {
                    totalAmountDisplaySpan.innerHTML = '₱ ' + currentTotalAmount.toFixed(2);
                }
                return;
            }

            const barangay = barangaySelect ? barangaySelect.value.trim().toLowerCase() : '';

            // Check if barangay exists in location table
            if (barangay && locationCharges[barangay] !== undefined) {
                currentDeliveryFee = locationCharges[barangay];
                if (deliveryFeeRow) deliveryFeeRow.style.display = 'flex';
                if (deliveryFeeAmountSpan) {
                    deliveryFeeAmountSpan.innerHTML = '₱ ' + currentDeliveryFee.toFixed(2);
                }
            }
            else if (barangay && locationCharges[barangay] === undefined) {
                // Barangay not found in database
                currentDeliveryFee = 0;
                if (deliveryFeeRow) deliveryFeeRow.style.display = 'none';
                if (barangay !== '') {
                    showToast('Delivery fee not set for this barangay. Fee will be ₱0.00', 'error');
                }
            }
            else {
                // No barangay selected
                currentDeliveryFee = 0;
                if (deliveryFeeRow) deliveryFeeRow.style.display = 'none';
            }

            // Update total amount
            currentTotalAmount = subtotalAmount + currentDeliveryFee;
            if (totalAmountDisplaySpan) {
                totalAmountDisplaySpan.innerHTML = '₱ ' + currentTotalAmount.toFixed(2);
            }

            // Re-validate checkout button
            validateDeliveryInputs();
        }

        // Validate delivery inputs and enable/disable checkout button
        function validateDeliveryInputs() {
            const customerName = customerNameInput ? customerNameInput.value.trim() : '';
            const city = citySelect ? citySelect.value.trim() : '';
            const barangay = barangaySelect ? barangaySelect.value.trim() : '';
            const deliveryAddress = deliveryAddressInput ? deliveryAddressInput.value.trim() : '';
            const deliveryDate = deliveryDateInput ? deliveryDateInput.value : '';

            const isValid = customerName !== '' && city !== '' && barangay !== '' && deliveryAddress !== '' && deliveryDate !== '';
            if (checkoutBtn) checkoutBtn.disabled = !isValid;
            return isValid;
        }

        // ========== BURGER MENU TOGGLE ==========
        const burgerBtn = document.getElementById('burgerBtn');
        const sideMenu = document.getElementById('sideMenu');
        const menuOverlay = document.getElementById('menuOverlay');

        function openMenu() {
            sideMenu.classList.add('open');
            menuOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            sideMenu.classList.remove('open');
            menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        burgerBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sideMenu.classList.contains('open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        menuOverlay.addEventListener('click', closeMenu);

        document.querySelectorAll('.side-menu .nav-item').forEach(link => {
            link.addEventListener('click', () => {
                closeMenu();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeMenu();
            }
        });

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        function showCustomAlert() {
            const alert = document.getElementById('customAlert');
            alert.style.display = 'flex';
        }

        function closeCustomAlert() {
            const alert = document.getElementById('customAlert');
            alert.style.display = 'none';
            window.location.href = 'orders.php';
        }

        async function updateCartItem(cartId, action) {
            showLoading();
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('cart_id', cartId);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../Customer_API/cart_operations.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error updating cart', 'error');
                }
            } catch (err) {
                console.error('Error:', err);
                showToast('Network error. Please try again.', 'error');
            } finally {
                hideLoading();
            }
        }

        async function clearCart() {
            if (!confirm('Are you sure you want to clear your entire cart?')) return;

            showLoading();
            try {
                const formData = new FormData();
                formData.append('action', 'clear_cart');
                formData.append('acc_number', accNum);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../Customer_API/cart_operations.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast('Cart cleared successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error clearing cart', 'error');
                }
            } catch (err) {
                console.error('Error:', err);
                showToast('Network error. Please try again.', 'error');
            } finally {
                hideLoading();
            }
        }

        // Setup event listeners for delivery inputs
        function setupInputListeners() {
            if (customerNameInput) customerNameInput.addEventListener('input', validateDeliveryInputs);
            if (citySelect) {
                citySelect.addEventListener('change', function() {
                    populateBarangay();
                    validateDeliveryInputs();
                });
            }
            if (barangaySelect) {
                barangaySelect.addEventListener('change', function() {
                    updateDeliveryFee();
                    validateDeliveryInputs();
                });
            }
            if (deliveryAddressInput) deliveryAddressInput.addEventListener('input', validateDeliveryInputs);
            if (deliveryDateInput) deliveryDateInput.addEventListener('change', validateDeliveryInputs);
        }

        async function proceedToCheckout() {
            // Delivery order
            const customerName = customerNameInput ? customerNameInput.value.trim() : '';
            const deliveryAddress = deliveryAddressInput ? deliveryAddressInput.value.trim() : '';
            const city = citySelect ? citySelect.value.trim() : '';
            const barangay = barangaySelect ? barangaySelect.value.trim() : '';
            const deliveryDate = deliveryDateInput ? deliveryDateInput.value : '';

            if (!customerName) {
                showToast('Please enter customer name', 'error');
                return;
            }

            if (!city) {
                showToast('Please select city/municipality', 'error');
                return;
            }

            if (!barangay) {
                showToast('Please select barangay', 'error');
                return;
            }

            if (!deliveryAddress) {
                showToast('Please enter delivery address', 'error');
                return;
            }

            if (!deliveryDate) {
                showToast('Please select delivery date', 'error');
                return;
            }

            // Format delivery date
            const formattedDate = new Date(deliveryDate).toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const confirmed = confirm(
                '📋 Order Confirmation\n\n' +
                'Customer: ' + customerName + '\n' +
                'Delivery Address: ' + deliveryAddress + '\n' +
                'City/Municipality: ' + city + '\n' +
                'Barangay: ' + barangay + '\n' +
                'Delivery Date: ' + formattedDate + '\n' +
                'Subtotal: ₱ ' + subtotalAmount.toFixed(2) + '\n' +
                (currentDeliveryFee > 0 ? 'Delivery Fee: ₱ ' + currentDeliveryFee.toFixed(2) + '\n' : '') +
                'Total Amount: ₱ ' + currentTotalAmount.toFixed(2) + '\n\n' +
                'Are you sure you want to place this order?'
            );

            if (!confirmed) {
                return;
            }

            showLoading();
            try {
                const formData = new FormData();
                formData.append('action', 'checkout');
                formData.append('acc_number', accNum);
                formData.append('customer_name', customerName);
                formData.append('delivery_address', deliveryAddress);
                formData.append('city', city);
                formData.append('barangay', barangay);
                formData.append('delivery_date', deliveryDate);
                formData.append('delivery_fee', currentDeliveryFee);
                formData.append('total_amount_with_fee', currentTotalAmount);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../Customer_API/cart_operations.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    hideLoading();
                    showCustomAlert();
                } else {
                    showToast(data.message || 'Error placing order', 'error');
                    hideLoading();
                }
            } catch (err) {
                console.error('Error:', err);
                showToast('Network error. Please try again.', 'error');
                hideLoading();
            }
        }

        // Quantity buttons
        document.querySelectorAll('.decrement').forEach(btn => {
            btn.addEventListener('click', () => {
                const cartId = btn.dataset.id;
                updateCartItem(cartId, 'decrement');
            });
        });

        document.querySelectorAll('.increment').forEach(btn => {
            btn.addEventListener('click', () => {
                const cartId = btn.dataset.id;
                updateCartItem(cartId, 'increment');
            });
        });

        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', () => {
                const cartId = btn.dataset.id;
                if (confirm('Remove this item from cart?')) {
                    updateCartItem(cartId, 'remove');
                }
            });
        });

        document.getElementById('clearCartBtn')?.addEventListener('click', clearCart);
        document.getElementById('checkoutBtn')?.addEventListener('click', proceedToCheckout);

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        if (deliveryDateInput) {
            deliveryDateInput.min = today;
        }

        // Initial setup
        if (hasItems) {
            setupInputListeners();
            // Initialize barangay dropdown with default (empty)
            barangaySelect.disabled = true;
            updateDeliveryFee();
            validateDeliveryInputs();
        }
    </script>
</body>

</html>