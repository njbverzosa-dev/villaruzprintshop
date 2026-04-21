<?php
// all_products.php
require_once 'access_sessions.php';

// Fetch all merchandise inventory
$stmt = $pdo->prepare("SELECT * FROM merchandise_inventory");
$stmt->execute();
$allProducts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Villaruz Print Shop · All Products</title>
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

        /* Keyboard Shortcut Hint */
        .shortcut-hint {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1e293b;
            padding: 10px 18px;
            border-radius: 40px;
            font-size: 12px;
            color: #94a3b8;
            border: 1px solid #e2e8f0;
            z-index: 999;
            pointer-events: none;
            font-family: monospace;
            backdrop-filter: blur(4px);
        }

        .shortcut-hint i {
            color: #3b82f6;
            margin-right: 8px;
        }

        .shortcut-hint kbd {
            background: #0f172a;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            margin: 0 3px;
            font-weight: 600;
            color: #f1f5f9;
        }

        /* Search Bar - Live Search */
        .search-container {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input-wrapper {
            flex: 1;
            position: relative;
        }

        .search-input-wrapper i {
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
            color: #1e293b;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-input::placeholder {
            color: #94a3b8;
        }

        .search-info {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
        }

        .clear-search-btn {
            background: #e2e8f0;
            border: none;
            border-radius: 30px;
            padding: 10px 20px;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .clear-search-btn:hover {
            background: #cbd5e1;
            color: #1e293b;
        }

        /* Add Product Button */
        .add-product-btn {
            background: linear-gradient(145deg, #3b82f6, #6366f1);
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

        /* Merchandise Table Styles */
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
            color: #3b82f6;
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

        .inventory-table tr {
            transition: all 0.2s ease;
        }

        .inventory-table tr:hover {
            background: #f8fafc;
        }

        /* Hide rows for live search */
        .inventory-table tr.hidden-row {
            display: none;
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
            color: #475569;
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
            background: #3b82f6;
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

        /* SELL button */
        .sell-btn {
            background: #10b981;
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 8px;
        }

        .sell-btn:hover:not(:disabled) {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        /* UPDATE button */
        .update-btn {
            background: #3b82f6;
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .update-btn:hover:not(:disabled) {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .sell-btn:disabled,
        .update-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-content h3 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .modal-content h3 i {
            color: #3b82f6;
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
            background: #3b82f6;
            color: #ffffff;
        }

        .modal-confirm:hover {
            background: #2563eb;
            transform: translateY(-1px);
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

        .unit-badge {
            background: #e2e8f0;
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
            border: 2px solid #ffffff;
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

        /* Row highlight effect */
        .row-highlight {
            animation: highlightFade 1.5s ease;
        }

        @keyframes highlightFade {
            0% {
                background-color: #fef3c7;
            }

            100% {
                background-color: transparent;
            }
        }

        /* No results message */
        .no-results {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        /* Footer */
        footer {
            background: #ffffff;
            padding: 20px 5%;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
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

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .inventory-table th,
            .inventory-table td {
                padding: 10px 8px;
                font-size: 12px;
            }

            .search-container {
                flex-direction: column;
            }

            .clear-search-btn {
                width: 100%;
                justify-content: center;
            }

            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .sell-btn,
            .update-btn {
                width: 100%;
                text-align: center;
            }

            .shortcut-hint {
                bottom: 10px;
                right: 10px;
                font-size: 10px;
                padding: 6px 12px;
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
                <div class="user-name">
                    <?php echo htmlspecialchars($user['username'] ?? $user['acc_number']); ?>
                </div>
            </div>
            <div class="menu-nav">
                <a href="all_products.php" class="nav-item active">
                    <i class="fas fa-boxes"></i>
                    <span>Product</span>
                </a>
                <a href="sold_products.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Sold</span>
                </a>
                <a href="all_pending_orders.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
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
                    <h1>PRODUCT PAGE
                        <span><?php echo htmlspecialchars($user['acc_number']); ?></span>
                    </h1>
                </div>
            </div>

            <div class="merchandise-section">
                <div class="section-header">
                    <h5><i class="fas fa-boxes"></i> Merchandise Inventory</h5>
                    <button class="add-product-btn" id="addProductBtn">
                        <i class="fas fa-plus-circle"></i> Add New
                    </button>
                </div>

                <!-- Live Search Bar -->
                <div style="padding: 20px 25px;">
                    <div class="search-container">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" id="liveSearchInput" class="search-input"
                                placeholder="Search by product name..." autocomplete="off">
                        </div>
                        <button class="clear-search-btn" id="clearSearchBtn">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                    <div id="searchInfo" class="search-info"></div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="inventory-table" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if (empty($allProducts)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        No merchandise found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allProducts as $product): ?>
                                    <tr data-id="<?php echo $product['id']; ?>" id="row-<?php echo $product['id']; ?>"
                                        data-name="<?php echo strtolower(htmlspecialchars($product['product_name'])); ?>"
                                        data-first-letter="<?php echo strtolower(substr(htmlspecialchars($product['product_name']), 0, 1)); ?>">
                                        <td><?php echo $product['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                        <td><span class="unit-badge"><?php echo htmlspecialchars($product['unit'] ?? 'Pcs'); ?></span></td>
                                        <td class="qty-cell">
                                            <div class="qty-control">
                                                <button class="qty-btn decrement" data-id="<?php echo $product['id']; ?>">-</button>
                                                <span class="qty-value" id="qty-<?php echo $product['id']; ?>"><?php echo number_format($product['qty_on_hand']); ?></span>
                                                <button class="qty-btn increment" data-id="<?php echo $product['id']; ?>">+</button>
                                            </div>
                                        </td>
                                        <td>₱ <?php echo number_format($product['selling_price'], 2); ?></td>
                                        <td class="action-buttons">
                                            <button class="sell-btn" data-id="<?php echo $product['id']; ?>">SELL</button>
                                            <button class="update-btn" data-id="<?php echo $product['id']; ?>">UPDATE</button>
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

    <!-- Sell Confirmation Modal -->
    <div id="quantityModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-check-circle"></i> Confirm Sale</h3>
            <p id="modalProductName" style="margin-bottom: 10px;"></p>
            <p id="modalCurrentStock" style="margin-bottom: 10px; color: #64748b;"></p>
            <p id="modalTotalAmount" style="margin-bottom: 15px; font-size: 18px; color: #3b82f6;"></p>
            <div class="modal-buttons">
                <button class="modal-btn modal-cancel" id="modalCancel">Cancel</button>
                <button class="modal-btn modal-confirm" id="modalConfirm">Confirm Sale</button>
            </div>
        </div>
    </div>

    <!-- Update Product Modal -->
    <div id="updateProductModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-edit"></i> Update Product</h3>
            <label>Product Name</label>
            <input type="text" id="updateProductName" placeholder="Enter product name">
            <label>Unit</label>
            <input type="text" id="updateUnit" placeholder="Enter unit (e.g., Pcs, box, ream)">
            <label>Quantity</label>
            <input type="number" id="updateQuantity" placeholder="Enter quantity" min="0">
            <label>Unit Cost (₱)</label>
            <input type="number" id="updatePrice" placeholder="Enter selling price" min="0" step="0.01">
            <div class="modal-buttons">
                <button class="modal-btn modal-cancel" id="cancelUpdateProduct">Cancel</button>
                <button class="modal-btn modal-confirm" id="confirmUpdateProduct">Update Now</button>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
            <label>Product Name</label>
            <input type="text" id="productName" placeholder="Enter product name">
            <label>Unit</label>
            <input type="text" id="productUnit" placeholder="Enter unit (e.g., Pcs, box, ream)" value="Pcs">
            <label>Quantity</label>
            <input type="number" id="productQuantity" placeholder="Enter quantity" min="0">
            <label>Unit Cost (₱)</label>
            <input type="number" id="productPrice" placeholder="Enter selling price" min="0" step="0.01">
            <div class="modal-buttons">
                <button class="modal-btn modal-cancel" id="cancelAddProduct">Cancel</button>
                <button class="modal-btn modal-confirm" id="confirmAddProduct">Add Product</button>
            </div>
        </div>
    </div>

    <!-- Keyboard Shortcut Hint -->
    <div class="shortcut-hint">
        <i class="fas fa-keyboard"></i> Press <kbd>+</kbd> to add product | <kbd>ESC</kbd> to close
    </div>

    <footer>
        <div class="copyright">
            <p>© 2026 Villaruz Print Shop & General Merchandise. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        let selectedProductId = null;
        let selectedProductName = null;
        let selectedSellingPrice = null;
        let quantityToSell = 0;

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

        // Live Search Functionality
        const searchInput = document.getElementById('liveSearchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const searchInfo = document.getElementById('searchInfo');
        const tableRows = document.querySelectorAll('#tableBody tr');

        function performLiveSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            if (searchTerm === '') {
                tableRows.forEach(row => {
                    row.classList.remove('hidden-row');
                    visibleCount++;
                });
                if (searchInfo) {
                    searchInfo.innerHTML = `<i class="fas fa-info-circle"></i> Showing all ${visibleCount} products`;
                }
            } else {
                tableRows.forEach(row => {
                    const productName = row.getAttribute('data-name') || '';
                    const matches = productName.includes(searchTerm);
                    
                    if (matches) {
                        row.classList.remove('hidden-row');
                        visibleCount++;
                    } else {
                        row.classList.add('hidden-row');
                    }
                });

                if (searchInfo) {
                    if (visibleCount === 0) {
                        searchInfo.innerHTML = `<i class="fas fa-search"></i> No products found matching "${searchInput.value}"`;
                    } else {
                        searchInfo.innerHTML = `<i class="fas fa-search"></i> Found ${visibleCount} product(s) matching "${searchInput.value}"`;
                    }
                }
            }

            // Show no results message if needed
            const tbody = document.getElementById('tableBody');
            const existingNoResults = document.querySelector('.no-results-row');

            if (visibleCount === 0 && searchTerm !== '') {
                if (!existingNoResults) {
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results-row';
                    noResultsRow.innerHTML = `<td colspan="6" class="no-results"><i class="fas fa-box-open"></i> No products found matching "<strong>${escapeHtml(searchTerm)}</strong>"<br><small>Try a different search term</small></td>`;
                    tbody.appendChild(noResultsRow);
                }
            } else {
                if (existingNoResults) {
                    existingNoResults.remove();
                }
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performLiveSearch, 300);
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                performLiveSearch();
                searchInput.focus();
            });
        }

        // Toast notification function
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

        // Highlight row function
        function highlightRow(rowId) {
            const row = document.getElementById(rowId);
            if (row) {
                row.classList.add('row-highlight');
                setTimeout(() => {
                    row.classList.remove('row-highlight');
                }, 1500);
            }
        }

        // Function to add new product
        async function addProduct(productName, unit, quantity, price) {
            if (!productName.trim()) {
                showToast('Product name is required', 'error');
                return false;
            }

            if (!unit.trim()) {
                showToast('Unit is required', 'error');
                return false;
            }

            if (price <= 0) {
                showToast('Unit cost must be greater than 0', 'error');
                return false;
            }

            if (quantity < 0 || isNaN(quantity)) {
                showToast('Quantity cannot be negative', 'error');
                return false;
            }

            const confirmBtn = document.getElementById('confirmAddProduct');
            const saveIndicator = document.createElement('span');
            saveIndicator.className = 'save-spinner';
            confirmBtn.appendChild(saveIndicator);
            confirmBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'add_product');
                formData.append('product_name', productName);
                formData.append('unit', unit);
                formData.append('quantity', quantity);
                formData.append('selling_price', price);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../API/add_product.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast('Product added successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                    return true;
                } else {
                    showToast(data.message || 'Failed to add product', 'error');
                    return false;
                }
            } catch (err) {
                console.error('Network error:', err);
                showToast('Network error. Please try again.', 'error');
                return false;
            } finally {
                saveIndicator.remove();
                confirmBtn.disabled = false;
            }
        }

        // Function to update product
        async function updateProduct(productId, productName, unit, quantity, price) {
            if (!productName.trim()) {
                showToast('Product name is required', 'error');
                return false;
            }

            if (!unit.trim()) {
                showToast('Unit is required', 'error');
                return false;
            }

            if (price <= 0) {
                showToast('Unit cost must be greater than 0', 'error');
                return false;
            }

            if (quantity < 0 || isNaN(quantity)) {
                showToast('Quantity cannot be negative', 'error');
                return false;
            }

            const confirmBtn = document.getElementById('confirmUpdateProduct');
            const saveIndicator = document.createElement('span');
            saveIndicator.className = 'save-spinner';
            confirmBtn.appendChild(saveIndicator);
            confirmBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'update_product');
                formData.append('product_id', productId);
                formData.append('product_name', productName);
                formData.append('unit', unit);
                formData.append('quantity', quantity);
                formData.append('selling_price', price);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../API/update_product.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast('Product updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                    return true;
                } else {
                    showToast(data.message || 'Failed to update product', 'error');
                    return false;
                }
            } catch (err) {
                console.error('Network error:', err);
                showToast('Network error. Please try again.', 'error');
                return false;
            } finally {
                saveIndicator.remove();
                confirmBtn.disabled = false;
            }
        }

        // Function to sell product
        async function sellProduct(productId, quantityToSell, productName, sellingPrice) {
            const qtySpan = document.getElementById(`qty-${productId}`);
            let currentQty = parseFloat(qtySpan.textContent);

            if (quantityToSell > currentQty) {
                showToast('Insufficient stock!', 'error');
                return false;
            }

            if (quantityToSell <= 0) {
                showToast('Invalid quantity!', 'error');
                return false;
            }

            const newQty = currentQty - quantityToSell;

            const decrementBtn = document.querySelector(`.qty-btn.decrement[data-id="${productId}"]`);
            const incrementBtn = document.querySelector(`.qty-btn.increment[data-id="${productId}"]`);
            const sellBtn = document.querySelector(`.sell-btn[data-id="${productId}"]`);
            const updateBtn = document.querySelector(`.update-btn[data-id="${productId}"]`);
            
            if (decrementBtn) decrementBtn.disabled = true;
            if (incrementBtn) incrementBtn.disabled = true;
            if (sellBtn) sellBtn.disabled = true;
            if (updateBtn) updateBtn.disabled = true;

            const saveIndicator = document.createElement('span');
            saveIndicator.className = 'save-spinner';
            qtySpan.parentNode.appendChild(saveIndicator);

            try {
                const formData = new FormData();
                formData.append('action', 'sell_product');
                formData.append('product_id', productId);
                formData.append('quantity', quantityToSell);
                formData.append('product_name', productName);
                formData.append('selling_price', sellingPrice);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../API/sold_products.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    qtySpan.textContent = newQty.toFixed(0);
                    showToast(`Sold! ${quantityToSell} pcs × ₱ ${sellingPrice.toFixed(2)} = ₱ ${data.total_amount.toFixed(2)}`, 'success');
                    highlightRow(`row-${productId}`);
                    return true;
                } else {
                    showToast(data.message || 'Sale failed', 'error');
                    return false;
                }
            } catch (err) {
                console.error('Network error:', err);
                showToast('Network error. Please try again.', 'error');
                return false;
            } finally {
                saveIndicator.remove();
                if (decrementBtn) decrementBtn.disabled = false;
                if (incrementBtn) incrementBtn.disabled = false;
                if (sellBtn) sellBtn.disabled = false;
                if (updateBtn) updateBtn.disabled = false;
            }
        }

        // Handle increment and decrement buttons
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                const productId = this.dataset.id;
                const qtySpan = document.getElementById(`qty-${productId}`);
                let currentQty = parseFloat(qtySpan.textContent);

                if (isNaN(currentQty)) currentQty = 0;

                let newQty = currentQty;
                if (this.classList.contains('increment')) {
                    newQty = currentQty + 1;
                } else if (this.classList.contains('decrement')) {
                    newQty = Math.max(0, currentQty - 1);
                }

                if (newQty !== currentQty) {
                    qtySpan.textContent = newQty.toFixed(0);
                }
            });
        });

        // Modal elements
        const modal = document.getElementById('quantityModal');
        const modalConfirm = document.getElementById('modalConfirm');
        const modalCancel = document.getElementById('modalCancel');
        const modalProductName = document.getElementById('modalProductName');
        const modalCurrentStock = document.getElementById('modalCurrentStock');
        const modalTotalAmount = document.getElementById('modalTotalAmount');

        // Update Product Modal elements
        const updateProductModal = document.getElementById('updateProductModal');
        const cancelUpdateProduct = document.getElementById('cancelUpdateProduct');
        const confirmUpdateProduct = document.getElementById('confirmUpdateProduct');
        const updateProductName = document.getElementById('updateProductName');
        const updateUnit = document.getElementById('updateUnit');
        const updateQuantity = document.getElementById('updateQuantity');
        const updatePrice = document.getElementById('updatePrice');

        // Add Product Modal elements
        const addProductModal = document.getElementById('addProductModal');
        const addProductBtn = document.getElementById('addProductBtn');
        const cancelAddProduct = document.getElementById('cancelAddProduct');
        const confirmAddProduct = document.getElementById('confirmAddProduct');
        const productNameInput = document.getElementById('productName');
        const productUnitInput = document.getElementById('productUnit');
        const productQuantityInput = document.getElementById('productQuantity');
        const productPriceInput = document.getElementById('productPrice');

        // Handle SELL button click
        document.querySelectorAll('.sell-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                const productId = this.dataset.id;
                const row = this.closest('tr');
                const productName = row.querySelector('td:nth-child(2) strong').textContent;
                const sellingPriceCell = row.querySelector('td:nth-child(5)');
                const sellingPrice = parseFloat(sellingPriceCell.textContent.replace('₱ ', ''));
                const qtySpan = document.getElementById(`qty-${productId}`);
                const originalQty = parseFloat(qtySpan.getAttribute('data-original') || qtySpan.textContent);
                const currentQty = parseFloat(qtySpan.textContent);
                
                const quantityDecreased = currentQty < originalQty;
                const sellQuantity = originalQty - currentQty;

                if (!quantityDecreased || sellQuantity <= 0) {
                    showToast('Use the - button to reduce quantity before selling', 'error');
                    qtySpan.textContent = originalQty;
                    return;
                }

                const totalAmount = sellingPrice * sellQuantity;

                selectedProductId = productId;
                selectedProductName = productName;
                selectedSellingPrice = sellingPrice;
                quantityToSell = sellQuantity;

                modalProductName.innerHTML = `<strong>Product:</strong> ${productName}<br><strong>Unit Cost:</strong> ₱ ${sellingPrice.toFixed(2)}`;
                modalCurrentStock.innerHTML = `<strong>Stock Change:</strong> ${originalQty} → ${currentQty}<br><strong>Quantity Sold:</strong> ${sellQuantity}`;
                modalTotalAmount.innerHTML = `<strong>Total Amount:</strong> ₱ ${totalAmount.toFixed(2)}`;

                modal.style.display = 'flex';
            });
        });

        // Handle UPDATE button click
        document.querySelectorAll('.update-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                const productId = this.dataset.id;
                const row = this.closest('tr');
                const productName = row.querySelector('td:nth-child(2) strong').textContent;
                const unit = row.querySelector('td:nth-child(3) .unit-badge').textContent;
                const qtySpan = document.getElementById(`qty-${productId}`);
                const currentQty = parseFloat(qtySpan.textContent);
                const priceCell = row.querySelector('td:nth-child(5)');
                const currentPrice = parseFloat(priceCell.textContent.replace('₱ ', ''));

                updateProductName.value = productName;
                updateUnit.value = unit;
                updateQuantity.value = currentQty;
                updatePrice.value = currentPrice;

                selectedProductId = productId;
                updateProductModal.style.display = 'flex';
                setTimeout(() => updateProductName.focus(), 100);
            });
        });

        // Modal confirm for sale
        modalConfirm.addEventListener('click', async () => {
            modal.style.display = 'none';
            await sellProduct(selectedProductId, quantityToSell, selectedProductName, selectedSellingPrice);
            selectedProductId = null;
        });

        modalCancel.addEventListener('click', () => {
            if (selectedProductId) {
                const qtySpan = document.getElementById(`qty-${selectedProductId}`);
                const originalValue = qtySpan.getAttribute('data-original');
                if (originalValue) {
                    qtySpan.textContent = originalValue;
                }
            }
            modal.style.display = 'none';
            selectedProductId = null;
        });

        // Update Product Modal handlers
        cancelUpdateProduct.addEventListener('click', () => {
            updateProductModal.style.display = 'none';
            selectedProductId = null;
        });

        confirmUpdateProduct.addEventListener('click', async () => {
            const productName = updateProductName.value;
            const unit = updateUnit.value;
            const quantity = parseInt(updateQuantity.value);
            const price = parseFloat(updatePrice.value);

            if (!productName.trim()) {
                showToast('Product name is required', 'error');
                updateProductName.focus();
                return;
            }

            if (!unit.trim()) {
                showToast('Unit is required', 'error');
                updateUnit.focus();
                return;
            }

            if (price <= 0) {
                showToast('Unit cost must be greater than 0', 'error');
                updatePrice.focus();
                return;
            }

            if (quantity < 0 || isNaN(quantity)) {
                showToast('Quantity cannot be negative', 'error');
                updateQuantity.focus();
                return;
            }

            updateProductModal.style.display = 'none';
            await updateProduct(selectedProductId, productName, unit, quantity, price);
            selectedProductId = null;
        });

        // Add Product Modal handlers
        addProductBtn.addEventListener('click', () => {
            productNameInput.value = '';
            productUnitInput.value = 'Pcs';
            productQuantityInput.value = '';
            productPriceInput.value = '';
            addProductModal.style.display = 'flex';
            setTimeout(() => productNameInput.focus(), 100);
        });

        cancelAddProduct.addEventListener('click', () => {
            addProductModal.style.display = 'none';
        });

        confirmAddProduct.addEventListener('click', async () => {
            const productName = productNameInput.value;
            const unit = productUnitInput.value;
            const quantity = parseInt(productQuantityInput.value);
            const price = parseFloat(productPriceInput.value);

            if (!productName.trim()) {
                showToast('Product name is required', 'error');
                productNameInput.focus();
                return;
            }

            if (!unit.trim()) {
                showToast('Unit is required', 'error');
                productUnitInput.focus();
                return;
            }

            if (price <= 0) {
                showToast('Unit cost must be greater than 0', 'error');
                productPriceInput.focus();
                return;
            }

            if (isNaN(quantity) || quantity < 0) {
                showToast('Quantity cannot be negative', 'error');
                productQuantityInput.focus();
                return;
            }

            addProductModal.style.display = 'none';
            await addProduct(productName, unit, quantity, price);
        });

        // Plus sign key (+) to open Add Product Modal
        document.addEventListener('keydown', function(e) {
            if (e.key === '+' || e.key === '=' || e.code === 'NumpadAdd') {
                const activeElement = document.activeElement;
                const isInputFocused = activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'SELECT');

                if (!isInputFocused) {
                    e.preventDefault();
                    addProductBtn.click();
                }
            }

            // Escape key to close modals
            if (e.key === 'Escape') {
                if (addProductModal.style.display === 'flex') {
                    addProductModal.style.display = 'none';
                }
                if (updateProductModal.style.display === 'flex') {
                    updateProductModal.style.display = 'none';
                }
                if (modal.style.display === 'flex') {
                    if (selectedProductId) {
                        const qtySpan = document.getElementById(`qty-${selectedProductId}`);
                        const originalValue = qtySpan.getAttribute('data-original');
                        if (originalValue) {
                            qtySpan.textContent = originalValue;
                        }
                    }
                    modal.style.display = 'none';
                    selectedProductId = null;
                }
                closeMenu();
            }
        });

        // Store original quantities on page load
        document.querySelectorAll('.qty-value').forEach(span => {
            span.setAttribute('data-original', span.textContent);
        });

        // Initial search display
        performLiveSearch();
    </script>
</body>

</html>