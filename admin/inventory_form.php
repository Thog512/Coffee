<?php
require_once __DIR__ . '/../config/config.php';
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Inventory.php';

// Check authentication
Auth::checkAuth();

$inventory = new Inventory();
$item = null;
$isEdit = false;

// Check if editing
if (isset($_GET['id'])) {
    $isEdit = true;
    $item = $inventory->getById($_GET['id']);
    if (!$item) {
        header('Location: inventory.php?error=not_found');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'item_name' => $_POST['item_name'],
        'item_type' => $_POST['item_type'],
        'quantity' => $_POST['quantity'],
        'unit' => $_POST['unit'],
        'min_quantity' => $_POST['min_quantity'],
        'expiration_date' => !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null,
        'supplier' => $_POST['supplier'],
        'cost_per_unit' => $_POST['cost_per_unit'],
        'status' => $_POST['status'] ?? 'available'
    ];

    if ($isEdit) {
        $success = $inventory->update($_GET['id'], $data);
        $message = $success ? 'Cập nhật thành công!' : 'Có lỗi xảy ra!';
    } else {
        $success = $inventory->create($data);
        $message = $success ? 'Thêm mới thành công!' : 'Có lỗi xảy ra!';
    }

    if ($success) {
        header('Location: inventory.php?success=' . urlencode($message));
        exit;
    }
}

