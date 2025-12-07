<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Reservation.php';

// Manager only
Auth::requireManager();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('ID đặt bàn không hợp lệ!', 'danger');
    redirect('reservations.php');
}

$reservation_id = (int)$_GET['id'];
$reservation_manager = new Reservation();

$reservation_info = $reservation_manager->getById($reservation_id);

if (!$reservation_info) {
    set_flash_message('Không tìm thấy đơn đặt bàn!', 'danger');
    redirect('reservations.php');
}

if ($reservation_manager->delete($reservation_id)) {
    set_flash_message('Xóa đơn đặt bàn của khách hàng "' . htmlspecialchars($reservation_info['customer_name']) . '" thành công!', 'success');
} else {
    set_flash_message('Lỗi khi xóa đơn đặt bàn!', 'danger');
}

redirect('reservations.php');
?>
