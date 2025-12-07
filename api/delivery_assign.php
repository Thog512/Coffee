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
$shipper_id = $_POST['shipper_id'] ?? null;

if (!$delivery_id || !$shipper_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $delivery = new Delivery();
    $success = $delivery->assignDelivery($delivery_id, $shipper_id, $_SESSION['user_id']);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Phân công shipper thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể phân công shipper']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
