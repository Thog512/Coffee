<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect(APP_URL . '/admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $auth = new Auth();
    if ($auth->login($username, $password)) {
        redirect(APP_URL . '/admin/dashboard.php');
    } else {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Hiniu Coffee</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-left-content">
                <img src="../assets/images/logo.png" alt="Hiniu Logo" class="auth-logo">
                <h1>Chào Mừng Đến Hiniu Coffee</h1>
                <p>Quản lý quán cà phê của bạn một cách chuyên nghiệp. Đăng nhập để truy cập trang quản trị.</p>
            </div>
        </div>
        <div class="auth-right">
            <h2>Đăng Nhập Quản Trị</h2>
            <?php if ($error): ?>
                <div class="message error"><?php echo $error == 'Invalid username or password' ? 'Tên đăng nhập hoặc mật khẩu không đúng' : $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                                <div class="form-group">
                    <label for="username">Tên Đăng Nhập</label>
                    <input type="text" id="username" name="username" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Mật Khẩu</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn">Đăng Nhập</button>
            </form>
            <div class="switch-link">
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký tại đây</a></p>
            </div>
        </div>
    </div>
</body>
</html>
