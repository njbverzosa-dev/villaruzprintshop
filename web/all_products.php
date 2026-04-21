<?php
// all_products.php
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'access_sessions.php';

// If you have an auto-incrementing 'id' column (newest = highest id)
$stmt = $pdo->prepare("SELECT * FROM merchandise_inventory");
$stmt->execute();
$allProducts = $stmt->fetchAll();

// Fetch all unique categories from the database
$stmt = $pdo->prepare("SELECT DISTINCT category FROM merchandise_inventory WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
    <title>Stocks Products | Villaruz Print Shop & General Merchandise</title>
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

        .add-product-btn {
            background: linear-gradient(145deg, #3b82f6, #6366f1);
            border: none;
            border-radius: 30px;
            padding: 10px 25px;
            color: #ffffff;
            font-weight: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-product-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Category Section */
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
            margin-top: 8px;
            margin-bottom: 15px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .product-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 16px 12px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .product-card:hover {
            border-color: #3b82f6;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .product-icon {
            font-size: 44px;
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .product-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #0f172a;
            line-height: 1.3;
        }

        .product-unit {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 8px;
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        .product-price {
            font-size: 18px;
            font-weight: 800;
            color: #3b82f6;
            margin-bottom: 12px;
        }

        .card-qty-control {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #f8fafc;
            border-radius: 40px;
            padding: 4px 8px;
            margin-bottom: 12px;
            width: 100%;
            border: 1px solid #e2e8f0;
        }

        .card-qty-btn {
            background: #ffffff;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 16px;
            font-weight: bold;
            color: #3b82f6;
            cursor: pointer;
            transition: 0.2s;
        }

        .card-qty-btn:hover {
            background: #3b82f6;
            color: #ffffff;
        }

        .card-qty-value {
            font-size: 14px;
            font-weight: 600;
            min-width: 35px;
            text-align: center;
            color: #0f172a;
        }

        .card-actions {
            display: flex;
            gap: 8px;
            width: 100%;
            margin-top: 4px;
        }

        .sell-btn,
        .update-btn,
        .desc-btn {
            flex: 1;
            border: none;
            border-radius: 10px;
            padding: 8px 0;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .sell-btn {
            background: #10b981;
            color: white;
        }

        .sell-btn:hover:not(:disabled) {
            background: #059669;
            transform: translateY(-2px);
        }

        .update-btn {
            background: #3b82f6;
            color: white;
        }

        .update-btn:hover:not(:disabled) {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .desc-btn {
            background: #8b5cf6;
            color: white;
        }

        .desc-btn:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }

        .sell-btn:disabled,
        .update-btn:disabled,
        .desc-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Description Modal - Modern Design */
        .desc-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1200;
            justify-content: center;
            align-items: center;
        }

        .desc-modal-content {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 32px;
            max-width: 500px;
            width: 90%;
            animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .desc-modal-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            padding: 24px 28px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .desc-modal-header h3 {
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .desc-modal-header h3 i {
            font-size: 28px;
        }

        .close-desc-modal {
            font-size: 32px;
            font-weight: 300;
            cursor: pointer;
            transition: all 0.2s;
            line-height: 1;
            opacity: 0.8;
        }

        .close-desc-modal:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .desc-modal-body {
            padding: 28px;
        }

        .product-info-section {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .product-detail-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .product-detail-row:last-child {
            border-bottom: none;
        }

        .product-detail-icon {
            width: 40px;
            height: 40px;
            background: #eff6ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8b5cf6;
        }

        .product-detail-icon i {
            font-size: 18px;
        }

        .product-detail-text {
            flex: 1;
        }

        .product-detail-label {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .product-detail-value {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin-top: 2px;
        }

        .description-section {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .description-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .description-title i {
            color: #8b5cf6;
            font-size: 18px;
        }

        .description-title span {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }

        .description-text {
            color: #475569;
            line-height: 1.6;
            font-size: 14px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .notes-section {
            background: #fffbeb;
            border-radius: 20px;
            padding: 20px;
            border-left: 4px solid #f59e0b;
        }

        .notes-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .notes-title i {
            color: #f59e0b;
            font-size: 16px;
        }

        .notes-title span {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }

        .notes-text {
            color: #78350f;
            line-height: 1.6;
            font-size: 14px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .empty-description {
            color: #94a3b8;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }

        .desc-modal-footer {
            padding: 20px 28px 28px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .close-desc-btn {
            width: 100%;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            border: none;
            padding: 14px;
            border-radius: 16px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .close-desc-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
        }

        /* Other Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1100;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: modalFadeIn 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
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
        .modal-content select,
        .modal-content textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }

        .modal-content input:focus,
        .modal-content select:focus,
        .modal-content textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #ffffff;
        }

        .modal-content textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-content label {
            display: block;
            text-align: left;
            margin-top: 10px;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .modal-content label .required {
            color: #ef4444;
            margin-left: 4px;
        }

        .modal-content .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: -8px;
            margin-bottom: 8px;
            display: none;
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
            transition: all 0.3s;
        }

        .modal-confirm {
            background: #3b82f6;
            color: white;
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

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
            }

            .top-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-wrapper {
                max-width: none;
            }

            .category-buttons {
                justify-content: center;
            }

            .modal-content {
                padding: 20px;
                max-height: 85vh;
            }

            .desc-modal-content {
                width: 95%;
            }

            .desc-modal-header h3 {
                font-size: 18px;
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
                <div class="user-name"><?php echo htmlspecialchars($user['acc_number']); ?></div>
            </div>
            <div class="menu-nav">
                <a href="registered_customers.php" class="nav-item">
                    <i class="fas fa-user-friends"></i>
                    <span>Customers</span>
                </a>
                <a href="all_products.php" class="nav-item active">
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
                    <h4>PRODUCT GALLERY</h4>
                </div>
            </div>

            <div class="top-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="liveSearchInput" class="search-input" placeholder="Search by product name..."
                        autocomplete="off">
                </div>
                <button class="add-product-btn" id="addProductBtn">
                    <i class="fas fa-plus-circle"></i> Add New Product
                </button>
            </div>

            <!-- Category Section - Dynamically generated from database -->
            <div class="category-section">
                <div class="category-title">FILTER BY CATEGORY</div>
                <div class="category-buttons" id="categoryButtonsContainer">
                    <button class="category-btn reset-btn active" data-category="all">
                        <i class="fas fa-th-large"></i> All Products
                    </button>
                    <?php foreach ($categories as $category): ?>
                        <?php if (!empty($category)): ?>
                            <button class="category-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                                <i
                                    class="<?php echo isset($categoryIcons[$category]) ? $categoryIcons[$category] : 'fas fa-tag'; ?>"></i>
                                <?php echo htmlspecialchars($category); ?>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="searchInfo" class="search-info"></div>

            <div class="products-grid" id="productsGrid">
                <?php if (empty($allProducts)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #94a3b8;">
                        <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                        No products found. Click "Add New Product" to get started.
                    </div>
                <?php else: ?>
                    <?php foreach ($allProducts as $product): ?>
                        <div class="product-card" data-id="<?php echo $product['id']; ?>"
                            data-name="<?php echo strtolower(htmlspecialchars($product['product_name'])); ?>"
                            data-fullname="<?php echo htmlspecialchars($product['product_name']); ?>"
                            data-category="<?php echo htmlspecialchars($product['category'] ?? ''); ?>"
                            data-description="<?php echo htmlspecialchars($product['description'] ?? ''); ?>"
                            data-unit="<?php echo htmlspecialchars($product['unit'] ?? 'Pcs'); ?>"
                            data-price="<?php echo number_format($product['selling_price'], 2); ?>"
                            data-qty="<?php echo number_format($product['qty_on_hand']); ?>"
                            data-first-letter="<?php echo strtolower(substr(htmlspecialchars($product['product_name']), 0, 1)); ?>">
                            <div class="product-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></div>
                            <div class="product-unit"><?php echo htmlspecialchars($product['unit'] ?? 'Pcs'); ?></div>
                            <div class="product-price">₱ <?php echo number_format($product['selling_price'], 2); ?></div>

                            <div class="card-qty-control">
                                <button class="card-qty-btn decrement-card" data-id="<?php echo $product['id']; ?>">-</button>
                                <span class="card-qty-value"
                                    id="qty-<?php echo $product['id']; ?>"><?php echo number_format($product['qty_on_hand']); ?></span>
                                <button class="card-qty-btn increment-card" data-id="<?php echo $product['id']; ?>">+</button>
                            </div>

                            <div class="card-actions">
                                <button class="sell-btn" data-id="<?php echo $product['id']; ?>">SELL</button>
                                <button class="desc-btn" data-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-info-circle"></i> INFO
                                </button>
                            </div>
                            <div class="card-actions" style="margin-top: 4px;">
                                <button class="update-btn" data-id="<?php echo $product['id']; ?>" style="width: 100%;">UPDATE</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Description Modal - Modern Design -->
    <div id="descriptionModal" class="desc-modal">
        <div class="desc-modal-content">
            <div class="desc-modal-header">
                <h3>
                    <i class="fas fa-file-alt"></i>
                    Product Information
                </h3>
                <span class="close-desc-modal">&times;</span>
            </div>
            <div class="desc-modal-body">
                <div class="product-info-section">
                    <div class="product-detail-row">
                        <div class="product-detail-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="product-detail-text">
                            <div class="product-detail-label">Product Name</div>
                            <div class="product-detail-value" id="descProductName">-</div>
                        </div>
                    </div>
                    <div class="product-detail-row">
                        <div class="product-detail-icon">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="product-detail-text">
                            <div class="product-detail-label">Unit</div>
                            <div class="product-detail-value" id="descProductUnit">-</div>
                        </div>
                    </div>
                    <div class="product-detail-row">
                        <div class="product-detail-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="product-detail-text">
                            <div class="product-detail-label">Price</div>
                            <div class="product-detail-value" id="descProductPrice">-</div>
                        </div>
                    </div>
                    <div class="product-detail-row">
                        <div class="product-detail-icon">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <div class="product-detail-text">
                            <div class="product-detail-label">Stock Quantity</div>
                            <div class="product-detail-value" id="descProductQty">-</div>
                        </div>
                    </div>
                </div>

                <div class="description-section">
                    <div class="description-title">
                        <i class="fas fa-align-left"></i>
                        <span>Description</span>
                    </div>
                    <div class="description-text" id="descProductDescription">
                        No description available.
                    </div>
                </div>
            </div>
            <div class="desc-modal-footer">
                <button class="close-desc-btn">
                    <i class="fas fa-check-circle"></i> Got it
                </button>
            </div>
        </div>
    </div>

    <!-- Sell Modal -->
    <div id="quantityModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-check-circle"></i> Confirm Sale</h3>
            <p id="modalProductName" style="margin-bottom: 10px;"></p>
            <p id="modalCurrentStock" style="margin-bottom: 10px; color: #64748b;"></p>
            <p id="modalTotalAmount" style="margin-bottom: 15px; font-size: 18px; color: #3b82f6;"></p>
            <label for="purposeSelect">Purpose / Note:</label>
            <select name="note" id="purposeSelect">
                <option value="">Select Purpose</option>
                <option value="Use">Use</option>
                <option value="Sell">Sell</option>
            </select>
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
            
            <label>Product Name <span class="required">*</span></label>
            <input type="text" id="updateProductName" placeholder="Enter product name">
            <div class="error-message" id="updateNameError">Please enter a valid product name</div>
            
            <label>Unit <span class="required">*</span></label>
            <input type="text" id="updateUnit" placeholder="Enter unit (e.g., Pcs, box, ream)">
            <div class="error-message" id="updateUnitError">Please enter a valid unit</div>
            
            <label>Quantity <span class="required">*</span></label>
            <input type="text" id="updateQuantity" placeholder="Enter quantity" pattern="[0-9]+">
            <div class="error-message" id="updateQuantityError">Please enter a valid number (0-9 only)</div>
            
            <label>Unit Cost (₱) <span class="required">*</span></label>
            <input type="text" id="updatePrice" placeholder="Enter selling price">
            <div class="error-message" id="updatePriceError">Please enter a valid price (numbers and decimal only)</div>
            
            <label>Description</label>
            <textarea id="updateDescription" placeholder="Enter product description (optional)"></textarea>
                        
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
            
            <label>Product Name <span class="required">*</span></label>
            <input type="text" id="productName" placeholder="Enter product name">
            <div class="error-message" id="addNameError">Please enter a valid product name</div>
            
            <label>Unit <span class="required">*</span></label>
            <input type="text" id="productUnit" placeholder="Enter unit (e.g., Pcs, box, ream)" value="Pcs">
            <div class="error-message" id="addUnitError">Please enter a valid unit</div>
            
            <label>Quantity <span class="required">*</span></label>
            <input type="text" id="productQuantity" placeholder="Enter quantity">
            <div class="error-message" id="addQuantityError">Please enter a valid number (0-9 only)</div>
            
            <label>Unit Cost (₱) <span class="required">*</span></label>
            <input type="text" id="productPrice" placeholder="Enter selling price">
            <div class="error-message" id="addPriceError">Please enter a valid price (numbers and decimal only)</div>
            
            <label>Description</label>
            <textarea id="productDescription" placeholder="Enter product description (optional)"></textarea>
            
            <div class="modal-buttons">
                <button class="modal-btn modal-cancel" id="cancelAddProduct">Cancel</button>
                <button class="modal-btn modal-confirm" id="confirmAddProduct">Add Product</button>
            </div>
        </div>
    </div>

    <footer>
        <div class="copyright">
            <p>© 2026 Villaruz Print Shop & General Merchandise — Quality prints & everyday goods, delivered with care.
            </p>
        </div>
    </footer>

    <script>
        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let selectedProductId = null;
        let selectedProductName = null;
        let selectedSellingPrice = null;
        let quantityToSell = 0;

        // ========== DESCRIPTION MODAL FUNCTIONS ==========
        const descModal = document.getElementById('descriptionModal');
        const closeDescModalBtn = document.querySelector('.close-desc-modal');
        const closeDescFooterBtn = document.querySelector('.close-desc-btn');

        function openDescriptionModal(productCard) {
            // Get product data from data attributes
            const productName = productCard.getAttribute('data-fullname') || 'N/A';
            const productUnit = productCard.getAttribute('data-unit') || 'N/A';
            const productPrice = productCard.getAttribute('data-price') || '0';
            const productQty = productCard.getAttribute('data-qty') || '0';
            const productDescription = productCard.getAttribute('data-description') || '';

            // Populate modal fields
            document.getElementById('descProductName').textContent = productName;
            document.getElementById('descProductUnit').textContent = productUnit;
            document.getElementById('descProductPrice').textContent = '₱ ' + productPrice;
            document.getElementById('descProductQty').textContent = productQty;
            
            const descElement = document.getElementById('descProductDescription');
            if (productDescription && productDescription.trim() !== '') {
                descElement.innerHTML = productDescription.replace(/\n/g, '<br>');
            } else {
                descElement.innerHTML = '<em style="color: #94a3b8;">No description available.</em>';
            }

            descModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeDescriptionModal() {
            descModal.style.display = 'none';
            document.body.style.overflow = '';
        }

        if (closeDescModalBtn) closeDescModalBtn.addEventListener('click', closeDescriptionModal);
        if (closeDescFooterBtn) closeDescFooterBtn.addEventListener('click', closeDescriptionModal);

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === descModal) {
                closeDescriptionModal();
            }
        });

        // ========== VALIDATION FUNCTIONS ==========
        function validateText(input, errorElement, fieldName) {
            const value = input.value.trim();
            if (value === '') {
                errorElement.style.display = 'block';
                return false;
            }
            const regex = /^[a-zA-Z\s\-'.,()&]+$/;
            if (!regex.test(value)) {
                errorElement.style.display = 'block';
                errorElement.textContent = `Please enter a valid ${fieldName} (letters, spaces, hyphens only)`;
                return false;
            }
            errorElement.style.display = 'none';
            return true;
        }

        function validateNumber(input, errorElement) {
            const value = input.value.trim();
            if (value === '') {
                errorElement.style.display = 'block';
                errorElement.textContent = 'This field is required';
                return false;
            }
            const regex = /^\d+$/;
            if (!regex.test(value)) {
                errorElement.style.display = 'block';
                errorElement.textContent = 'Please enter numbers only (0-9)';
                return false;
            }
            if (parseInt(value) < 0) {
                errorElement.style.display = 'block';
                errorElement.textContent = 'Quantity cannot be negative';
                return false;
            }
            errorElement.style.display = 'none';
            return true;
        }

        function validatePrice(input, errorElement) {
            const value = input.value.trim();
            if (value === '') {
                errorElement.style.display = 'block';
                errorElement.textContent = 'This field is required';
                return false;
            }
            const regex = /^\d+(\.\d{1,2})?$/;
            if (!regex.test(value)) {
                errorElement.style.display = 'block';
                errorElement.textContent = 'Please enter a valid price (e.g., 99.99)';
                return false;
            }
            if (parseFloat(value) <= 0) {
                errorElement.style.display = 'block';
                errorElement.textContent = 'Price must be greater than 0';
                return false;
            }
            errorElement.style.display = 'none';
            return true;
        }

        function clearAddValidationErrors() {
            document.getElementById('addNameError').style.display = 'none';
            document.getElementById('addUnitError').style.display = 'none';
            document.getElementById('addQuantityError').style.display = 'none';
            document.getElementById('addPriceError').style.display = 'none';
        }

        function clearUpdateValidationErrors() {
            document.getElementById('updateNameError').style.display = 'none';
            document.getElementById('updateUnitError').style.display = 'none';
            document.getElementById('updateQuantityError').style.display = 'none';
            document.getElementById('updatePriceError').style.display = 'none';
        }

        // Real-time validation for add modal
        const addNameInput = document.getElementById('productName');
        const addUnitInput = document.getElementById('productUnit');
        const addQuantityInput = document.getElementById('productQuantity');
        const addPriceInput = document.getElementById('productPrice');

        if (addNameInput) {
            addNameInput.addEventListener('input', () => validateText(addNameInput, document.getElementById('addNameError'), 'product name'));
        }
        if (addUnitInput) {
            addUnitInput.addEventListener('input', () => validateText(addUnitInput, document.getElementById('addUnitError'), 'unit'));
        }
        if (addQuantityInput) {
            addQuantityInput.addEventListener('input', () => validateNumber(addQuantityInput, document.getElementById('addQuantityError')));
        }
        if (addPriceInput) {
            addPriceInput.addEventListener('input', () => validatePrice(addPriceInput, document.getElementById('addPriceError')));
        }

        // Real-time validation for update modal
        const updateNameInput = document.getElementById('updateProductName');
        const updateUnitInput = document.getElementById('updateUnit');
        const updateQuantityInput = document.getElementById('updateQuantity');
        const updatePriceInput = document.getElementById('updatePrice');

        if (updateNameInput) {
            updateNameInput.addEventListener('input', () => validateText(updateNameInput, document.getElementById('updateNameError'), 'product name'));
        }
        if (updateUnitInput) {
            updateUnitInput.addEventListener('input', () => validateText(updateUnitInput, document.getElementById('updateUnitError'), 'unit'));
        }
        if (updateQuantityInput) {
            updateQuantityInput.addEventListener('input', () => validateNumber(updateQuantityInput, document.getElementById('updateQuantityError')));
        }
        if (updatePriceInput) {
            updatePriceInput.addEventListener('input', () => validatePrice(updatePriceInput, document.getElementById('updatePriceError')));
        }

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

        function filterByCategory() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            let visibleCount = 0;

            productCards.forEach(card => {
                const productName = card.getAttribute('data-fullname') || '';
                const productNameLower = productName.toLowerCase();
                const productCategory = card.getAttribute('data-category') || '';

                let categoryMatch = true;
                if (activeCategory !== 'all') {
                    categoryMatch = (productCategory === activeCategory);
                }

                let searchMatch = true;
                if (searchTerm !== '') {
                    searchMatch = productNameLower.includes(searchTerm);
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
                    searchInfo.innerHTML = `<i class="fas fa-folder-open"></i> Showing ${visibleCount} products in <strong>${activeCategory}</strong> category`;
                } else {
                    searchInfo.innerHTML = `<i class="fas fa-folder-open"></i> Found ${visibleCount} product(s) in <strong>${activeCategory}</strong> matching "${searchTerm}"`;
                }
            } else {
                if (searchTerm === '') {
                    searchInfo.innerHTML = `<i class="fas fa-info-circle"></i> Showing all ${visibleCount} products`;
                } else {
                    searchInfo.innerHTML = `<i class="fas fa-search"></i> Found ${visibleCount} product(s) matching "${searchTerm}"`;
                }
            }
        }

        categoryButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const category = btn.getAttribute('data-category');
                activeCategory = category;
                categoryButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterByCategory();
            });
        });

        const searchInput = document.getElementById('liveSearchInput');
        const searchInfo = document.getElementById('searchInfo');
        const productCards = document.querySelectorAll('.product-card');

        function performLiveSearch() {
            filterByCategory();
        }

        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performLiveSearch, 100);
            });
        }
        performLiveSearch();

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

        // Quantity Controls
        document.querySelectorAll('.decrement-card').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const productId = this.dataset.id;
                const qtySpan = document.getElementById(`qty-${productId}`);
                let currentQty = parseInt(qtySpan.textContent);
                if (!isNaN(currentQty) && currentQty > 0) {
                    qtySpan.textContent = currentQty - 1;
                    // Update data-qty attribute on parent card
                    const card = this.closest('.product-card');
                    if (card) card.setAttribute('data-qty', qtySpan.textContent);
                }
            });
        });

        document.querySelectorAll('.increment-card').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const productId = this.dataset.id;
                const qtySpan = document.getElementById(`qty-${productId}`);
                let currentQty = parseInt(qtySpan.textContent);
                if (!isNaN(currentQty)) {
                    qtySpan.textContent = currentQty + 1;
                    // Update data-qty attribute on parent card
                    const card = this.closest('.product-card');
                    if (card) card.setAttribute('data-qty', qtySpan.textContent);
                }
            });
        });

        // Description Button
        document.querySelectorAll('.desc-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const productCard = this.closest('.product-card');
                openDescriptionModal(productCard);
            });
        });

        // Sell Button
        document.querySelectorAll('.sell-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const productId = this.dataset.id;
                const card = this.closest('.product-card');
                const productName = card.querySelector('.product-title').textContent;
                const priceElem = card.querySelector('.product-price');
                const sellingPrice = parseFloat(priceElem.textContent.replace('₱ ', '').replace(',', ''));
                const qtySpan = document.getElementById(`qty-${productId}`);
                let currentQty = parseInt(qtySpan.textContent);

                if (!qtySpan.hasAttribute('data-original')) {
                    qtySpan.setAttribute('data-original', qtySpan.textContent);
                }
                const originalQty = parseInt(qtySpan.getAttribute('data-original'));
                const sellQuantity = originalQty - currentQty;

                if (sellQuantity <= 0) {
                    showToast('No quantity decrease detected. Use the - button to reduce stock before selling.', 'error');
                    qtySpan.textContent = originalQty;
                    return;
                }

                const totalAmount = sellingPrice * sellQuantity;
                selectedProductId = productId;
                selectedProductName = productName;
                selectedSellingPrice = sellingPrice;
                quantityToSell = sellQuantity;

                document.getElementById('modalProductName').innerHTML = `<strong>Product:</strong> ${productName}<br><strong>Unit Cost:</strong> ₱ ${sellingPrice.toFixed(2)}`;
                document.getElementById('modalCurrentStock').innerHTML = `<strong>Stock Change:</strong> ${originalQty} → ${currentQty}<br><strong>Quantity Sold:</strong> ${sellQuantity}`;
                document.getElementById('modalTotalAmount').innerHTML = `<strong>Total Amount:</strong> ₱ ${totalAmount.toFixed(2)}<br><small>(${sellQuantity} × ₱ ${sellingPrice.toFixed(2)})</small>`;

                document.getElementById('purposeSelect').value = '';
                document.getElementById('quantityModal').style.display = 'flex';
            });
        });

        // Update Button
        document.querySelectorAll('.update-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const productId = this.dataset.id;
                const card = this.closest('.product-card');
                const productName = card.querySelector('.product-title').textContent;
                const unitElem = card.querySelector('.product-unit');
                const unit = unitElem ? unitElem.textContent : 'Pcs';
                const qtySpan = document.getElementById(`qty-${productId}`);
                const currentQty = parseInt(qtySpan.textContent);
                const priceElem = card.querySelector('.product-price');
                const currentPrice = parseFloat(priceElem.textContent.replace('₱ ', '').replace(',', ''));
                const description = card.getAttribute('data-description') || '';

                document.getElementById('updateProductName').value = productName;
                document.getElementById('updateUnit').value = unit;
                document.getElementById('updateQuantity').value = currentQty;
                document.getElementById('updatePrice').value = currentPrice;
                document.getElementById('updateDescription').value = description;

                selectedProductId = productId;
                clearUpdateValidationErrors();
                document.getElementById('updateProductModal').style.display = 'flex';
                setTimeout(() => document.getElementById('updateProductName').focus(), 100);
            });
        });

        // Sell Confirmation
        const modal = document.getElementById('quantityModal');
        const modalConfirm = document.getElementById('modalConfirm');
        const modalCancel = document.getElementById('modalCancel');

        modalConfirm.addEventListener('click', async () => {
            const selectedPurpose = document.getElementById('purposeSelect').value;

            if (!selectedPurpose) {
                showToast('Please select a purpose for this sale', 'error');
                return;
            }

            modal.style.display = 'none';
            const qtySpan = document.getElementById(`qty-${selectedProductId}`);
            const originalQty = parseInt(qtySpan.getAttribute('data-original'));
            const currentQty = parseInt(qtySpan.textContent);
            const sellQuantity = originalQty - currentQty;

            if (sellQuantity <= 0) {
                showToast('Invalid sale quantity', 'error');
                return;
            }

            const sellBtn = document.querySelector(`.sell-btn[data-id="${selectedProductId}"]`);
            const updateBtn = document.querySelector(`.update-btn[data-id="${selectedProductId}"]`);
            const decrBtn = document.querySelector(`.decrement-card[data-id="${selectedProductId}"]`);
            const incrBtn = document.querySelector(`.increment-card[data-id="${selectedProductId}"]`);

            if (sellBtn) sellBtn.disabled = true;
            if (updateBtn) updateBtn.disabled = true;
            if (decrBtn) decrBtn.disabled = true;
            if (incrBtn) incrBtn.disabled = true;

            const saveIndicator = document.createElement('span');
            saveIndicator.className = 'save-spinner';
            if (sellBtn) sellBtn.appendChild(saveIndicator);

            try {
                const formData = new FormData();
                formData.append('action', 'sell_product');
                formData.append('product_id', selectedProductId);
                formData.append('quantity', sellQuantity);
                formData.append('product_name', selectedProductName);
                formData.append('selling_price', selectedSellingPrice);
                formData.append('purpose', selectedPurpose);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../API/sold_products.php', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    qtySpan.textContent = currentQty;
                    qtySpan.setAttribute('data-original', currentQty);
                    // Update data-qty on card
                    const card = document.querySelector(`.product-card[data-id="${selectedProductId}"]`);
                    if (card) card.setAttribute('data-qty', currentQty);
                    showToast(`Sold! ${sellQuantity} × ₱ ${selectedSellingPrice.toFixed(2)} = ₱ ${data.total_amount.toFixed(2)}`, 'success');
                } else {
                    qtySpan.textContent = originalQty;
                    showToast(data.message || 'Sale failed', 'error');
                }
            } catch (err) {
                qtySpan.textContent = originalQty;
                showToast('Network error', 'error');
            } finally {
                if (sellBtn) sellBtn.disabled = false;
                if (updateBtn) updateBtn.disabled = false;
                if (decrBtn) decrBtn.disabled = false;
                if (incrBtn) incrBtn.disabled = false;
                if (saveIndicator) saveIndicator.remove();
            }
            selectedProductId = null;
        });

        modalCancel.addEventListener('click', () => {
            if (selectedProductId) {
                const qtySpan = document.getElementById(`qty-${selectedProductId}`);
                const originalQty = qtySpan.getAttribute('data-original');
                if (originalQty) qtySpan.textContent = originalQty;
            }
            modal.style.display = 'none';
            selectedProductId = null;
        });

        // Update Product
        const updateModal = document.getElementById('updateProductModal');
        const cancelUpdate = document.getElementById('cancelUpdateProduct');
        const confirmUpdate = document.getElementById('confirmUpdateProduct');

        cancelUpdate.addEventListener('click', () => {
            updateModal.style.display = 'none';
            selectedProductId = null;
        });

        confirmUpdate.addEventListener('click', async () => {
            const isValidName = validateText(updateNameInput, document.getElementById('updateNameError'), 'product name');
            const isValidUnit = validateText(updateUnitInput, document.getElementById('updateUnitError'), 'unit');
            const isValidQuantity = validateNumber(updateQuantityInput, document.getElementById('updateQuantityError'));
            const isValidPrice = validatePrice(updatePriceInput, document.getElementById('updatePriceError'));

            if (!isValidName || !isValidUnit || !isValidQuantity || !isValidPrice) {
                showToast('Please correct the errors in the form', 'error');
                return;
            }

            const productName = updateNameInput.value.trim();
            const unit = updateUnitInput.value.trim();
            const quantity = parseInt(updateQuantityInput.value.trim());
            const price = parseFloat(updatePriceInput.value.trim());
            const description = document.getElementById('updateDescription').value.trim();

            updateModal.style.display = 'none';
            const saveIndicator = document.createElement('span');
            saveIndicator.className = 'save-spinner';
            confirmUpdate.appendChild(saveIndicator);
            confirmUpdate.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'update_product');
                formData.append('product_id', selectedProductId);
                formData.append('product_name', productName);
                formData.append('unit', unit);
                formData.append('quantity', quantity);
                formData.append('selling_price', price);
                formData.append('description', description);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../API/update_product.php', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    showToast('Product updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Update failed', 'error');
                }
            } catch (err) {
                showToast('Network error', 'error');
            } finally {
                saveIndicator.remove();
                confirmUpdate.disabled = false;
            }
        });

        // Add Product
        const addModal = document.getElementById('addProductModal');
        const addBtn = document.getElementById('addProductBtn');
        const cancelAdd = document.getElementById('cancelAddProduct');
        const confirmAdd = document.getElementById('confirmAddProduct');

        addBtn.addEventListener('click', () => {
            document.getElementById('productName').value = '';
            document.getElementById('productUnit').value = 'Pcs';
            document.getElementById('productQuantity').value = '';
            document.getElementById('productPrice').value = '';
            document.getElementById('productDescription').value = '';
            clearAddValidationErrors();
            addModal.style.display = 'flex';
            setTimeout(() => document.getElementById('productName').focus(), 100);
        });

        cancelAdd.addEventListener('click', () => {
            addModal.style.display = 'none';
        });

        confirmAdd.addEventListener('click', async () => {
            const isValidName = validateText(addNameInput, document.getElementById('addNameError'), 'product name');
            const isValidUnit = validateText(addUnitInput, document.getElementById('addUnitError'), 'unit');
            const isValidQuantity = validateNumber(addQuantityInput, document.getElementById('addQuantityError'));
            const isValidPrice = validatePrice(addPriceInput, document.getElementById('addPriceError'));

            if (!isValidName || !isValidUnit || !isValidQuantity || !isValidPrice) {
                showToast('Please correct the errors in the form', 'error');
                return;
            }

            const productName = addNameInput.value.trim();
            const unit = addUnitInput.value.trim();
            const quantity = parseInt(addQuantityInput.value.trim());
            const price = parseFloat(addPriceInput.value.trim());
            const description = document.getElementById('productDescription').value.trim();

            addModal.style.display = 'none';
            const saveIndicator = document.createElement('span');
            saveIndicator.className = 'save-spinner';
            confirmAdd.appendChild(saveIndicator);
            confirmAdd.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'add_product');
                formData.append('product_name', productName);
                formData.append('unit', unit);
                formData.append('quantity', quantity);
                formData.append('selling_price', price);
                formData.append('description', description);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../API/add_product.php', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    showToast('Product added successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Add failed', 'error');
                }
            } catch (err) {
                showToast('Network error', 'error');
            } finally {
                saveIndicator.remove();
                confirmAdd.disabled = false;
            }
        });

        // Store original quantities
        document.querySelectorAll('.card-qty-value').forEach(span => {
            span.setAttribute('data-original', span.textContent);
        });
    </script>
</body>

</html>