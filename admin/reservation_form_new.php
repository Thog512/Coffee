<?php
// Process form submission BEFORE any output
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Reservation.php';
require_once __DIR__ . '/../classes/Table.php';

// Manager only
Auth::requireManager();

$reservation_manager = new Reservation();
$table_manager = new Table();

$edit_mode = false;
$reservation_data = null;

// Check if editing existing reservation
if (isset($_GET['id'])) {
    $edit_mode = true;
    $reservation_id = (int)$_GET['id'];
    $reservation_data = $reservation_manager->getById($reservation_id);
    
    if (!$reservation_data) {
        set_flash_message('Không tìm thấy đơn đặt bàn!', 'danger');
        redirect('reservations.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'table_id' => !empty($_POST['table_id']) ? (int)$_POST['table_id'] : null,
        'customer_name' => trim($_POST['customer_name']),
        'customer_phone' => trim($_POST['customer_phone']),
        'guest_count' => (int)$_POST['guest_count'],
        'reservation_date' => $_POST['reservation_date'],
        'reservation_time' => $_POST['reservation_time'],
        'status' => $_POST['status'] ?? 'pending',
        'special_requests' => trim($_POST['special_requests'] ?? '')
    ];
    
    // Validation
    if (empty($data['customer_name']) || empty($data['customer_phone']) || empty($data['reservation_date']) || empty($data['reservation_time'])) {
        set_flash_message('Vui lòng điền đầy đủ các trường bắt buộc.', 'danger');
    } else {
        // Check availability if table is selected
        if ($data['table_id']) {
            $exclude_id = $edit_mode ? $reservation_id : null;
            $is_available = $reservation_manager->checkAvailability(
                $data['table_id'],
                $data['reservation_date'],
                $data['reservation_time'],
                2,
                $exclude_id
            );
            
            if (!$is_available) {
                set_flash_message('Bàn đã chọn không còn trống trong khung giờ này. Vui lòng chọn bàn khác.', 'danger');
                $data = null; // Don't proceed
            }
        }
        
        if ($data) {
            if ($edit_mode) {
                if ($reservation_manager->update($reservation_id, $data)) {
                    set_flash_message('Cập nhật đơn đặt bàn thành công!', 'success');
                    redirect('reservations.php');
                    exit;
                } else {
                    set_flash_message('Lỗi khi cập nhật đơn đặt bàn!', 'danger');
                }
            } else {
                if ($reservation_manager->create($data)) {
                    set_flash_message('Tạo đơn đặt bàn mới thành công!', 'success');
                    redirect('reservations.php');
                    exit;
                } else {
                    set_flash_message('Lỗi khi tạo đơn đặt bàn!', 'danger');
                }
            }
        }
    }
}

// Now include header after POST processing
$page_title = 'Quản Lý Đặt Bàn';
include 'includes/header.php';

// Get initial available tables if editing
$initial_available_tables = [];
if ($edit_mode && $reservation_data) {
    $initial_available_tables = $reservation_manager->getAvailableTables(
        $reservation_data['reservation_date'],
        $reservation_data['reservation_time'],
        $reservation_data['guest_count']
    );
    // Add current table if it exists
    if ($reservation_data['table_id']) {
        $current_table = $table_manager->getById($reservation_data['table_id']);
        if ($current_table) {
            $initial_available_tables[] = $current_table;
        }
    }
}
?>

<style>
.availability-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    margin: 20px 0;
    display: none;
}

.availability-section.show {
    display: block;
}

.table-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.table-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.table-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.table-card.selected {
    border-color: #667eea;
    background: #f8f9ff;
}

.table-card.unavailable {
    opacity: 0.5;
    cursor: not-allowed;
    background: #f5f5f5;
}

.table-card .table-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.table-card .table-info {
    font-size: 0.9rem;
    color: #6c757d;
}

