<?php 
$page_title = 'Quản Lý Bàn';
include 'includes/header.php';

require_once __DIR__ . '/../classes/Table.php';

$table_manager = new Table();
$view = $_GET['view'] ?? 'list'; // list or layout
$tables = $table_manager->getAll();
$floors = $table_manager->getFloors();
$selected_floor = $_GET['floor'] ?? ($floors[0] ?? 1);
$tables_by_floor = $table_manager->getByFloor($selected_floor);

function get_status_badge($status) {
    switch ($status) {
        case 'available': return 'badge-success';
        case 'occupied': return 'badge-danger';
        case 'reserved': return 'badge-warning';
        case 'maintenance': return 'badge-secondary';
        default: return 'badge-light';
    }
}

function get_status_icon($status) {
    switch ($status) {
        case 'available': return 'fa-check-circle';
        case 'occupied': return 'fa-user-times';
        case 'reserved': return 'fa-bookmark';
        case 'maintenance': return 'fa-tools';
        default: return 'fa-question-circle';
    }
}
?>

<style>
.view-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    border-bottom: 2px solid #e0e0e0;
}

.view-tab {
    padding: 12px 30px;
    background: #f5f5f5;
    border: none;
    border-radius: 8px 8px 0 0;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.view-tab:hover {
    background: #e0e0e0;
    color: #333;
}

.view-tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.floor-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.floor-tab {
    padding: 10px 25px;
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    color: #495057;
    transition: all 0.3s;
}

.floor-tab:hover {
    background: #e9ecef;
    border-color: #adb5bd;
    color: #495057;
}

.floor-tab.active {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.table-layout-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
    min-height: 400px;
}

.table-card {
    background: white;
    border: 3px solid #ddd;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.table-card.available {
    border-color: #28a745;
    background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f4 100%);
}

