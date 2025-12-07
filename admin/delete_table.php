<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Table.php';

if (!is_logged_in()) {
    redirect('index.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('ID bàn không hợp lệ!', 'danger');
    redirect('tables.php');
}

$table_id = (int)$_GET['id'];
$table_manager = new Table();

$table_info = $table_manager->getById($table_id);

if (!$table_info) {
    set_flash_message('Không tìm thấy bàn!', 'danger');
    redirect('tables.php');
}

// In a real application, you should check if the table has active orders or reservations before deleting.
if ($table_manager->delete($table_id)) {
    set_flash_message('Xóa bàn "' . htmlspecialchars($table_info['table_number']) . '" thành công!', 'success');
} else {
    set_flash_message('Lỗi khi xóa bàn!', 'danger');
}

redirect('tables.php');
?>
