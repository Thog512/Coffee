<?php 
$page_title = 'Thêm/Sửa Shipper';
include 'includes/header.php';

require_once __DIR__ . '/../classes/Delivery.php';
require_once __DIR__ . '/../classes/User.php';

$delivery = new Delivery();
$user = new User();

$edit_mode = false;
$shipper_data = null;

// Get all users with shipper role
$users = $user->getAll();

if (isset($_GET['id'])) {
    $edit_mode = true;
    $shipper_data = $delivery->getShipperById($_GET['id']);
    
    if (!$shipper_data) {
        set_flash_message('Không tìm thấy shipper!', 'error');
        header('Location: shippers.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'user_id' => $_POST['user_id'],
        'full_name' => $_POST['full_name'],
        'phone' => $_POST['phone'],
        'vehicle_type' => $_POST['vehicle_type'],
        'license_plate' => $_POST['license_plate'] ?? null,
        'max_orders' => $_POST['max_orders'] ?? 3,
        'status' => $_POST['status'] ?? 'available'
    ];
    
    try {
        if ($edit_mode) {
            $success = $delivery->updateShipper($_GET['id'], $data);
            $message = 'Cập nhật shipper thành công!';
        } else {
            $success = $delivery->createShipper($data);
            $message = 'Thêm shipper mới thành công!';
        }
        
        if ($success) {
            set_flash_message($message, 'success');
            header('Location: shippers.php');
            exit;
        } else {
            set_flash_message('Có lỗi xảy ra!', 'error');
        }
    } catch (Exception $e) {
        set_flash_message('Lỗi: ' . $e->getMessage(), 'error');
    }
}
?>

<div class="page-header">
    <h1><?php echo $edit_mode ? '✏️ Sửa Shipper' : '➕ Thêm Shipper Mới'; ?></h1>
</div>

<?php display_flash_message(); ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>User Account <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-control" required <?php echo $edit_mode ? 'disabled' : ''; ?>>
                            <option value="">Chọn user...</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['user_id']; ?>" 
                                    <?php echo ($shipper_data && $shipper_data['user_id'] == $u['user_id']) ? 'selected' : ''; ?>>
                                <?php echo $u['full_name'] . ' (' . $u['email'] . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="user_id" value="<?php echo $shipper_data['user_id']; ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Họ Tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo $shipper_data['full_name'] ?? ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Số Điện Thoại <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo $shipper_data['phone'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Phương Tiện <span class="text-danger">*</span></label>
                        <select name="vehicle_type" class="form-control" required>
                            <option value="motorbike" <?php echo ($shipper_data['vehicle_type'] ?? '') == 'motorbike' ? 'selected' : ''; ?>>🏍️ Xe máy</option>
                            <option value="bicycle" <?php echo ($shipper_data['vehicle_type'] ?? '') == 'bicycle' ? 'selected' : ''; ?>>🚲 Xe đạp</option>
                            <option value="car" <?php echo ($shipper_data['vehicle_type'] ?? '') == 'car' ? 'selected' : ''; ?>>🚗 Ô tô</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Biển Số Xe</label>
                        <input type="text" name="license_plate" class="form-control" 
                               value="<?php echo $shipper_data['license_plate'] ?? ''; ?>" 
                               placeholder="VD: 29A-12345">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Số Đơn Tối Đa</label>
                        <input type="number" name="max_orders" class="form-control" 
                               value="<?php echo $shipper_data['max_orders'] ?? 3; ?>" min="1" max="10">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Trạng Thái</label>
                        <select name="status" class="form-control">
                            <option value="available" <?php echo ($shipper_data['status'] ?? 'available') == 'available' ? 'selected' : ''; ?>>Sẵn sàng</option>
                            <option value="offline" <?php echo ($shipper_data['status'] ?? '') == 'offline' ? 'selected' : ''; ?>>Offline</option>
                            <option value="inactive" <?php echo ($shipper_data['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Không hoạt động</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_mode ? '💾 Cập Nhật' : '➕ Thêm Shipper'; ?>
                </button>
                <a href="shippers.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
