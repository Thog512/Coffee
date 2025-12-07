<?php 
$page_title = 'Quản Lý Giao Hàng';
include 'includes/header.php'; 

require_once __DIR__ . '/../classes/Delivery.php';

$delivery = new Delivery();

// Get filter
$status_filter = $_GET['status'] ?? null;

$deliveries = $delivery->getAllDeliveries($status_filter);
$active_deliveries = $delivery->getActiveDeliveries();
$available_shippers = $delivery->getAvailableShippers();

// Statistics
$stats = $delivery->getStatistics();
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>📦 Quản Lý Giao Hàng</h1>
        <a href="delivery_form.php" class="btn btn-primary">+ Tạo Đơn Giao Hàng</a>
    </div>
    <p>Theo dõi và phân công đơn giao hàng.</p>
</div>

<?php display_flash_message(); ?>

<!-- Statistics -->
<div class="stats-summary">
    <div class="stat-box total">
        <h4>Tổng Đơn</h4>
        <div class="stat-value"><?php echo $stats['total_deliveries']; ?></div>
    </div>
    <div class="stat-box active">
        <h4>Đang Giao</h4>
        <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
    </div>
    <div class="stat-box success">
        <h4>Thành Công</h4>
        <div class="stat-value"><?php echo $stats['successful']; ?></div>
    </div>
    <div class="stat-box warning">
        <h4>Đã Hủy</h4>
        <div class="stat-value"><?php echo $stats['cancelled']; ?></div>
    </div>
</div>

<!-- Available Shippers Quick View -->
<div class="card mb-3">
    <div class="card-header bg-success text-white">
        <h5>🛵 Shipper Sẵn Sàng (<?php echo count($available_shippers); ?>)</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($available_shippers as $shipper): ?>
            <div class="col-md-3 mb-2">
                <div class="shipper-card">
                    <strong><?php echo htmlspecialchars($shipper['full_name']); ?></strong><br>
                    <small>
                        📞 <?php echo $shipper['phone']; ?><br>
                        📊 <?php echo $shipper['current_orders']; ?>/<?php echo $shipper['max_orders']; ?> đơn<br>
                        ⭐ <?php echo number_format($shipper['rating'], 2); ?>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="toolbar">
    <div class="filter-group">
        <a href="deliveries.php" class="btn btn-sm <?php echo !$status_filter ? 'btn-primary' : 'btn-outline-primary'; ?>">
            Tất cả
        </a>
        <a href="?status=pending" class="btn btn-sm <?php echo $status_filter == 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
            Chờ phân công
        </a>
        <a href="?status=assigned" class="btn btn-sm <?php echo $status_filter == 'assigned' ? 'btn-info' : 'btn-outline-info'; ?>">
            Đã phân công
        </a>
        <a href="?status=picked_up" class="btn btn-sm <?php echo $status_filter == 'picked_up' ? 'btn-primary' : 'btn-outline-primary'; ?>">
            Đã lấy hàng
        </a>
        <a href="?status=in_transit" class="btn btn-sm <?php echo $status_filter == 'in_transit' ? 'btn-primary' : 'btn-outline-primary'; ?>">
            Đang giao
        </a>
        <a href="?status=delivered" class="btn btn-sm <?php echo $status_filter == 'delivered' ? 'btn-success' : 'btn-outline-success'; ?>">
            Đã giao
        </a>
        <a href="?status=cancelled" class="btn btn-sm <?php echo $status_filter == 'cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?>">
            Đã hủy
        </a>
    </div>
</div>

