<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Product.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('ID sản phẩm không hợp lệ!', 'danger');
    redirect('products.php');
}

$product_id = (int)$_GET['id'];
$product = new Product();

// Get product info before deleting (for logging purposes)
$product_info = $product->getById($product_id);

if (!$product_info) {
    set_flash_message('Không tìm thấy sản phẩm!', 'danger');
    redirect('products.php');
}

// Delete the product
try {
    $delete_result = $product->delete($product_id);
    
    if ($delete_result) {
        set_flash_message('Xóa sản phẩm "' . htmlspecialchars($product_info['product_name']) . '" thành công!', 'success');
    } else {
        set_flash_message('Không thể xóa sản phẩm. Vui lòng thử lại!', 'danger');
        error_log("Failed to delete product ID: " . $product_id);
    }
} catch (Exception $e) {
    set_flash_message('Lỗi hệ thống: ' . $e->getMessage(), 'danger');
    error_log("Exception when deleting product: " . $e->getMessage());
}

redirect('products.php');
?>
