<?php 
$page_title = 'Quản Lý Đặt Bàn';
include 'includes/header.php';

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Reservation.php';

// Manager only
Auth::requireManager();

$reservation_manager = new Reservation();

// Filter parameters
$filter_status = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Get reservations
$reservations = $reservation_manager->getAll();

// Apply filters
if ($filter_status !== 'all') {
    $reservations = array_filter($reservations, function($r) use ($filter_status) {
        return isset($r['status']) && $r['status'] === $filter_status;
    });
}

if (!empty($search_query)) {
    $reservations = array_filter($reservations, function($r) use ($search_query) {
        return (isset($r['customer_name']) && stripos($r['customer_name'], $search_query) !== false) || 
               (isset($r['customer_phone']) && stripos($r['customer_phone'], $search_query) !== false);
    });
}

if (!empty($filter_date)) {
    $reservations = array_filter($reservations, function($r) use ($filter_date) {
        return isset($r['reservation_date']) && $r['reservation_date'] === $filter_date;
    });
}

// Calculate statistics
$total_reservations = count($reservations);
$pending_count = count(array_filter($reservations, fn($r) => isset($r['status']) && $r['status'] === 'pending'));
$confirmed_count = count(array_filter($reservations, fn($r) => isset($r['status']) && $r['status'] === 'confirmed'));
$arrived_count = count(array_filter($reservations, fn($r) => isset($r['status']) && $r['status'] === 'arrived'));
$today_count = count(array_filter($reservations, fn($r) => isset($r['reservation_date']) && $r['reservation_date'] === date('Y-m-d')));

function get_reservation_status_badge($status) {
    switch ($status) {
        case 'confirmed': return 'badge-success';
        case 'pending': return 'badge-info';
        case 'arrived': return 'badge-primary';
        case 'completed': return 'badge-dark';
        case 'cancelled':
        case 'no_show': return 'badge-danger';
        default: return 'badge-secondary';
    }
}
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-left: 4px solid;
}

