<?php 
$page_title = 'Quản Lý Sản Phẩm';
include 'includes/header.php'; 

// Only managers can access
require_once __DIR__ . '/../classes/Auth.php';
Auth::requireManager();

require_once __DIR__ . '/../classes/Product.php';

$product = new Product();
$products = $product->getAll();

// Calculate statistics
$total_products = count($products);
$active_products = count(array_filter($products, function($p) { return $p['status'] == 'active'; }));
$inactive_products = count(array_filter($products, function($p) { return $p['status'] == 'inactive'; }));
$out_of_stock = count(array_filter($products, function($p) { return $p['status'] == 'out_of_stock'; }));
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Quản Lý Sản Phẩm</h1>
        <a href="product_form.php" class="btn btn-primary">+ Thêm Sản Phẩm Mới</a>
    </div>
    <p>Quản lý tất cả sản phẩm trong quán cà phê của bạn.</p>
</div>

<?php display_flash_message(); ?>

<!-- Statistics Summary -->
<div class="stats-summary">
    <div class="stat-box total">
        <h4>Tổng Sản Phẩm</h4>
        <div class="stat-value"><?php echo $total_products; ?></div>
    </div>
    <div class="stat-box active">
        <h4>Đang Hoạt Động</h4>
        <div class="stat-value"><?php echo $active_products; ?></div>
    </div>
    <div class="stat-box inactive">
        <h4>Không Hoạt Động</h4>
        <div class="stat-value"><?php echo $inactive_products; ?></div>
    </div>
    <div class="stat-box">
        <h4>Hết Hàng</h4>
        <div class="stat-value"><?php echo $out_of_stock; ?></div>
    </div>
</div>

<!-- Toolbar -->
<div class="product-toolbar">
    <div class="view-toggle">
        <button class="btn active" onclick="switchView('table')" id="btn-table">
            <i class="fas fa-list"></i> Xem Bảng
        </button>
        <button class="btn" onclick="switchView('grid')" id="btn-grid">
            <i class="fas fa-th"></i> Xem Lưới
        </button>
    </div>
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Tìm kiếm sản phẩm..." onkeyup="searchProducts()">
    </div>
</div>