$pageTitle = $isEdit ? "Sửa Nguyên Liệu" : "Thêm Nguyên Liệu";
include 'includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>
            <i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus'; ?>"></i>
            <?php echo $pageTitle; ?>
        </h1>
        <div class="header-actions">
            <a href="inventory.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay Lại
            </a>
        </div>
    </div>

    <div class="form-container">
        <form method="POST" class="inventory-form">
            <div class="form-grid">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Thông Tin Cơ Bản</h3>
                    
                    <div class="form-group">
                        <label for="item_name">Tên Nguyên Liệu <span class="required">*</span></label>
                        <input type="text" 
                               id="item_name" 
                               name="item_name" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="item_type">Loại <span class="required">*</span></label>
                        <select id="item_type" name="item_type" class="form-control" required>
                            <option value="">-- Chọn loại --</option>
                            <option value="ingredient" <?php echo ($item['item_type'] ?? '') === 'ingredient' ? 'selected' : ''; ?>>
                                Nguyên liệu
                            </option>
                            <option value="supply" <?php echo ($item['item_type'] ?? '') === 'supply' ? 'selected' : ''; ?>>
                                Vật tư
                            </option>
                            <option value="packaging" <?php echo ($item['item_type'] ?? '') === 'packaging' ? 'selected' : ''; ?>>
                                Bao bì
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="supplier">Nhà Cung Cấp</label>
                        <input type="text" 
                               id="supplier" 
                               name="supplier" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($item['supplier'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Quantity Information -->
                <div class="form-section">
                    <h3><i class="fas fa-boxes"></i> Số Lượng</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">Số Lượng Hiện Tại <span class="required">*</span></label>
                            <input type="number" 
                                   id="quantity" 
                                   name="quantity" 
                                   class="form-control"
                                   step="0.01"
                                   value="<?php echo $item['quantity'] ?? '0'; ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="unit">Đơn Vị <span class="required">*</span></label>
                            <input type="text" 
                                   id="unit" 
                                   name="unit" 
                                   class="form-control"
                                   placeholder="kg, lít, gói..."
                                   value="<?php echo htmlspecialchars($item['unit'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="min_quantity">Tồn Kho Tối Thiểu <span class="required">*</span></label>
                        <input type="number" 
                               id="min_quantity" 
                               name="min_quantity" 
                               class="form-control"
                               step="0.01"
                               value="<?php echo $item['min_quantity'] ?? '0'; ?>"
                               required>
                        <small class="form-text">Cảnh báo khi số lượng <= giá trị này</small>
                    </div>

                    <div class="form-group">
                        <label for="cost_per_unit">Giá/Đơn Vị (VNĐ) <span class="required">*</span></label>
                        <input type="number" 
                               id="cost_per_unit" 
                               name="cost_per_unit" 
                               class="form-control"
                               step="1"
                               value="<?php echo $item['cost_per_unit'] ?? '0'; ?>"
                               required>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-alt"></i> Thông Tin Bổ Sung</h3>
                    
                    <div class="form-group">
                        <label for="expiration_date">Hạn Sử Dụng</label>
                        <input type="date" 
                               id="expiration_date" 
                               name="expiration_date" 
                               class="form-control"
                               value="<?php echo $item['expiration_date'] ?? ''; ?>">
                        <small class="form-text">Để trống nếu không có hạn sử dụng</small>
                    </div>

                    <?php if ($isEdit): ?>
                    <div class="form-group">
                        <label for="status">Trạng Thái</label>
                        <select id="status" name="status" class="form-control">
                            <option value="available" <?php echo ($item['status'] ?? '') === 'available' ? 'selected' : ''; ?>>
                                Còn hàng
                            </option>
                            <option value="low_stock" <?php echo ($item['status'] ?? '') === 'low_stock' ? 'selected' : ''; ?>>
                                Sắp hết
                            </option>
                            <option value="out_of_stock" <?php echo ($item['status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>>
                                Hết hàng
                            </option>
                            <option value="expired" <?php echo ($item['status'] ?? '') === 'expired' ? 'selected' : ''; ?>>
                                Hết hạn
                            </option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Lưu ý:</strong>
                            <ul>
                                <li>Trạng thái sẽ tự động cập nhật dựa trên số lượng và hạn sử dụng</li>
                                <li>Cảnh báo sẽ hiển thị khi tồn kho <= tồn kho tối thiểu</li>
                                <li>Cảnh báo hết hạn sẽ hiển thị 7 ngày trước ngày hết hạn</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="preview-section">
                <h3><i class="fas fa-eye"></i> Xem Trước</h3>
                <div class="preview-grid">
                    <div class="preview-item">
                        <label>Tổng Giá Trị:</label>
                        <span id="total_value" class="preview-value">0 đ</span>
                    </div>
                    <div class="preview-item">
                        <label>Trạng Thái Dự Kiến:</label>
                        <span id="predicted_status" class="preview-value">
                            <span class="badge badge-success">Còn hàng</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="inventory.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 
                    <?php echo $isEdit ? 'Cập Nhật' : 'Thêm Mới'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.form-container {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.inventory-form {
    max-width: 1200px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.form-section h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 18px;
    padding-bottom: 10px;
    border-bottom: 2px solid #667eea;
}

.form-section h3 i {
    color: #667eea;
    margin-right: 8px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.required {
    color: #dc3545;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.info-box {
    display: flex;
    gap: 12px;
    padding: 15px;
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    border-radius: 4px;
    margin-top: 20px;
}

.info-box i {
    color: #2196f3;
    font-size: 20px;
}

.info-box ul {
    margin: 5px 0 0 0;
    padding-left: 20px;
}

.info-box li {
    font-size: 13px;
    color: #555;
    margin-bottom: 5px;
}

.preview-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    border-radius: 8px;
    color: white;
    margin-bottom: 30px;
}

.preview-section h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.preview-item {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 6px;
}

.preview-item label {
    display: block;
    font-size: 13px;
    margin-bottom: 8px;
    opacity: 0.9;
}

.preview-value {
    font-size: 20px;
    font-weight: bold;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}
</style>

<script>
// Calculate total value and predict status
function updatePreview() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const costPerUnit = parseFloat(document.getElementById('cost_per_unit').value) || 0;
    const minQuantity = parseFloat(document.getElementById('min_quantity').value) || 0;
    const expirationDate = document.getElementById('expiration_date').value;
    
    // Calculate total value
    const totalValue = quantity * costPerUnit;
    document.getElementById('total_value').textContent = 
        new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(totalValue);
    
    // Predict status
    let status = 'available';
    let statusClass = 'badge-success';
    let statusText = 'Còn hàng';
    
    if (expirationDate) {
        const expDate = new Date(expirationDate);
        const today = new Date();
        const daysLeft = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));
        
        if (daysLeft < 0) {
            status = 'expired';
            statusClass = 'badge-dark';
            statusText = 'Hết hạn';
        } else if (daysLeft <= 7) {
            status = 'expiring_soon';
            statusClass = 'badge-warning';
            statusText = 'Sắp hết hạn (' + daysLeft + ' ngày)';
        }
    }
    
    if (quantity <= 0) {
        status = 'out_of_stock';
        statusClass = 'badge-danger';
        statusText = 'Hết hàng';
    } else if (quantity <= minQuantity) {
        if (status !== 'expired' && status !== 'expiring_soon') {
            status = 'low_stock';
            statusClass = 'badge-warning';
            statusText = 'Sắp hết';
        }
    }
    
    document.getElementById('predicted_status').innerHTML = 
        '<span class="badge ' + statusClass + '">' + statusText + '</span>';
}

// Add event listeners
document.getElementById('quantity').addEventListener('input', updatePreview);
document.getElementById('cost_per_unit').addEventListener('input', updatePreview);
document.getElementById('min_quantity').addEventListener('input', updatePreview);
document.getElementById('expiration_date').addEventListener('change', updatePreview);

// Initial preview
updatePreview();

// Form validation
document.querySelector('.inventory-form').addEventListener('submit', function(e) {
    const quantity = parseFloat(document.getElementById('quantity').value);
    const minQuantity = parseFloat(document.getElementById('min_quantity').value);
    
    if (quantity < 0) {
        e.preventDefault();
        alert('Số lượng không thể âm!');
        return false;
    }
    
    if (minQuantity < 0) {
        e.preventDefault();
        alert('Tồn kho tối thiểu không thể âm!');
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
