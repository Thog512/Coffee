<?php
require_once __DIR__ . '/../config/config.php';
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Inventory.php';

// Check authentication
Auth::checkAuth();

$inventory = new Inventory();

// Get filter parameters
$inventoryId = $_GET['item_id'] ?? null;
$limit = $_GET['limit'] ?? 100;

// Get logs
$logs = $inventory->getLogs($inventoryId, $limit);

// Get item info if filtering by item
$item = null;
if ($inventoryId) {
    $item = $inventory->getById($inventoryId);
}

$pageTitle = "Lịch Sử Nhập/Xuất Kho";
include 'includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>
            <i class="fas fa-history"></i> 
            <?php echo $item ? 'Lịch Sử: ' . htmlspecialchars($item['item_name']) : 'Lịch Sử Nhập/Xuất Kho'; ?>
        </h1>
        <div class="header-actions">
            <?php if ($item): ?>
            <a href="inventory_logs.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Xem Tất Cả
            </a>
            <?php endif; ?>
            <a href="inventory.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay Lại
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Số bản ghi:</label>
                <select name="limit" class="filter-select" onchange="this.form.submit()">
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200</option>
                    <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                </select>
            </div>
            <?php if ($inventoryId): ?>
            <input type="hidden" name="item_id" value="<?php echo $inventoryId; ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <?php
        $totalRestock = 0;
        $totalUse = 0;
        $totalWaste = 0;
        $totalAdjustment = 0;
        
        foreach ($logs as $log) {
            switch ($log['action_type']) {
                case 'restock':
                    $totalRestock += abs($log['quantity_change']);
                    break;
                case 'use':
                    $totalUse += abs($log['quantity_change']);
                    break;
                case 'waste':
                    $totalWaste += abs($log['quantity_change']);
                    break;
                case 'adjustment':
                    $totalAdjustment += abs($log['quantity_change']);
                    break;
            }
        }
        ?>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($totalRestock, 2); ?></h3>
                <p>Tổng Nhập Kho</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($totalUse, 2); ?></h3>
                <p>Tổng Sử Dụng</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-trash"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($totalWaste, 2); ?></h3>
                <p>Tổng Hao Hụt</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($totalAdjustment, 2); ?></h3>
                <p>Tổng Điều Chỉnh</p>
            </div>
        </div>
    </div>

    <!-- Logs Timeline -->
    <div class="logs-container">
        <h2><i class="fas fa-clock"></i> Lịch Sử Thao Tác (<?php echo count($logs); ?> bản ghi)</h2>
        
        <?php if (empty($logs)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Chưa có lịch sử thao tác nào</p>
        </div>
        <?php else: ?>
        <div class="timeline">
            <?php foreach ($logs as $log): ?>
            <div class="timeline-item <?php echo $log['action_type']; ?>">
                <div class="timeline-marker">
                    <i class="fas fa-<?php 
                        echo $log['action_type'] === 'restock' ? 'arrow-down' : 
                            ($log['action_type'] === 'use' ? 'arrow-up' : 
                            ($log['action_type'] === 'waste' ? 'trash' : 'exchange-alt')); 
                    ?>"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <div class="timeline-title">
                            <strong><?php echo htmlspecialchars($log['item_name']); ?></strong>
                            <?php
                            $actionLabels = [
                                'restock' => '<span class="badge badge-success">Nhập kho</span>',
                                'use' => '<span class="badge badge-primary">Sử dụng</span>',
                                'waste' => '<span class="badge badge-danger">Hao hụt</span>',
                                'adjustment' => '<span class="badge badge-warning">Điều chỉnh</span>'
                            ];
                            echo $actionLabels[$log['action_type']] ?? $log['action_type'];
                            ?>
                        </div>
                        <div class="timeline-time">
                            <i class="fas fa-clock"></i>
                            <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                        </div>
                    </div>
                    <div class="timeline-body">
                        <div class="log-details">
                            <div class="log-detail">
                                <label>Thay đổi:</label>
                                <span class="quantity-change <?php echo $log['quantity_change'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $log['quantity_change'] >= 0 ? '+' : ''; ?>
                                    <?php echo number_format($log['quantity_change'], 2); ?>
                                </span>
                            </div>
                            <div class="log-detail">
                                <label>Số lượng sau:</label>
                                <span><?php echo number_format($log['quantity_after'], 2); ?></span>
                            </div>
                            <div class="log-detail">
                                <label>Người thực hiện:</label>
                                <span>
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($log['performed_by_name'] ?? 'N/A'); ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($log['reason'])): ?>
                        <div class="log-reason">
                            <i class="fas fa-comment"></i>
                            <em><?php echo htmlspecialchars($log['reason']); ?></em>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.filter-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filter-form {
    display: flex;
    gap: 20px;
    align-items: center;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    font-weight: 500;
    color: #333;
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.logs-container {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.logs-container h2 {
    margin: 0 0 30px 0;
    color: #333;
    padding-bottom: 15px;
    border-bottom: 2px solid #667eea;
}

.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #667eea, #764ba2);
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -32px;
    top: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.timeline-item.restock .timeline-marker {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.timeline-item.use .timeline-marker {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.timeline-item.waste .timeline-marker {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.timeline-item.adjustment .timeline-marker {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border-left: 3px solid #667eea;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.timeline-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
}

.timeline-time {
    color: #666;
    font-size: 14px;
}

.timeline-time i {
    margin-right: 5px;
}

.log-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.log-detail {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.log-detail label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}

.log-detail span {
    font-size: 15px;
    color: #333;
}

.quantity-change {
    font-weight: bold;
    font-size: 18px;
}

.quantity-change.positive {
    color: #28a745;
}

.quantity-change.negative {
    color: #dc3545;
}

.log-reason {
    padding: 12px;
    background: white;
    border-radius: 4px;
    border-left: 3px solid #2196f3;
    display: flex;
    gap: 10px;
    align-items: start;
}

.log-reason i {
    color: #2196f3;
    margin-top: 2px;
}

.log-reason em {
    color: #555;
    font-size: 14px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state p {
    font-size: 18px;
}
</style>

<?php include 'includes/footer.php'; ?>
