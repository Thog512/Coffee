<?php 
$page_title = 'Quản Lý Sản Phẩm';
include 'includes/header.php'; 

require_once __DIR__ . '/../classes/Product.php';

$product = new Product();
$categories = $product->getAllCategories();

$edit_mode = false;
$product_data = null;

// Check if editing existing product
if (isset($_GET['id'])) {
    $edit_mode = true;
    $product_id = (int)$_GET['id'];
    $product_data = $product->getById($product_id);
    
    if (!$product_data) {
        set_flash_message('Không tìm thấy sản phẩm!', 'danger');
        redirect('products.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'category_id' => (int)$_POST['category_id'],
        'product_name' => trim($_POST['product_name']),
        'description' => trim($_POST['description']),
        'price' => (float)$_POST['price'],
        'image' => trim($_POST['image']),
        'status' => $_POST['status'],
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0
    ];
    
    // Validation
    $errors = [];
    if (empty($data['product_name'])) {
        $errors[] = 'Tên sản phẩm không được để trống.';
    }
    if ($data['price'] <= 0) {
        $errors[] = 'Giá sản phẩm phải lớn hơn 0.';
    }
    if (empty($data['category_id'])) {
        $errors[] = 'Vui lòng chọn danh mục.';
    }
    
    if (empty($errors)) {
        if ($edit_mode) {
            // Update existing product
            if ($product->update($product_id, $data)) {
                set_flash_message('Cập nhật sản phẩm thành công!', 'success');
                redirect('products.php');
            } else {
                set_flash_message('Lỗi khi cập nhật sản phẩm!', 'danger');
            }
        } else {
            // Create new product
            if ($product->create($data)) {
                set_flash_message('Thêm sản phẩm thành công!', 'success');
                redirect('products.php');
            } else {
                set_flash_message('Lỗi khi thêm sản phẩm!', 'danger');
            }
        }
    } else {
        foreach ($errors as $error) {
            set_flash_message($error, 'danger');
        }
    }
}
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><?php echo $edit_mode ? 'Chỉnh Sửa Sản Phẩm' : 'Thêm Sản Phẩm Mới'; ?></h1>
        <a href="products.php" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group mb-3">
                        <label for="product_name" class="form-label">Tên Sản Phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="product_name" name="product_name" 
                               value="<?php echo $edit_mode ? htmlspecialchars($product_data['product_name']) : ''; ?>" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="description" class="form-label">Mô Tả</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $edit_mode ? htmlspecialchars($product_data['description']) : ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="price" class="form-label">Giá (VNĐ) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="price" name="price" step="1000" min="0"
                                       value="<?php echo $edit_mode ? $product_data['price'] : ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="category_id" class="form-label">Danh Mục <span class="text-danger">*</span></label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">-- Chọn danh mục --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>" 
                                                <?php echo ($edit_mode && $product_data['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="image" class="form-label">Tên File Hình Ảnh</label>
                        <input type="text" class="form-control" id="image" name="image" 
                               value="<?php echo $edit_mode ? htmlspecialchars($product_data['image']) : ''; ?>" 
                               placeholder="vd: coffee.jpg">
                        <small class="form-text text-muted">Lưu file hình ảnh vào thư mục assets/images/</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Cài Đặt</h5>
                            
                            <div class="form-group mb-3">
                                <label for="status" class="form-label">Trạng Thái</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?php echo ($edit_mode && $product_data['status'] == 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                                    <option value="inactive" <?php echo ($edit_mode && $product_data['status'] == 'inactive') ? 'selected' : ''; ?>>Không hoạt động</option>
                                    <option value="out_of_stock" <?php echo ($edit_mode && $product_data['status'] == 'out_of_stock') ? 'selected' : ''; ?>>Hết hàng</option>
                                </select>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1"
                                       <?php echo ($edit_mode && $product_data['is_featured']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">
                                    Sản phẩm nổi bật
                                </label>
                            </div>

                            <?php if ($edit_mode && !empty($product_data['image'])): ?>
                                <div class="mt-3">
                                    <label class="form-label">Hình Ảnh Hiện Tại:</label>
                                    <img src="<?php echo APP_URL . '/assets/images/products/' . htmlspecialchars($product_data['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product_data['product_name']); ?>" 
                                         class="img-fluid rounded" style="max-height: 200px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_mode ? 'Cập Nhật Sản Phẩm' : 'Thêm Sản Phẩm'; ?>
                </button>
                <a href="products.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