.stat-card.pending { border-left-color: #17a2b8; }
.stat-card.confirmed { border-left-color: #28a745; }
.stat-card.arrived { border-left-color: #667eea; }
.stat-card.today { border-left-color: #ffc107; }

.stat-label {
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.filter-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 15px;
    align-items: end;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
}

.status-filter {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.status-chip {
    padding: 8px 16px;
    border-radius: 20px;
    border: 2px solid #e0e0e0;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #333;
    font-size: 0.9rem;
    font-weight: 500;
}

.status-chip:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.status-chip.active {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.quick-action-btn {
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-action-btn.confirm {
    background: #28a745;
    color: white;
}

.quick-action-btn.arrive {
    background: #667eea;
    color: white;
}

.quick-action-btn.complete {
    background: #6c757d;
    color: white;
}

.quick-action-btn:hover {
    opacity: 0.8;
    transform: translateY(-1px);
}

.table-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-calendar-check"></i> Quản Lý Đặt Bàn</h1>
        <a href="reservation_form.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tạo Đơn Đặt Bàn Mới
        </a>
    </div>
    <p>Theo dõi và quản lý các yêu cầu đặt bàn của khách hàng.</p>
</div>

<?php display_flash_message(); ?>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card pending">
        <div class="stat-label">Chờ Xác Nhận</div>
        <div class="stat-value"><?php echo $pending_count; ?></div>
    </div>
    <div class="stat-card confirmed">
        <div class="stat-label">Đã Xác Nhận</div>
        <div class="stat-value"><?php echo $confirmed_count; ?></div>
    </div>
    <div class="stat-card arrived">
        <div class="stat-label">Đã Đến</div>
        <div class="stat-value"><?php echo $arrived_count; ?></div>
    </div>
    <div class="stat-card today">
        <div class="stat-label">Hôm Nay</div>
        <div class="stat-value"><?php echo $today_count; ?></div>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <form method="GET" action="" id="filterForm">
        <div class="filter-row">
            <div class="form-group">
                <label class="form-label"><i class="fas fa-search"></i> Tìm kiếm</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Tên khách hàng hoặc số điện thoại..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fas fa-calendar"></i> Ngày</label>
                <input type="date" name="date" class="form-control" 
                       value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> Lọc
                </button>
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <a href="reservations.php" class="btn btn-secondary w-100">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </div>
    </form>
    
    <!-- Status Filters -->
    <div class="status-filter">
        <a href="?status=all<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?>" 
           class="status-chip <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> Tất Cả (<?php echo $total_reservations; ?>)
        </a>
        <a href="?status=pending<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?>" 
           class="status-chip <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> Chờ Xác Nhận
        </a>
        <a href="?status=confirmed<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?>" 
           class="status-chip <?php echo $filter_status === 'confirmed' ? 'active' : ''; ?>">
            <i class="fas fa-check"></i> Đã Xác Nhận
        </a>
        <a href="?status=arrived<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?>" 
           class="status-chip <?php echo $filter_status === 'arrived' ? 'active' : ''; ?>">
            <i class="fas fa-user-check"></i> Đã Đến
        </a>
        <a href="?status=completed<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?>" 
           class="status-chip <?php echo $filter_status === 'completed' ? 'active' : ''; ?>">
            <i class="fas fa-check-double"></i> Hoàn Thành
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Khách Hàng</th>
                        <th>Điện Thoại</th>
                        <th>Ngày Đặt</th>
                        <th>Giờ Đặt</th>
                        <th>Số Khách</th>
                        <th>Bàn</th>
                        <th>Trạng Thái</th>
                        <th class="text-end">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reservations)): ?>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($reservation['customer_phone']); ?></td>
                                <td><?php echo format_date($reservation['reservation_date'], 'd/m/Y'); ?></td>
                                <td><?php echo format_date($reservation['reservation_time'], 'H:i'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['guest_count']); ?> người</td>
                                <td><?php echo htmlspecialchars($reservation['table_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                        $status_text = $reservation['status'] ?? 'pending';
                                        if ($status_text == 'pending') $status_text = 'Chờ Xác Nhận';
                                        elseif ($status_text == 'confirmed') $status_text = 'Đã Xác Nhận';
                                        elseif ($status_text == 'arrived') $status_text = 'Đã Đến';
                                        elseif ($status_text == 'completed') $status_text = 'Hoàn Thành';
                                        elseif ($status_text == 'cancelled') $status_text = 'Đã Hủy';
                                        elseif ($status_text == 'no_show') $status_text = 'Không Đến';
                                    ?>
                                    <span class="badge <?php echo get_reservation_status_badge($reservation['status'] ?? 'pending'); ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="table-actions">
                                        <?php if (($reservation['status'] ?? 'pending') === 'pending'): ?>
                                            <form method="POST" action="quick_update_reservation.php" style="display:inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" class="quick-action-btn confirm" title="Xác nhận">
                                                    <i class="fas fa-check"></i> Xác nhận
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (($reservation['status'] ?? '') === 'confirmed'): ?>
                                            <form method="POST" action="quick_update_reservation.php" style="display:inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                                <input type="hidden" name="status" value="arrived">
                                                <button type="submit" class="quick-action-btn arrive" title="Đánh dấu đã đến">
                                                    <i class="fas fa-user-check"></i> Đã đến
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (($reservation['status'] ?? '') === 'arrived'): ?>
                                            <form method="POST" action="quick_update_reservation.php" style="display:inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="quick-action-btn complete" title="Hoàn thành">
                                                    <i class="fas fa-check-double"></i> Hoàn thành
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="reservation_form.php?id=<?php echo $reservation['reservation_id']; ?>" 
                                           class="btn-action btn-edit" title="Sửa chi tiết">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_reservation.php?id=<?php echo $reservation['reservation_id']; ?>" 
                                           class="btn-action btn-delete" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa đơn đặt bàn này không?');" 
                                           title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Chưa có đơn đặt bàn nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
