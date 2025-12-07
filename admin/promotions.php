<?php 
$page_title = 'Quản Lý Khuyến Mãi';
include 'includes/header.php'; 

require_once __DIR__ . '/../classes/Promotion.php';

$promotion = new Promotion();

// Auto-update expired promotions
$promotion->updateExpiredPromotions();

$promotions = $promotion->getAll();

// Calculate statistics
$total_promotions = count($promotions);
$active_promotions = count(array_filter($promotions, function($p) { return $p['status'] == 'active'; }));
$inactive_promotions = count(array_filter($promotions, function($p) { return $p['status'] == 'inactive'; }));
$expired_promotions = count(array_filter($promotions, function($p) { return $p['status'] == 'expired'; }));

// Get statistics
$stats = $promotion->getStatistics();
$total_discount = array_sum(array_column($stats, 'total_discount'));
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>🎁 Quản Lý Khuyến Mãi</h1>
        <a href="promotion_form.php" class="btn btn-primary">+ Tạo Khuyến Mãi Mới</a>
    </div>
    <p>Quản lý các chương trình khuyến mãi và ưu đãi cho khách hàng.</p>
</div>

<?php display_flash_message(); ?>

<!-- Statistics Summary -->
<div class="stats-summary">
    <div class="stat-box total">
        <h4>Tổng Khuyến Mãi</h4>
        <div class="stat-value"><?php echo $total_promotions; ?></div>
    </div>
    <div class="stat-box active">
        <h4>Đang Hoạt Động</h4>
        <div class="stat-value"><?php echo $active_promotions; ?></div>
    </div>
    <div class="stat-box warning">
        <h4>Đã Hết Hạn</h4>
        <div class="stat-value"><?php echo $expired_promotions; ?></div>
    </div>
    <div class="stat-box">
        <h4>Tổng Tiền Giảm</h4>
        <div class="stat-value"><?php echo number_format($total_discount, 0, ',', '.'); ?>đ</div>
    </div>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Tìm kiếm khuyến mãi..." onkeyup="searchPromotions()">
    </div>
    <div class="filter-group">
        <select id="statusFilter" onchange="filterPromotions()">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang hoạt động</option>
            <option value="inactive">Không hoạt động</option>
            <option value="expired">Đã hết hạn</option>
        </select>
        <select id="typeFilter" onchange="filterPromotions()">
            <option value="">Tất cả loại</option>
            <option value="percentage">Giảm theo %</option>
            <option value="fixed_amount">Giảm cố định</option>
            <option value="buy_x_get_y">Mua X Tặng Y</option>
            <option value="combo">Combo</option>
        </select>
    </div>
</div>

<!-- Promotions Table -->
<div class="card">
    <div class="card-body">
        <table class="table" id="promotionsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên Khuyến Mãi</th>
                    <th>Loại</th>
                    <th>Giá Trị</th>
                    <th>Thời Gian</th>
                    <th>Mã Voucher</th>
                    <th>Sử Dụng</th>
                    <th>Trạng Thái</th>
                    <th>Ưu Tiên</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promotions as $promo): 
                    // Get type label
                    $type_labels = [
                        'percentage' => 'Giảm %',
                        'fixed_amount' => 'Giảm cố định',
                        'buy_x_get_y' => 'Mua X Tặng Y',
                        'combo' => 'Combo'
                    ];
                    $type_label = $type_labels[$promo['promotion_type']] ?? $promo['promotion_type'];
                    
                    // Format discount value
                    if ($promo['promotion_type'] == 'percentage') {
                        $discount_display = $promo['discount_value'] . '%';
                    } else {
                        $discount_display = number_format($promo['discount_value'], 0, ',', '.') . 'đ';
                    }
                    
                    // Status badge
                    $status_class = [
                        'active' => 'success',
                        'inactive' => 'secondary',
                        'expired' => 'danger'
                    ];
                    $status_labels = [
                        'active' => 'Hoạt động',
                        'inactive' => 'Tạm dừng',
                        'expired' => 'Hết hạn'
                    ];
                    
                    // Check if currently valid
                    $today = date('Y-m-d');
                    $is_valid = ($promo['status'] == 'active' && 
                                $promo['start_date'] <= $today && 
                                $promo['end_date'] >= $today);
                ?>
                <tr data-status="<?php echo $promo['status']; ?>" data-type="<?php echo $promo['promotion_type']; ?>">
                    <td><?php echo $promo['promotion_id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($promo['promotion_name']); ?></strong>
                        <?php if ($is_valid): ?>
                            <span class="badge badge-success">🔥 Đang chạy</span>
                        <?php endif; ?>
                        <?php if ($promo['description']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($promo['description'], 0, 50)); ?>...</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-info"><?php echo $type_label; ?></span></td>
                    <td>
                        <strong><?php echo $discount_display; ?></strong>
                        <?php if ($promo['min_order_value'] > 0): ?>
                            <br><small class="text-muted">Đơn tối thiểu: <?php echo number_format($promo['min_order_value'], 0, ',', '.'); ?>đ</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small>
                            <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?><br>
                            đến<br>
                            <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?>
                        </small>
                        <?php if ($promo['start_time'] && $promo['end_time']): ?>
                            <br><span class="badge badge-warning">⏰ <?php echo substr($promo['start_time'], 0, 5); ?>-<?php echo substr($promo['end_time'], 0, 5); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($promo['voucher_code']): ?>
                            <code><?php echo $promo['voucher_code']; ?></code>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $promo['usage_count']; ?>
                        <?php if ($promo['usage_limit']): ?>
                            / <?php echo $promo['usage_limit']; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $status_class[$promo['status']]; ?>">
                            <?php echo $status_labels[$promo['status']]; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-dark"><?php echo $promo['priority']; ?></span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="promotion_form.php?id=<?php echo $promo['promotion_id']; ?>" 
                               class="btn btn-sm btn-primary" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deletePromotion(<?php echo $promo['promotion_id']; ?>)" 
                                    class="btn btn-sm btn-danger" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Top Promotions Stats -->
<div class="card mt-4">
    <div class="card-header">
        <h5>📊 Top Khuyến Mãi Được Sử Dụng Nhiều Nhất</h5>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Tên Khuyến Mãi</th>
                    <th>Số Lần Sử Dụng</th>
                    <th>Tổng Tiền Giảm</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stat['promotion_name']); ?></td>
                    <td><?php echo $stat['total_usage'] ?? 0; ?></td>
                    <td><?php echo number_format($stat['total_discount'] ?? 0, 0, ',', '.'); ?>đ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function searchPromotions() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('promotionsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    }
}

function filterPromotions() {
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    const table = document.getElementById('promotionsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const status = row.getAttribute('data-status');
        const type = row.getAttribute('data-type');
        
        let showRow = true;
        
        if (statusFilter && status !== statusFilter) {
            showRow = false;
        }
        
        if (typeFilter && type !== typeFilter) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    }
}

function deletePromotion(id) {
    if (confirm('Bạn có chắc chắn muốn xóa khuyến mãi này?')) {
        fetch('<?php echo APP_URL; ?>/api/promotion_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Xóa khuyến mãi thành công!');
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra: ' + error);
        });
    }
}
</script>

<style>
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}
.badge-success { background: #28a745; color: white; }
.badge-danger { background: #dc3545; color: white; }
.badge-warning { background: #ffc107; color: #000; }
.badge-info { background: #17a2b8; color: white; }
.badge-secondary { background: #6c757d; color: white; }
.badge-dark { background: #343a40; color: white; }

code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
</style>

<?php include 'includes/footer.php'; ?>
