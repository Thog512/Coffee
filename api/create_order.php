<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/session.php';
    require_once __DIR__ . '/../classes/Order.php';
    require_once __DIR__ . '/../classes/Table.php';

    // Get the posted data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!$data || !isset($data['cart']) || empty($data['cart'])) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu đơn hàng không hợp lệ.']);
        exit;
    }

$order_manager = new Order();

// Prepare order data
$subtotal = 0;
foreach ($data['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$orderData = [
    'customer_id' => null, // Can be implemented later
    'customer_name' => $data['customer_name'] ?? 'Khách lẻ',
    'customer_phone' => $data['customer_phone'] ?? '',
    'customer_address' => '',
    'order_type' => $data['order_type'],
    'table_id' => ($data['order_type'] == 'dine_in' && !empty($data['table_id'])) ? $data['table_id'] : null,
    'subtotal' => $subtotal,
    'discount' => 0, // Placeholder
    'tax' => 0, // Placeholder
    'delivery_fee' => 0, // Placeholder
    'total_amount' => $subtotal, // For now, total is same as subtotal
    'created_by' => $_SESSION['user_id'] ?? 1 // Default to admin if not logged in
];

$items = [];
foreach ($data['cart'] as $item) {
    $items[] = [
        'product_id' => $item['id'],
        'product_name' => $item['name'],
        'quantity' => $item['quantity'],
        'unit_price' => $item['price'],
        'subtotal' => $item['price'] * $item['quantity']
    ];
}

    // Create order
    $orderId = $order_manager->create($orderData, $items);

    if ($orderId) {
        // If dine-in, update table status to 'occupied'
        if ($orderData['order_type'] == 'dine_in' && $orderData['table_id']) {
            $table_manager = new Table();
            $table_manager->updateStatus($orderData['table_id'], 'occupied');
        }
        echo json_encode(['success' => true, 'message' => 'Tạo đơn hàng thành công!', 'order_id' => $orderId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể tạo đơn hàng. Vui lòng thử lại.']);
    }
    
} catch (Exception $e) {
    error_log('Create order error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>
