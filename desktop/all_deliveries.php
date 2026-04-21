<?php
// all_products.php 
require_once 'access_sessions.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Make sure cart is an array
if (!is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Count items in cart (filter out any invalid entries)
$cartItemCount = 0;
if (is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item) && isset($item['quantity']) && $item['quantity'] > 0) {
            $cartItemCount++;
        }
    }
}

// Check if cart has items (for initial page load)
$hasCartItems = $cartItemCount > 0;

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
    <title>GameEarn · Online Shopping</title>
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
            color: #3b82f6;
        }

        /* Cart Section */
        .cart-section {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        /* Hide cart section when empty */
        .cart-section.hidden-cart {
            display: none !important;
        }

        .cart-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }

        .cart-header h2 {
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0f172a;
        }

        .cart-header h2 i {
            color: #3b82f6;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table th,
        .cart-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .cart-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cart-summary {
            padding: 20px 25px;
            background: #f8fafc;
            border-radius: 0 0 20px 20px;
        }

        .ordered-by-input {
            margin-bottom: 20px;
        }

        .ordered-by-input label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e293b;
        }

        .ordered-by-input input {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
        }

        .ordered-by-input input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 20px;
        }

        .order-now-btn {
            background: linear-gradient(145deg, #10b981, #059669);
            border: none;
            border-radius: 12px;
            padding: 14px 30px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .order-now-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .order-now-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .remove-cart-btn {
            background: #ef4444;
            border: none;
            border-radius: 20px;
            padding: 6px 15px;
            color: white;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .remove-cart-btn:hover {
            background: #dc2626;
        }

        /* Products Section */
        .merchandise-section {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
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

        /* Search Bar - Live Search */
        .search-container {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
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
            margin-left: 10px;
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

        /* Hide row class for search */
        .hidden-row {
            display: none;
        }

        /* No results styling */
        .no-results {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
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
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .inventory-table tr:hover:not(.hidden-row) {
            background: #f8fafc;
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

        .qty-value {
            min-width: 60px;
            text-align: center;
            padding: 0 8px;
            font-weight: 600;
            color: #1e293b;
            background: #f8fafc;
        }

        /* ADD TO CART button */
        .add-to-cart-btn {
            background: #3b82f6;
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-to-cart-btn:hover:not(:disabled) {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .add-to-cart-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .unit-badge {
            background: #e2e8f0;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            color: #475569;
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

        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
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
                    <h1>ONLINE SHOPPING
                        <span><?php echo htmlspecialchars($user['acc_number']); ?></span>
                    </h1>
                </div>
            </div>

            <!-- Shopping Cart Section - PHP determines initial visibility -->
            <div class="cart-section <?php echo !$hasCartItems ? 'hidden-cart' : ''; ?>" id="cartSection">
                <div class="cart-header">
                    <h2><i class="fas fa-shopping-cart"></i> Ordered Cart</h2>
                </div>
                <div id="cartContent">
                    <!-- Cart content will be loaded here -->
                </div>
            </div>

            <!-- Products Section -->
            <div class="merchandise-section">
                <div class="section-header">
                    <h5><i class="fas fa-boxes"></i> Available Products</h5>
                </div>
                <!-- Live Search Bar -->
                <div style="padding: 20px 25px 0 25px;">
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
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Unit</th>
                                <th>Available Stock</th>
                                <th>Unit Cost</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <?php if (empty($allProducts)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        No products available
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allProducts as $product): ?>
                                    <tr data-id="<?php echo $product['id']; ?>"
                                        data-name="<?php echo strtolower(htmlspecialchars($product['product_name'])); ?>">
                                        <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                        <td><span
                                                class="unit-badge"><?php echo htmlspecialchars($product['unit'] ?? 'Pcs'); ?></span>
                                        </td>
                                        <td id="stock-<?php echo $product['id']; ?>"
                                            data-original-stock="<?php echo $product['qty_on_hand']; ?>">
                                            <?php echo number_format($product['qty_on_hand']); ?>
                                        </td>
                                        <td>₱ <?php echo number_format($product['selling_price'], 2); ?></td>
                                        <td>
                                            <div class="qty-control">
                                                <button class="qty-btn decrement"
                                                    data-id="<?php echo $product['id']; ?>">-</button>
                                                <span class="qty-value" id="qty-<?php echo $product['id']; ?>">0</span>
                                                <button class="qty-btn increment"
                                                    data-id="<?php echo $product['id']; ?>">+</button>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="add-to-cart-btn" data-id="<?php echo $product['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                data-price="<?php echo $product['selling_price']; ?>"
                                                data-unit="<?php echo htmlspecialchars($product['unit'] ?? 'Pcs'); ?>">
                                                <i class="fas fa-cart-plus"></i> ADD TO CART
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

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner"></i>
            <p>Processing...</p>
        </div>
    </div>

    <footer>
        <div class="copyright">
            <p>© 2025 GameEarn. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        let cart = <?php echo json_encode($_SESSION['cart']); ?>;

        // Ensure cart is an object and filter out invalid entries
        if (typeof cart !== 'object' || cart === null) {
            cart = {};
        }

        // Clean cart - remove any invalid items
        for (let id in cart) {
            if (!cart[id] || typeof cart[id] !== 'object' || !cart[id].quantity || cart[id].quantity <= 0) {
                delete cart[id];
            }
        }

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
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'flex';
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'none';
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>]/g, function (m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        function saveCartToSession() {
            fetch('API/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_cart',
                    cart: cart,
                    csrf_token: csrfToken
                })
            }).catch(err => console.error('Error saving cart:', err));
        }

        function toggleCartSection() {
            const cartSection = document.getElementById('cartSection');
            if (cartSection) {
                let hasValidItems = false;
                if (cart && typeof cart === 'object') {
                    for (let id in cart) {
                        if (cart[id] && cart[id].quantity && cart[id].quantity > 0) {
                            hasValidItems = true;
                            break;
                        }
                    }
                }
                if (!hasValidItems) {
                    cartSection.classList.add('hidden-cart');
                } else {
                    cartSection.classList.remove('hidden-cart');
                }
            }
        }

        async function updateStockInDatabase(productId, quantity, action) {
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('product_id', productId);
                formData.append('quantity', quantity);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('/../API/update_stock.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            } catch (err) {
                console.error('Error updating stock:', err);
                throw err;
            }
        }

        async function addToCart(productId, productName, price, unit, quantity) {
            if (quantity <= 0) {
                showToast('Please select quantity first (use + button to increase)', 'error');
                return false;
            }

            const stockSpan = document.getElementById(`stock-${productId}`);
            if (!stockSpan) {
                showToast('Error: Product not found', 'error');
                return false;
            }

            let currentStock = parseInt(stockSpan.getAttribute('data-original-stock') || stockSpan.textContent.replace(/,/g, ''));

            if (quantity > currentStock) {
                showToast(`Insufficient stock! Only ${currentStock} available.`, 'error');
                return false;
            }

            showLoading();

            try {
                await updateStockInDatabase(productId, quantity, 'deduct_stock');

                const newStock = currentStock - quantity;
                stockSpan.textContent = newStock.toLocaleString();
                stockSpan.setAttribute('data-original-stock', newStock);

                if (cart[productId]) {
                    cart[productId].quantity = (cart[productId].quantity || 0) + quantity;
                } else {
                    cart[productId] = {
                        id: productId,
                        name: productName,
                        price: parseFloat(price),
                        unit: unit,
                        quantity: quantity
                    };
                }

                saveCartToSession();
                await renderCart();

                const qtySpan = document.getElementById(`qty-${productId}`);
                if (qtySpan) {
                    qtySpan.textContent = '0';
                }

                showToast(`${quantity} × ${productName} added to cart!`, 'success');
                return true;
            } catch (err) {
                showToast(err.message || 'Error adding to cart', 'error');
                return false;
            } finally {
                hideLoading();
            }
        }

        async function renderCart() {
            const cartContent = document.getElementById('cartContent');
            if (!cartContent) return;

            for (let id in cart) {
                if (!cart[id] || typeof cart[id] !== 'object' || !cart[id].quantity || cart[id].quantity <= 0) {
                    delete cart[id];
                }
            }

            if (!cart || Object.keys(cart).length === 0) {
                cartContent.innerHTML = '';
                toggleCartSection();
                return;
            }

            let html = `
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Unit</th>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

            let totalAmount = 0;

            for (const [id, item] of Object.entries(cart)) {
                if (!item || !item.name || !item.quantity || item.quantity <= 0) continue;

                const amount = (item.quantity || 0) * (item.price || 0);
                totalAmount += amount;
                html += `
                <tr data-cart-id="${id}">
                    <td><strong>${escapeHtml(item.name)}</strong></td>
                    <td><span class="unit-badge">${escapeHtml(item.unit || 'Pcs')}</span></td>
                    <td>${item.quantity || 0}</td>
                    <td>₱ ${(item.price || 0).toFixed(2)}</td>
                    <td>₱ ${amount.toFixed(2)}</td>
                    <td><button class="remove-cart-btn" data-id="${id}">Remove</button></td>
                </tr>
            `;
            }

            html += `
                </tbody>
            </table>
            <div class="cart-summary">
                <div class="ordered-by-input">
                    <label for="orderedBy">Ordered By (Customer Name):</label>
                    <input type="text" id="orderedBy" placeholder="Enter customer name" required>
                </div>
                <div class="ordered-by-input">
                    <label for="deliveryAddress">Delivery Address:</label>
                    <input type="text" id="deliveryAddress" placeholder="Enter customer address" required>
                </div>
                <div class="total-amount">
                    Total Amount: ₱ ${totalAmount.toFixed(2)}
                </div>
                <button class="order-now-btn" id="orderNowBtn" ${totalAmount === 0 ? 'disabled' : ''}>
                    <i class="fas fa-check-circle"></i> Order Now
                </button>
            </div>
        `;

            cartContent.innerHTML = html;
            toggleCartSection();

            document.querySelectorAll('.remove-cart-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const productId = btn.dataset.id;
                    const item = cart[productId];
                    if (!item) return;

                    showLoading();
                    try {
                        await updateStockInDatabase(productId, item.quantity, 'restore_stock');
                        const stockSpan = document.getElementById(`stock-${productId}`);
                        if (stockSpan) {
                            const currentStock = parseInt(stockSpan.getAttribute('data-original-stock') || stockSpan.textContent.replace(/,/g, ''));
                            const newStock = currentStock + item.quantity;
                            stockSpan.textContent = newStock.toLocaleString();
                            stockSpan.setAttribute('data-original-stock', newStock);
                        }
                        delete cart[productId];
                        saveCartToSession();
                        await renderCart();
                        showToast('Item removed from cart and stock restored', 'success');
                    } catch (err) {
                        showToast(err.message || 'Error removing item', 'error');
                    } finally {
                        hideLoading();
                    }
                });
            });

            const orderNowBtn = document.getElementById('orderNowBtn');
            if (orderNowBtn) {
                orderNowBtn.addEventListener('click', placeOrder);
            }
        }

        async function checkExistingOrders(customerName) {
            try {
                const formData = new FormData();
                formData.append('ordered_by', customerName);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('/../API/check_existing_orders.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                return data;
            } catch (err) {
                console.error('Error checking existing orders:', err);
                return { success: false, has_orders: false };
            }
        }

        async function placeOrder() {
            const orderedBy = document.getElementById('orderedBy')?.value.trim();
            const deliveryAddress = document.getElementById('deliveryAddress')?.value.trim();

            if (!orderedBy) {
                showToast('Please enter customer name', 'error');
                return;
            }

            if (!deliveryAddress) {
                showToast('Please enter delivery address', 'error');
                return;
            }

            if (!cart || Object.keys(cart).length === 0) {
                showToast('Cart is empty', 'error');
                return;
            }

            let isFollowUp = false;
            let existingDeliveryNumber = '';

            showLoading();
            try {
                const existingOrders = await checkExistingOrders(orderedBy);

                if (existingOrders.success && existingOrders.has_orders && existingOrders.orders.length > 0) {
                    const userConfirmed = confirm(
                        `Customer "${orderedBy}" has existing pending orders.\n\n` +
                        `Delivery Number: ${existingOrders.orders[0].delivery_number}\n` +
                        `Current Total: ₱ ${parseFloat(existingOrders.orders[0].total_amount).toFixed(2)}\n\n` +
                        `Do you want to add these items as a follow-up order to the existing delivery?\n\n` +
                        `Click "OK" for Follow-up Order\n` +
                        `Click "Cancel" for New Order`
                    );

                    if (userConfirmed) {
                        isFollowUp = true;
                        existingDeliveryNumber = existingOrders.orders[0].delivery_number;
                        showToast('Processing follow-up order...', 'success');
                    } else {
                        showToast('Creating new order...', 'success');
                    }
                }
            } catch (err) {
                console.error('Error checking orders:', err);
            } finally {
                hideLoading();
            }

            const orderNowBtn = document.getElementById('orderNowBtn');
            if (orderNowBtn) {
                orderNowBtn.disabled = true;
                orderNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
            showLoading();

            try {
                const formData = new FormData();
                formData.append('action', 'place_order');
                formData.append('ordered_by', orderedBy);
                formData.append('delivery_address', deliveryAddress);
                formData.append('cart', JSON.stringify(cart));
                formData.append('csrf_token', csrfToken);
                formData.append('is_follow_up', isFollowUp ? '1' : '0');
                formData.append('existing_delivery_number', existingDeliveryNumber);

                const response = await fetch('/../API/place_orders.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    cart = {};
                    saveCartToSession();
                    await renderCart();

                    const orderedByInput = document.getElementById('orderedBy');
                    const addressInput = document.getElementById('deliveryAddress');
                    if (orderedByInput) orderedByInput.value = '';
                    if (addressInput) addressInput.value = '';

                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Failed to place order', 'error');
                }
            } catch (err) {
                console.error('Error:', err);
                showToast('Network error. Please try again.', 'error');
            } finally {
                if (orderNowBtn) {
                    orderNowBtn.disabled = false;
                    orderNowBtn.innerHTML = '<i class="fas fa-check-circle"></i> Order Now';
                }
                hideLoading();
            }
        }

        // Event Listeners
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', async function (e) {
                e.preventDefault();
                const productId = this.dataset.id;
                const qtySpan = document.getElementById(`qty-${productId}`);
                if (!qtySpan) return;

                let currentQty = parseInt(qtySpan.textContent) || 0;
                const stockSpan = document.getElementById(`stock-${productId}`);
                if (!stockSpan) return;

                const maxStock = parseInt(stockSpan.getAttribute('data-original-stock') || stockSpan.textContent.replace(/,/g, ''));

                if (this.classList.contains('increment')) {
                    if (currentQty < maxStock) {
                        qtySpan.textContent = currentQty + 1;
                    } else {
                        showToast(`Only ${maxStock} available in stock`, 'error');
                    }
                } else if (this.classList.contains('decrement')) {
                    if (currentQty > 0) {
                        qtySpan.textContent = currentQty - 1;
                    }
                }
            });
        });

        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const productId = this.dataset.id;
                const productName = this.dataset.name;
                const price = parseFloat(this.dataset.price);
                const unit = this.dataset.unit;
                const qtySpan = document.getElementById(`qty-${productId}`);
                const quantity = qtySpan ? parseInt(qtySpan.textContent) : 0;
                addToCart(productId, productName, price, unit, quantity);
            });
        });

        // Initial render
        renderCart();

        // Set active nav item
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.querySelector('span')?.textContent === 'Product') {
                item.classList.add('active');
            }
        });

        // Live search functionality
        const searchInput = document.getElementById('liveSearchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const searchInfo = document.getElementById('searchInfo');
        const tableBody = document.getElementById('productsTableBody');

        function performLiveSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr');
            let visibleCount = 0;

            const existingNoResults = tableBody.querySelector('.no-results-row');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            if (searchTerm === '') {
                rows.forEach(row => {
                    row.classList.remove('hidden-row');
                    row.style.display = '';
                    visibleCount++;
                });
                if (searchInfo) {
                    searchInfo.innerHTML = `<i class="fas fa-info-circle"></i> Showing all ${visibleCount} products`;
                }
            } else {
                rows.forEach(row => {
                    if (row.classList && row.classList.contains('no-results-row')) return;

                    const productName = row.getAttribute('data-name') || '';
                    const productNameCell = row.querySelector('td:first-child strong');
                    const actualProductName = productNameCell ? productNameCell.textContent.toLowerCase() : productName;
                    const matches = actualProductName.includes(searchTerm);

                    if (matches) {
                        row.classList.remove('hidden-row');
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.classList.add('hidden-row');
                        row.style.display = 'none';
                    }
                });

                if (searchInfo) {
                    if (visibleCount === 0) {
                        searchInfo.innerHTML = `<i class="fas fa-search"></i> No products found matching "${searchInput.value}"`;
                        const noResultsRow = document.createElement('tr');
                        noResultsRow.className = 'no-results-row';
                        noResultsRow.innerHTML = `<td colspan="6" class="no-results">
                        <i class="fas fa-box-open"></i>
                        No products found matching "<strong>${escapeHtml(searchInput.value)}</strong>"<br>
                        <small>Try a different search term</small>
                    </td>`;
                        tableBody.appendChild(noResultsRow);
                    } else {
                        searchInfo.innerHTML = `<i class="fas fa-search"></i> Found ${visibleCount} product(s) matching "${escapeHtml(searchInput.value)}"`;
                    }
                }
            }
        }

        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function () {
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

        performLiveSearch();
    </script>
</body>

</html>