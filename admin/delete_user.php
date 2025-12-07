<?php
// Process DELETE BEFORE any output
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';

// Check authentication and permission
Auth::requireManager();

$user = new User();
$user_id = $_GET['id'] ?? null;

if ($user_id) {
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Bạn không thể xóa tài khoản của chính mình.'];
    } else {
        if ($user->delete($user_id)) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Xóa người dùng thành công.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Xóa người dùng thất bại.'];
        }
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid user ID.'];
}

// Redirect without any output
redirect(APP_URL . '/admin/users.php');
?>
