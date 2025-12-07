<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';

if (!is_logged_in()) {
    redirect(APP_URL . '/admin/index.php');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Trang Quản Trị'; ?> - Hiniu Coffee</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/chatbot.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <header class="admin-header">
                <div class="header-left">
                    <!-- Can add search bar or other elements here -->
                </div>
                <div class="user-info">
                    <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                    <a href="<?php echo APP_URL; ?>/admin/logout.php" class="logout-btn">Đăng Xuất <i class="fas fa-sign-out-alt"></i></a>
                </div>
            </header>
            <main class="page-content">
