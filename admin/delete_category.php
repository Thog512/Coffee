<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Category.php';

if (!is_logged_in()) {
    redirect('index.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('ID danh mục không hợp lệ!', 'danger');
    redirect('categories.php');
}

$category_id = (int)$_GET['id'];
$category_manager = new Category();

$category_info = $category_manager->getById($category_id);

if (!$category_info) {
    set_flash_message('Không tìm thấy danh mục!', 'danger');
    redirect('categories.php');
}

if ($category_manager->delete($category_id)) {
    set_flash_message('Xóa danh mục "' . htmlspecialchars($category_info['category_name']) . '" thành công!', 'success');
} else {
    set_flash_message('Không thể xóa danh mục này vì vẫn còn sản phẩm thuộc về nó. Vui lòng chuyển các sản phẩm sang danh mục khác trước khi xóa.', 'danger');
}

redirect('categories.php');
?>