.table-card.occupied {
    border-color: #dc3545;
    background: linear-gradient(135deg, #ffebee 0%, #fce4e4 100%);
}

.table-card.reserved {
    border-color: #ffc107;
    background: linear-gradient(135deg, #fff8e1 0%, #fff9f0 100%);
}

.table-card.maintenance {
    border-color: #6c757d;
    background: linear-gradient(135deg, #e0e0e0 0%, #f5f5f5 100%);
    opacity: 0.7;
}

.table-card-icon {
    font-size: 36px;
    margin-bottom: 10px;
}

.table-card-number {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 5px;
}

.table-card-capacity {
    font-size: 13px;
    color: #666;
    margin-bottom: 8px;
}

.table-card-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.layout-legend {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.legend-color {
    width: 25px;
    height: 25px;
    border-radius: 6px;
    border: 2px solid;
}

.empty-layout {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-layout i {
    font-size: 60px;
    margin-bottom: 15px;
    opacity: 0.3;
}
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chair"></i> Quản Lý Bàn</h1>
        <a href="table_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Bàn Mới</a>
    </div>
    <p>Quản lý danh sách và sơ đồ bàn trong quán</p>
</div>

<?php display_flash_message(); ?>

<!-- View Tabs -->
<div class="view-tabs">
    <a href="?view=list" class="view-tab <?php echo $view == 'list' ? 'active' : ''; ?>">
        <i class="fas fa-list"></i> Danh Sách
    </a>
    <a href="?view=layout" class="view-tab <?php echo $view == 'layout' ? 'active' : ''; ?>">
        <i class="fas fa-map-marked-alt"></i> Sơ Đồ Bàn
    </a>
</div>

<?php if ($view == 'list'): ?>
<!-- LIST VIEW -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Số Bàn</th>
                        <th>Sức Chứa</th>
                        <th>Tầng</th>
                        <th>Trạng Thái</th>
                        <th class="text-end">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tables)): ?>
                        <?php foreach ($tables as $table): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($table['table_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($table['capacity']); ?> người</td>
                                <td>Tầng <?php echo htmlspecialchars($table['floor_level']); ?></td>
                                <td>
                                    <?php $status = $table['STATUS'] ?? 'maintenance'; ?>
                                    <?php 
                                        $status_text = $status;
                                        if ($status == 'available') $status_text = 'Trống';
                                        elseif ($status == 'occupied') $status_text = 'Đang Dùng';
                                        elseif ($status == 'reserved') $status_text = 'Đã Đặt';
                                        elseif ($status == 'maintenance') $status_text = 'Bảo Trì';
                                    ?>
                                    <span class="badge <?php echo get_status_badge($status); ?>">
                                        <i class="fas <?php echo get_status_icon($status); ?>"></i> 
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="table_form.php?id=<?php echo $table['table_id']; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i> Sửa
                                        </a>
                                        <a href="delete_table.php?id=<?php echo $table['table_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa bàn này không?');">
                                            <i class="fas fa-trash"></i> Xóa
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Chưa có bàn nào được thêm.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>
<!-- LAYOUT VIEW -->
<?php if (!empty($floors)): ?>
    <!-- Floor Tabs -->
    <div class="floor-tabs">
        <?php foreach ($floors as $floor): ?>
            <a href="?view=layout&floor=<?php echo $floor; ?>" 
               class="floor-tab <?php echo $floor == $selected_floor ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Tầng <?php echo $floor; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">
                <i class="fas fa-building"></i> Tầng <?php echo $selected_floor; ?>
            </h5>
            
            <?php if (empty($tables_by_floor)): ?>
                <div class="empty-layout">
                    <i class="fas fa-chair"></i>
                    <h4>Chưa có bàn nào ở tầng này</h4>
                    <p>Thêm bàn mới để hiển thị trên sơ đồ</p>
                </div>
            <?php else: ?>
                <div class="table-layout-grid">
                    <?php foreach ($tables_by_floor as $table): 
                        $status = $table['STATUS'] ?? 'available';
                        $status_text = '';
                        $status_badge = '';
                        
                        // Debug: Log mỗi bàn
                        error_log("Table " . $table['table_number'] . " (ID: " . $table['table_id'] . ") has status: " . $status);
                        
                        switch($status) {
                            case 'available':
                                $status_text = 'Trống';
                                $status_badge = 'badge-success';
                                break;
                            case 'occupied':
                                $status_text = 'Đang Dùng';
                                $status_badge = 'badge-danger';
                                break;
                            case 'reserved':
                                $status_text = 'Đã Đặt';
                                $status_badge = 'badge-warning';
                                break;
                            case 'maintenance':
                                $status_text = 'Bảo Trì';
                                $status_badge = 'badge-secondary';
                                break;
                        }
                    ?>
                        <div class="table-card <?php echo htmlspecialchars($status); ?>" 
                             onclick="window.location.href='table_form.php?id=<?php echo $table['table_id']; ?>&from=layout'">
                            <div class="table-card-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div class="table-card-number">
                                Bàn <?php echo htmlspecialchars($table['table_number']); ?>
                            </div>
                            <div class="table-card-capacity">
                                <i class="fas fa-users"></i> <?php echo $table['capacity']; ?> người
                            </div>
                            <span class="table-card-status badge <?php echo $status_badge; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Legend -->
                <div class="layout-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f4 100%); border-color: #28a745;"></div>
                        <span><strong>Trống</strong></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: linear-gradient(135deg, #ffebee 0%, #fce4e4 100%); border-color: #dc3545;"></div>
                        <span><strong>Đang Dùng</strong></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: linear-gradient(135deg, #fff8e1 0%, #fff9f0 100%); border-color: #ffc107;"></div>
                        <span><strong>Đã Đặt</strong></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: linear-gradient(135deg, #e0e0e0 0%, #f5f5f5 100%); border-color: #6c757d;"></div>
                        <span><strong>Bảo Trì</strong></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-layout">
                <i class="fas fa-chair"></i>
                <h4>Chưa có bàn nào</h4>
                <p>Thêm bàn mới để bắt đầu sử dụng sơ đồ</p>
                <a href="table_form.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Thêm Bàn Mới
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
