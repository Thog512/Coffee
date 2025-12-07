<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Customer.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $auth = new Auth();
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        // Check if customer profile exists
        $customer = new Customer();
        $customer_profile = $customer->getCustomerByUserId($_SESSION['user_id']);
        
        if ($customer_profile) {
            $_SESSION['customer_id'] = $customer_profile['customer_id'];
            header('Location: dashboard.php');
            exit;
        } else {
            // Create customer profile
            $customer_data = [
                'user_id' => $_SESSION['user_id'],
                'full_name' => $_SESSION['full_name'],
                'email' => $email,
                'phone' => '',
                'date_of_birth' => null,
                'gender' => null,
                'address' => '',
                'city' => '',
                'district' => '',
                'status' => 'active'
            ];
            
            $customer_id = $customer->createCustomer($customer_data);
            $_SESSION['customer_id'] = $customer_id;
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Hiniu Coffee</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/customer.css">
</head>
<body class="customer-page">
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="Hiniu Coffee">
                <h1>Hiniu Coffee</h1>
            </div>
            
            <h2>Đăng Nhập</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="email@example.com">
                </div>
                
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="••••••••">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember"> Ghi nhớ đăng nhập
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Đăng Nhập</button>
            </form>
            
            <div class="login-footer">
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                <p><a href="forgot_password.php">Quên mật khẩu?</a></p>
            </div>
        </div>
    </div>
</body>
</html>
