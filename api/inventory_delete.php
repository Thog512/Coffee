<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Inventory.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input');
    }
    
    $inventoryId = $input['inventory_id'] ?? null;
    
    if (!$inventoryId) {
        throw new Exception('Missing inventory ID');
    }
    
    $inventory = new Inventory();
    
    // Check if item exists
    $item = $inventory->getById($inventoryId);
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    // Delete item
    $success = $inventory->delete($inventoryId);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Xóa thành công'
        ]);
    } else {
        throw new Exception('Failed to delete item');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
