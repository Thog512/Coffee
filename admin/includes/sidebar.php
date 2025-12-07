<?php
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../../classes/Auth.php';
$isManager = Auth::isManager();
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo APP_URL; ?>/admin/dashboard.php">
            <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="Hiniu Logo" class="logo">
        </a>
    </div>
    <ul class="sidebar-nav">
        <!-- Trang Chủ - Everyone -->
        <li>
            <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Trang Chủ</span>
            </a>
        </li>
        
        <!-- Bán Hàng - Everyone (Customer can buy) -->
        <li>
            <a href="<?php echo APP_URL; ?>/admin/pos.php" class="<?php echo ($current_page == 'pos.php') ? 'active' : ''; ?>">
                <i class="fas fa-cash-register"></i>
                <span>Mua Hàng</span>
            </a>
        </li>
        
        <!-- Bàn - Everyone (Customer can view tables, Manager can manage reservations) -->
        <li class="has-submenu">
            <a href="<?php echo APP_URL; ?>/admin/tables.php" class="<?php echo in_array($current_page, ['tables.php', 'table_form.php', 'reservations.php', 'reservation_form.php', 'reservation_form_new.php', 'reservation_calendar.php']) ? 'active' : ''; ?>">
                <i class="fas fa-chair"></i>
                <span>Bàn</span>
            </a>
            <ul class="submenu">
                <li>
                    <a href="<?php echo APP_URL; ?>/admin/tables.php" class="<?php echo $current_page == 'tables.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> Danh Sách Bàn
                    </a>
                </li>
                <?php if ($isManager): ?>
                <li>
                    <a href="<?php echo APP_URL; ?>/admin/reservation_calendar.php" class="<?php echo $current_page == 'reservation_calendar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Lịch Đặt Bàn
                    </a>
                </li>
                <li>
                    <a href="<?php echo APP_URL; ?>/admin/reservations.php" class="<?php echo $current_page == 'reservations.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Quản Lý Đặt Bàn
                    </a>
                </li>
                <li>
                    <a href="<?php echo APP_URL; ?>/admin/reservation_form_new.php" class="<?php echo $current_page == 'reservation_form_new.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i> Tạo Đơn Đặt Bàn
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>
        
        <?php if ($isManager): ?>
        <!-- MANAGER ONLY SECTIONS -->
        
        <li class="menu-divider">
            <span>QUẢN LÝ</span>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/admin/users.php" class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Người Dùng</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/products.php" class="<?php echo in_array($current_page, ['products.php', 'product_form.php']) ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                <span>Sản Phẩm</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/orders.php" class="<?php echo in_array($current_page, ['orders.php', 'order_details.php']) ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Đơn Hàng</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/categories.php" class="<?php echo in_array($current_page, ['categories.php', 'category_form.php']) ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Danh Mục</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Báo Cáo</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/ai_insights.php" class="<?php echo ($current_page == 'ai_insights.php') ? 'active' : ''; ?>">
                <i class="fas fa-brain"></i>
                <span>Phân Tích AI</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/inventory.php" class="<?php echo (in_array($current_page, ['inventory.php', 'inventory_form.php', 'inventory_logs.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-warehouse"></i>
                <span>Quản Lý Kho</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/promotions.php" class="<?php echo (in_array($current_page, ['promotions.php', 'promotion_form.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i>
                <span>Khuyến Mãi</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/deliveries.php" class="<?php echo (in_array($current_page, ['deliveries.php', 'shippers.php', 'shipper_form.php', 'delivery_tracking.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-shipping-fast"></i>
                <span>Giao Hàng</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/shippers.php" class="<?php echo (in_array($current_page, ['shippers.php', 'shipper_form.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-motorcycle"></i>
                <span>Quản Lý Shipper</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/recommendation_analytics.php" class="<?php echo ($current_page == 'recommendation_analytics.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Phân Tích AI</span>
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>/admin/settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Cài Đặt</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</aside>