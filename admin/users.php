<?php 
$page_title = 'Quản Lý Người Dùng';
include 'includes/header.php';

// Only managers can access
require_once __DIR__ . '/../classes/Auth.php';
Auth::requireManager();

require_once __DIR__ . '/../classes/User.php';

// Fetch all users from the database
$user = new User();
$users = $user->getAll();
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Quản Lý Người Dùng</h1>
        <a href="user_form.php" class="btn btn-primary">Thêm Người Dùng</a>
    </div>
    <p>Quản lý tất cả tài khoản người dùng trong hệ thống.</p>
</div>

<?php display_flash_message(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên Đăng Nhập</th>
                        <th>Email</th>
                        <th>Họ Tên</th>
                        <th>Vai Trò</th>
                        <th>Trạng Thái</th>
                        <th>Đăng Nhập Gần Nhất</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): ?>
                        <?php foreach ($users as $user_item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user_item['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($user_item['username']); ?></td>
                                <td><?php echo htmlspecialchars($user_item['email']); ?></td>
                                <td><?php echo htmlspecialchars($user_item['full_name']); ?></td>
                                <td>
                                    <?php 
                                    $role_name = $user_item['role_name'] ?? 'N/A';
                                    $role_colors = [
                                        'Manager' => 'danger',
                                        'Waiter' => 'info',
                                        'Warehouse Staff' => 'warning',
                                        'Accountant' => 'primary',
                                        'Shipper' => 'success',
                                        'Customer' => 'secondary'
                                    ];
                                    $badge_color = $role_colors[$role_name] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_color; ?>"><?php echo htmlspecialchars($role_name); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $status = $user_item['status'] ?? 'active';
                                    $badge_class = $status == 'active' ? 'success' : 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($user_item['last_login'] ? date('d/m/Y H:i', strtotime($user_item['last_login'])) : 'Chưa đăng nhập'); ?></td>
                                <td>
                                    <a href="user_form.php?id=<?php echo $user_item['user_id']; ?>" class="btn btn-sm btn-secondary">Sửa</a>
                                    <a href="delete_user.php?id=<?php echo $user_item['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Không tìm thấy người dùng nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
