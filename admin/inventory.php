<?php
require_once __DIR__ . '/../config/config.php';
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Inventory.php';

// Check authentication - Manager only
Auth::requireManager();

$inventory = new Inventory();
$items = $inventory->getAll();
$stats = $inventory->getStatistics();
$alerts = $inventory->getAlerts();

$pageTitle = "Quản Lý Kho";
include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-warehouse"></i> Quản Lý Kho Nguyên Liệu</h1>
        <div class="header-actions">
            <a href="inventory_form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm Nguyên Liệu
            </a>
            <a href="inventory_logs.php" class="btn btn-secondary">
                <i class="fas fa-history"></i> Lịch Sử
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card pink">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-info">
                <h3>Tổng Nguyên Liệu</h3>
                <div class="stat-number"><?php echo number_format($stats['total_items']); ?></div>
            </div>
        </div>

        <div class="stat-card orange">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3>Sắp Hết Hàng</h3>
                <div class="stat-number"><?php echo number_format($stats['low_stock_count']); ?></div>
            </div>
        </div>

        <div class="stat-card blue">
            <div class="stat-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <div class="stat-info">
                <h3>Sắp Hết Hạn</h3>
                <div class="stat-number"><?php echo number_format($stats['expiring_soon_count']); ?></div>
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3>Giá Trị Kho</h3>
                <div class="stat-number"><?php echo number_format($stats['total_value'], 0, ',', '.'); ?>đ</div>
            </div>
        </div>
    </div>

    <!-- Alerts Section -->
    <?php if (!empty($alerts)): ?>
    <div class="alerts-section">
        <h2><i class="fas fa-bell"></i> Cảnh Báo (<?php echo count($alerts); ?>)</h2>
        <div class="alerts-grid">
            <?php foreach ($alerts as $alert): ?>
            <div class="alert alert-<?php echo $alert['severity']; ?>">
                <i class="fas fa-<?php echo $alert['type'] == 'low_stock' ? 'box-open' : 'clock'; ?>"></i>
                <div class="alert-content">
                    <strong><?php echo htmlspecialchars($alert['item_name']); ?></strong>
                    <p><?php echo htmlspecialchars($alert['message']); ?></p>
                </div>
                <a href="inventory_form.php?id=<?php echo $alert['item_id']; ?>" class="alert-action">
                    <i class="fas fa-edit"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Inventory Table -->
    <div class="table-container">
        <div class="table-header">
            <h2><i class="fas fa-list"></i> Danh Sách Nguyên Liệu</h2>
            <div class="table-actions">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Tìm kiếm nguyên liệu...">
                </div>
                <select id="filterType" class="filter-select">
                    <option value="">Tất cả loại</option>
                    <option value="ingredient">Nguyên liệu</option>
                    <option value="supply">Vật tư</option>
                    <option value="packaging">Bao bì</option>
                </select>
                <select id="filterStatus" class="filter-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="available">Còn hàng</option>
                    <option value="low_stock">Sắp hết</option>
                    <option value="out_of_stock">Hết hàng</option>
                    <option value="expired">Hết hạn</option>
                </select>
            </div>
        </div>

        <table class="data-table" id="inventoryTable">
            <thead>
                <tr>
                    <th>Tên Nguyên Liệu</th>
                    <th>Loại</th>
                    <th>Số Lượng</th>
                    <th>Đơn Vị</th>
                    <th>Tồn Tối Thiểu</th>
                    <th>Hạn Sử Dụng</th>
                    <th>Nhà Cung Cấp</th>
                    <th>Giá/Đơn Vị</th>
                    <th>Trạng Thái</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr data-type="<?php echo $item['item_type']; ?>" data-status="<?php echo $item['status'] ?? 'available'; ?>">
                    <td>
                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                    </td>
                    <td>
                        <?php
                        $typeLabels = [
                            'ingredient' => '<span class="badge badge-primary">Nguyên liệu</span>',
                            'supply' => '<span class="badge badge-info">Vật tư</span>',
                            'packaging' => '<span class="badge badge-secondary">Bao bì</span>'
                        ];
                        echo $typeLabels[$item['item_type']] ?? $item['item_type'];
                        ?>
                    </td>
                    <td>
                        <strong class="<?php echo $item['quantity'] <= $item['min_quantity'] ? 'text-danger' : ''; ?>">
                            <?php echo number_format($item['quantity'], 2); ?>
                        </strong>
                    </td>
                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                    <td><?php echo number_format($item['min_quantity'], 2); ?></td>
                    <td>
                        <?php
                        if ($item['expiration_date']) {
                            $expDate = strtotime($item['expiration_date']);
                            $daysLeft = ceil(($expDate - time()) / 86400);
                            $class = $daysLeft <= 3 ? 'text-danger' : ($daysLeft <= 7 ? 'text-warning' : '');
                            echo '<span class="' . $class . '">' . date('d/m/Y', $expDate) . '</span>';
                            if ($daysLeft > 0 && $daysLeft <= 7) {
                                echo '<br><small>(' . $daysLeft . ' ngày)</small>';
                            }
                        } else {
                            echo '<span class="text-muted">Không có</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></td>
                    <td><?php echo number_format($item['cost_per_unit'], 0, ',', '.'); ?>đ</td>
                    <td>
                        <?php
                        $statusLabels = [
                            'available' => '<span class="badge badge-success">Còn hàng</span>',
                            'low_stock' => '<span class="badge badge-warning">Sắp hết</span>',
                            'out_of_stock' => '<span class="badge badge-danger">Hết hàng</span>',
                            'expired' => '<span class="badge badge-dark">Hết hạn</span>'
                        ];
                        $status = $item['status'] ?? 'available';
                        echo $statusLabels[$status] ?? '<span class="badge badge-secondary">' . $status . '</span>';
                        ?>
                    </td>
                    <td class="actions">
                        <button onclick="quickAdjust(<?php echo $item['inventory_id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')" 
                                class="btn-icon" title="Điều chỉnh số lượng">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        <a href="inventory_form.php?id=<?php echo $item['inventory_id']; ?>" 
                           class="btn-icon" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteItem(<?php echo $item['inventory_id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')" 
                                class="btn-icon btn-danger" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<!-- Quick Adjust Modal -->
<div id="adjustModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-exchange-alt"></i> Điều Chỉnh Số Lượng</h2>
            <span class="close" onclick="closeAdjustModal()">&times;</span>
        </div>
        <form id="adjustForm" onsubmit="submitAdjustment(event)">
            <input type="hidden" id="adjust_inventory_id">
            <div class="form-group">
                <label>Nguyên liệu:</label>
                <input type="text" id="adjust_item_name" readonly class="form-control">
            </div>
            <div class="form-group">
                <label>Loại thao tác:</label>
                <select id="adjust_action" class="form-control" required>
                    <option value="restock">Nhập kho</option>
                    <option value="use">Sử dụng</option>
                    <option value="waste">Hao hụt</option>
                    <option value="adjustment">Điều chỉnh</option>
                </select>
            </div>
            <div class="form-group">
                <label>Số lượng thay đổi:</label>
                <input type="number" id="adjust_quantity" class="form-control" step="0.01" required>
                <small class="form-text">Nhập số dương để tăng, số âm để giảm</small>
            </div>
            <div class="form-group">
                <label>Lý do:</label>
                <textarea id="adjust_reason" class="form-control" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeAdjustModal()" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-primary">Xác Nhận</button>
            </div>
        </form>
    </div>
</div>

<style>
.alerts-section {
    margin: 25px 0;
}

.alerts-section h2 {
    margin-bottom: 20px;
    color: var(--black);
    font-weight: 700;
    font-size: 1.3rem;
}

.alerts-section h2 i {
    color: var(--primary-pink);
    margin-right: 8px;
}

.alerts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 15px;
}

.alert {
    display: flex;
    align-items: center;
    padding: 18px;
    border-radius: 10px;
    border-left: 4px solid;
    background: white;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.alert:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.12);
}

