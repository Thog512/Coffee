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
    $action = $input['action'] ?? null;
    $quantity = $input['quantity'] ?? null;
    $reason = $input['reason'] ?? '';
    
    // Validate input
    if (!$inventoryId || !$action || $quantity === null) {
        throw new Exception('Missing required fields');
    }
    
    // Validate action type
    $validActions = ['restock', 'use', 'waste', 'adjustment'];
    if (!in_array($action, $validActions)) {
        throw new Exception('Invalid action type');
    }
    
    // Convert quantity based on action
    $quantityChange = floatval($quantity);
    if ($action === 'use' || $action === 'waste') {
        $quantityChange = -abs($quantityChange); // Make negative for use/waste
    }
    
    $inventory = new Inventory();
    $userId = $_SESSION['user_id'];
    
    // Update quantity
    $success = $inventory->updateQuantity($inventoryId, $quantityChange, $action, $reason, $userId);
    
    if ($success) {
        // Get updated item
        $item = $inventory->getById($inventoryId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Điều chỉnh thành công',
            'data' => [
                'new_quantity' => $item['quantity'],
                'status' => $item['status']
            ]
        ]);
    } else {
        throw new Exception('Failed to update quantity');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
