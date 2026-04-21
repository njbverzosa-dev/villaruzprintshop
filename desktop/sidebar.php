<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    .sidebar {
        width: 280px;
        background: #ffffff;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        height: 100vh;
        position: sticky;
        top: 0;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.03);
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        padding: 20px 15px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .logo {
        font-size: 24px;
        font-weight: 800;
        background: linear-gradient(145deg, #3b82f6, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        white-space: nowrap;
    }

    .sidebar-content {
        padding: 20px 15px;
        flex: 1;
    }

    .nav-section {
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #94a3b8;
        margin-bottom: 15px;
        padding-left: 5px;
        font-weight: 600;
    }

    .nav-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .nav-item {
        background: #f8fafc;
        border-radius: 16px;
        padding: 16px 8px;
        text-align: center;
        transition: all 0.3s;
        border: 1px solid #e2e8f0;
        color: #64748b;
        display: block;
        text-decoration: none;
    }

    .nav-item:hover {
        border-color: #3b82f6;
        transform: translateY(-3px);
        color: #1e293b;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* .nav-item.active {
        border-color: #3b82f6;
        background: #eff6ff;
        color: #3b82f6;
    } */

    .nav-item i {
        font-size: 28px;
        margin-bottom: 8px;
        display: block;
        color: #3b82f6;
    }

    .nav-item span {
        font-size: 14px;
        font-weight: 500;
        display: block;
        word-break: break-word;
    }

    .logo {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px 15px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        position: sticky;
        top: 0;
        z-index: 10;
        width: 100%;
    }

    .logo img {
        width: 100%;
        max-width: 180px;
        height: auto;
        object-fit: contain;
        display: block;
    }

    @media (max-width: 768px) {
        .logo img {
            max-width: 140px;
        }
    }
</style>

<aside class="sidebar">
    <div class="logo">
        <img src="logo/logo.jpeg" alt="Villaruz Print Shop Logo">
    </div>
    <div class="sidebar-content">
        <div class="nav-section">
            <div class="section-title">MAIN</div>
            <div class="nav-grid">
                <a href="all_products.php" class="nav-item">
                    <i class="fas fa-boxes"></i>
                    <span>Product</span>
                </a>

                <a href="sold_products.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Sold</span>
                </a>

                <a href="all_deliveries.php" class="nav-item">
                    <i class="fas fa-truck"></i>
                    <span>Deliveries</span>
                </a>
                
                <a href="all_costumer.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>

                <a href="closed.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</aside>