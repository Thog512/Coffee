<?php 
$page_title = 'Trang Chủ';
include 'includes/header.php';

require_once __DIR__ . '/../classes/Statistics.php';

$stats = new Statistics();
$today_revenue = $stats->getTodayRevenue();
$total_revenue = $stats->getTotalRevenue();
$today_orders = $stats->getTodayOrders();
$total_orders = $stats->getTotalOrders();
$total_products = $stats->getTotalProducts();
$total_customers = $stats->getTotalCustomers();
$recent_orders = $stats->getRecentOrders(5);
$top_products = $stats->getTopSellingProducts(5);
?>

<div class="page-header">
    <h1>Trang Chủ</h1>
    <p>Tổng quan hiệu suất quán cà phê của bạn.</p>
</div>

<div class="stats-grid">
    <div class="stat-card pink">
        <div class="icon-container">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-info">
            <h3>Doanh Thu Hôm Nay</h3>
            <p class="stat-number"><?php echo format_currency($today_revenue); ?></p>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="icon-container">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
            <h3>Đơn Hàng Hôm Nay</h3>
            <p class="stat-number"><?php echo $today_orders; ?></p>
        </div>
    </div>
    <div class="stat-card green">
        <div class="icon-container">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <h3>Tổng Sản Phẩm</h3>
            <p class="stat-number"><?php echo $total_products; ?></p>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="icon-container">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info">
            <h3>Tổng Doanh Thu</h3>
            <p class="stat-number"><?php echo format_currency($total_revenue); ?></p>
        </div>
    </div>
</div>

<!-- Recent Orders and Top Products -->
<div class="row mt-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Đơn Hàng Gần Đây</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_orders)):
                                foreach ($recent_orders as $order):
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo format_currency($order['total_amount']); ?></td>
                                    <td>
                                        <?php 
                                            $status_text = $order['order_status'];
                                            if ($status_text == 'pending') $status_text = 'Chờ';
                                            elseif ($status_text == 'confirmed') $status_text = 'Xác Nhận';
                                            elseif ($status_text == 'preparing') $status_text = 'Đang Làm';
                                            elseif ($status_text == 'ready') $status_text = 'Sẵn Sàng';
                                            elseif ($status_text == 'completed') $status_text = 'Xong';
                                            elseif ($status_text == 'cancelled') $status_text = 'Hủy';
                                        ?>
                                        <span class="badge <?php echo get_order_status_badge($order['order_status']); ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td><?php echo format_date($order['created_at'], 'H:i d/m'); ?></td>
                                </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <tr><td colspan="5" class="text-center">Chưa có đơn hàng nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Sản Phẩm Bán Chạy</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($top_products)):
                    foreach ($top_products as $product):
                ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                            <br><small class="text-muted">Đã bán: <?php echo $product['total_sold']; ?></small>
                        </div>
                        <div class="text-end">
                            <strong><?php echo format_currency($product['revenue']); ?></strong>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <p class="text-center">Chưa có dữ liệu.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
