<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(APP_URL . '/admin/dashboard.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    
    $userData = [
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? ''
    ];

    if (empty($userData['username']) || empty($userData['email']) || empty($userData['password']) || empty($userData['full_name'])) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Mật khẩu không khớp.';
    } else {
        $result = $auth->register($userData);
        if ($result['success']) {
            $success = 'Đăng ký thành công! Bạn có thể <a href="index.php">đăng nhập</a> ngay.';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Hiniu Coffee</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-left-content">
                <img src="../assets/images/logo.png" alt="Hiniu Logo" class="auth-logo">
                <h1>Tham Gia Hiniu Coffee</h1>
                <p>Tạo tài khoản của bạn và trở thành một phần của cộng đồng yêu cà phê. Nhiều ưu đãi đang chờ bạn!</p>
            </div>
        </div>
        <div class="auth-right">
            <h2>Tạo Tài Khoản</h2>
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php else: ?>
            <form method="POST">
                                <div class="form-group">
                    <label for="full_name">Họ Tên</label>
                    <input type="text" id="full_name" name="full_name" required autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="username">Tên Đăng Nhập</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="phone">Số Điện Thoại (Không bắt buộc)</label>
                    <input type="text" id="phone" name="phone" autocomplete="tel">
                </div>
                <div class="form-group">
                    <label for="password">Mật Khẩu</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Xác Nhận Mật Khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn">Đăng Ký</button>
            </form>
            <?php endif; ?>
            <div class="switch-link">
                <p>Đã có tài khoản? <a href="index.php">Đăng nhập tại đây</a></p>
            </div>
        </div>
    </div>
</body>
</html>
