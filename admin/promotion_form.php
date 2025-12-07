<?php
// Process form BEFORE any output
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Promotion.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Product.php';

$promotion = new Promotion();
$category = new Category();
$product = new Product();

$categories = $category->getAll();
$products = $product->getAll();

$edit_mode = false;
$promo_data = null;

// Check if editing
if (isset($_GET['id'])) {
    $edit_mode = true;
    $promo_data = $promotion->getById($_GET['id']);
    
    if (!$promo_data) {
        set_flash_message('Không tìm thấy khuyến mãi!', 'error');
        header('Location: promotions.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'promotion_name' => $_POST['promotion_name'],
        'description' => $_POST['description'],
        'promotion_type' => $_POST['promotion_type'],
        'discount_value' => $_POST['discount_value'],
        'min_order_value' => $_POST['min_order_value'] ?? 0,
        'max_discount' => $_POST['max_discount'] ?? null,
        'buy_quantity' => $_POST['buy_quantity'] ?? null,
        'get_quantity' => $_POST['get_quantity'] ?? null,
        'applicable_to' => $_POST['applicable_to'] ?? 'all',
        'applicable_ids' => null,
        'voucher_code' => !empty($_POST['voucher_code']) ? $_POST['voucher_code'] : null,
        'usage_limit' => $_POST['usage_limit'] ?? null,
        'usage_per_customer' => $_POST['usage_per_customer'] ?? 1,
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'start_time' => !empty($_POST['start_time']) ? $_POST['start_time'] : null,
        'end_time' => !empty($_POST['end_time']) ? $_POST['end_time'] : null,
        'applicable_days' => null,
        'payment_methods' => null,
        'customer_type' => $_POST['customer_type'] ?? 'all',
        'status' => $_POST['status'],
        'priority' => $_POST['priority'] ?? 0,
        'created_by' => $_SESSION['user_id']
    ];
    
    // Handle applicable days
    if (isset($_POST['applicable_days']) && !empty($_POST['applicable_days'])) {
        $data['applicable_days'] = json_encode(array_map('intval', $_POST['applicable_days']));
    }
    
    // Handle payment methods
    if (isset($_POST['payment_methods']) && !empty($_POST['payment_methods'])) {
        $data['payment_methods'] = json_encode($_POST['payment_methods']);
    }
    
    // Handle applicable IDs
    if ($data['applicable_to'] != 'all' && isset($_POST['applicable_ids'])) {
        $data['applicable_ids'] = json_encode(array_map('intval', $_POST['applicable_ids']));
    }
    
    try {
        if ($edit_mode) {
            $success = $promotion->update($_GET['id'], $data);
            $message = 'Cập nhật khuyến mãi thành công!';
        } else {
            $success = $promotion->create($data);
            $message = 'Thêm khuyến mãi mới thành công!';
        }
        
        if ($success) {
            set_flash_message($message, 'success');
            header('Location: promotions.php');
            exit;
        } else {
            set_flash_message('Có lỗi xảy ra!', 'error');
        }
    } catch (Exception $e) {
        set_flash_message('Lỗi: ' . $e->getMessage(), 'error');
    }
}

// Include header AFTER POST processing
$page_title = 'Thêm/Sửa Khuyến Mãi';
include 'includes/header.php';
?>

<div class="page-header">
    <h1><?php echo $edit_mode ? '✏️ Sửa Khuyến Mãi' : '➕ Thêm Khuyến Mãi Mới'; ?></h1>
    <p>Điền thông tin chi tiết về chương trình khuyến mãi.</p>
</div>

<?php display_flash_message(); ?>

<div class="card">
    <div class="card-body">
        <form method="POST" id="promotionForm">
            <!-- Basic Information -->
            <h5 class="mb-3">📋 Thông Tin Cơ Bản</h5>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Tên Khuyến Mãi <span class="text-danger">*</span></label>
                        <input type="text" name="promotion_name" class="form-control" 
                               value="<?php echo $promo_data['promotion_name'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Trạng Thái</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo ($promo_data['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                            <option value="inactive" <?php echo ($promo_data['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Tạm dừng</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Mô Tả</label>
                <textarea name="description" class="form-control" rows="3"><?php echo $promo_data['description'] ?? ''; ?></textarea>
            </div>
            
            <!-- Promotion Type & Value -->
            <h5 class="mb-3 mt-4">💰 Loại & Giá Trị Khuyến Mãi</h5>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Loại Khuyến Mãi <span class="text-danger">*</span></label>
                        <select name="promotion_type" id="promotionType" class="form-control" required onchange="updateTypeFields()">
                            <option value="percentage" <?php echo ($promo_data['promotion_type'] ?? '') == 'percentage' ? 'selected' : ''; ?>>Giảm theo % (Percentage)</option>
                            <option value="fixed_amount" <?php echo ($promo_data['promotion_type'] ?? '') == 'fixed_amount' ? 'selected' : ''; ?>>Giảm cố định (Fixed Amount)</option>
                            <option value="buy_x_get_y" <?php echo ($promo_data['promotion_type'] ?? '') == 'buy_x_get_y' ? 'selected' : ''; ?>>Mua X Tặng Y</option>
                            <option value="combo" <?php echo ($promo_data['promotion_type'] ?? '') == 'combo' ? 'selected' : ''; ?>>Combo</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Giá Trị <span class="text-danger">*</span></label>
                        <input type="number" name="discount_value" id="discountValue" class="form-control" 
                               value="<?php echo $promo_data['discount_value'] ?? ''; ?>" 
                               step="0.01" required>
                        <small class="form-text text-muted" id="valueHint">Nhập % (0-100) hoặc số tiền</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Ưu Tiên</label>
                        <input type="number" name="priority" class="form-control" 
                               value="<?php echo $promo_data['priority'] ?? 0; ?>">
                        <small class="form-text text-muted">Số càng cao = ưu tiên càng cao</small>
                    </div>
                </div>
            </div>
            
            <!-- Buy X Get Y Fields -->
            <div class="row" id="buyXGetYFields" style="display: none;">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Số Lượng Mua (X)</label>
                        <input type="number" name="buy_quantity" class="form-control" 
                               value="<?php echo $promo_data['buy_quantity'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Số Lượng Tặng (Y)</label>
                        <input type="number" name="get_quantity" class="form-control" 
                               value="<?php echo $promo_data['get_quantity'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Conditions -->
            <h5 class="mb-3 mt-4">⚙️ Điều Kiện Áp Dụng</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Giá Trị Đơn Tối Thiểu (đ)</label>
                        <input type="number" name="min_order_value" class="form-control" 
                               value="<?php echo $promo_data['min_order_value'] ?? 0; ?>">
                    </div>
                </div>
                <div class="col-md-6" id="maxDiscountField">
                    <div class="form-group">
                        <label>Giảm Tối Đa (đ)</label>
                        <input type="number" name="max_discount" class="form-control" 
                               value="<?php echo $promo_data['max_discount'] ?? ''; ?>">
                        <small class="form-text text-muted">Chỉ áp dụng cho giảm %</small>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Loại Khách Hàng</label>
                        <select name="customer_type" class="form-control">
                            <option value="all" <?php echo ($promo_data['customer_type'] ?? 'all') == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                            <option value="new" <?php echo ($promo_data['customer_type'] ?? '') == 'new' ? 'selected' : ''; ?>>Khách mới</option>
                            <option value="member" <?php echo ($promo_data['customer_type'] ?? '') == 'member' ? 'selected' : ''; ?>>Thành viên</option>
                            <option value="student" <?php echo ($promo_data['customer_type'] ?? '') == 'student' ? 'selected' : ''; ?>>Sinh viên</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Mã Voucher (Tùy chọn)</label>
                        <input type="text" name="voucher_code" class="form-control" 
                               value="<?php echo $promo_data['voucher_code'] ?? ''; ?>" 
                               placeholder="VD: WELCOME30">
                    </div>
                </div>
            </div>
            
            <!-- Time & Date -->
            <h5 class="mb-3 mt-4">📅 Thời Gian Áp Dụng</h5>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Ngày Bắt Đầu <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo $promo_data['start_date'] ?? date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Ngày Kết Thúc <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo $promo_data['end_date'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Giờ Bắt Đầu (Tùy chọn)</label>
                        <input type="time" name="start_time" class="form-control" 
                               value="<?php echo $promo_data['start_time'] ?? ''; ?>">
                        <small class="form-text text-muted">Cho Happy Hour</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Giờ Kết Thúc (Tùy chọn)</label>
                        <input type="time" name="end_time" class="form-control" 
                               value="<?php echo $promo_data['end_time'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Ngày Trong Tuần</label>
                <div class="checkbox-group">
                    <?php 
                    $days = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật'];
                    $selected_days = $promo_data ? json_decode($promo_data['applicable_days'], true) : null;
                    for ($i = 1; $i <= 7; $i++): 
                        $checked = $selected_days && in_array($i, $selected_days) ? 'checked' : '';
                    ?>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="applicable_days[]" value="<?php echo $i; ?>" <?php echo $checked; ?>>
                        <?php echo $days[$i-1]; ?>
                    </label>
                    <?php endfor; ?>
                </div>
                <small class="form-text text-muted">Để trống = áp dụng tất cả các ngày</small>
            </div>
            
            <!-- Usage Limits -->
            <h5 class="mb-3 mt-4">🎯 Giới Hạn Sử Dụng</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Tổng Số Lần Sử Dụng</label>
                        <input type="number" name="usage_limit" class="form-control" 
                               value="<?php echo $promo_data['usage_limit'] ?? ''; ?>" 
                               placeholder="Để trống = không giới hạn">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Số Lần/Khách Hàng</label>
                        <input type="number" name="usage_per_customer" class="form-control" 
                               value="<?php echo $promo_data['usage_per_customer'] ?? 1; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="form-group">
                <label>Phương Thức Thanh Toán</label>
                <div class="checkbox-group">
                    <?php 
                    $payment_methods = ['cash' => 'Tiền mặt', 'momo' => 'MoMo', 'zalopay' => 'ZaloPay', 'vnpay' => 'VNPay', 'bank_transfer' => 'Chuyển khoản'];
                    $selected_payments = $promo_data ? json_decode($promo_data['payment_methods'], true) : null;
                    foreach ($payment_methods as $key => $label): 
                        $checked = $selected_payments && in_array($key, $selected_payments) ? 'checked' : '';
                    ?>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="payment_methods[]" value="<?php echo $key; ?>" <?php echo $checked; ?>>
                        <?php echo $label; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <small class="form-text text-muted">Để trống = áp dụng tất cả phương thức</small>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_mode ? '💾 Cập Nhật' : '➕ Tạo Khuyến Mãi'; ?>
                </button>
                <a href="promotions.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateTypeFields() {
    const type = document.getElementById('promotionType').value;
    const buyXGetYFields = document.getElementById('buyXGetYFields');
    const maxDiscountField = document.getElementById('maxDiscountField');
    const valueHint = document.getElementById('valueHint');
    
    if (type === 'buy_x_get_y') {
        buyXGetYFields.style.display = 'flex';
        maxDiscountField.style.display = 'none';
        valueHint.textContent = 'Giá trị món tặng (%)';
    } else if (type === 'percentage') {
        buyXGetYFields.style.display = 'none';
        maxDiscountField.style.display = 'block';
        valueHint.textContent = 'Nhập % (0-100)';
    } else {
        buyXGetYFields.style.display = 'none';
        maxDiscountField.style.display = 'none';
        valueHint.textContent = 'Nhập số tiền (VNĐ)';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateTypeFields();
});
</script>

<style>
.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}
.checkbox-inline {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 0;
}
</style>

<?php include 'includes/footer.php'; ?>