.alert i {
    font-size: 28px;
    margin-right: 18px;
    flex-shrink: 0;
}

.alert-warning {
    border-left-color: #ff9f40;
    background: linear-gradient(135deg, #fff9f0 0%, #fff3e0 100%);
}

.alert-warning i {
    color: #ff9f40;
}

.alert-danger {
    border-left-color: #ff4444;
    background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
}

.alert-danger i {
    color: #ff4444;
}

.alert-content {
    flex: 1;
}

.alert-content strong {
    display: block;
    margin-bottom: 6px;
    color: var(--black);
    font-weight: 700;
    font-size: 1rem;
}

.alert-content p {
    margin: 0;
    color: var(--medium-gray);
    font-size: 0.9rem;
    line-height: 1.5;
}

.alert-action {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.05);
    border-radius: 8px;
    color: var(--dark-gray);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.alert-action:hover {
    background: var(--primary-pink);
    color: var(--white);
    transform: scale(1.1);
}

.filter-select {
    padding: 10px 14px;
    border: 2px solid rgba(0,0,0,0.1);
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    background: var(--white);
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary-pink);
    box-shadow: 0 0 0 3px rgba(255,105,180,0.1);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background-color: var(--white);
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 520px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 24px;
    border-bottom: 2px solid rgba(255,105,180,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--black);
}

