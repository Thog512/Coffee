<?php 
// Process POST BEFORE any output
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/Table.php';

$table_manager = new Table();

$edit_mode = false;
$table_data = null;
$table_id = null;

// Check if editing existing table
if (isset($_GET['id'])) {
    $edit_mode = true;
    $table_id = (int)$_GET['id'];
    $table_data = $table_manager->getById($table_id);
    
    if (!$table_data) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Không tìm thấy bàn!'];
        redirect(APP_URL . '/admin/tables.php');
    }
}

// Handle form submission FIRST (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'table_number' => trim($_POST['table_number']),
        'capacity' => (int)$_POST['capacity'],
        'floor_level' => (int)$_POST['floor_level'],
        'status' => $_POST['status'] ?? 'available'
    ];
    
    // Debug log
    error_log("Form POST data: " . print_r($data, true));
    error_log("Edit mode: " . ($edit_mode ? 'yes' : 'no') . ", Table ID: " . $table_id);
    
    if (empty($data['table_number']) || $data['capacity'] <= 0) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Số bàn và sức chứa phải hợp lệ.'];
    } else {
        if ($edit_mode) {
            $result = $table_manager->update($table_id, $data);
            error_log("Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
            
            if ($result) {
                // Verify update thành công bằng cách query lại
                $updated_table = $table_manager->getById($table_id);
                error_log("Verified status after update: " . ($updated_table['STATUS'] ?? 'NONE'));
                
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cập nhật thông tin bàn thành công!'];
                // Redirect back to where user came from
                $redirect_url = $_GET['from'] ?? 'list';
                if ($redirect_url == 'layout') {
                    $floor = $data['floor_level'];
                    // Thêm timestamp để force refresh
                    redirect(APP_URL . '/admin/tables.php?view=layout&floor=' . $floor . '&t=' . time());
                } else {
                    redirect(APP_URL . '/admin/tables.php?t=' . time());
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi khi cập nhật thông tin bàn!'];
                error_log("Update failed for table $table_id");
            }
        } else {
            if ($table_manager->create($data)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Thêm bàn mới thành công!'];
                $floor = $data['floor_level'];
                redirect(APP_URL . '/admin/tables.php?view=layout&floor=' . $floor);
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi khi thêm bàn mới!'];
            }
        }
    }
}

// NOW include header (after POST processing)
$page_title = 'Quản Lý Bàn';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><?php echo $edit_mode ? 'Chỉnh Sửa Bàn' : 'Thêm Bàn Mới'; ?></h1>
        <a href="tables.php" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="table_number" class="form-label">Số Bàn / Tên Bàn <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="table_number" name="table_number" 
                               value="<?php echo $edit_mode ? htmlspecialchars($table_data['table_number']) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="capacity" class="form-label">Sức Chứa (người) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="capacity" name="capacity" 
                               value="<?php echo $edit_mode ? $table_data['capacity'] : '2'; ?>" min="1" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="floor_level" class="form-label">Tầng</label>
                        <input type="number" class="form-control" id="floor_level" name="floor_level" 
                               value="<?php echo $edit_mode ? $table_data['floor_level'] : '1'; ?>" min="1">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="status" class="form-label">Trạng Thái</label>
                        <select class="form-control" id="status" name="status">
                            <option value="available" <?php echo ($edit_mode && isset($table_data['STATUS']) && $table_data['STATUS'] == 'available') ? 'selected' : ''; ?>>Còn trống</option>
                            <option value="occupied" <?php echo ($edit_mode && isset($table_data['STATUS']) && $table_data['STATUS'] == 'occupied') ? 'selected' : ''; ?>>Đang có khách</option>
                            <option value="reserved" <?php echo ($edit_mode && isset($table_data['STATUS']) && $table_data['STATUS'] == 'reserved') ? 'selected' : ''; ?>>Đã đặt trước</option>
                            <option value="maintenance" <?php echo ($edit_mode && isset($table_data['STATUS']) && $table_data['STATUS'] == 'maintenance') ? 'selected' : ''; ?>>Bảo trì</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_mode ? 'Cập Nhật' : 'Thêm Mới'; ?>
                </button>
                <a href="tables.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
