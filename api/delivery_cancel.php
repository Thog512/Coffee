<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../classes/Delivery.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$delivery_id = $_POST['delivery_id'] ?? null;
$reason = $_POST['reason'] ?? 'Không có lý do';

if (!$delivery_id) {
    echo json_encode(['success' => false, 'message' => 'Missing delivery ID']);
    exit;
}

try {
    $delivery = new Delivery();
    $success = $delivery->cancelDelivery($delivery_id, 'admin', $reason, $_SESSION['user_id']);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Hủy đơn giao hàng thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
