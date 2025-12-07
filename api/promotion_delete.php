<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../classes/Promotion.php';

// Check if user is logged in
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
    echo json_encode(['success' => false, 'message' => 'Missing promotion ID']);
    exit;
}

try {
    $promotion = new Promotion();
    
    // Check if promotion exists
    $promo = $promotion->getById($id);
    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Promotion not found']);
        exit;
    }
    
    // Delete promotion
    $success = $promotion->delete($id);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Xóa khuyến mãi thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể xóa khuyến mãi'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
