<?php
// sufficient_stock.php
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'access_sessions.php';

// Get all products from order_status_history that are still pending
$stmt = $pdo->prepare("
    SELECT 
        osh.product_name,
        osh.pieces,
        osh.delivery_number,
        osh.acc_number,
        osh.unit,
        osh.total_amount,
        osh.date_time_sold,
        mi.qty_on_hand as current_stock,
        mi.selling_price,
        mi.id as product_id,
        mi.category,
        mi.description
    FROM order_status_history osh
    INNER JOIN merchandise_inventory mi ON osh.product_name = mi.product_name
    WHERE osh.status = 'PENDING'
    ORDER BY osh.date_time_sold DESC
");
$stmt->execute();
$pendingOrders = $stmt->fetchAll();

// Filter only products with sufficient stock (current_stock >= pieces)
$sufficientStockProducts = [];
$insufficientStockProducts = [];

foreach ($pendingOrders as $order) {
    if ($order['current_stock'] >= $order['pieces']) {
        $sufficientStockProducts[] = $order;
    } else {
        $insufficientStockProducts[] = $order;
    }
}

// Fetch all unique categories from sufficient stock products
$categories = [];
foreach ($sufficientStockProducts as $product) {
    if (!empty($product['category']) && !in_array($product['category'], $categories)) {
        $categories[] = $product['category'];
    }
}
sort($categories);

// Define icon mapping for categories
$categoryIcons = [
    'Paper & Pads' => 'fas fa-copy',
    'Folders & Envelopes' => 'fas fa-folder-open',
    'Pens & Writing Instruments' => 'fas fa-pen-fancy', 
    'Office Equipment & Supplies' => 'fas fa-print',
    'Ink & Printer Supplies' => 'fas fa-ink',
    'Electronics & IT Accessories' => 'fas fa-laptop',
    'Security & Surveillance' => 'fas fa-video',
    'Janitorial & General Merchandise' => 'fas fa-broom',
    'Certificates & Miscellaneous' => 'fas fa-certificate',
    'General Merchandise' => 'fas fa-store',
    'Home Appliances' => 'fas fa-plug'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Sufficient Stock | Villaruz Print Shop & General Merchandise</title>
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
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

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

        .stats-summary {
            display: flex;
            gap: 15px;
        }

        .stat-badge {
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }

        .stat-badge.success {
            background: #d1fae5;
            color: #059669;
        }

        .stat-badge.warning {
            background: #fee2e2;
            color: #dc2626;
        }

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

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .search-wrapper {
            flex: 1;
            position: relative;
            max-width: 400px;
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

        .category-section {
            margin-bottom: 25px;
        }

        .category-title {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .category-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .category-btn {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .category-btn i {
            font-size: 14px;
            color: #3b82f6;
        }

        .category-btn:hover {
            background: #f8fafc;
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .category-btn.active {
            background: linear-gradient(145deg, #3b82f6, #6366f1);
            border-color: #3b82f6;
            color: white;
        }

        .category-btn.active i {
            color: white;
        }

        .reset-btn {
            background: #f1f5f9;
            border-color: #e2e8f0;
        }

        .reset-btn:hover {
            background: #e2e8f0;
        }

        .search-info {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 15px;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 12px;
            display: inline-block;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .order-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .order-card:hover {
            border-color: #10b981;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .delivery-number {
            font-size: 11px;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            color: #475569;
            font-weight: 600;
        }

        .stock-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #d1fae5;
            color: #059669;
        }

        .product-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .product-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .detail-label {
            color: #64748b;
            font-weight: 500;
        }

        .detail-value {
            color: #1e293b;
            font-weight: 600;
        }

        .detail-value.stock-sufficient {
            color: #059669;
        }

        .detail-value.stock-insufficient {
            color: #dc2626;
        }

        .stock-bar {
            margin-top: 8px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }

        .stock-fill {
            height: 100%;
            background: #10b981;
            border-radius: 3px;
            transition: width 0.3s;
        }

        .stock-fill.warning {
            background: #f59e0b;
        }

        .stock-fill.danger {
            background: #ef4444;
        }

        .order-amount {
            font-size: 20px;
            font-weight: 800;
            color: #3b82f6;
            margin: 12px 0;
            text-align: right;
        }

        .order-date {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 12px;
            text-align: right;
        }

        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .action-btn {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            padding: 8px 0;
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

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 60px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
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
        <div class="burger-btn" id="burgerBtn">
            <i class="fas fa-bars"></i>
        </div>

        <div class="menu-overlay" id="menuOverlay"></div>

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
                <a href="sold_products.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Sold</span>
                </a>
                <a href="all_pending_orders.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Orders</span>
                </a>
                <a href="sufficient_stock.php" class="nav-item active">
                    <i class="fas fa-check-circle"></i>
                    <span>Sufficient Stock</span>
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
                    <h4>SUFFICIENT STOCK ORDERS</h4>
                </div>
                <div class="stats-summary">
                    <span class="stat-badge success">
                        <i class="fas fa-check-circle"></i> Sufficient: <?php echo count($sufficientStockProducts); ?>
                    </span>
                    <span class="stat-badge warning">
                        <i class="fas fa-exclamation-triangle"></i> Insufficient: <?php echo count($insufficientStockProducts); ?>
                    </span>
                </div>
            </div>

            <div class="top-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="liveSearchInput" class="search-input" placeholder="Search by product or delivery number..."
                        autocomplete="off">
                </div>
            </div>

            <!-- Category Section -->
            <?php if (!empty($categories)): ?>
            <div class="category-section">
                <div class="category-title">FILTER BY CATEGORY</div>
                <div class="category-buttons" id="categoryButtonsContainer">
                    <button class="category-btn reset-btn active" data-category="all">
                        <i class="fas fa-th-large"></i> All Products
                    </button>
                    <?php foreach ($categories as $category): ?>
                        <button class="category-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                            <i class="<?php echo isset($categoryIcons[$category]) ? $categoryIcons[$category] : 'fas fa-tag'; ?>"></i>
                            <?php echo htmlspecialchars($category); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div id="searchInfo" class="search-info"></div>

            <div class="orders-grid" id="ordersGrid">
                <?php if (empty($sufficientStockProducts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                        <h3>No Pending Orders with Sufficient Stock</h3>
                        <p>All pending orders have sufficient inventory to fulfill.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($sufficientStockProducts as $order):
                        $stockPercentage = ($order['pieces'] / $order['current_stock']) * 100;
                        $stockClass = $stockPercentage >= 80 ? 'danger' : ($stockPercentage >= 50 ? 'warning' : '');
                    ?>
                        <div class="order-card" 
                             data-product="<?php echo strtolower(htmlspecialchars($order['product_name'])); ?>"
                             data-delivery="<?php echo strtolower(htmlspecialchars($order['delivery_number'])); ?>"
                             data-category="<?php echo htmlspecialchars($order['category'] ?? ''); ?>">
                            
                            <div class="order-header">
                                <span class="delivery-number">#<?php echo htmlspecialchars($order['delivery_number']); ?></span>
                                <span class="stock-status"><i class="fas fa-check-circle"></i> Sufficient Stock</span>
                            </div>
                            
                            <div class="product-name">
                                <i class="fas fa-box"></i> <?php echo htmlspecialchars($order['product_name']); ?>
                            </div>
                            
                            <div class="product-details">
                                <div class="detail-row">
                                    <span class="detail-label">Order Quantity:</span>
                                    <span class="detail-value"><?php echo number_format($order['pieces']); ?> <?php echo htmlspecialchars($order['unit'] ?? 'Pcs'); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Available Stock:</span>
                                    <span class="detail-value stock-sufficient"><?php echo number_format($order['current_stock']); ?> <?php echo htmlspecialchars($order['unit'] ?? 'Pcs'); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Remaining After Order:</span>
                                    <span class="detail-value"><?php echo number_format($order['current_stock'] - $order['pieces']); ?> <?php echo htmlspecialchars($order['unit'] ?? 'Pcs'); ?></span>
                                </div>
                                <div class="stock-bar">
                                    <div class="stock-fill <?php echo $stockClass; ?>" style="width: <?php echo min(100, $stockPercentage); ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="order-amount">
                                ₱ <?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                            
                            <div class="order-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('M d, Y g:i A', strtotime($order['date_time_sold'])); ?>
                            </div>
                            
                            <div class="card-actions">
                                <a href="../delivery_receipt.php?delivery_number=<?php echo urlencode($order['delivery_number']); ?>" 
                                   class="action-btn" target="_blank">
                                    <i class="fas fa-receipt"></i> Delivery
                                </a>
                                <a href="../billing_receipt.php?delivery_number=<?php echo urlencode($order['delivery_number']); ?>" 
                                   class="action-btn" target="_blank">
                                    <i class="fas fa-file-invoice"></i> Billing
                                </a>
                                <a href="../web/selected_data.php?delivery_number=<?php echo urlencode($order['delivery_number']); ?>" 
                                   class="action-btn" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer>
        <div class="copyright">
            <p>© 2026 Villaruz Print Shop & General Merchandise — Quality prints & everyday goods, delivered with care.</p>
        </div>
    </footer>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Burger Menu Toggle
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

        // Category Filter Variables
        let activeCategory = 'all';
        const categoryButtons = document.querySelectorAll('.category-btn');
        const searchInput = document.getElementById('liveSearchInput');
        const searchInfo = document.getElementById('searchInfo');
        const orderCards = document.querySelectorAll('.order-card');

        function filterOrders() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            let visibleCount = 0;

            orderCards.forEach(card => {
                const productName = card.getAttribute('data-product') || '';
                const deliveryNumber = card.getAttribute('data-delivery') || '';
                const productCategory = card.getAttribute('data-category') || '';

                let categoryMatch = true;
                if (activeCategory !== 'all') {
                    categoryMatch = (productCategory === activeCategory);
                }

                let searchMatch = true;
                if (searchTerm !== '') {
                    searchMatch = productName.includes(searchTerm) || deliveryNumber.includes(searchTerm);
                }

                if (categoryMatch && searchMatch) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (activeCategory !== 'all') {
                if (searchTerm === '') {
                    searchInfo.innerHTML = `<i class="fas fa-folder-open"></i> Showing ${visibleCount} order(s) in <strong>${activeCategory}</strong> category`;
                } else {
                    searchInfo.innerHTML = `<i class="fas fa-folder-open"></i> Found ${visibleCount} order(s) in <strong>${activeCategory}</strong> matching "${searchTerm}"`;
                }
            } else {
                if (searchTerm === '') {
                    searchInfo.innerHTML = `<i class="fas fa-info-circle"></i> Showing ${visibleCount} order(s) with sufficient stock`;
                } else {
                    searchInfo.innerHTML = `<i class="fas fa-search"></i> Found ${visibleCount} order(s) matching "${searchTerm}"`;
                }
            }
        }

        if (categoryButtons.length > 0) {
            categoryButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const category = btn.getAttribute('data-category');
                    activeCategory = category;
                    categoryButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    filterOrders();
                });
            });
        }

        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(filterOrders, 100);
            });
        }

        // Initial filter
        filterOrders();

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle')}"></i> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>

</html>