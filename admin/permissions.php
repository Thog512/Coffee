<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/Permission.php';

// Check permission
Permission::require('roles.manage', APP_URL . '/admin/dashboard.php');

$permission_class = new Permission();
$roles = $permission_class->getAllRoles();
$permissions_by_category = $permission_class->getPermissionsByCategory();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['role_id'])) {
    $role_id = $_POST['role_id'];
    $permission_ids = $_POST['permissions'] ?? [];
    
    if ($permission_class->syncRolePermissions($role_id, $permission_ids)) {
        Permission::logAction('update_permissions', 'role', $role_id, null, ['permissions' => $permission_ids]);
        $success = "Cập nhật quyền thành công!";
    } else {
        $error = "Có lỗi xảy ra!";
    }
}

// Get selected role permissions
$selected_role_id = $_GET['role_id'] ?? ($roles[0]['role_id'] ?? null);
$selected_permissions = [];
if ($selected_role_id) {
    $perms = $permission_class->getRolePermissions($selected_role_id);
    $selected_permissions = array_column($perms, 'permission_id');
}

$page_title = "Quản Lý Phân Quyền";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Hiniu Coffee</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-shield-alt"></i> <?php echo $page_title; ?></h1>
                <div class="actions">
                    <a href="audit_logs.php" class="btn btn-outline">
                        <i class="fas fa-history"></i> Nhật Ký Hoạt Động
                    </a>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="permissions-container">
                <!-- Role Selector -->
                <div class="role-selector">
                    <h3>Chọn Vai Trò</h3>
                    <div class="role-list">
                        <?php foreach ($roles as $role): ?>
                        <a href="?role_id=<?php echo $role['role_id']; ?>" 
                           class="role-item <?php echo ($role['role_id'] == $selected_role_id) ? 'active' : ''; ?>">
                            <div class="role-name"><?php echo htmlspecialchars($role['role_display_name']); ?></div>
                            <div class="role-desc"><?php echo htmlspecialchars($role['description']); ?></div>
                            <?php if ($role['is_system']): ?>
                                <span class="badge badge-info">System</span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Permissions Editor -->
                <div class="permissions-editor">
                    <?php if ($selected_role_id): ?>
                    <form method="POST">
                        <input type="hidden" name="role_id" value="<?php echo $selected_role_id; ?>">
                        
                        <div class="permissions-header">
                            <h3>Phân Quyền Cho: 
                                <?php 
                                $selected_role = array_filter($roles, fn($r) => $r['role_id'] == $selected_role_id);
                                echo htmlspecialchars(reset($selected_role)['role_display_name']); 
                                ?>
                            </h3>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu Thay Đổi
                            </button>
                        </div>
                        
                        <div class="permissions-grid">
                            <?php foreach ($permissions_by_category as $category => $perms): ?>
                            <div class="permission-category">
                                <h4><?php echo ucfirst($category); ?></h4>
                                <div class="permission-items">
                                    <?php foreach ($perms as $perm): ?>
                                    <label class="permission-item">
                                        <input type="checkbox" 
                                               name="permissions[]" 
                                               value="<?php echo $perm['permission_id']; ?>"
                                               <?php echo in_array($perm['permission_id'], $selected_permissions) ? 'checked' : ''; ?>>
                                        <div class="permission-info">
                                            <div class="permission-name"><?php echo htmlspecialchars($perm['permission_display_name']); ?></div>
                                            <div class="permission-desc"><?php echo htmlspecialchars($perm['description']); ?></div>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Lưu Phân Quyền
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shield-alt"></i>
                        <p>Chọn một vai trò để quản lý quyền</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .permissions-container {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 20px;
        margin-top: 20px;
    }
    
    .role-selector {
        background: white;
        border-radius: 8px;
        padding: 20px;
        height: fit-content;
    }
    
    .role-list {
        margin-top: 15px;
    }
    
    .role-item {
        display: block;
        padding: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        color: inherit;
    }
    
    .role-item:hover {
        border-color: #8B4513;
        background: #f9f9f9;
    }
    
    .role-item.active {
        border-color: #8B4513;
        background: #fff8f0;
    }
    
    .role-name {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .role-desc {
        font-size: 0.85em;
        color: #666;
    }
    
    .permissions-editor {
        background: white;
        border-radius: 8px;
        padding: 20px;
    }
    
    .permissions-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .permissions-grid {
        display: grid;
        gap: 20px;
    }
    
    .permission-category h4 {
        background: #f5f5f5;
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 10px;
        text-transform: uppercase;
        font-size: 0.9em;
        color: #8B4513;
    }
    
    .permission-items {
        display: grid;
        gap: 10px;
        padding-left: 15px;
    }
    
    .permission-item {
        display: flex;
        align-items: start;
        gap: 10px;
        padding: 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .permission-item:hover {
        background: #f9f9f9;
    }
    
    .permission-item input[type="checkbox"] {
        margin-top: 3px;
    }
    
    .permission-info {
        flex: 1;
    }
    
    .permission-name {
        font-weight: 500;
        margin-bottom: 3px;
    }
    
    .permission-desc {
        font-size: 0.85em;
        color: #666;
    }
    
    .form-actions {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
        text-align: right;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    </style>
</body>
</html>
