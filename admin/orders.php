<?php 
$page_title = 'Quản Lý Đơn Hàng';
include 'includes/header.php';

require_once __DIR__ . '/../classes/Order.php';

$order_manager = new Order();
$orders = $order_manager->getAll();

?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Quản Lý Đơn Hàng</h1>
        <a href="pos.php" class="btn btn-primary">+ Tạo Đơn Hàng Mới (POS)</a>
    </div>
    <p>Xem và quản lý tất cả các đơn hàng trong hệ thống.</p>
</div>

<?php display_flash_message(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID Đơn</th>
                        <th>Khách Hàng</th>
                        <th>Tổng Tiền</th>
                        <th>Loại Đơn</th>
                        <th>Trạng Thái Đơn</th>
                        <th>Ngày Tạo</th>
                        <th class="text-end">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)):
                        foreach ($orders as $order):
                    ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><strong><?php echo format_currency($order['total_amount']); ?></strong></td>
                                <td>
                                    <?php 
                                        $type_text = $order['order_type'];
                                        if ($type_text == 'dine_in') $type_text = 'Tại Bàn';
                                        elseif ($type_text == 'takeaway') $type_text = 'Mang Đi';
                                        elseif ($type_text == 'delivery') $type_text = 'Giao Hàng';
                                        echo $type_text;
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        $status_text = $order['order_status'];
                                        if ($status_text == 'pending') $status_text = 'Chờ Xử Lý';
                                        elseif ($status_text == 'confirmed') $status_text = 'Xác Nhận';
                                        elseif ($status_text == 'preparing') $status_text = 'Đang Chuẩn Bị';
                                        elseif ($status_text == 'ready') $status_text = 'Sẵn Sàng';
                                        elseif ($status_text == 'completed') $status_text = 'Hoàn Thành';
                                        elseif ($status_text == 'cancelled') $status_text = 'Đã Hủy';
                                    ?>
                                    <span class="badge <?php echo get_order_status_badge($order['order_status']); ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td><?php echo format_date($order['created_at']); ?></td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-eye"></i> Xem
                                        </a>
                                    </div>
                                </td>
                            </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="7" class="text-center">Chưa có đơn hàng nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
