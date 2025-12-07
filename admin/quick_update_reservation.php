<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Reservation.php';

// Manager only
Auth::requireManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = (int)$_POST['reservation_id'];
    $new_status = $_POST['status'];
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'arrived', 'completed', 'cancelled', 'no_show'];
    if (!in_array($new_status, $valid_statuses)) {
        set_flash_message('Trạng thái không hợp lệ!', 'danger');
        redirect('reservations.php');
    }
    
    $reservation_manager = new Reservation();
    $reservation_data = $reservation_manager->getById($reservation_id);
    
    if (!$reservation_data) {
        set_flash_message('Không tìm thấy đơn đặt bàn!', 'danger');
        redirect('reservations.php');
    }
    
    // Update only status
    $update_data = [
        'table_id' => $reservation_data['table_id'],
        'customer_name' => $reservation_data['customer_name'],
        'customer_phone' => $reservation_data['customer_phone'],
        'guest_count' => $reservation_data['guest_count'],
        'reservation_date' => $reservation_data['reservation_date'],
        'reservation_time' => $reservation_data['reservation_time'],
        'status' => $new_status,
        'special_requests' => $reservation_data['special_requests']
    ];
    
    if ($reservation_manager->update($reservation_id, $update_data)) {
        // Use switch for PHP 7.x compatibility
        switch($new_status) {
            case 'confirmed':
                $status_text = 'Đã xác nhận';
                break;
            case 'arrived':
                $status_text = 'Đã đến';
                break;
            case 'completed':
                $status_text = 'Hoàn thành';
                break;
            case 'cancelled':
                $status_text = 'Đã hủy';
                break;
            case 'no_show':
                $status_text = 'Không đến';
                break;
            default:
                $status_text = 'Cập nhật';
        }
        
        set_flash_message("$status_text đơn đặt bàn thành công!", 'success');
    } else {
        set_flash_message('Lỗi khi cập nhật trạng thái!', 'danger');
    }
}

// Preserve filters when redirecting
$redirect_url = 'reservations.php';
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    $query_string = parse_url($referer, PHP_URL_QUERY);
    if ($query_string) {
        $redirect_url .= '?' . $query_string;
    }
}

redirect($redirect_url);
