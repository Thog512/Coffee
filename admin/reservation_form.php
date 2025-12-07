<?php
// Process form BEFORE any output
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
$tables = $table_manager->getAll();

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
        'status' => $_POST['status'],
        'special_requests' => trim($_POST['special_requests'])
    ];
    
    if (empty($data['customer_name']) || empty($data['customer_phone']) || empty($data['reservation_date']) || empty($data['reservation_time'])) {
        set_flash_message('Vui lòng điền đầy đủ các trường bắt buộc.', 'danger');
    } else {
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

// Include header AFTER POST processing
$page_title = 'Quản Lý Đặt Bàn';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><?php echo $edit_mode ? 'Chỉnh Sửa Đặt Bàn' : 'Tạo Đơn Đặt Bàn Mới'; ?></h1>
        <a href="reservations.php" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
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
                               value="<?php echo $edit_mode ? $reservation_data['reservation_date'] : date('Y-m-d'); ?>" required>
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
                        <label for="guest_count" class="form-label">Số Lượng Khách</label>
                        <input type="number" class="form-control" id="guest_count" name="guest_count" 
                               value="<?php echo $edit_mode ? $reservation_data['guest_count'] : '2'; ?>" min="1">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="table_id" class="form-label">Chọn Bàn</label>
                        <select class="form-control" id="table_id" name="table_id">
                            <option value="">-- Chọn bàn --</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?php echo $table['table_id']; ?>" <?php echo ($edit_mode && $reservation_data['table_id'] == $table['table_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($table['table_number']); ?> (Sức chứa: <?php echo $table['capacity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
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
            </div>

            <div class="form-group mb-3">
                <label for="special_requests" class="form-label">Ghi Chú / Yêu Cầu Đặc Biệt</label>
                <textarea class="form-control" id="special_requests" name="special_requests" rows="3"><?php echo $edit_mode ? htmlspecialchars($reservation_data['special_requests']) : ''; ?></textarea>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_mode ? 'Cập Nhật' : 'Tạo Đơn'; ?>
                </button>
                <a href="reservations.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
