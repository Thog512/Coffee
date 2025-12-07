<?php 
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// Must be logged in
if (!is_logged_in()) {
    redirect(APP_URL . '/admin/index.php');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('ID đơn hàng không hợp lệ.', 'danger');
    redirect('orders.php');
}

$order_id = (int)$_GET['id'];
$order_manager = new Order();

// Handle status update BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['order_status'];
    if ($order_manager->updateStatus($order_id, $new_status)) {
        set_flash_message('Cập nhật trạng thái thành công!', 'success');
        redirect('order_details.php?id=' . $order_id);
    } else {
        set_flash_message('Lỗi khi cập nhật trạng thái.', 'danger');
    }
}

// Get order data
$order = $order_manager->getById($order_id);
$order_items = $order_manager->getOrderItems($order_id);

if (!$order) {
    set_flash_message('Không tìm thấy đơn hàng.', 'danger');
    redirect('orders.php');
}

$order_status_badge = get_order_status_badge($order['order_status']);

// NOW include header - after all redirects are done
$page_title = 'Chi Tiết Đơn Hàng';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Chi Tiết Đơn Hàng #<?php echo $order_id; ?></h1>
        <a href="orders.php" class="btn btn-secondary">← Quay Lại Danh Sách</a>
    </div>
</div>

<div class="row">
    <!-- Order Items -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Các Sản Phẩm Trong Đơn</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td class="text-end"><?php echo format_currency($item['unit_price']); ?></td>
                                    <td class="text-end"><strong><?php echo format_currency($item['subtotal']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-end">
                    <div class="text-end" style="width: 250px;">
                        <div class="d-flex justify-content-between">
                            <span>Tạm tính:</span>
                            <span><?php echo format_currency($order['subtotal']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Giảm giá:</span>
                            <span>- <?php echo format_currency($order['discount']); ?></span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between h5 mb-0">
                            <strong>Tổng cộng:</strong>
                            <strong><?php echo format_currency($order['total_amount']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông Tin Đơn Hàng</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Trạng thái:</strong>
                        <span class="badge <?php echo $order_status_badge; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Loại đơn:</strong>
                        <span><?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></span>
                    </li>
                    <?php if ($order['table_number']): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Bàn số:</strong>
                        <span><?php echo htmlspecialchars($order['table_number']); ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Khách hàng:</strong>
                        <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Nhân viên:</strong>
                        <span><?php echo htmlspecialchars($order['staff_name'] ?? 'N/A'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Ngày tạo:</strong>
                        <span><?php echo format_date($order['created_at']); ?></span>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="order_status" class="form-label"><strong>Cập nhật trạng thái:</strong></label>
                        <select name="order_status" id="order_status" class="form-select" required>
                            <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Chờ Xử Lý</option>
                            <option value="confirmed" <?php echo $order['order_status'] == 'confirmed' ? 'selected' : ''; ?>>Xác Nhận</option>
                            <option value="preparing" <?php echo $order['order_status'] == 'preparing' ? 'selected' : ''; ?>>Đang Chuẩn Bị</option>
                            <option value="ready" <?php echo $order['order_status'] == 'ready' ? 'selected' : ''; ?>>Sẵn Sàng</option>
                            <option value="delivering" <?php echo $order['order_status'] == 'delivering' ? 'selected' : ''; ?>>Đang Giao</option>
                            <option value="completed" <?php echo $order['order_status'] == 'completed' ? 'selected' : ''; ?>>Hoàn Thành</option>
                            <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Đã Hủy</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Lưu Thay Đổi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>