.modal-header h2 i {
    color: var(--primary-pink);
    margin-right: 8px;
}

.close {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: var(--dark-gray);
    transition: all 0.3s ease;
}

.close:hover {
    background: var(--primary-pink);
    color: var(--white);
    transform: rotate(90deg);
}

.modal form {
    padding: 24px;
}

.modal .form-group label {
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 8px;
}

.modal .form-control {
    border: 2px solid rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 10px 14px;
    transition: all 0.3s ease;
}

.modal .form-control:focus {
    outline: none;
    border-color: var(--primary-pink);
    box-shadow: 0 0 0 3px rgba(255,105,180,0.1);
}

.modal-footer {
    padding: 20px 24px;
    border-top: 2px solid rgba(255,105,180,0.1);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: rgba(0,0,0,0.02);
}

/* Inventory specific improvements */
.table-container {
    margin-top: 25px;
}

.table-header {
    margin-bottom: 0;
}

.table-header h2 {
    font-weight: 700;
}

.table-header h2 i {
    color: var(--primary-pink);
}
</style>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    filterTable();
});

document.getElementById('filterType').addEventListener('change', function() {
    filterTable();
});

document.getElementById('filterStatus').addEventListener('change', function() {
    filterTable();
});

function filterTable() {
    const searchValue = document.getElementById('searchInput').value.toLowerCase();
    const typeFilter = document.getElementById('filterType').value;
    const statusFilter = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('#inventoryTable tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const type = row.getAttribute('data-type');
        const status = row.getAttribute('data-status');
        
        const matchesSearch = text.includes(searchValue);
        const matchesType = !typeFilter || type === typeFilter;
        const matchesStatus = !statusFilter || status === statusFilter;
        
        row.style.display = (matchesSearch && matchesType && matchesStatus) ? '' : 'none';
    });
}

// Quick adjust modal
function quickAdjust(id, name) {
    document.getElementById('adjust_inventory_id').value = id;
    document.getElementById('adjust_item_name').value = name;
    document.getElementById('adjust_quantity').value = '';
    document.getElementById('adjust_reason').value = '';
    document.getElementById('adjustModal').style.display = 'block';
}

function closeAdjustModal() {
    document.getElementById('adjustModal').style.display = 'none';
}

function submitAdjustment(e) {
    e.preventDefault();
    
    const data = {
        inventory_id: document.getElementById('adjust_inventory_id').value,
        action: document.getElementById('adjust_action').value,
        quantity: document.getElementById('adjust_quantity').value,
        reason: document.getElementById('adjust_reason').value
    };
    
    fetch('../api/inventory_adjust.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Điều chỉnh thành công!');
            location.reload();
        } else {
            alert('Lỗi: ' + result.message);
        }
    })
    .catch(error => {
        alert('Có lỗi xảy ra: ' + error);
    });
}

// Delete item
function deleteItem(id, name) {
    if (confirm('Bạn có chắc muốn xóa "' + name + '"?')) {
        fetch('../api/inventory_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ inventory_id: id })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Xóa thành công!');
                location.reload();
            } else {
                alert('Lỗi: ' + result.message);
            }
        });
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('adjustModal');
    if (event.target == modal) {
        closeAdjustModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
