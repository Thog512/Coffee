<?php
/**
 * Recommendation Tracking API
 * Track when recommendations are shown, clicked, or added to cart
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once __DIR__ . '/../classes/Database.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get input data
    $input = $_POST;
    
    // If JSON body
    if (empty($input)) {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    $type = $input['type'] ?? '';
    $product_id = (int)($input['product_id'] ?? 0);
    $event_type = $input['event_type'] ?? 'shown'; // shown, clicked, added_to_cart
    
    // Validate input - silent fail for missing data
    if (empty($type) || empty($product_id)) {
        // Just return success without tracking
        echo json_encode([
            'success' => true,
            'message' => 'No tracking data provided',
            'skipped' => true
        ]);
        exit;
    }
    
    // Validate type
    if (!in_array($type, ['cart', 'customer', 'time', 'trending', 'together', 'popular'])) {
        throw new Exception('Invalid recommendation type: ' . $type);
    }
    
    // Validate product ID
    if ($product_id <= 0) {
        throw new Exception('Invalid product ID: ' . $product_id);
    }
    
    // Validate event type
    if (!in_array($event_type, ['shown', 'clicked', 'added_to_cart'])) {
        throw new Exception('Invalid event type: ' . $event_type);
    }
    
    // Get session and user info
    $session_id = session_id();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Insert tracking data
    $query = "INSERT INTO recommendation_analytics 
              (recommendation_type, product_id, event_type, session_id, user_agent, ip_address) 
              VALUES (:type, :product_id, :event_type, :session_id, :user_agent, :ip_address)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':type' => $type,
        ':product_id' => $product_id,
        ':event_type' => $event_type,
        ':session_id' => $session_id,
        ':user_agent' => $user_agent,
        ':ip_address' => $ip_address
    ]);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Tracking recorded',
        'data' => [
            'type' => $type,
            'product_id' => $product_id,
            'event_type' => $event_type
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
