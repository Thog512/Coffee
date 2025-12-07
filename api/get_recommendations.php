<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../classes/AIRecommendation.php';

$ai = new AIRecommendation();

// Get request type
$type = $_GET['type'] ?? 'cart';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

$recommendations = [];

try {
    switch ($type) {
        case 'cart':
            // Get recommendations based on cart items
            $cart_items = isset($_POST['cart']) ? json_decode($_POST['cart'], true) : [];
            $recommendations = $ai->getRecommendationsForCart($cart_items, $limit);
            break;
            
        case 'customer':
            // Get recommendations for specific customer
            $customer_name = $_GET['customer'] ?? '';
            if ($customer_name) {
                $recommendations = $ai->getRecommendationsForCustomer($customer_name, $limit);
            } else {
                $recommendations = $ai->getPopularProducts($limit);
            }
            break;
            
        case 'popular':
            // Get popular products
            $recommendations = $ai->getPopularProducts($limit);
            break;
            
        case 'trending':
            // Get trending products
            $recommendations = $ai->getTrendingProducts($limit);
            break;
            
        case 'time':
            // Get products by time of day
            $hour = isset($_GET['hour']) ? (int)$_GET['hour'] : null;
            $recommendations = $ai->getProductsByTimeOfDay($hour, $limit);
            break;
            
        case 'together':
            // Get frequently bought together
            $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
            if ($product_id > 0) {
                $recommendations = $ai->getFrequentlyBoughtTogether($product_id, $limit);
            }
            break;
            
        default:
            $recommendations = $ai->getPopularProducts($limit);
    }
    
    echo json_encode([
        'success' => true,
        'type' => $type,
        'recommendations' => $recommendations,
        'count' => count($recommendations)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
