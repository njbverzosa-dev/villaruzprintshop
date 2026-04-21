<?php
// sold_product.php
require_once 'access_sessions.php'; // includes session check, fetches $user

// Fetch order status history
$stmt = $pdo->prepare("SELECT * FROM order_status_history ORDER BY id DESC");
$stmt->execute();
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameEarn · Sold Products</title>
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

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            color: #ffffff;
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

        .section-header h2 {
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0f172a;
        }

        .section-header h2 i {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Add Product Button */
        .add-product-btn {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            border: none;
            border-radius: 30px;
            padding: 10px 25px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .add-product-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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

        .status-pending {
            background: #f59e0b;
            color: #ffffff;
        }

        .status-cancelled {
            background: #ef4444;
            color: #ffffff;
        }

        /* Update Status Button */
        .update-status-btn {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            border: none;
            border-radius: 20px;
            padding: 6px 16px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .update-status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .update-status-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* SELL button */
        .update-btn {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .update-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .update-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Quantity control */
        .qty-control {
            display: inline-flex;
            align-items: center;
            background: #f8fafc;
            border-radius: 40px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .qty-btn {
            background: #ffffff;
            border: none;
            color: #64748b;
            width: 32px;
            height: 32px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover:not(:disabled) {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            color: #ffffff;
        }

        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .qty-value {
            min-width: 60px;
            text-align: center;
            padding: 0 8px;
            font-weight: 600;
            color: #1e293b;
            background: #f8fafc;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 30px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-content h3 {
            color: #0f172a;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .modal-content h3 i {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 8px;
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #1e293b;
            font-size: 16px;
            transition: all 0.3s;
        }

        .modal-content input:focus,
        .modal-content select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #ffffff;
        }

        .modal-content input::placeholder {
            color: #94a3b8;
        }

        .modal-content label {
            display: block;
            text-align: left;
            margin-top: 10px;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .modal-confirm {
            background: linear-gradient(145deg, #3b82f6, #8b5cf6);
            color: #ffffff;
        }

        .modal-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .modal-cancel {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .modal-cancel:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .category-badge {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            color: #475569;
        }

        /* Loading spinner */
        .save-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #3b82f6;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .save-feedback {
            font-size: 12px;
            margin-left: 8px;
        }

        .save-success {
            color: #10b981;
        }

        .save-error {
            color: #ef4444;
        }

        /* Scrollbar Styling */
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

        ::-webkit-scrollbar-thumb:hover {
            background: #3b82f6;
        }

        /* Footer */
        footer {
            background: #ffffff;
            padding: 20px 5%;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .app-wrapper {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
                position: relative;
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
                    <h1>Sold Products
                        <span><?php echo htmlspecialchars($user['username'] ?? $user['acc_number']); ?></span>
                    </h1>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-value"><?php echo count($orders); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-value">
                        <?php
                        $pending = count(array_filter($orders, fn($o) => $o['status'] === 'PENDING'));
                        echo $pending;
                        ?>
                    </div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value">
                        <?php
                        $paid = count(array_filter($orders, fn($o) => $o['status'] === 'PAID'));
                        echo $paid;
                        ?>
                    </div>
                    <div class="stat-label">Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-value">
                        ₱ <?php
                        $totalRevenue = array_sum(array_column($orders, 'total_amount'));
                        echo number_format($totalRevenue, 2);
                        ?>
                    </div>
                    <div class="stat-label">Total Sales</div>
                </div>
            </div>

            <div class="merchandise-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Order Status History</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="inventory-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Order #</th>
                                <th>Product Name</th>
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
                                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo $order['unit']; ?></td>
                                        <td>₱ <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusText = $order['status'];
                                            switch ($statusText) {
                                                case 'PAID':
                                                    $statusClass = 'status-paid';
                                                    break;
                                                case 'PENDING':
                                                    $statusClass = 'status-pending';
                                                    break;
                                                case 'CANCELLED':
                                                    $statusClass = 'status-cancelled';
                                                    break;
                                                default:
                                                    $statusClass = 'status-pending';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"
                                                id="status-<?php echo $order['id']; ?>">
                                                <?php echo htmlspecialchars($statusText); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $order['date_time_sold']; ?></td>
                                        <td>
                                            <button class="update-status-btn" data-id="<?php echo $order['id']; ?>"
                                                data-status="<?php echo $order['status']; ?>">
                                                <i class="fas fa-edit"></i> Update Status
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

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h3>Update Order Status</h3>
            <p id="modalOrderInfo" style="margin-bottom: 10px; color: #b0b8d4;"></p>
            <select id="modalStatus">
                <option value="PENDING">PENDING</option>
                <option value="PAID">PAID</option>
                <option value="CANCELLED">CANCELLED</option>
            </select>
            <div class="modal-buttons">
                <button class="modal-btn modal-cancel" id="modalCancel">Cancel</button>
                <button class="modal-btn modal-confirm" id="modalConfirm">Update Status</button>
            </div>
        </div>
    </div>

    <footer>
        <div class="copyright">
            <p>© 2025 GameEarn. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        let selectedOrderId = null;
        let selectedOrderNumber = null;

        // Modal elements
        const statusModal = document.getElementById('statusModal');
        const modalStatus = document.getElementById('modalStatus');
        const modalOrderInfo = document.getElementById('modalOrderInfo');
        const modalConfirmStatus = document.getElementById('modalConfirm');
        const modalCancelStatus = document.getElementById('modalCancel');

        // Function to update order status
        async function updateOrderStatus(orderId, newStatus) {
            const statusSpan = document.getElementById(`status-${orderId}`);
            const originalStatus = statusSpan.textContent;

            // Disable the update button for this order
            const updateBtn = document.querySelector(`.update-status-btn[data-id="${orderId}"]`);
            if (updateBtn) {
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            }

            try {
                const formData = new FormData();
                formData.append('action', 'update_order_status');
                formData.append('order_id', orderId);
                formData.append('status', newStatus);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('/../API/update_sold_products.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    // Update status badge
                    statusSpan.textContent = newStatus;

                    // Update badge class
                    statusSpan.className = 'status-badge';
                    if (newStatus === 'PAID') {
                        statusSpan.classList.add('status-paid');
                    } else if (newStatus === 'PENDING') {
                        statusSpan.classList.add('status-pending');
                    } else if (newStatus === 'CANCELLED') {
                        statusSpan.classList.add('status-cancelled');
                    }

                    // Update button data-status attribute
                    if (updateBtn) {
                        updateBtn.setAttribute('data-status', newStatus);
                    }

                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4caf50; color: white; padding: 12px 20px; border-radius: 10px; z-index: 2000;';
                    successMsg.innerHTML = '<i class="fas fa-check-circle"></i> Status updated to ' + newStatus;
                    document.body.appendChild(successMsg);
                    setTimeout(() => successMsg.remove(), 3000);

                    // Update stats (optional - refresh page to update counts)
                    setTimeout(() => location.reload(), 1500);

                    return true;
                } else {
                    statusSpan.textContent = originalStatus;
                    const errorMsg = document.createElement('div');
                    errorMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #f44336; color: white; padding: 12px 20px; border-radius: 10px; z-index: 2000;';
                    errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + (data.message || 'Update failed');
                    document.body.appendChild(errorMsg);
                    setTimeout(() => errorMsg.remove(), 3000);
                    return false;
                }
            } catch (err) {
                console.error('Network error:', err);
                statusSpan.textContent = originalStatus;
                const errorMsg = document.createElement('div');
                errorMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #f44336; color: white; padding: 12px 20px; border-radius: 10px; z-index: 2000;';
                errorMsg.innerHTML = '<i class="fas fa-wifi"></i> Network error';
                document.body.appendChild(errorMsg);
                setTimeout(() => errorMsg.remove(), 3000);
                return false;
            } finally {
                if (updateBtn) {
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = '<i class="fas fa-edit"></i> Update Status';
                }
            }
        }

        // Handle Update Status button click
        document.querySelectorAll('.update-status-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();

                selectedOrderId = this.dataset.id;
                const currentStatus = this.dataset.status;
                const row = this.closest('tr');
                const orderNumber = row.querySelector('td:nth-child(2) strong').textContent;
                const productName = row.querySelector('td:nth-child(3)').textContent;

                selectedOrderNumber = orderNumber;

                modalOrderInfo.innerHTML = `<strong>Order:</strong> ${orderNumber}<br><strong>Product:</strong> ${productName}<br><strong>Current Status:</strong> ${currentStatus}`;
                modalStatus.value = currentStatus;
                statusModal.style.display = 'flex';
            });
        });

        // Modal confirm
        modalConfirmStatus.addEventListener('click', async () => {
            const newStatus = modalStatus.value;

            if (!newStatus) {
                alert('Please select a status');
                return;
            }

            statusModal.style.display = 'none';
            await updateOrderStatus(selectedOrderId, newStatus);
            selectedOrderId = null;
        });

        // Modal cancel
        modalCancelStatus.addEventListener('click', () => {
            statusModal.style.display = 'none';
            selectedOrderId = null;
        });

        // Close modal when clicking outside
        statusModal.addEventListener('click', (e) => {
            if (e.target === statusModal) {
                statusModal.style.display = 'none';
                selectedOrderId = null;
            }
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