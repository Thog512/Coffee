<?php 
$page_title = 'Quản Lý Shipper';
include 'includes/header.php'; 

require_once __DIR__ . '/../classes/Delivery.php';

$delivery = new Delivery();
$shippers = $delivery->getAllShippers();

// Calculate statistics
$total_shippers = count($shippers);
$available = count(array_filter($shippers, fn($s) => $s['status'] == 'available'));
$busy = count(array_filter($shippers, fn($s) => $s['status'] == 'busy'));
$offline = count(array_filter($shippers, fn($s) => $s['status'] == 'offline'));

// Get performance data
$performance = $delivery->getShipperPerformance();
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>🛵 Quản Lý Shipper</h1>
        <a href="shipper_form.php" class="btn btn-primary">+ Thêm Shipper Mới</a>
    </div>
    <p>Quản lý đội ngũ giao hàng và theo dõi hiệu suất.</p>
</div>

<?php display_flash_message(); ?>

<!-- Statistics Summary -->
<div class="stats-summary">
    <div class="stat-box total">
        <h4>Tổng Shipper</h4>
        <div class="stat-value"><?php echo $total_shippers; ?></div>
    </div>
    <div class="stat-box active">
        <h4>Sẵn Sàng</h4>
        <div class="stat-value"><?php echo $available; ?></div>
    </div>
    <div class="stat-box warning">
        <h4>Đang Bận</h4>
        <div class="stat-value"><?php echo $busy; ?></div>
    </div>
    <div class="stat-box">
        <h4>Offline</h4>
        <div class="stat-value"><?php echo $offline; ?></div>
    </div>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Tìm kiếm shipper..." onkeyup="searchShippers()">
    </div>
    <div class="filter-group">
        <select id="statusFilter" onchange="filterShippers()">
            <option value="">Tất cả trạng thái</option>
            <option value="available">Sẵn sàng</option>
            <option value="busy">Đang bận</option>
            <option value="offline">Offline</option>
            <option value="inactive">Không hoạt động</option>
        </select>
    </div>
</div>

<!-- Shippers Table -->
<div class="card">
    <div class="card-body">
        <table class="table" id="shippersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ Tên</th>
                    <th>SĐT</th>
                    <th>Phương Tiện</th>
                    <th>Biển Số</th>
                    <th>Đơn Hiện Tại</th>
                    <th>Đánh Giá</th>
                    <th>Tổng Đơn</th>
                    <th>Thành Công</th>
                    <th>Trạng Thái</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shippers as $shipper): 
                    $success_rate = $shipper['total_deliveries'] > 0 
                        ? round(($shipper['successful_deliveries'] / $shipper['total_deliveries']) * 100, 1) 
                        : 0;
                    
                    $status_class = [
                        'available' => 'success',
                        'busy' => 'warning',
                        'offline' => 'secondary',
                        'inactive' => 'danger'
                    ];
                    
                    $status_labels = [
                        'available' => 'Sẵn sàng',
                        'busy' => 'Đang bận',
                        'offline' => 'Offline',
                        'inactive' => 'Không hoạt động'
                    ];
                    
                    $vehicle_icons = [
                        'motorbike' => '🏍️',
                        'bicycle' => '🚲',
                        'car' => '🚗'
                    ];
                ?>
                <tr data-status="<?php echo $shipper['status']; ?>">
                    <td><?php echo $shipper['shipper_id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($shipper['full_name']); ?></strong>
                        <?php if ($shipper['status'] == 'available'): ?>
                            <span class="badge badge-success">✓ Online</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $shipper['phone']; ?></td>
                    <td>
                        <?php echo $vehicle_icons[$shipper['vehicle_type']] ?? ''; ?>
                        <?php echo ucfirst($shipper['vehicle_type']); ?>
                    </td>
                    <td><?php echo $shipper['license_plate'] ?: '-'; ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php echo $shipper['current_orders']; ?>/<?php echo $shipper['max_orders']; ?>
                        </span>
                    </td>
                    <td>
                        <div class="rating">
                            ⭐ <?php echo number_format($shipper['rating'], 2); ?>
                        </div>
                    </td>
                    <td><?php echo $shipper['total_deliveries']; ?></td>
                    <td>
                        <span class="text-success">
                            <?php echo $shipper['successful_deliveries']; ?>
                            (<?php echo $success_rate; ?>%)
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $status_class[$shipper['status']]; ?>">
                            <?php echo $status_labels[$shipper['status']]; ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="shipper_form.php?id=<?php echo $shipper['shipper_id']; ?>" 
                               class="btn btn-sm btn-primary" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deleteShipper(<?php echo $shipper['shipper_id']; ?>)" 
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

<!-- Performance Chart -->
<div class="card mt-4">
    <div class="card-header">
        <h5>📊 Hiệu Suất Shipper</h5>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Shipper</th>
                    <th>Tổng Đơn</th>
                    <th>Thành Công</th>
                    <th>Tỷ Lệ</th>
                    <th>Đánh Giá</th>
                    <th>Đơn Đang Chạy</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($performance as $perf): ?>
                <tr>
                    <td><?php echo htmlspecialchars($perf['full_name']); ?></td>
                    <td><?php echo $perf['total_deliveries']; ?></td>
                    <td><?php echo $perf['successful_deliveries']; ?></td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $perf['success_rate']; ?>%">
                                <?php echo $perf['success_rate']; ?>%
                            </div>
                        </div>
                    </td>
                    <td>⭐ <?php echo number_format($perf['rating'], 2); ?></td>
                    <td><?php echo $perf['active_deliveries']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function searchShippers() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('shippersTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    }
}

function filterShippers() {
    const statusFilter = document.getElementById('statusFilter').value;
    const table = document.getElementById('shippersTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const status = row.getAttribute('data-status');
        
        if (!statusFilter || status === statusFilter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

function deleteShipper(id) {
    if (confirm('Bạn có chắc chắn muốn xóa shipper này?')) {
        fetch('<?php echo APP_URL; ?>/api/shipper_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Xóa shipper thành công!');
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
.rating {
    font-weight: bold;
    color: #f39c12;
}
.progress {
    margin: 0;
}
</style>

<?php include 'includes/footer.php'; ?>
