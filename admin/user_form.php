<?php
// Process POST BEFORE any output (include header)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Role.php';

$user = new User();
$role = new Role();

$user_id = $_GET['id'] ?? null;
$is_editing = !is_null($user_id);

// Handle POST submission FIRST (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'full_name' => $_POST['full_name'],
        'phone' => $_POST['phone'],
        'status' => $_POST['status'],
        'role_id' => $_POST['role_id'] ?? 6  // Default to Customer
    ];

    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }

    if ($is_editing) {
        // Update logic
        if ($user->update($user_id, $data)) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cập nhật người dùng thành công.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Cập nhật người dùng thất bại.'];
        }
    } else {
        // Create logic
        if ($user->create($data)) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Tạo người dùng thành công.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Tạo người dùng thất bại.'];
        }
    }

    redirect(APP_URL . '/admin/users.php');
}

// Prepare data for display
$user_data = [];
$user_roles = [];

if ($is_editing) {
    $user_data = $user->getById($user_id);
    $user_roles = $role->getUserRoles($user_id);
    $current_role_id = !empty($user_roles) ? $user_roles[0] : 6;  // Get first role or default to Customer
    $page_title = 'Sửa Người Dùng';
} else {
    $current_role_id = 6;  // Default to Customer for new users
    $page_title = 'Thêm Người Dùng';
}

$all_roles = $role->getAll();

// NOW include header (after POST processing)
include 'includes/header.php';
?>

<div class="page-header">
    <h1><?php echo $page_title; ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="user_form.php<?php echo $is_editing ? '?id=' . $user_id : ''; ?>" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="username">Tên Đăng Nhập</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="full_name">Họ Tên</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="phone">Số Điện Thoại</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password">Mật Khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo $is_editing ? '' : 'required'; ?>>
                        <?php if ($is_editing): ?>
                            <small class="form-text text-muted">Để trống nếu không muốn thay đổi mật khẩu.</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">Trạng Thái</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active" <?php echo (isset($user_data['status']) && $user_data['status'] == 'active') ? 'selected' : ''; ?>>Hoạt Động</option>
                            <option value="inactive" <?php echo (isset($user_data['status']) && $user_data['status'] == 'inactive') ? 'selected' : ''; ?>>Không Hoạt Động</option>
                            <option value="suspended" <?php echo (isset($user_data['status']) && $user_data['status'] == 'suspended') ? 'selected' : ''; ?>>Tạm Khóa</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Vai Trò</label>
                <div>
                    <?php foreach ($all_roles as $role_item): 
                        // Use description as display name
                        $display_name = !empty($role_item['description']) ? $role_item['description'] : $role_item['role_name'];
                    ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="role_<?php echo $role_item['role_id']; ?>" name="role_id" value="<?php echo $role_item['role_id']; ?>" <?php echo ($role_item['role_id'] == $current_role_id) ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="role_<?php echo $role_item['role_id']; ?>">
                                <strong><?php echo htmlspecialchars($role_item['role_name']); ?></strong> - <?php echo htmlspecialchars($display_name); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small class="form-text text-muted">Chọn một vai trò cho người dùng này.</small>
            </div>

            <button type="submit" class="btn btn-primary"><?php echo $is_editing ? 'Cập Nhật' : 'Tạo Mới'; ?></button>
            <a href="users.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
