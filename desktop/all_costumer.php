<?php
// all_costumer.php
require_once 'access_sessions.php'; // includes session check, fetches $user

// Fetch all deliveries
$stmt = $pdo->prepare("SELECT * FROM for_deliveries ORDER BY date_time_sold DESC");
$stmt->execute();
$deliveries = $stmt->fetchAll();

// Calculate statistics
$totalRevenue = 0;
$totalPaid = 0;
$totalNotPaid = 0;
$totalCancelled = 0;

foreach ($deliveries as $delivery) {
    $totalRevenue += floatval($delivery['total_amount']);
    
    switch ($delivery['status']) {
        case 'PAID':
            $totalPaid += floatval($delivery['total_amount']);
            break;
        case 'CANCELLED':
            $totalCancelled += floatval($delivery['total_amount']);
            break;
        case 'PENDING':
        default:
            $totalNotPaid += floatval($delivery['total_amount']);
            break;
    }
}

$totalCustomers = count(array_unique(array_column($deliveries, 'ordered_by')));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameEarn · Customer Orders Summary</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ========== MODERN GRAY DASHBOARD STYLES ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #e8ecf1;
            color: #2c3e50;
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
            color: #1e293b;
        }

        .welcome span {
            color: #6366f1;
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
            border-color: #6366f1;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.1);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .stat-icon.total { color: #6366f1; }
        .stat-icon.paid { color: #10b981; }
        .stat-icon.not-paid { color: #f59e0b; }
        .stat-icon.cancelled { color: #ef4444; }
        .stat-icon.customers { color: #8b5cf6; }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #1e293b;
        }

        .stat-label {
            font-size: 14px;
            color: #64748b;
        }

        /* Table Styles */
        .orders-section {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
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
            color: #1e293b;
        }

        .section-header h2 i {
            color: #6366f1;
        }

        /* Search Bar */
        .search-bar {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            padding: 8px 16px;
            color: #1e293b;
            font-size: 14px;
            width: 250px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #6366f1;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-input::placeholder {
            color: #94a3b8;
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
            width: 110px;
        }

        .status-select:hover {
            border-color: #6366f1;
        }

        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .orders-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            position: sticky;
            top: 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .orders-table tr:hover {
            background: #f8fafc;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-paid {
            background: #10b981;
            color: white;
        }

        .status-not-paid,
        .status-pending {
            background: #f59e0b;
            color: white;
        }

        .status-cancelled {
            background: #ef4444;
            color: white;
        }

        /* View Receipt Button */
        .view-receipt-btn {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 6px 12px;
            color: #6366f1;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin: 2px;
        }

        .view-receipt-btn:hover {
            background: #6366f1;
            border-color: #6366f1;
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Restore Button */
        .restore-btn {
            background: linear-gradient(145deg, #8f9190, #939493);
            border: none;
            border-radius: 20px;
            padding: 6px 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 12px;
            margin: 2px;
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

        /* Footer */
        footer {
            background: #ffffff;
            padding: 20px 5%;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .app-wrapper {
                flex-direction: column;
            }

            .orders-table th,
            .orders-table td {
                padding: 10px 8px;
                font-size: 12px;
            }

            .search-input {
                width: 100%;
            }
            
            .view-receipt-btn, .restore-btn {
                font-size: 10px;
                padding: 4px 8px;
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
                    <h1>CUSTOMER ORDERS
                        <span><?php echo htmlspecialchars($user['username'] ?? $user['acc_number']); ?></span>
                    </h1>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-value">₱ <?php echo number_format($totalRevenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon paid"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value">₱ <?php echo number_format($totalPaid, 2); ?></div>
                    <div class="stat-label">Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon not-paid"><i class="fas fa-clock"></i></div>
                    <div class="stat-value">₱ <?php echo number_format($totalNotPaid, 2); ?></div>
                    <div class="stat-label">Not Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon cancelled"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value">₱ <?php echo number_format($totalCancelled, 2); ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon customers"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $totalCustomers; ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
            </div>

            <!-- Orders Summary Section -->
            <div class="orders-section">
                <div class="section-header">
                    <h2><i class="fas fa-file-invoice-dollar"></i> Customer Order Summary</h2>
                    <div class="search-bar">
                        <input type="text" id="searchInput" class="search-input"
                            placeholder="Search by customer name...">
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="orders-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer Name</th>
                                <th>Delivery Number</th>
                                <th>Address</th>
                                <th>Total Amount</th>
                                <th>Date & Time Sold</th>
                                <th>Status</th>
                                <th>Update Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deliveries)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-receipt"></i> No orders found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $counter = 1; ?>
                                <?php foreach ($deliveries as $delivery): ?>
                                    <?php
                                    $status = $delivery['status'];
                                    $statusClass = strtolower(str_replace(' ', '-', $status));
                                    if ($statusClass == 'pending') $statusClass = 'not-paid';
                                    ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($delivery['ordered_by']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($delivery['delivery_number']); ?></td>
                                        <td><?php echo htmlspecialchars($delivery['delivery_address'] ?? 'N/A'); ?></td>
                                        <td>₱ <?php echo number_format($delivery['total_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($delivery['date_time_sold'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $statusClass; ?>" id="status-badge-<?php echo $delivery['id']; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <select class="status-select status-<?php echo $statusClass; ?>" 
                                                    id="status-select-<?php echo $delivery['id']; ?>"
                                                    data-order-id="<?php echo $delivery['id']; ?>"
                                                    data-delivery-number="<?php echo htmlspecialchars($delivery['delivery_number']); ?>">
                                                <option value="PENDING" <?php echo $status == 'PENDING' ? 'selected' : ''; ?>>PENDING</option>
                                                <option value="PAID" <?php echo $status == 'PAID' ? 'selected' : ''; ?>>PAID</option>
                                                <option value="CANCELLED" <?php echo $status == 'CANCELLED' ? 'selected' : ''; ?>>CANCELLED</option>
                                            </select>
                                        </td>
                                        <td style="text-align:center;">
                                            <a href="delivery_receipt.php?delivery_number=<?php echo urlencode($delivery['delivery_number']); ?>" 
                                               class="view-receipt-btn" target="_blank">
                                                <i class="fas fa-receipt"></i> Delivery
                                            </a>
                                            <a href="billing_receipt.php?delivery_number=<?php echo urlencode($delivery['delivery_number']); ?>" 
                                               class="view-receipt-btn" target="_blank">
                                                <i class="fas fa-file-invoice"></i> Billing
                                            </a>
                                            <button class="restore-btn" 
                                                    onclick="restoreDelivery('<?php echo addslashes($delivery['delivery_number']); ?>')">
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

        // Restore delivery function
        async function restoreDelivery(deliveryNumber) {
            const confirmed = confirm(`Are you sure you want to restore all items in Delivery #${deliveryNumber} back to inventory?\n\nThis will:\n- Add all items back to stock\n- Delete the delivery record\n- This action cannot be undone!`);
            
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
                formData.append('action', 'restore_delivery');
                formData.append('delivery_number', deliveryNumber);
                formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

                const response = await fetch('API/restore_delivery.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    // Reload page after 1.5 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'Failed to restore delivery', 'error');
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

        // Function to update select color based on status
        function updateSelectColor(selectElement, status) {
            selectElement.classList.remove('status-paid', 'status-not-paid', 'status-cancelled');
            if (status === 'PAID') {
                selectElement.classList.add('status-paid');
            } else if (status === 'CANCELLED') {
                selectElement.classList.add('status-cancelled');
            } else {
                selectElement.classList.add('status-not-paid');
            }
        }

        // Status update functionality
        document.querySelectorAll('.status-select').forEach(select => {
            // Apply initial color class
            updateSelectColor(select, select.value);
            
            select.addEventListener('change', async function() {
                const orderId = this.dataset.orderId;
                const deliveryNumber = this.dataset.deliveryNumber;
                const newStatus = this.value;
                
                // Confirm status change
                const confirmed = confirm(`Are you sure you want to change status to ${newStatus} for Delivery #${deliveryNumber}?`);
                
                if (!confirmed) {
                    // Reset to original value
                    const originalValue = this.querySelector('option[selected]')?.value || 'NOT PAID';
                    this.value = originalValue;
                    updateSelectColor(this, originalValue);
                    return;
                }
                
                // Show loading state
                this.disabled = true;
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'update_order_status');
                    formData.append('order_id', orderId);
                    formData.append('delivery_number', deliveryNumber);
                    formData.append('status', newStatus);
                    formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
                    
                    const response = await fetch('API/update_delivery_status.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast(data.message, 'success');
                        
                        // Update status badge
                        const statusBadge = document.getElementById(`status-badge-${orderId}`);
                        if (statusBadge) {
                            statusBadge.textContent = newStatus;
                            let badgeClass = 'status-badge ';
                            if (newStatus === 'PAID') badgeClass += 'status-paid';
                            else if (newStatus === 'CANCELLED') badgeClass += 'status-cancelled';
                            else badgeClass += 'status-not-paid';
                            statusBadge.className = badgeClass;
                        }
                        
                        // Update select color
                        updateSelectColor(this, newStatus);
                        
                        // Update stats (reload page after short delay)
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message || 'Failed to update status', 'error');
                        // Reset to original value
                        const originalValue = this.querySelector('option[selected]')?.value || 'NOT PAID';
                        this.value = originalValue;
                        updateSelectColor(this, originalValue);
                    }
                } catch (err) {
                    console.error('Error:', err);
                    showToast('Network error. Please try again.', 'error');
                    const originalValue = this.querySelector('option[selected]')?.value || 'NOT PAID';
                    this.value = originalValue;
                    updateSelectColor(this, originalValue);
                } finally {
                    this.disabled = false;
                }
            });
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const ordersTable = document.getElementById('ordersTable');

        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                const searchTerm = this.value.toLowerCase();
                const rows = ordersTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const customerName = row.cells[1]?.textContent.toLowerCase() || '';
                    const matchesSearch = searchTerm === '' || customerName.includes(searchTerm);
                    row.style.display = matchesSearch ? '' : 'none';
                });
            });
        }

        // Set active nav item
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.querySelector('span')?.textContent === 'Customers') {
                item.classList.add('active');
            }
        });
    </script>
</body>

</html>