<?php 
// Include config and functions first
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/Delivery.php';
require_once __DIR__ . '/../classes/Order.php';

// Check authentication
if (!is_logged_in()) {
    redirect(APP_URL . '/admin/index.php');
    exit;
}

$delivery = new Delivery();
$orderClass = new Order();

// Handle form submission BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Show POST data
    // echo '<pre>POST Data: '; print_r($_POST); echo '</pre>';
    
    try {
        // Validate required fields
        if (empty($_POST['customer_name']) || empty($_POST['customer_phone']) || 
            empty($_POST['delivery_address']) || empty($_POST['distance']) || 
            empty($_POST['delivery_fee']) || empty($_POST['payment_method'])) {
            set_flash_message('error', 'Vui lòng điền đầy đủ thông tin bắt buộc!');
        } else {
            // Handle order_id - convert empty string to NULL
            $order_id = !empty($_POST['order_id']) ? intval($_POST['order_id']) : null;
            
            $data = [
                'order_id' => $order_id,
                'customer_name' => trim($_POST['customer_name']),
                'customer_phone' => trim($_POST['customer_phone']),
                'delivery_address' => trim($_POST['delivery_address']),
                'delivery_notes' => trim($_POST['delivery_notes'] ?? ''),
                'distance' => floatval($_POST['distance']),
                'delivery_fee' => floatval($_POST['delivery_fee']),
                'payment_method' => $_POST['payment_method'],
                'cod_amount' => in_array($_POST['payment_method'], ['cash', 'cod']) ? floatval($_POST['cod_amount'] ?? 0) : 0,
                'pickup_location' => trim($_POST['pickup_location'] ?? 'Hiniu Coffee - Cửa hàng chính'),
                'estimated_delivery_time' => !empty($_POST['estimated_delivery_time']) ? $_POST['estimated_delivery_time'] : null,
                'created_by' => $_SESSION['user_id'] ?? null
            ];
            
            // Debug: Show data to be inserted
            // echo '<pre>Data to insert: '; print_r($data); echo '</pre>'; exit;
            
            $delivery_id = $delivery->createDelivery($data);
            
            if ($delivery_id) {
                // Auto assign if shipper selected
                if (!empty($_POST['shipper_id'])) {
                    $delivery->assignDelivery($delivery_id, $_POST['shipper_id'], $_SESSION['user_id'] ?? null);
                }
                
                set_flash_message('success', 'Tạo đơn giao hàng thành công! #' . $delivery_id);
                redirect(APP_URL . '/admin/deliveries.php');
                exit;
            } else {
                set_flash_message('error', 'Không thể tạo đơn giao hàng. Vui lòng kiểm tra lại thông tin.');
            }
        }
    } catch (PDOException $e) {
        // Show detailed SQL error
        set_flash_message('error', 'Lỗi database: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
        error_log('Delivery creation error: ' . $e->getMessage());
    } catch (Exception $e) {
        set_flash_message('error', 'Lỗi: ' . $e->getMessage());
        error_log('Delivery creation error: ' . $e->getMessage());
    }
}

// Get data for display
$available_shippers = $delivery->getAvailableShippers();
$pending_orders = $orderClass->getOrdersWithoutDelivery();

// Now include header after form processing
$page_title = 'Tạo Đơn Giao Hàng';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>📦 Tạo Đơn Giao Hàng</h1>
        <a href="deliveries.php" class="btn btn-secondary">← Quay Lại</a>
    </div>
    <p>Tạo đơn giao hàng mới và phân công shipper.</p>
</div>

