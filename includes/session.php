<?php
// Session management

// Ensure config is loaded
if (!defined('SESSION_TIMEOUT')) {
    require_once __DIR__ . '/../config/config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check session timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

// Check if user has permission
function has_permission($permission) {
    return isset($_SESSION['permissions']) && in_array($permission, $_SESSION['permissions']);
}

function require_permission($permission) {
    // First check if user is logged in
    if (!is_logged_in()) {
        redirect(APP_URL . '/admin/index.php');
    }
    
    // For now, allow all logged-in users (temporary during development)
    // TODO: Implement proper permission checking when roles are fully configured
    return true;
    
    // Uncomment this when permissions are properly set up:
    // if (!has_permission($permission)) {
    //     $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Bạn không có quyền truy cập trang này.'];
    //     redirect(APP_URL . '/admin/dashboard.php');
    // }
}

// Require login
function require_login() {
    if (!is_logged_in()) {
        redirect(APP_URL . '/admin/index.php');
    }
}
?>
