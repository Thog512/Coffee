<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Inventory.php';

$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    header('Location: products.php');
    exit;
}

$productModel = new Product();
$inventoryModel = new Inventory();

$product = $productModel->getById($product_id);
$recipe = $inventoryModel->getProductRecipe($product_id);
$all_ingredients = $inventoryModel->getAll();
$product_cost = $inventoryModel->getProductCost($product_id);

// Handle add ingredient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_ingredient'])) {
    $result = $inventoryModel->addRecipeIngredient(
        $product_id,
        $_POST['ingredient_id'],
        $_POST['quantity'],
        $_POST['unit'],
        $_POST['notes'] ?? ''
    );
    
    if ($result) {
        header("Location: product_recipe.php?id=$product_id&success=1");
        exit;
    } else {
        $error = "Có lỗi xảy ra!";
    }
}

// Handle remove ingredient
if (isset($_GET['remove']) && isset($_GET['recipe_id'])) {
    $inventoryModel->removeRecipeIngredient($_GET['recipe_id']);
    header("Location: product_recipe.php?id=$product_id&removed=1");
    exit;
}

$page_title = "Công Thức - " . $product['product_name'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Hiniu Coffee</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-flask"></i> <?php echo $page_title; ?></h1>
                    <p>Quản lý nguyên liệu cho sản phẩm</p>
                </div>
                <div class="actions">
                    <a href="products.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Cập nhật công thức thành công!</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['removed'])): ?>
                <div class="alert alert-info">Đã xóa nguyên liệu khỏi công thức!</div>
            <?php endif; ?>
            
            <!-- Product Info -->
            <div class="product-info-card">
                <div class="product-details">
                    <div class="product-image-wrapper">
                        <?php if (!empty($product['image'])): 
                            // Xử lý đường dẫn hình ảnh
                            $image_path = $product['image'];
                            
                            // Nếu đường dẫn đã có "assets/images/products/", dùng trực tiếp
                            if (strpos($image_path, 'assets/images/products/') === 0) {
                                $image_url = APP_URL . '/' . $image_path;
                            } 
                            // Nếu chỉ có tên file, thêm đường dẫn đầy đủ
                            else {
                                $image_url = APP_URL . '/assets/images/products/' . basename($image_path);
                            }
                        ?>
                            <img src="<?php echo $image_url; ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                 class="product-img"
                                 onerror="handleImageError(this)">
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <i class="fas fa-coffee fa-3x"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info-text">
                        <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>
                        <p><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                        <div class="price-info">
                            <span class="label">Giá bán:</span>
                            <span class="price"><?php echo number_format($product['price']); ?>đ</span>
                        </div>
                    </div>
                </div>
                <div class="cost-summary">
                    <div class="cost-item">
                        <span>Giá vốn:</span>
                        <strong><?php echo number_format($product_cost); ?>đ</strong>
                    </div>
                    <div class="cost-item">
                        <span>Lợi nhuận:</span>
                        <strong class="profit"><?php echo number_format($product['price'] - $product_cost); ?>đ</strong>
                    </div>
                    <div class="cost-item">
                        <span>Tỷ suất:</span>
                        <strong class="margin">
                            <?php 
                            $margin = $product['price'] > 0 ? (($product['price'] - $product_cost) / $product['price'] * 100) : 0;
                            echo number_format($margin, 1); 
                            ?>%
                        </strong>
                    </div>
                </div>
            </div>
            
            <div class="recipe-container">
                <!-- Current Recipe -->
                <div class="recipe-list">
                    <h3>Công Thức Hiện Tại</h3>
                    
                    <?php if (empty($recipe)): ?>
                        <div class="empty-state">
                            <i class="fas fa-flask"></i>
                            <p>Chưa có nguyên liệu nào trong công thức</p>
                            <small>Thêm nguyên liệu bên phải để tạo công thức</small>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nguyên Liệu</th>
                                    <th>Số Lượng</th>
                                    <th>Đơn Vị</th>
                                    <th>Đơn Giá</th>
                                    <th>Thành Tiền</th>
                                    <th>Ghi Chú</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recipe as $item): 
                                    // Tính giá CÓ chuyển đổi đơn vị
                                    $item_cost = $inventoryModel->calculateIngredientCost(
                                        $item['quantity'],
                                        $item['unit'],
                                        $item['inventory_unit'],
                                        $item['cost_per_unit']
                                    );
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                    <td><?php echo number_format($item['quantity'], 2); ?></td>
                                    <td><?php echo $item['unit']; ?></td>
                                    <td><?php echo number_format($item['cost_per_unit']); ?>đ/<?php echo $item['inventory_unit']; ?></td>
                                    <td><strong><?php echo number_format($item_cost); ?>đ</strong></td>
                                    <td><?php echo htmlspecialchars($item['notes'] ?? ''); ?></td>
                                    <td>
                                        <a href="?id=<?php echo $product_id; ?>&remove=1&recipe_id=<?php echo $item['recipe_id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Xóa nguyên liệu này?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4"><strong>Tổng giá vốn:</strong></td>
                                    <td colspan="3"><strong><?php echo number_format($product_cost); ?>đ</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Add Ingredient Form -->
                <div class="add-ingredient-form">
                    <h3>Thêm Nguyên Liệu</h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Nguyên Liệu *</label>
                            <select name="ingredient_id" class="form-control" required>
                                <option value="">-- Chọn nguyên liệu --</option>
                                <?php foreach ($all_ingredients as $ingredient): ?>
                                    <option value="<?php echo $ingredient['inventory_id']; ?>">
                                        <?php echo htmlspecialchars($ingredient['item_name']); ?> 
                                        (Tồn: <?php echo $ingredient['quantity']; ?> <?php echo $ingredient['unit']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Số Lượng *</label>
                                <input type="number" name="quantity" class="form-control" 
                                       step="0.001" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Đơn Vị *</label>
                                <select name="unit" class="form-control" required>
                                    <option value="g">g (gram)</option>
                                    <option value="ml">ml (mililít)</option>
                                    <option value="kg">kg (kilogram)</option>
                                    <option value="l">l (lít)</option>
                                    <option value="pcs">pcs (cái)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Ghi Chú</label>
                            <textarea name="notes" class="form-control" rows="2" 
                                      placeholder="Ví dụ: Cà phê espresso, Sữa tươi nguyên kem..."></textarea>
                        </div>
                        
                        <button type="submit" name="add_ingredient" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Thêm Nguyên Liệu
                        </button>
                    </form>
                    
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Lưu ý:</strong>
                            <ul>
                                <li>Số lượng là cho <strong>1 sản phẩm</strong></li>
                                <li>Khi bán hàng, hệ thống tự động trừ nguyên liệu</li>
                                <li>Đơn vị phải khớp với đơn vị trong kho</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .product-info-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .product-details {
        display: flex;
        gap: 20px;
        flex: 1;
    }
    
    .product-details img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .price-info {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }
    
    .price-info .price {
        font-size: 1.2em;
        font-weight: 600;
        color: #8B4513;
    }
    
    .cost-summary {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 6px;
        min-width: 200px;
    }
    
    .cost-item {
        display: flex;
        justify-content: space-between;
        gap: 15px;
    }
    
    .cost-item .profit {
        color: #28a745;
    }
    
    .cost-item .margin {
        color: #007bff;
    }
    
    .recipe-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }
    
    .recipe-list, .add-ingredient-form {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .recipe-list h3, .add-ingredient-form h3 {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .info-box {
        margin-top: 20px;
        padding: 15px;
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        border-radius: 4px;
        display: flex;
        gap: 10px;
    }
    
    .info-box i {
        color: #2196f3;
        font-size: 20px;
    }
    
    .info-box ul {
        margin: 5px 0 0 0;
        padding-left: 20px;
    }
    
    .info-box li {
        margin: 5px 0;
        font-size: 0.9em;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .recipe-container {
            grid-template-columns: 1fr;
        }
        .product-info-card {
            flex-direction: column;
        }
        .cost-summary {
            width: 100%;
        }
    }
    
    @media (max-width: 768px) {
        .product-details {
            flex-direction: column;
        }
        .product-image-wrapper {
            width: 100%;
            height: 200px;
        }
    }
    </style>
    
    <script>
    function handleImageError(img) {
        img.style.display = 'none';
        const placeholder = document.createElement('div');
        placeholder.className = 'no-image-placeholder';
        placeholder.innerHTML = '<i class="fas fa-coffee fa-3x"></i>';
        img.parentElement.appendChild(placeholder);
    }
    </script>
</body>
</html>
