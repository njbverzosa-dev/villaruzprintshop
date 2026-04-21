<?php
// sold_products.php
require_once 'access_sessions.php'; // includes session check, fetches $user


// Fetch where columns are NULL OR empty string
$stmt = $pdo->prepare("
    SELECT * FROM order_status_history 
    WHERE (acc_number IS NULL OR acc_number = '')
      AND (delivery_address IS NULL OR delivery_address = '')
      AND (delivery_number IS NULL OR delivery_number = '')
    ORDER BY id DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();


// Calculate statistics
$totalPaid = 0;          // sum of paid orders
$totalPendingAmount = 0; // sum of pending/not paid orders
$totalCancelled = 0;     // sum of cancelled orders
$pendingCount = 0;
$paidCount = 0;
$cancelledCount = 0;

foreach ($orders as $o) {
    $amount = floatval($o['total_amount']);
    $status = $o['status'];

    if ($status === 'PAID') {
        $totalPaid += $amount;
        $paidCount++;
    } elseif ($status === 'CANCELLED') {
        $totalCancelled += $amount;
        $cancelledCount++;
    } else { // PENDING, NOT PAID, or empty/null
        $totalPendingAmount += $amount;
        $pendingCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Sold Products | Villaruz Print Shop & General Merchandise</title>
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

        .welcome h4 {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: #3b82f6;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.1);
        }

        .stat-icon {
            font-size: 32px;
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #0f172a;
        }

        .stat-label {
            font-size: 14px;
            color: #64748b;
        }

        /* Table Styles */
        .merchandise-section {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
            margin-top: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-header h5 {
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0f172a;
        }

        .section-header h5 i {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            white-space: nowrap;
        }

        .inventory-table th,
        .inventory-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            white-space: nowrap;
        }

        .inventory-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            position: sticky;
            top: 0;
            white-space: nowrap;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .inventory-table tr:hover {
            background: #f8fafc;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-paid {
            background: #10b981;
            color: #ffffff;
        }

        .status-not-paid,
        .status-pending {
            background: #f59e0b;
            color: #ffffff;
        }

        .status-cancelled {
            background: #ef4444;
            color: #ffffff;
        }

        /* Status Select Dropdown */
        .status-select {
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            background: #f8fafc;
            transition: all 0.3s;
            margin-right: 8px;
        }

        .status-select:hover {
            border-color: #3b82f6;
        }

        .status-select.status-paid {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        .status-select.status-pending {
            background: #f59e0b;
            color: white;
            border-color: #f59e0b;
        }

        .status-select.status-cancelled {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        /* Print Receipt Button */
        .print-receipt-btn,
        .restore-btn {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            border: none;
            border-radius: 20px;
            padding: 6px 16px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin: 2px;
        }

        .restore-btn {
            background: linear-gradient(145deg, #059669, #047857);
        }

        .restore-btn:hover {
            background: linear-gradient(145deg, #047857, #065f46);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .print-receipt-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        /* Action cell */
        .action-cell {
            white-space: nowrap;
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

        .toast-warning {
            background: #f59e0b;
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

            .inventory-table th,
            .inventory-table td {
                padding: 10px 8px;
                font-size: 12px;
                text-align: center;
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
                <div class="user-name"><?php echo htmlspecialchars($user['acc_number'] ?? 'User'); ?></div>
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
                <a href="sold_products.php" class="nav-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Sold</span>
                </a>
                <a href="all_pending_orders.php" class="nav-item">
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
                    <h4>SOLD PRODUCTS</h4>
                </div>
            </div>

            <!-- Info Banner -->
            <div class="info-banner">
                <i class="fas fa-info-circle"></i>
                <span>Showing products without customer information (walk-in customers / counter sales).</span>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <!-- Total Sales (Paid) -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-value">₱ <?php echo number_format($totalPaid, 2); ?></div>
                    <div class="stat-label">Total Sales (Paid)</div>
                </div>
                <!-- Pending Amount -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-value">₱ <?php echo number_format($totalPendingAmount, 2); ?></div>
                    <div class="stat-label">Pending Amount</div>
                </div>
                <!-- Paid Orders Count -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?php echo $paidCount; ?></div>
                    <div class="stat-label">Paid Orders</div>
                </div>
                <!-- Cancelled Orders Count -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?php echo $cancelledCount; ?></div>
                    <div class="stat-label">Cancelled Orders</div>
                </div>
            </div>

            <div class="merchandise-section">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> Walk-in Orders</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="inventory-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Total Amount</th>
                                <th>Date Sold</th>
                                <th>Note</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-shopping-bag" style="font-size: 48px; color: #94a3b8; margin-bottom: 15px; display: block;"></i>
                                        No walk-in orders found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr data-id="<?php echo $order['id']; ?>">
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo $order['pieces']; ?></td>
                                        <td><?php echo htmlspecialchars($order['unit'] ?? 'Pcs'); ?></td>
                                        <td>₱ <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($order['date_time_sold'])); ?></td>
                                        <td><?php echo htmlspecialchars($order['note'] ?? ''); ?></td>
                                        <td>
                                            <span class="status-badge 
                                                <?php
                                                if ($order['status'] == 'PAID')
                                                    echo 'status-paid';
                                                elseif ($order['status'] == 'CANCELLED')
                                                    echo 'status-cancelled';
                                                else
                                                    echo 'status-pending';
                                                ?>
                                            ">
                                                <?php echo $order['status'] ?? 'PENDING'; ?>
                                            </span>
                                        </td>
                                        <td class="action-cell">
                                            <!-- Status Dropdown (Only for PENDING orders, NOT for PAID) -->
                                            <?php if ($order['status'] == 'PENDING' || empty($order['status'])): ?>
                                                <select class="status-select" id="status-select-<?php echo $order['id']; ?>"
                                                    data-order-id="<?php echo $order['id']; ?>"
                                                    data-delivery-number="<?php echo htmlspecialchars($order['delivery_number'] ?? ''); ?>">
                                                    <option value="PENDING" selected>PENDING</option>
                                                    <option value="CANCELLED">CANCELLED TO DELETE</option>
                                                    <option value="PAID">PAID</option>
                                                </select>
                                            <?php endif; ?>

                                            <!-- Restore Button (Only for PAID orders) -->
                                            <?php if ($order['status'] == 'PAID'): ?>
                                                <button class="restore-btn"
                                                    onclick="restoreOrder(<?php echo $order['id']; ?>, '<?php echo addslashes($order['product_name']); ?>', <?php echo $order['pieces']; ?>)">
                                                    <i class="fas fa-undo-alt"></i> RESTORE AND DELETE
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <footer>
        <div class="copyright">
            <p>© 2026 Villaruz Print Shop & General Merchandise — Quality prints & everyday goods, delivered with care.</p>
        </div>
    </footer>

    <script>
        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?php echo $_SESSION['csrf_token']; ?>';

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

        // ========== TOAST ==========
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'}"></i> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // ========== UPDATE STATUS ==========
        document.querySelectorAll('.status-select').forEach(select => {
            const updateSelectColor = (selectEl) => {
                const value = selectEl.value;
                selectEl.classList.remove('status-paid', 'status-pending', 'status-cancelled');
                if (value === 'PAID') {
                    selectEl.classList.add('status-paid');
                } else if (value === 'CANCELLED') {
                    selectEl.classList.add('status-cancelled');
                } else {
                    selectEl.classList.add('status-pending');
                }
            };

            updateSelectColor(select);

            select.addEventListener('change', async function () {
                const orderId = this.dataset.orderId;
                const deliveryNumber = this.dataset.deliveryNumber;
                const newStatus = this.value;

                let confirmMessage = `Are you sure you want to change status to ${newStatus} for this order?`;

                if (newStatus === 'CANCELLED') {
                    confirmMessage = `⚠️ WARNING: Cancelling this order will DELETE it permanently. This action cannot be undone. Are you sure?`;
                }

                if (newStatus === 'PAID') {
                    confirmMessage = `💰 Confirm payment: This will deduct stock from inventory. Are you sure?`;
                }

                const confirmed = confirm(confirmMessage);
                if (!confirmed) {
                    // Reset to original value
                    this.value = 'PENDING';
                    updateSelectColor(this);
                    return;
                }

                this.disabled = true;

                try {
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('order_id', orderId);
                    formData.append('delivery_number', deliveryNumber);
                    formData.append('status', newStatus);
                    formData.append('csrf_token', csrfToken);

                    const response = await fetch('../API/update_sold_products.php', {
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
                        updateSelectColor(this);
                    }
                } catch (err) {
                    console.error('Error:', err);
                    showToast('Network error. Please try again.', 'error');
                    this.value = 'PENDING';
                    updateSelectColor(this);
                } finally {
                    this.disabled = false;
                }
            });
        });

        // ========== RESTORE ORDER (Only for PAID orders) ==========
        async function restoreOrder(orderId, productName, pieces) {
            const confirmed = confirm(`🔄 Are you sure you want to restore ${pieces} piece(s) of "${productName}" back to inventory?\n\nThis will:\n✅ Add ${pieces} back to stock\n❌ Delete this order record\n\nThis action cannot be undone!`);
            if (!confirmed) return;

            const restoreBtn = event.target.closest('.restore-btn');
            const originalText = restoreBtn.innerHTML;
            restoreBtn.disabled = true;
            restoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';

            try {
                const formData = new FormData();
                formData.append('action', 'restore_order');
                formData.append('order_id', orderId);
                formData.append('product_name', productName);
                formData.append('pieces', pieces);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../API/update_sold_products.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Failed to restore order', 'error');
                    restoreBtn.disabled = false;
                    restoreBtn.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Error:', err);
                showToast('Network error. Please try again.', 'error');
                restoreBtn.disabled = false;
                restoreBtn.innerHTML = originalText;
            }
        }
    </script>
</body>

</html>