.table-card .table-status {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.table-card .table-status.available {
    background: #28a745;
}

.table-card .table-status.occupied {
    background: #dc3545;
}

.check-availability-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.check-availability-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.check-availability-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.availability-loading {
    text-align: center;
    padding: 20px;
}

.availability-result {
    margin-top: 15px;
}

.result-message {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: 500;
}

.result-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.result-message.warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.result-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><?php echo $edit_mode ? 'Chỉnh Sửa Đặt Bàn' : 'Tạo Đơn Đặt Bàn Mới'; ?></h1>
        <a href="reservations.php" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" id="reservationForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="customer_name" class="form-label">Tên Khách Hàng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" 
                               value="<?php echo $edit_mode ? htmlspecialchars($reservation_data['customer_name']) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="customer_phone" class="form-label">Số Điện Thoại <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="customer_phone" name="customer_phone" 
                               value="<?php echo $edit_mode ? htmlspecialchars($reservation_data['customer_phone']) : ''; ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="reservation_date" class="form-label">Ngày Đặt <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="reservation_date" name="reservation_date" 
                               value="<?php echo $edit_mode ? $reservation_data['reservation_date'] : date('Y-m-d'); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="reservation_time" class="form-label">Giờ Đặt <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="reservation_time" name="reservation_time" 
                               value="<?php echo $edit_mode ? $reservation_data['reservation_time'] : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="guest_count" class="form-label">Số Lượng Khách <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="guest_count" name="guest_count" 
                               value="<?php echo $edit_mode ? $reservation_data['guest_count'] : '2'; ?>" min="1" max="20" required>
                    </div>
                </div>
            </div>

            <!-- Check Availability Button -->
            <div class="text-center mb-3">
                <button type="button" class="check-availability-btn" id="checkAvailabilityBtn">
                    <i class="fas fa-search"></i> Kiểm Tra Bàn Trống
                </button>
            </div>

            <!-- Availability Section -->
            <div class="availability-section" id="availabilitySection">
                <div class="availability-loading" id="availabilityLoading">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Đang kiểm tra tình trạng bàn...</p>
                </div>
                
                <div class="availability-result" id="availabilityResult" style="display: none;">
                    <div class="result-message" id="resultMessage"></div>
                    <div class="table-grid" id="tableGrid"></div>
                </div>
            </div>

            <!-- Hidden input for selected table -->
            <input type="hidden" id="table_id" name="table_id" value="<?php echo $edit_mode && $reservation_data['table_id'] ? $reservation_data['table_id'] : ''; ?>">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="status" class="form-label">Trạng Thái</label>
                        <select class="form-control" id="status" name="status">
                            <option value="pending" <?php echo ($edit_mode && $reservation_data['status'] == 'pending') ? 'selected' : ''; ?>>Chờ xác nhận</option>
                            <option value="confirmed" <?php echo ($edit_mode && $reservation_data['status'] == 'confirmed') ? 'selected' : ''; ?>>Đã xác nhận</option>
                            <option value="arrived" <?php echo ($edit_mode && $reservation_data['status'] == 'arrived') ? 'selected' : ''; ?>>Đã đến</option>
                            <option value="completed" <?php echo ($edit_mode && $reservation_data['status'] == 'completed') ? 'selected' : ''; ?>>Hoàn thành</option>
                            <option value="cancelled" <?php echo ($edit_mode && $reservation_data['status'] == 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                            <option value="no_show" <?php echo ($edit_mode && $reservation_data['status'] == 'no_show') ? 'selected' : ''; ?>>Không đến</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="selected_table_display" class="form-label">Bàn Đã Chọn</label>
                        <input type="text" class="form-control" id="selected_table_display" readonly 
                               placeholder="Chưa chọn bàn - Click 'Kiểm Tra Bàn Trống' để chọn">
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="special_requests" class="form-label">Ghi Chú / Yêu Cầu Đặc Biệt</label>
                <textarea class="form-control" id="special_requests" name="special_requests" rows="3"><?php echo $edit_mode ? htmlspecialchars($reservation_data['special_requests'] ?? '') : ''; ?></textarea>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> <?php echo $edit_mode ? 'Cập Nhật' : 'Tạo Đơn'; ?>
                </button>
                <a href="reservations.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkBtn = document.getElementById('checkAvailabilityBtn');
    const availabilitySection = document.getElementById('availabilitySection');
    const loadingDiv = document.getElementById('availabilityLoading');
    const resultDiv = document.getElementById('availabilityResult');
    const resultMessage = document.getElementById('resultMessage');
    const tableGrid = document.getElementById('tableGrid');
    const tableIdInput = document.getElementById('table_id');
    const tableDisplay = document.getElementById('selected_table_display');
    
    const dateInput = document.getElementById('reservation_date');
    const timeInput = document.getElementById('reservation_time');
    const guestInput = document.getElementById('guest_count');
    
    // Check availability
    checkBtn.addEventListener('click', function() {
        const date = dateInput.value;
        const time = timeInput.value;
        const guests = guestInput.value;
        
        if (!date || !time || !guests) {
            alert('Vui lòng điền đầy đủ ngày, giờ và số lượng khách trước khi kiểm tra.');
            return;
        }
        
        // Show loading
        availabilitySection.classList.add('show');
        loadingDiv.style.display = 'block';
        resultDiv.style.display = 'none';
        checkBtn.disabled = true;
        
        // Fetch available tables
        fetch(`../api/check_table_availability.php?date=${date}&time=${time}&guests=${guests}`)
            .then(response => response.json())
            .then(data => {
                loadingDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                checkBtn.disabled = false;
                
                if (data.success) {
                    // Display message
                    if (data.available_count > 0) {
                        resultMessage.className = 'result-message success';
                        resultMessage.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
                    } else {
                        resultMessage.className = 'result-message warning';
                        resultMessage.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${data.message}`;
                    }
                    
                    // Display available tables
                    tableGrid.innerHTML = '';
                    if (data.available_tables.length > 0) {
                        data.available_tables.forEach(table => {
                            const card = createTableCard(table);
                            tableGrid.appendChild(card);
                        });
                    } else {
                        tableGrid.innerHTML = '<p class="text-center text-muted">Không có bàn trống. Vui lòng chọn thời gian khác.</p>';
                    }
                } else {
                    resultMessage.className = 'result-message error';
                    resultMessage.innerHTML = `<i class="fas fa-times-circle"></i> Lỗi: ${data.error}`;
                    tableGrid.innerHTML = '';
                }
            })
            .catch(error => {
                loadingDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                checkBtn.disabled = false;
                resultMessage.className = 'result-message error';
                resultMessage.innerHTML = `<i class="fas fa-times-circle"></i> Lỗi kết nối: ${error.message}`;
                tableGrid.innerHTML = '';
            });
    });
    
    // Create table card
    function createTableCard(table) {
        const card = document.createElement('div');
        card.className = 'table-card';
        card.dataset.tableId = table.table_id;
        
        card.innerHTML = `
            <div class="table-status available"></div>
            <div class="table-name">${table.table_number}</div>
            <div class="table-info">
                <i class="fas fa-users"></i> Sức chứa: ${table.capacity} người<br>
                <i class="fas fa-layer-group"></i> Tầng ${table.floor_level}
            </div>
        `;
        
        // Select table on click
        card.addEventListener('click', function() {
            // Remove previous selection
            document.querySelectorAll('.table-card').forEach(c => c.classList.remove('selected'));
            
            // Add selection
            card.classList.add('selected');
            tableIdInput.value = table.table_id;
            tableDisplay.value = `${table.table_number} (Sức chứa: ${table.capacity} người, Tầng ${table.floor_level})`;
        });
        
        return card;
    }
    
    // Auto-trigger check if editing and has initial data
    <?php if ($edit_mode && $reservation_data): ?>
    setTimeout(() => {
        checkBtn.click();
    }, 500);
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>