<?php display_flash_message(); ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Thông Tin Giao Hàng</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="deliveryForm">
                    <!-- Order Selection -->
                    <div class="form-group">
                        <label for="order_id">Đơn Hàng (Tùy chọn)</label>
                        <select name="order_id" id="order_id" class="form-control">
                            <option value="">-- Không liên kết đơn hàng --</option>
                            <?php if (empty($pending_orders)): ?>
                                <option value="" disabled>Không có đơn hàng nào chưa giao</option>
                            <?php else: ?>
                                <?php foreach ($pending_orders as $order): ?>
                                <option value="<?php echo $order['order_id']; ?>" 
                                        data-customer="<?php echo htmlspecialchars($order['customer_name'] ?? ''); ?>"
                                        data-phone="<?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?>"
                                        data-address="<?php echo htmlspecialchars($order['customer_address'] ?? ''); ?>"
                                        data-total="<?php echo $order['total_amount'] ?? 0; ?>">
                                    #<?php echo $order['order_id']; ?> 
                                    <?php 
                                    // Show order type icon
                                    $type_icon = '';
                                    switch($order['order_type'] ?? '') {
                                        case 'delivery': $type_icon = '🚚'; break;
                                        case 'dine-in': $type_icon = '🍽️'; break;
                                        case 'takeaway': $type_icon = '🥡'; break;
                                        default: $type_icon = '📦';
                                    }
                                    echo $type_icon;
                                    ?>
                                    <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?> 
                                    (<?php echo number_format($order['total_amount'] ?? 0, 0, ',', '.'); ?>đ)
                                    - <?php echo ucfirst($order['order_status'] ?? 'pending'); ?>
                                    <?php if (!empty($order['customer_address'])): ?>
                                        - <?php echo htmlspecialchars(substr($order['customer_address'], 0, 25)); ?>...
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Chọn đơn hàng để tự động điền thông tin khách hàng
                            <?php if (!empty($pending_orders)): ?>
                                (Có <?php echo count($pending_orders); ?> đơn chưa giao)
                            <?php endif; ?>
                        </small>
                    </div>

                    <hr>

                    <!-- Customer Information -->
                    <h6 class="mb-3">Thông Tin Khách Hàng</h6>
                    
                    <div class="form-group">
                        <label for="customer_name">Tên Khách Hàng <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_phone">Số Điện Thoại <span class="text-danger">*</span></label>
                        <input type="tel" name="customer_phone" id="customer_phone" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="delivery_address">Địa Chỉ Giao Hàng <span class="text-danger">*</span></label>
                        <textarea name="delivery_address" id="delivery_address" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="delivery_notes">Ghi Chú</label>
                        <textarea name="delivery_notes" id="delivery_notes" class="form-control" rows="2" 
                                  placeholder="Ghi chú đặc biệt cho shipper..."></textarea>
                    </div>

                    <hr>

                    <!-- Delivery Details -->
                    <h6 class="mb-3">Chi Tiết Giao Hàng</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pickup_location">Điểm Lấy Hàng</label>
                                <input type="text" name="pickup_location" id="pickup_location" 
                                       class="form-control" value="Hiniu Coffee - Cửa hàng chính">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="distance">Khoảng Cách (km) <span class="text-danger">*</span></label>
                                <input type="number" name="distance" id="distance" class="form-control" 
                                       step="0.1" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="delivery_fee">Phí Giao Hàng (đ) <span class="text-danger">*</span></label>
                                <input type="number" name="delivery_fee" id="delivery_fee" class="form-control" 
                                       min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estimated_delivery_time">Thời Gian Dự Kiến</label>
                                <input type="datetime-local" name="estimated_delivery_time" id="estimated_delivery_time" 
                                       class="form-control">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Payment Information -->
                    <h6 class="mb-3">Thanh Toán</h6>

                    <div class="form-group">
                        <label for="payment_method">Phương Thức Thanh Toán <span class="text-danger">*</span></label>
                        <select name="payment_method" id="payment_method" class="form-control" required>
                            <option value="cash">Tiền Mặt (COD)</option>
                            <option value="momo">Momo</option>
                            <option value="zalopay">ZaloPay</option>
                            <option value="vnpay">VNPay</option>
                            <option value="bank_transfer">Chuyển Khoản</option>
                        </select>
                    </div>

                    <div class="form-group" id="codAmountGroup" style="display: block;">
                        <label for="cod_amount">Số Tiền Thu Hộ (đ)</label>
                        <input type="number" name="cod_amount" id="cod_amount" class="form-control" min="0" value="0">
                        <small class="form-text text-muted">Số tiền shipper cần thu từ khách hàng (nếu thanh toán tiền mặt)</small>
                    </div>

                    <hr>

                    <!-- Shipper Assignment -->
                    <h6 class="mb-3">Phân Công Shipper (Tùy chọn)</h6>

                    <div class="form-group">
                        <label for="shipper_id">Chọn Shipper</label>
                        <select name="shipper_id" id="shipper_id" class="form-control">
                            <option value="">-- Phân công sau --</option>
                            <?php foreach ($available_shippers as $shipper): ?>
                            <option value="<?php echo $shipper['shipper_id']; ?>">
                                <?php echo htmlspecialchars($shipper['full_name']); ?> 
                                (<?php echo $shipper['vehicle_type']; ?>) - 
                                <?php echo $shipper['current_orders']; ?>/<?php echo $shipper['max_orders']; ?> đơn, 
                                ⭐ <?php echo number_format($shipper['rating'], 2); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Có thể phân công shipper sau khi tạo đơn</small>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> Tạo Đơn Giao Hàng
                        </button>
                        <a href="deliveries.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-md-4">
        <!-- Delivery Fee Calculator -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">💰 Tính Phí Giao Hàng</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Khoảng Cách (km)</label>
                    <input type="number" id="calcDistance" class="form-control" step="0.1" min="0">
                </div>
                <button type="button" class="btn btn-info btn-block" onclick="calculateFee()">
                    Tính Phí
                </button>
                <div id="feeResult" class="mt-3"></div>
                
                <hr>
                
                <small class="text-muted">
                    <strong>Bảng Giá:</strong><br>
                    • 0-3 km: 15,000đ<br>
                    • 3-5 km: 20,000đ<br>
                    • 5-10 km: 30,000đ<br>
                    • >10 km: 40,000đ + 5,000đ/km
                </small>
            </div>
        </div>

        <!-- Available Shippers -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">🛵 Shipper Sẵn Sàng (<?php echo count($available_shippers); ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($available_shippers)): ?>
                    <p class="text-muted">Không có shipper sẵn sàng</p>
                <?php else: ?>
                    <?php foreach ($available_shippers as $shipper): ?>
                    <div class="shipper-info mb-2">
                        <strong><?php echo htmlspecialchars($shipper['full_name']); ?></strong><br>
                        <small>
                            📞 <?php echo $shipper['phone']; ?><br>
                            🚗 <?php echo $shipper['vehicle_type']; ?><br>
                            📊 <?php echo $shipper['current_orders']; ?>/<?php echo $shipper['max_orders']; ?> đơn<br>
                            ⭐ <?php echo number_format($shipper['rating'], 2); ?>
                        </small>
                        <hr>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-fill customer info when order selected
