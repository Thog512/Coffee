<?php 
$page_title = 'Quản Lý Danh Mục';
include 'includes/header.php';

require_once __DIR__ . '/../classes/Category.php';

$category_manager = new Category();
$categories = $category_manager->getAll();
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Quản Lý Danh Mục</h1>
        <a href="category_form.php" class="btn btn-primary">+ Thêm Danh Mục Mới</a>
    </div>
    <p>Tổ chức các sản phẩm của bạn bằng cách quản lý danh mục.</p>
</div>

<?php display_flash_message(); ?>

<div class="category-grid">
    <?php if (!empty($categories)):
        foreach ($categories as $category):
            $status = htmlspecialchars($category['status'] ?? 'inactive');
            $badge_class = ($status == 'active') ? 'badge-success' : 'badge-secondary';
            $image_path = !empty($category['image']) ? 'categories/' . $category['image'] : 'logo.png';
    ?>
            <div class="category-card">
                <div class="category-card-header">
                    <img src="<?php echo APP_URL . '/assets/images/' . htmlspecialchars($image_path); ?>" 
                         alt="<?php echo htmlspecialchars($category['category_name']); ?>"
                         onerror="this.src='<?php echo APP_URL; ?>/assets/images/logo.png'">
                    <div class="category-card-actions">
                        <a href="category_form.php?id=<?php echo $category['category_id']; ?>" class="btn-action-icon btn-edit"><i class="fas fa-edit"></i></a>
                        <a href="delete_category.php?id=<?php echo $category['category_id']; ?>" class="btn-action-icon btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này không?');"><i class="fas fa-trash"></i></a>
                    </div>
                    <span class="badge <?php echo $badge_class; ?>"><?php echo $status == 'active' ? 'Hoạt Động' : 'Không Hoạt Động'; ?></span>
                </div>
                <div class="category-card-body">
                    <h5 class="category-card-title"><?php echo htmlspecialchars($category['category_name']); ?></h5>
                    <p class="category-card-description"><?php echo htmlspecialchars($category['description']); ?></p>
                </div>
                <div class="category-card-footer">
                    <span>Thứ tự: <strong><?php echo htmlspecialchars($category['display_order']); ?></strong></span>
                    <span>Sản phẩm: <strong><?php echo htmlspecialchars($category['product_count']); ?></strong></span>
                </div>
            </div>
    <?php 
        endforeach;
    else:
    ?>
        <div class="col-12">
            <p class="text-center">Chưa có danh mục nào.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
