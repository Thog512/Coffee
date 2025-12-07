<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Customer.php';

// Check login
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customer_class = new Customer();
$customer = $customer_class->getCustomerById($_SESSION['customer_id']);
$recent_orders = $customer_class->getOrderHistory($_SESSION['customer_id'], 5);
$loyalty_transactions = $customer_class->getLoyaltyTransactions($_SESSION['customer_id'], 10);
$rewards = $customer_class->getAllRewards();
$favorites = $customer_class->getFavorites($_SESSION['customer_id']);

// Calculate tier progress
$tier_thresholds = [
    'bronze' => 0,
    'silver' => 2000000,
    'gold' => 5000000,
    'platinum' => 10000000
];

$current_tier = $customer['loyalty_tier'];
$current_spent = $customer['total_spent'];
$next_tier = '';
$progress = 0;

if ($current_tier == 'bronze') {
    $next_tier = 'silver';
    $progress = ($current_spent / $tier_thresholds['silver']) * 100;
} elseif ($current_tier == 'silver') {
    $next_tier = 'gold';
    $progress = (($current_spent - $tier_thresholds['silver']) / ($tier_thresholds['gold'] - $tier_thresholds['silver'])) * 100;
} elseif ($current_tier == 'gold') {
    $next_tier = 'platinum';
    $progress = (($current_spent - $tier_thresholds['gold']) / ($tier_thresholds['platinum'] - $tier_thresholds['gold'])) * 100;
} else {
    $next_tier = 'platinum';
    $progress = 100;
}

$progress = min(100, max(0, $progress));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hiniu Coffee</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="customer-page">
    <?php include 'includes/header.php'; ?>
    
    <div class="customer-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="customer-content">
            <div class="page-header">
                <h1>Xin chào, <?php echo htmlspecialchars($customer['full_name']); ?>! 👋</h1>
                <p>Chào mừng bạn quay trở lại với Hiniu Coffee</p>
            </div>
            
            <!-- Loyalty Card -->
            <div class="loyalty-card <?php echo $customer['loyalty_tier']; ?>">
                <div class="tier-badge">
                    <i class="fas fa-crown"></i>
                    <span><?php echo strtoupper($customer['loyalty_tier']); ?></span>
                </div>
                <div class="points-display">
                    <h2><?php echo number_format($customer['loyalty_points']); ?></h2>
                    <p>Điểm tích lũy</p>
                </div>
                <div class="tier-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <p>
                        <?php if ($next_tier != 'platinum' || $current_tier != 'platinum'): ?>
                            Còn <?php echo number_format($tier_thresholds[$next_tier] - $current_spent); ?>đ để lên hạng <?php echo strtoupper($next_tier); ?>
                        <?php else: ?>
                            Bạn đã đạt hạng cao nhất!
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-shopping-bag"></i>
                    <h3><?php echo $customer['total_orders']; ?></h3>
                    <p>Đơn hàng</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-coins"></i>
                    <h3><?php echo number_format($customer['loyalty_points']); ?></h3>
                    <p>Điểm</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-heart"></i>
                    <h3><?php echo count($favorites); ?></h3>
                    <p>Yêu thích</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-gift"></i>
                    <h3><?php echo count(array_filter($rewards, fn($r) => $r['points_required'] <= $customer['loyalty_points'])); ?></h3>
                    <p>Quà đổi được</p>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="section">
                <div class="section-header">
                    <h2>Đơn Hàng Gần Đây</h2>
                    <a href="orders.php" class="btn btn-sm btn-outline">Xem tất cả</a>
                </div>
                <div class="orders-list">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted">Bạn chưa có đơn hàng nào.</p>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h4>#<?php echo $order['order_id']; ?></h4>
                                <p><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="order-details">
                                <p><?php echo $order['total_items']; ?> món</p>
                                <h4><?php echo number_format($order['total_amount']); ?>đ</h4>
                            </div>
                            <div class="order-status">
                                <span class="badge badge-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Available Rewards -->
            <div class="section">
                <div class="section-header">
                    <h2>Quà Tặng Có Thể Đổi</h2>
                    <a href="rewards.php" class="btn btn-sm btn-outline">Xem tất cả</a>
                </div>
                <div class="rewards-grid">
                    <?php 
                    $available_rewards = array_filter($rewards, fn($r) => $r['points_required'] <= $customer['loyalty_points']);
                    $available_rewards = array_slice($available_rewards, 0, 4);
                    ?>
                    <?php if (empty($available_rewards)): ?>
                        <p class="text-muted">Tích thêm điểm để đổi quà nhé!</p>
                    <?php else: ?>
                        <?php foreach ($available_rewards as $reward): ?>
                        <div class="reward-card">
                            <div class="reward-icon">
                                <i class="fas fa-gift"></i>
                            </div>
                            <h4><?php echo htmlspecialchars($reward['reward_name']); ?></h4>
                            <p><?php echo htmlspecialchars($reward['description']); ?></p>
                            <div class="reward-points">
                                <span><?php echo number_format($reward['points_required']); ?> điểm</span>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="redeemReward(<?php echo $reward['reward_id']; ?>)">
                                Đổi ngay
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function redeemReward(rewardId) {
        if (confirm('Bạn có chắc muốn đổi quà này?')) {
            fetch('<?php echo APP_URL; ?>/api/customer_redeem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `reward_id=${rewardId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Đổi quà thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            });
        }
    }
    </script>
</body>
</html>