<!-- Table View -->
<div id="table-view" class="table-view">
    <div class="product-table">
        <table>
            <thead>
                <tr>
                    <th>Hình Ảnh</th>
                    <th>Tên Sản Phẩm</th>
                    <th>Danh Mục</th>
                    <th>Giá</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <?php if ($products): ?>
                    <?php foreach ($products as $product_item): ?>
                        <tr class="product-row" data-name="<?php echo strtolower(htmlspecialchars($product_item['product_name'])); ?>" data-category="<?php echo strtolower(htmlspecialchars($product_item['category_name'] ?? '')); ?>">
                            <td>
                                <?php 
                                    $image_path = !empty($product_item['image']) ? $product_item['image'] : 'logo.png';
                                ?>
                                <img src="<?php echo APP_URL . '/assets/images/products/' . htmlspecialchars($image_path); ?>" 
                                     class="product-img-small" 
                                     alt="<?php echo htmlspecialchars($product_item['product_name']); ?>"
                                     onerror="this.src='<?php echo APP_URL; ?>/assets/images/logo.png'">
                            </td>
                            <td>
                                <div class="product-name"><?php echo htmlspecialchars($product_item['product_name']); ?></div>
                            </td>
                            <td>
                                <div class="product-category"><?php echo htmlspecialchars($product_item['category_name'] ?? 'N/A'); ?></div>
                            </td>
                            <td>
                                <strong><?php echo format_currency($product_item['price']); ?></strong>
                            </td>
                            <td>
                                <?php 
                                    $status = htmlspecialchars($product_item['status']);
                                    $badge_class = 'badge-secondary';
                                    if ($status == 'active') {
                                        $badge_class = 'badge-success';
                                    } elseif ($status == 'inactive') {
                                        $badge_class = 'badge-warning';
                                    } elseif ($status == 'out_of_stock') {
                                        $badge_class = 'badge-danger';
                                    }
                                ?>
                                <?php 
                                    $status_text = $status;
                                    if ($status == 'active') $status_text = 'Đang Bán';
                                    elseif ($status == 'inactive') $status_text = 'Ngừng Bán';
                                    elseif ($status == 'out_of_stock') $status_text = 'Hết Hàng';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="product_recipe.php?id=<?php echo $product_item['product_id']; ?>" class="btn-action btn-recipe" title="Công thức">
                                        <i class="fas fa-flask"></i> Công thức
                                    </a>
                                    <a href="product_form.php?id=<?php echo $product_item['product_id']; ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <a href="delete_product.php?id=<?php echo $product_item['product_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                        <i class="fas fa-trash"></i> Xóa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <p>Không tìm thấy sản phẩm nào. Vui lòng tạo bảng 'products' và 'categories' trong cơ sở dữ liệu.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Grid View -->
<div id="grid-view" class="grid-view" style="display: none;">
    <div class="product-grid" id="productGrid">
        <?php if ($products): ?>
            <?php foreach ($products as $product_item): ?>
                <div class="product-card" data-name="<?php echo strtolower(htmlspecialchars($product_item['product_name'])); ?>" data-category="<?php echo strtolower(htmlspecialchars($product_item['category_name'] ?? '')); ?>">
                    <?php 
                        $image_path = !empty($product_item['image']) ? $product_item['image'] : 'logo.png';
                    ?>
                    <img src="<?php echo APP_URL . '/assets/images/products/' . htmlspecialchars($image_path); ?>" 
                         class="product-card-img" 
                         alt="<?php echo htmlspecialchars($product_item['product_name']); ?>"
                         onerror="this.src='<?php echo APP_URL; ?>/assets/images/logo.png'">
                    <div class="product-card-body">
                        <div class="product-card-title"><?php echo htmlspecialchars($product_item['product_name']); ?></div>
                        <div class="product-card-category"><?php echo htmlspecialchars($product_item['category_name'] ?? 'N/A'); ?></div>
                        <div class="product-card-price"><?php echo format_currency($product_item['price']); ?></div>
                        <div class="product-card-footer">
                            <?php 
                                $status = htmlspecialchars($product_item['status']);
                                $badge_class = 'badge-secondary';
                                if ($status == 'active') {
                                    $badge_class = 'badge-success';
                                } elseif ($status == 'inactive') {
                                    $badge_class = 'badge-warning';
                                } elseif ($status == 'out_of_stock') {
                                    $badge_class = 'badge-danger';
                                }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
                            <div class="action-buttons">
                                <a href="product_recipe.php?id=<?php echo $product_item['product_id']; ?>" class="btn-action btn-recipe" title="Công thức">
                                    <i class="fas fa-flask"></i>
                                </a>
                                <a href="product_form.php?id=<?php echo $product_item['product_id']; ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_product.php?id=<?php echo $product_item['product_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                <p>Không tìm thấy sản phẩm nào.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function switchView(view) {
    const tableView = document.getElementById('table-view');
    const gridView = document.getElementById('grid-view');
    const btnTable = document.getElementById('btn-table');
    const btnGrid = document.getElementById('btn-grid');
    
    if (view === 'table') {
        tableView.style.display = 'block';
        gridView.style.display = 'none';
        btnTable.classList.add('active');
        btnGrid.classList.remove('active');
    } else {
        tableView.style.display = 'none';
        gridView.style.display = 'block';
        btnTable.classList.remove('active');
        btnGrid.classList.add('active');
    }
}

function searchProducts() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    
    // Search in table view
    const tableRows = document.querySelectorAll('#productTableBody .product-row');
    tableRows.forEach(row => {
        const name = row.getAttribute('data-name');
        const category = row.getAttribute('data-category');
        if (name.includes(filter) || category.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Search in grid view
    const gridCards = document.querySelectorAll('#productGrid .product-card');
    gridCards.forEach(card => {
        const name = card.getAttribute('data-name');
        const category = card.getAttribute('data-category');
        if (name.includes(filter) || category.includes(filter)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<style>
.btn-recipe {
    background-color: #9c27b0;
    color: white;
}

.btn-recipe:hover {
    background-color: #7b1fa2;
    color: white;
}
</style>

<?php include 'includes/footer.php'; ?>
