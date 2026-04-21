<?php
// registered_customers.php
require_once 'access_sessions.php'; // includes session check, fetches $user

// Fetch all customers from customers table
$stmt = $pdo->prepare("SELECT * FROM customers ORDER BY id DESC");
$stmt->execute();
$customers = $stmt->fetchAll();

// Calculate statistics
$totalCustomers = count($customers);
$activeCustomers = 0;
$inactiveCustomers = 0;

foreach ($customers as $customer) {
    // Check if active_email exists in the customer array
    if (isset($customer['active_email']) && ($customer['active_email'] == 1 || $customer['active_email'] === null)) {
        $activeCustomers++;
    } else {
        $inactiveCustomers++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Registered Customers | Villaruz Print Shop & General Merchandise</title>
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

        /* Search Bar */
        .search-wrapper {
            position: relative;
            max-width: 400px;
            margin-bottom: 20px;
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
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-info {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 15px;
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
        }

        .inventory-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            position: sticky;
            top: 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .inventory-table tr:hover {
            background: #f8fafc;
        }

        .delete-btn {
            background: #ef4444;
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
        }

        .delete-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        /* Active Badge */
        .active-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-left: 8px;
        }

        .badge-active {
            background: #10b981;
            color: white;
        }

        .badge-inactive {
            background: #ef4444;
            color: white;
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
                <a href="registered_customers.php" class="nav-item active">
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
                    <h4>REGISTERED CUSTOMERS</h4>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $totalCustomers; ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <div class="stat-value"><?php echo $activeCustomers; ?></div>
                    <div class="stat-label">Active Email</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <div class="stat-value"><?php echo $inactiveCustomers; ?></div>
                    <div class="stat-label">Inactive Email</div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name, phone or email..."
                    autocomplete="off">
            </div>
            <div id="searchInfo" class="search-info"></div>

            <div class="merchandise-section">
                <div class="section-header">
                    <h5><i class="fas fa-user-friends"></i> Customer List</h5>
                </div>
                <div style="overflow-x: auto;">
                    <table class="inventory-table" id="customersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Registered Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">No customers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr data-id="<?php echo $customer['id']; ?>"
                                        data-name="<?php echo strtolower(htmlspecialchars($customer['f_name'] ?? '')); ?>"
                                        data-phone="<?php echo htmlspecialchars($customer['phone_number'] ?? ''); ?>"
                                        data-email="<?php echo strtolower(htmlspecialchars($customer['email'] ?? '')); ?>">
                                        <td><?php echo $customer['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($customer['f_name'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($customer['phone_number'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?>
                                            <span class="active-badge <?php echo (isset($customer['active_email']) && ($customer['active_email'] == 1 || $customer['active_email'] === null)) ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo (isset($customer['active_email']) && ($customer['active_email'] == 1 || $customer['active_email'] === null)) ? 'ACTIVE' : 'INACTIVE'; ?>
                                            </span>
                                         </td>
                                        <td><?php echo date('M d, Y', strtotime($customer['created_at'] ?? 'now')); ?></td>
                                        <td>
                                            <button class="delete-btn"
                                                onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo addslashes($customer['f_name'] ?? 'Customer'); ?>')">
                                                <i class="fas fa-trash-alt"></i> DELETE
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
            <p>© 2026 Villaruz Print Shop & General Merchandise — Quality prints & everyday goods, delivered with care.
            </p>
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

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMenu();
            }
        });

        // ========== SEARCH FUNCTIONALITY ==========
        const searchInput = document.getElementById('searchInput');
        const searchInfo = document.getElementById('searchInfo');
        const customerRows = document.querySelectorAll('#customersTable tbody tr');

        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            if (searchTerm === '') {
                customerRows.forEach(row => {
                    row.style.display = '';
                    visibleCount++;
                });
                searchInfo.innerHTML = `<i class="fas fa-info-circle"></i> Showing all ${visibleCount} customers`;
            } else {
                customerRows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const phone = row.getAttribute('data-phone') || '';
                    const email = row.getAttribute('data-email') || '';

                    if (name.includes(searchTerm) || phone.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                searchInfo.innerHTML = `<i class="fas fa-search"></i> Found ${visibleCount} customer(s) matching "${searchTerm}"`;
            }
        }

        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });
        }
        performSearch();

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

        // ========== DELETE CUSTOMER ==========
        async function deleteCustomer(customerId, customerName) {
            const confirmed = confirm(`⚠️ Are you sure you want to delete "${customerName}"?\n\nThis action will permanently remove this customer from the database and cannot be undone!`);
            if (!confirmed) return;

            const deleteBtn = event.target.closest('.delete-btn');
            const originalText = deleteBtn.innerHTML;
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

            try {
                const formData = new FormData();
                formData.append('action', 'delete_customer');
                formData.append('customer_id', customerId);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../API/customer_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Failed to delete customer', 'error');
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Error:', err);
                showToast('Network error. Please try again.', 'error');
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalText;
            }
        }
    </script>
</body>

</html>