document.getElementById('order_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    
    if (this.value) {
        // Get data from selected option
        const customerName = selectedOption.dataset.customer || '';
        const customerPhone = selectedOption.dataset.phone || '';
        const customerAddress = selectedOption.dataset.address || '';
        const totalAmount = selectedOption.dataset.total || 0;
        
        // Fill form fields
        document.getElementById('customer_name').value = customerName;
        document.getElementById('customer_phone').value = customerPhone;
        document.getElementById('delivery_address').value = customerAddress;
        
        // Add visual feedback
        const fields = ['customer_name', 'customer_phone', 'delivery_address'];
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field.value) {
                field.style.backgroundColor = '#e8f5e9';
                setTimeout(() => {
                    field.style.backgroundColor = '';
                }, 1000);
            }
        });
        
        // Auto-fill COD amount if payment method is cash
        if (document.getElementById('payment_method').value === 'cash') {
            document.getElementById('cod_amount').value = totalAmount;
        }
        
        // Show success message
        console.log('✅ Auto-filled from Order #' + this.value);
    } else {
        // Clear fields
        document.getElementById('customer_name').value = '';
        document.getElementById('customer_phone').value = '';
        document.getElementById('delivery_address').value = '';
        document.getElementById('cod_amount').value = 0;
        
        console.log('🔄 Cleared form fields');
    }
});

// Show/hide COD amount field
document.getElementById('payment_method').addEventListener('change', function() {
    const codGroup = document.getElementById('codAmountGroup');
    const codAmount = document.getElementById('cod_amount');
    
    if (this.value === 'cash') {
        codGroup.style.display = 'block';
        
        // Auto-fill from order if selected
        const orderSelect = document.getElementById('order_id');
        const selectedOption = orderSelect.options[orderSelect.selectedIndex];
        if (orderSelect.value && selectedOption.dataset.total) {
            codAmount.value = selectedOption.dataset.total;
        }
    } else {
        codGroup.style.display = 'none';
        codAmount.value = 0;
    }
});

// Calculate delivery fee
function calculateFee() {
    const distance = parseFloat(document.getElementById('calcDistance').value);
    
    if (!distance || distance < 0) {
        document.getElementById('feeResult').innerHTML = 
            '<div class="alert alert-warning">Vui lòng nhập khoảng cách hợp lệ</div>';
        return;
    }
    
    let fee = 0;
    
    if (distance <= 3) {
        fee = 15000;
    } else if (distance <= 5) {
        fee = 20000;
    } else if (distance <= 10) {
        fee = 30000;
    } else {
        fee = 40000 + ((distance - 10) * 5000);
    }
    
    // Auto-fill the form
    document.getElementById('distance').value = distance;
    document.getElementById('delivery_fee').value = fee;
    
    document.getElementById('feeResult').innerHTML = 
        '<div class="alert alert-success">' +
        '<strong>Phí giao hàng:</strong><br>' +
        '<h4 class="mb-0">' + fee.toLocaleString('vi-VN') + 'đ</h4>' +
        '<small>Đã tự động điền vào form</small>' +
        '</div>';
}

// Set default estimated delivery time (current time + 30 minutes)
window.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    now.setMinutes(now.getMinutes() + 30);
    const dateTimeStr = now.toISOString().slice(0, 16);
    document.getElementById('estimated_delivery_time').value = dateTimeStr;
});

// Form validation
document.getElementById('deliveryForm').addEventListener('submit', function(e) {
    const distance = parseFloat(document.getElementById('distance').value);
    const fee = parseFloat(document.getElementById('delivery_fee').value);
    
    if (distance < 0) {
        e.preventDefault();
        alert('Khoảng cách không hợp lệ!');
        return false;
    }
    
    if (fee < 0) {
        e.preventDefault();
        alert('Phí giao hàng không hợp lệ!');
        return false;
    }
    
    if (document.getElementById('payment_method').value === 'cash') {
        const codAmount = parseFloat(document.getElementById('cod_amount').value);
        if (codAmount < 0) {
            e.preventDefault();
            alert('Số tiền COD không hợp lệ!');
            return false;
        }
    }
});
</script>

<style>
.shipper-info {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: #f8f9fa;
}

.form-group label {
    font-weight: 600;
}

.text-danger {
    color: #dc3545;
}

.card-header h6 {
    margin: 0;
}
</style>

<?php include 'includes/footer.php'; ?>