<!-- Deliveries Table -->
<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Đơn Hàng</th>
                    <th>Khách Hàng</th>
                    <th>Địa Chỉ</th>
                    <th>Shipper</th>
                    <th>Khoảng Cách</th>
                    <th>Phí Ship</th>
                    <th>COD</th>
                    <th>Trạng Thái</th>
                    <th>Thời Gian</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deliveries as $del): 
                    $status_class = [
                        'pending' => 'warning',
                        'assigned' => 'info',
                        'picked_up' => 'primary',
                        'in_transit' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'failed' => 'danger'
                    ];
                    
                    $status_labels = [
                        'pending' => 'Chờ phân công',
                        'assigned' => 'Đã phân công',
                        'picked_up' => 'Đã lấy hàng',
                        'in_transit' => 'Đang giao',
                        'delivered' => 'Đã giao',
                        'cancelled' => 'Đã hủy',
                        'failed' => 'Thất bại'
                    ];
                ?>
                <tr>
                    <td>#<?php echo $del['delivery_id']; ?></td>
                    <td>
                        <a href="order_details.php?id=<?php echo $del['order_id']; ?>">
                            #<?php echo $del['order_id']; ?>
                        </a>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($del['customer_name']); ?></strong><br>
                        <small>📞 <?php echo $del['customer_phone']; ?></small>
                    </td>
                    <td>
                        <small><?php echo htmlspecialchars(substr($del['delivery_address'], 0, 50)); ?>...</small>
                    </td>
                    <td>
                        <?php if ($del['shipper_name']): ?>
                            <strong><?php echo htmlspecialchars($del['shipper_name']); ?></strong><br>
                            <small>📞 <?php echo $del['shipper_phone']; ?></small>
                        <?php else: ?>
                            <span class="text-muted">Chưa phân công</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $del['distance']; ?> km</td>
                    <td><?php echo number_format($del['delivery_fee'], 0, ',', '.'); ?>đ</td>
                    <td>
                        <?php if ($del['cod_amount'] > 0): ?>
                            <strong class="text-danger"><?php echo number_format($del['cod_amount'], 0, ',', '.'); ?>đ</strong>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $status_class[$del['status']]; ?>">
                            <?php echo $status_labels[$del['status']]; ?>
                        </span>
                    </td>
                    <td>
                        <small><?php echo date('d/m H:i', strtotime($del['created_at'])); ?></small>
                    </td>
                    <td>
                        <div class="btn-group">
                            <?php if ($del['status'] == 'pending'): ?>
                                <button onclick="showAssignModal(<?php echo $del['delivery_id']; ?>)" 
                                        class="btn btn-sm btn-success" title="Phân công">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            <?php endif; ?>
                            <a href="delivery_tracking.php?id=<?php echo $del['delivery_id']; ?>" 
                               class="btn btn-sm btn-info" title="Theo dõi">
                                <i class="fas fa-map-marker-alt"></i>
                            </a>
                            <?php if ($del['status'] != 'delivered' && $del['status'] != 'cancelled'): ?>
                                <button onclick="cancelDelivery(<?php echo $del['delivery_id']; ?>)" 
                                        class="btn btn-sm btn-danger" title="Hủy">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Shipper Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Phân Công Shipper</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignDeliveryId">
                <div class="form-group">
                    <label>Chọn Shipper:</label>
                    <select id="assignShipperId" class="form-control">
                        <option value="">-- Chọn shipper --</option>
                        <?php foreach ($available_shippers as $shipper): ?>
                        <option value="<?php echo $shipper['shipper_id']; ?>">
                            <?php echo $shipper['full_name']; ?> 
                            (<?php echo $shipper['current_orders']; ?>/<?php echo $shipper['max_orders']; ?> đơn, 
                            ⭐ <?php echo number_format($shipper['rating'], 2); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="assignShipper()">Phân Công</button>
            </div>
        </div>
    </div>
</div>

<script>
function showAssignModal(deliveryId) {
    document.getElementById('assignDeliveryId').value = deliveryId;
    $('#assignModal').modal('show');
}

function assignShipper() {
    const deliveryId = document.getElementById('assignDeliveryId').value;
    const shipperId = document.getElementById('assignShipperId').value;
    
    if (!shipperId) {
        alert('Vui lòng chọn shipper!');
        return;
    }
    
    fetch('<?php echo APP_URL; ?>/api/delivery_assign.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `delivery_id=${deliveryId}&shipper_id=${shipperId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Phân công shipper thành công!');
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    });
}

function cancelDelivery(id) {
    const reason = prompt('Lý do hủy đơn:');
    if (reason) {
        fetch('<?php echo APP_URL; ?>/api/delivery_cancel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `delivery_id=${id}&reason=${encodeURIComponent(reason)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Hủy đơn thành công!');
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        });
    }
}
</script>

<style>
.shipper-card {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: #f8f9fa;
}
</style>

<?php include 'includes/footer.php'; ?>
