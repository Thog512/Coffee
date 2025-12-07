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

$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing shipper ID']);
    exit;
}

try {
    $delivery = new Delivery();
    $success = $delivery->deleteShipper($id);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Xóa shipper thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa shipper']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
