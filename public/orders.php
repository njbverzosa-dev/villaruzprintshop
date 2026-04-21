<?php
// orders.php 
require_once 'access_sessions.php';

$userAccNumber = $user['acc_number'] ?? '';

// Fetch all deliveries for this user from for_deliveries table
$stmt = $pdo->prepare("SELECT * FROM for_deliveries WHERE acc_number = ? ORDER BY date_time_sold DESC");
$stmt->execute([$userAccNumber]);
$deliveries = $stmt->fetchAll();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Orders | Villaruz Print Shop & General Merchandise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ========== MODERN LIGHT GRAY DASHBOARD STYLES ========== */
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

        /* Main content wrapper */
        .app-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Main content */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Dashboard header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #ffffff;
            padding: 20px 30px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .welcome h3 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .welcome h3 i {
            color: #3b82f6;
            margin-right: 10px;
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

        /* Orders Grid */
        .orders-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* Order Card */
        .order-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .order-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Order Header */
        .order-header {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .order-info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .order-info-item:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .order-info-item i {
            font-size: 20px;
            color: #3b82f6;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            border-radius: 10px;
        }

        .order-info-item .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .order-info-item .value {
            font-weight: 600;
            color: #0f172a;
            font-size: 13px;
            margin-top: 2px;
        }

        /* Status Badge */
        .status-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }

        .status-badge {
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .status-processing {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-completed {
            background: #d1fae5;
            color: #059669;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-ready {
            background: #d1fae5;
            color: #059669;
        }

        .status-shipped {
            background: #fed7aa;
            color: #c2410c;
        }

        /* View Order Button */
        .order-footer {
            padding: 15px 25px 20px 25px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            text-align: right;
        }

        .view-order-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .view-order-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        /* Cancel Item Button */
        .cancel-item-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .cancel-item-btn:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .cancel-item-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
        }

        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            width: 90%;
            max-width: 1000px;
            border-radius: 20px;
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease;
            max-height: 85vh;
            overflow-y: auto;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: #ffffff;
            border-radius: 20px 20px 0 0;
        }

        .modal-header h2 {
            font-size: 20px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            color: #3b82f6;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #94a3b8;
            transition: 0.2s;
            line-height: 1;
        }

        .close-modal:hover {
            color: #dc2626;
        }

        .modal-body {
            padding: 25px;
        }

        .order-summary {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }

        .order-summary p {
            margin: 8px 0;
            font-size: 14px;
        }

        .order-summary strong {
            color: #0f172a;
        }

        .items-table-modal {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items-table-modal th {
            text-align: left;
            padding: 12px 15px;
            background: #f1f5f9;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table-modal td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #1e293b;
        }

        .total-row-modal {
            background: #f8fafc;
            font-weight: 600;
        }

        .total-row-modal td {
            border-top: 1px solid #e2e8f0;
            padding: 15px;
        }

        .empty-items {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        /* Empty Orders Style */
        .empty-orders {
            text-align: center;
            padding: 80px 20px;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }

        .empty-orders i {
            font-size: 80px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-orders h3 {
            font-size: 24px;
            color: #475569;
            margin-bottom: 10px;
        }

        .empty-orders p {
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

        /* Toast Notification */
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .toast-success {
            background: #10b981;
        }

        .toast-error {
            background: #ef4444;
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

        /* Simple Footer */
        footer {
            background: #ffffff;
            padding: 20px 5%;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .dashboard-header {
                padding: 15px 20px;
            }

            .welcome h3 {
                font-size: 20px;
            }

            .order-info {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .order-header {
                padding: 15px 20px;
            }

            .burger-btn {
                top: 15px;
                right: 15px;
                width: 42px;
                height: 42px;
            }

            .side-menu {
                width: 260px;
            }

            .modal-content {
                margin: 10% auto;
                width: 95%;
            }

            .modal-body {
                padding: 15px;
            }

            .items-table-modal {
                font-size: 12px;
            }

            .items-table-modal th,
            .items-table-modal td {
                padding: 8px 10px;
            }

            .cancel-item-btn {
                padding: 4px 8px;
                font-size: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="app-wrapper">
        <!-- CSRF Token Hidden -->
        <input type="hidden" id="csrfToken" value="<?php echo $csrfToken; ?>">
        <!-- User Account Number Hidden -->
        <input type="hidden" id="userAccNumber" value="<?php echo htmlspecialchars($userAccNumber); ?>">

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
                <a href="cart.php" class="nav-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Cart</span>
                </a>
                <a href="orders.php" class="nav-item active">
                    <i class="fas fa-truck"></i>
                    <span>Order</span>
                </a>
                <a href="closed.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <main class="main-content">
            <div class="dashboard-header">
                <div class="welcome">
                    <h3><i class="fas fa-truck"></i> My Orders</h3>
                </div>
            </div>

            <?php if (empty($deliveries)): ?>
                <div class="empty-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a href="shop.php" class="shop-now-btn">Shop Now</a>
                </div>
            <?php else: ?>
                <div class="orders-container">
                    <?php foreach ($deliveries as $delivery): ?>
                        <div class="order-card" data-delivery-number="<?php echo htmlspecialchars($delivery['delivery_number']); ?>">
                            <div class="order-header">
                                <div class="order-info">
                                    <div class="order-info-item">
                                        <i class="fas fa-user"></i>
                                        <div>
                                            <div class="label">Ordered By</div>
                                            <div class="value"><?php echo htmlspecialchars($delivery['ordered_by']); ?></div>
                                        </div>
                                    </div>
                                    <div class="order-info-item">
                                        <i class="fas fa-hashtag"></i>
                                        <div>
                                            <div class="label">Delivery Number</div>
                                            <div class="value"><?php echo htmlspecialchars($delivery['delivery_number']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <div>
                                            <div class="label">Delivery Address</div>
                                            <div class="value"><?php echo htmlspecialchars($delivery['delivery_address']); ?></div>
                                        </div>
                                    </div>
                                    <div class="order-info-item">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <div>
                                            <div class="label">Delivery Fee</div>
                                            <div class="value">₱ <?php echo number_format($delivery['charge'] ?? 0, 2); ?></div>
                                        </div>
                                    </div>
                                    <div class="order-info-item">
                                        <i class="fas fa-receipt"></i>
                                        <div>
                                            <div class="label">Total Amount</div>
                                            <div class="value">₱ <?php echo number_format($delivery['total_amount'], 2); ?></div>
                                        </div>
                                    </div>
                                    <div class="order-info-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <div>
                                            <div class="label">Delivery Date</div>
                                            <div class="value"><?php echo htmlspecialchars($delivery['delivery_date'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    <div class="order-info-item">
                                        <i class="fas fa-clock"></i>
                                        <div>
                                            <div class="label">Order Date</div>
                                            <div class="value"><?php echo htmlspecialchars($delivery['date_time_sold']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                            <div class="order-footer">
                                <button class="view-order-btn"
                                    onclick="viewOrder('<?php echo htmlspecialchars($delivery['delivery_number']); ?>')">
                                    <i class="fas fa-eye"></i> View Order Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal for Order Details -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-receipt"></i> Order Details</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #3b82f6;"></i>
                    <p>Loading order details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Footer -->
    <footer>
        <div class="copyright">
            <p>© 2026 Villaruz Print Shop & General Merchandise — Quality prints & everyday goods, delivered with care.</p>
        </div>
    </footer>

    <script>
        // ========== CSRF TOKEN & USER DATA ==========
        const csrfToken = document.getElementById('csrfToken') ? document.getElementById('csrfToken').value : '';
        const userAccNumber = document.getElementById('userAccNumber') ? document.getElementById('userAccNumber').value : '';
        
        if (!csrfToken) {
            console.error('CSRF token not found');
        }
        if (!userAccNumber) {
            console.error('User account number not found');
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

        if (burgerBtn) {
            burgerBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (sideMenu.classList.contains('open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
        }

        if (menuOverlay) {
            menuOverlay.addEventListener('click', closeMenu);
        }

        document.querySelectorAll('.side-menu .nav-item').forEach(link => {
            link.addEventListener('click', () => {
                closeMenu();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeMenu();
                closeModal();
            }
        });

        // ========== TOAST FUNCTION ==========
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

        // ========== MODAL FUNCTIONS ==========
        const modal = document.getElementById('orderModal');
        const modalBody = document.getElementById('modalBody');
        const closeModalBtn = document.querySelector('.close-modal');

        function closeModal() {
            modal.style.display = 'none';
        }

        if (closeModalBtn) {
            closeModalBtn.onclick = function () {
                closeModal();
            }
        }

        window.onclick = function (event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        let currentDeliveryNumber = '';

        function viewOrder(deliveryNumber) {
            currentDeliveryNumber = deliveryNumber;
            modal.style.display = 'block';
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #3b82f6;"></i><p>Loading order details...</p></div>';

            // Fetch order items from order_status_history
            fetch('../Customer_API/get_order_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'delivery_number=' + encodeURIComponent(deliveryNumber) + '&acc_number=' + encodeURIComponent(userAccNumber)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.items && data.items.length > 0) {
                        let itemsHtml = `
                            
                            <table class="items-table-modal" style="white-space: nowrap;">
                                <thead>
                                    <tr>
                                        <th>Prod. Name</th>
                                        <th>Sell. Price</th>
                                        <th>Unit</th>
                                        <th>Quantity</th>
                                        <th>Total Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;

                        let grandTotal = 0;
                        data.items.forEach(item => {
                            const total = parseFloat(item.total_amount) || 0;
                            grandTotal += total;
                            itemsHtml += `
                                <tr>
                                    <td><strong>${escapeHtml(item.product_name)}</strong></td>
                                    <td>₱ ${parseFloat(item.selling_price).toFixed(2)}</td>
                                    <td>${escapeHtml(item.unit || 'Pcs')}</td>
                                    <td>${item.pieces}</td>
                                    <td>₱ ${total.toFixed(2)}</td>
                                    <td>
                                        <button class="cancel-item-btn" 
                                            onclick="cancelItem(${item.id}, '${escapeHtml(item.product_name)}', ${item.pieces}, '${deliveryNumber}')">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });

                        itemsHtml += `
                                </tbody>
                                <tfoot class="total-row-modal">
                                    <tr>
                                        <td colspan="4" style="text-align: right;"><strong>Grand Total:</strong></td>
                                        <td><strong>₱ ${grandTotal.toFixed(2)}</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        `;

                        modalBody.innerHTML = itemsHtml;
                    } else {
                        modalBody.innerHTML = `
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #dc2626;"></i>
                                <p>${data.message || 'No items found for this order'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #dc2626;"></i>
                            <p>Failed to load order details. Please try again.</p>
                        </div>
                    `;
                });
        }

        // ========== CANCEL INDIVIDUAL ITEM FUNCTION ==========
        async function cancelItem(orderId, productName, pieces, deliveryNumber) {
            const confirmed = confirm(`⚠️ CANCEL ITEM\n\nAre you sure you want to cancel ${pieces} piece(s) of "${productName}"?`);
            
            if (!confirmed) return;

            const cancelBtn = event.target.closest('.cancel-item-btn');
            const originalText = cancelBtn.innerHTML;
            cancelBtn.disabled = true;
            cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const formData = new FormData();
                formData.append('action', 'cancel_order_item');
                formData.append('order_id', orderId);
                formData.append('product_name', productName);
                formData.append('pieces', pieces);
                formData.append('delivery_number', deliveryNumber);
                formData.append('acc_number', userAccNumber);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../Customer_API/cancel_order_item.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                    },
                    body: formData
                });
                
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    // Refresh the modal content
                    setTimeout(() => {
                        viewOrder(deliveryNumber);
                        // Also refresh the main page to update totals
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }, 1000);
                } else {
                    showToast(data.message || 'Failed to cancel item', 'error');
                    cancelBtn.disabled = false;
                    cancelBtn.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Error:', err);
                showToast('Network error. Please try again.', 'error');
                cancelBtn.disabled = false;
                cancelBtn.innerHTML = originalText;
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>

</html>