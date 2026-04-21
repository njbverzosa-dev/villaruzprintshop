<?php
// all_costumer.php
require_once 'access_sessions.php'; // includes session check, fetches $user

// Fetch all deliveries (for statistics)
$stmt = $pdo->prepare("SELECT * FROM for_deliveries ORDER BY date_time_sold");
$stmt->execute();
$allDeliveries = $stmt->fetchAll();

// Calculate statistics from all deliveries
$totalPaid = 0;
$totalPending = 0;
$totalCancelled = 0;
$totalAll = 0;

foreach ($allDeliveries as $delivery) {
    $amount = floatval($delivery['total_amount']);
    $totalAll += $amount;

    switch ($delivery['status']) {
        case 'PAID':
            $totalPaid += $amount;
            break;
        case 'CANCELLED':
            $totalCancelled += $amount;
            break;
        case 'PENDING':
        default:
            $totalPending += $amount;
            break;
    }
}

$totalCustomers = count(array_unique(array_column($allDeliveries, 'ordered_by')));

// Fetch ONLY PENDING deliveries for the grid
$stmt = $pdo->prepare("SELECT * FROM for_deliveries WHERE status = 'PENDING' ORDER BY date_time_sold DESC");
$stmt->execute();
$pendingDeliveries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Pending Orders | Villaruz Print Shop & General Merchandise</title>
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

        /* Main content wrapper (no sidebar) */
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

        .welcome h1 {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }

        .welcome span {
            color: #3b82f6;
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Remove underline from any link inside stats-grid */
        .stats-grid a {
            text-decoration: none;
            display: block;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stat-card.active {
            background: #eff6ff;
            color: #3b82f6;
            border-left: 3px solid #3b82f6;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: #3b82f6;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.1);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .stat-icon.total-sales {
            color: #3b82f6;
        }

        .stat-icon.pending {
            color: #f59e0b;
        }

        .stat-icon.cancelled {
            color: #ef4444;
        }

        .stat-icon.all-revenue {
            color: #10b981;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #1e293b;
        }

        .stat-label {
            font-size: 14px;
            color: #64748b;
        }

        /* Search Bar */
        .search-section {
            margin-bottom: 25px;
            display: flex;
            justify-content: flex-end;
        }

        .search-wrapper {
            position: relative;
            width: 300px;
        }

        .search-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Orders Grid - Cards (shopping style) */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .order-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 16px;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            text-align: center;
        }

        .order-card:hover {
            border-color: #3b82f6;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .customer-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .delivery-number {
            font-size: 10px;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 20px;
            color: #475569;
            text-align: center;
        }

        .order-detail {
            margin-bottom: 10px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: #475569;
        }

        .order-detail i {
            width: 20px;
            color: #3b82f6;
            margin-top: 2px;
        }

        .order-detail span {
            flex: 1;
            word-break: break-word;
        }

        .order-amount {
            font-size: 20px;
            font-weight: 800;
            color: #3b82f6;
            margin: 10px 0;
            text-align: right;
        }

        .order-date {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 12px;
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
            text-align: center;
            width: fit-content;
        }

        .status-paid {
            background: #10b981;
            color: white;
        }

        .status-pending {
            background: #f59e0b;
            color: white;
        }

        .status-cancelled {
            background: #ef4444;
            color: white;
        }

        /* Status select */
        .status-select {
            width: 100%;
            padding: 8px 12px;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
            cursor: pointer;
        }

        .status-select:hover {
            border-color: #3b82f6;
        }

        /* Action buttons */
        .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .action-btn {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            padding: 6px 0;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            color: #475569;
        }

        .action-btn i {
            margin-right: 5px;
        }

        .action-btn:hover {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }

        /* Hide View button for pending orders */
        .action-btn.view-btn-hidden {
            display: none;
        }

        .restore-btn {
            background: #ef4444;
            border-color: #ef4444;
            color: white;
        }

        .restore-btn:hover {
            background: #dc2626;
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

        footer {
            background: #ffffff;
            padding: 20px 5%;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .search-section {
                justify-content: stretch;
            }

            .search-wrapper {
                width: 100%;
            }

            .orders-grid {
                grid-template-columns: 1fr;
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
                <div class="user-name"><?php echo htmlspecialchars($user['username'] ?? $user['acc_number']); ?></div>
            </div>
            <div class="menu-nav">
                <a href="registered_customers.php" class="nav-item">
                    <i class="fas fa-user-friends"></i>
                    <span>Customers</span>
                </a>
                <a href="all_products.php" class="nav-item">
                    <i class="fas fa-cubes"></i>
                    <span>Stocks</span>
                </a>
                <a href="sold_products.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Sold</span>
                </a>
                <a href="all_pending_orders.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Orders</span>
                </a>
                <a href="blank_spreadsheet.php" class="nav-item">
                    <i class="fas fa-table"></i>
                    <span>Spreadsheet</span>
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
                    <h4>PENDING ORDERS</h4>
                </div>
            </div>

           <!-- Stats Cards (totals from ALL orders) -->
            <div class="stats-grid">
                <a href="all_pending_orders.php">
                    <div class="stat-card active">
                        <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
                        <div class="stat-value">₱ <?php echo number_format($totalPending); ?></div>
                        <div class="stat-label">Total Pending</div>
                    </div>
                </a>

                <a href="all_costumer.php">
                    <div class="stat-card ">
                        <div class="stat-icon total-sales"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-value">₱
                            <?php echo number_format($totalPaid); ?>
                        </div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </a>
            </div>

            <!-- Search Bar (searches only among PENDING orders) -->
            <div class="search-section">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search by customer name..."
                        autocomplete="off">
                </div>
            </div>

            <!-- Orders Grid - Only PENDING deliveries -->
            <div class="orders-grid" id="ordersGrid">
                <?php if (empty($pendingDeliveries)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #94a3b8;">
                        <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                        No pending orders found.
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingDeliveries as $delivery):
                        $status = $delivery['status']; // should always be 'PENDING'
                        ?>
                        <div class="order-card"
                            data-customer="<?php echo strtolower(htmlspecialchars($delivery['ordered_by'])); ?>">
                            <div class="order-header">
                                <div class="delivery-number">#<?php echo htmlspecialchars($delivery['delivery_number']); ?>
                                </div>
                            </div>
                            <div class="order-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($delivery['delivery_address'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="customer-name"><?php echo htmlspecialchars($delivery['ordered_by']); ?></div>
                            <div class="order-amount">₱ <?php echo number_format($delivery['total_amount'], 2); ?></div>
                            <div class="order-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('M d, Y g:i A', strtotime($delivery['date_time_sold'])); ?>
                            </div>
                            <div class="status-badge status-pending" id="status-badge-<?php echo $delivery['id']; ?>">
                                <?php echo $status; ?>
                            </div>
                            <select class="status-select" id="status-select-<?php echo $delivery['id']; ?>"
                                data-order-id="<?php echo $delivery['id']; ?>"
                                data-delivery-number="<?php echo htmlspecialchars($delivery['delivery_number']); ?>">
                                <option value="PAID">PAID</option>
                                <option value="PENDING" selected>PENDING</option>
                            </select>
                            <div class="card-actions">
                                <a href="../delivery_receipt.php?delivery_number=<?php echo urlencode($delivery['delivery_number']); ?>"
                                    class="action-btn" target="_blank"><i class="fas fa-receipt"></i> Delivery</a>
                                <a href="../billing_receipt.php?delivery_number=<?php echo urlencode($delivery['delivery_number']); ?>"
                                    class="action-btn" target="_blank"><i class="fas fa-file-invoice"></i> Billing</a>
                                <!-- View button - hidden for pending orders -->
                                <a href="../web/selected_data.php?delivery_number=<?php echo urlencode($delivery['delivery_number']); ?>"
                                    class="action-btn view-btn-hidden" target="_blank" style="display: none;"><i class="fas fa-eye"></i>View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer>
        <div class="copyright">
            <p> © 2026 Villaruz Print Shop & General Merchandise — Quality prints & everyday goods, delivered with care.
            </p>
        </div>
    </footer>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';

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

        // ========== TOAST ==========
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

        // ========== STATUS UPDATE ==========
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', async function () {
                const orderId = this.dataset.orderId;
                const deliveryNumber = this.dataset.deliveryNumber;
                const newStatus = this.value;

                const confirmed = confirm(`Are you sure you want to change status to ${newStatus} for Delivery #${deliveryNumber}?`);
                if (!confirmed) {
                    // revert to original selected value (should be PENDING)
                    this.value = 'PENDING';
                    return;
                }

                this.disabled = true;
                try {
                    const formData = new FormData();
                    formData.append('action', 'update_order_status');
                    formData.append('order_id', orderId);
                    formData.append('delivery_number', deliveryNumber);
                    formData.append('status', newStatus);
                    formData.append('csrf_token', csrfToken);

                    const response = await fetch('../API/update_delivery_status.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data.message || 'Failed to update status', 'error');
                        this.value = 'PENDING';
                    }
                } catch (err) {
                    showToast('Network error', 'error');
                    this.value = 'PENDING';
                } finally {
                    this.disabled = false;
                }
            });
        });

        // ========== LIVE SEARCH ==========
        const searchInput = document.getElementById('searchInput');
        const orderCards = document.querySelectorAll('.order-card');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const term = this.value.toLowerCase().trim();
                orderCards.forEach(card => {
                    const customer = card.getAttribute('data-customer') || '';
                    if (term === '' || customer.includes(term)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>

</html>