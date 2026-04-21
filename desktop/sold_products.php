<?php
// sold_product.php
require_once 'access_sessions.php'; // includes session check, fetches $user

// Fetch order status history with delivery info
$stmt = $pdo->prepare("
    SELECT osh.*, fd.delivery_number 
    FROM order_status_history osh
    LEFT JOIN for_deliveries fd ON osh.order_id = fd.id
    ORDER BY osh.id DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

// Calculate statistics
$totalOrders = count($orders);
$pending = count(array_filter($orders, fn($o) => $o['status'] === 'PENDING' || $o['status'] === 'NOT PAID'));
$paid = count(array_filter($orders, fn($o) => $o['status'] === 'PAID'));
$cancelled = count(array_filter($orders, fn($o) => $o['status'] === 'CANCELLED'));
$totalRevenue = array_sum(array_column($orders, 'total_amount'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameEarn · Sold Products</title>
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

        .app-wrapper {
            display: flex;
            flex: 1;
        }

        /* Main content */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            height: 100vh;
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
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
            font-size: 28px;
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
        .print-receipt-btn, .restore-btn {
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
            background: linear-gradient(145deg, #19191a, #575858);
        }

        .restore-btn:hover {
            background: linear-gradient(145deg, #059669, #047857);
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
            .app-wrapper {
                flex-direction: column;
            }

            .inventory-table th,
            .inventory-table td {
                padding: 10px 8px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="app-wrapper">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div class="welcome">
                    <h1>SOLD PAGE
                        <span><?php echo htmlspecialchars($user['acc_number']); ?></span>
                    </h1>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-value"><?php echo $totalOrders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-value"><?php echo $pending; ?></div>
                    <div class="stat-label">Pending/Not Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?php echo $paid; ?></div>
                    <div class="stat-label">Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?php echo $cancelled; ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-value">₱ <?php echo number_format($totalRevenue, 2); ?></div>
                    <div class="stat-label">Total Sales</div>
                </div>
            </div>

            <div class="merchandise-section">
                <div class="section-header">
                    <h5><i class="fas fa-history"></i> Order Status History</h5>
                </div>
                <div style="overflow-x: auto;">
                    <table class="inventory-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Date Sold</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">No orders found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr data-id="<?php echo $order['id']; ?>">
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo $order['pieces']; ?></td>
                                        <td><?php echo htmlspecialchars($order['unit'] ?? 'Pcs'); ?></td>
                                        <td>₱ <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php
                                            $status = $order['status'];
                                            if ($status == 'PAID')
                                                echo 'status-paid';
                                            elseif ($status == 'CANCELLED')
                                                echo 'status-cancelled';
                                            else
                                                echo 'status-not-paid';
                                            ?>" id="status-<?php echo $order['id']; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($order['date_time_sold'])); ?></td>
                                        <td class="action-cell">
                                            <select class="status-select" id="status-select-<?php echo $order['id']; ?>"
                                                data-order-id="<?php echo $order['id']; ?>"
                                                data-delivery-number="<?php echo htmlspecialchars($order['delivery_number'] ?? ''); ?>">
                                                <option value="PENDING" <?php echo $order['status'] == 'PENDING' ? 'selected' : ''; ?>>PENDING</option>
                                                <option value="PAID" <?php echo $order['status'] == 'PAID' ? 'selected' : ''; ?>>
                                                    PAID</option>
                                                <option value="CANCELLED" <?php echo $order['status'] == 'CANCELLED' ? 'selected' : ''; ?>>CANCELLED</option>
                                            </select>
                                            <button class="print-receipt-btn"
                                                onclick="window.open('sales_invoice.php?order_id=<?php echo $order['id']; ?>', '_blank')">
                                                <i class="fas fa-print"></i> Print
                                            </button>
                                            <button class="restore-btn" onclick="restoreOrder(<?php echo $order['id']; ?>, '<?php echo addslashes($order['product_name']); ?>', <?php echo $order['pieces']; ?>)">
                                                <i class="fas fa-undo-alt"></i> Restore
                                            </button>
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
            <p>© 2025 GameEarn. All rights reserved.</p>
        </div>
    </footer>

    <script>
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

        // Restore order function
        async function restoreOrder(orderId, productName, pieces) {
            const confirmed = confirm(`Are you sure you want to restore ${pieces} piece(s) of "${productName}" back to inventory?`);
            
            if (!confirmed) {
                return;
            }

            // Show loading state on the restore button
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
                formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

                const response = await fetch('/../API/restore_order.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    // Reload page after 1.5 seconds to update the list
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
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

        // Status update functionality
        document.querySelectorAll('.status-select').forEach(select => {
            // Apply initial color class
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

                // Confirm status change
                const confirmed = confirm(`Are you sure you want to change status to ${newStatus} for this order?`);

                if (!confirmed) {
                    // Reset to original value
                    const originalValue = this.querySelector('option[selected]')?.value || 'PENDING';
                    this.value = originalValue;
                    updateSelectColor(this);
                    return;
                }

                // Show loading state
                this.disabled = true;

                try {
                    const formData = new FormData();
                    formData.append('action', 'update_single_order_status');
                    formData.append('order_id', orderId);
                    formData.append('delivery_number', deliveryNumber);
                    formData.append('status', newStatus);
                    formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

                    const response = await fetch('/../API/update_sold_products.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        showToast(data.message, 'success');

                        // Update status badge
                        const statusBadge = document.getElementById(`status-${orderId}`);
                        if (statusBadge) {
                            statusBadge.textContent = newStatus;
                            statusBadge.className = `status-badge ${newStatus === 'PAID' ? 'status-paid' : (newStatus === 'CANCELLED' ? 'status-cancelled' : 'status-not-paid')}`;
                        }

                        // Update select color
                        updateSelectColor(this);

                        // Update stats (reload page after short delay)
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message || 'Failed to update status', 'error');
                        // Reset to original value
                        const originalValue = this.querySelector('option[selected]')?.value || 'PENDING';
                        this.value = originalValue;
                        updateSelectColor(this);
                    }
                } catch (err) {
                    console.error('Error:', err);
                    showToast('Network error. Please try again.', 'error');
                    const originalValue = this.querySelector('option[selected]')?.value || 'PENDING';
                    this.value = originalValue;
                    updateSelectColor(this);
                } finally {
                    this.disabled = false;
                }
            });
        });

        // Set active nav item
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.querySelector('span')?.textContent === 'Sold') {
                item.classList.add('active');
            }
        });
    </script>
</body>

</html>