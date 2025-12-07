<?php 
$page_title = 'Quản Lý Danh Mục';
include 'includes/header.php'; 

require_once __DIR__ . '/../classes/Category.php';

$category_manager = new Category();

$edit_mode = false;
$category_data = null;

// Check if editing existing category
if (isset($_GET['id'])) {
    $edit_mode = true;
    $category_id = (int)$_GET['id'];
    $category_data = $category_manager->getById($category_id);
    
    if (!$category_data) {
        set_flash_message('Không tìm thấy danh mục!', 'danger');
        redirect('categories.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'category_name' => trim($_POST['category_name']),
        'description' => trim($_POST['description']),
        'image' => trim($_POST['image']),
        'display_order' => (int)$_POST['display_order'],
        'status' => $_POST['status']
    ];
    
    if (empty($data['category_name'])) {
        set_flash_message('Tên danh mục không được để trống.', 'danger');
    } else {
        if ($edit_mode) {
            if ($category_manager->update($category_id, $data)) {
                set_flash_message('Cập nhật danh mục thành công!', 'success');
                redirect('categories.php');
            } else {
                set_flash_message('Lỗi khi cập nhật danh mục!', 'danger');
            }
        } else {
            if ($category_manager->create($data)) {
                set_flash_message('Thêm danh mục thành công!', 'success');
                redirect('categories.php');
            } else {
                set_flash_message('Lỗi khi thêm danh mục!', 'danger');
            }
        }
    }
}
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><?php echo $edit_mode ? 'Chỉnh Sửa Danh Mục' : 'Thêm Danh Mục Mới'; ?></h1>
        <a href="categories.php" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-group mb-3">
                <label for="category_name" class="form-label">Tên Danh Mục <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="category_name" name="category_name" 
                       value="<?php echo $edit_mode ? htmlspecialchars($category_data['category_name']) : ''; ?>" required>
            </div>

            <div class="form-group mb-3">
                <label for="description" class="form-label">Mô Tả</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $edit_mode ? htmlspecialchars($category_data['description']) : ''; ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="display_order" class="form-label">Thứ Tự Hiển Thị</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" 
                               value="<?php echo $edit_mode ? $category_data['display_order'] : '0'; ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="status" class="form-label">Trạng Thái</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active" <?php echo ($edit_mode && $category_data['status'] == 'active') ? 'selected' : ''; ?>>Hoạt động</option>
                            <option value="inactive" <?php echo ($edit_mode && $category_data['status'] == 'inactive') ? 'selected' : ''; ?>>Không hoạt động</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="image" class="form-label">Tên File Hình Ảnh</label>
                <input type="text" class="form-control" id="image" name="image" 
                       value="<?php echo $edit_mode ? htmlspecialchars($category_data['image']) : ''; ?>" 
                       placeholder="vd: category_coffee.jpg">
                <small class="form-text text-muted">Lưu file hình ảnh vào thư mục assets/images/</small>
            </div>

            <?php if ($edit_mode && !empty($category_data['image'])): ?>
                <div class="mt-3">
                    <label class="form-label">Hình Ảnh Hiện Tại:</label>
                    <img src="<?php echo APP_URL . '/assets/images/categories/' . htmlspecialchars($category_data['image']); ?>" 
                         alt="<?php echo htmlspecialchars($category_data['category_name']); ?>" 
                         class="img-fluid rounded" style="max-height: 150px;">
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_mode ? 'Cập Nhật' : 'Thêm Mới'; ?>
                </button>
                <a href="categories.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
