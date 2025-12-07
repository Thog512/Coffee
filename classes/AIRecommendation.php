<?php
require_once __DIR__ . '/Database.php';

class AIRecommendation {
    private $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    /**
     * Get product recommendations based on cart items
     * Uses collaborative filtering and category-based recommendations
     */
    public function getRecommendationsForCart($cart_items, $limit = 5) {
        if (empty($cart_items)) {
            return $this->getPopularProducts($limit);
        }

        $product_ids = array_column($cart_items, 'product_id');
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

        // Get categories of products in cart
        $query = "SELECT DISTINCT category_id FROM products WHERE product_id IN ($placeholders)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($product_ids);
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($categories)) {
            return $this->getPopularProducts($limit);
        }

        // Get products from same categories that are not in cart
        $cat_placeholders = implode(',', array_fill(0, count($categories), '?'));
        $query = "SELECT p.*, c.category_name,
                  (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.product_id) as order_count,
                  (SELECT AVG(oi.quantity) FROM order_items oi WHERE oi.product_id = p.product_id) as avg_quantity
                  FROM products p
                  JOIN categories c ON p.category_id = c.category_id
                  WHERE p.category_id IN ($cat_placeholders)
                  AND p.product_id NOT IN ($placeholders)
                  AND p.status = 'active'
                  ORDER BY order_count DESC, avg_quantity DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $params = array_merge($categories, $product_ids);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recommendations based on customer's order history
     */
    public function getRecommendationsForCustomer($customer_name, $limit = 5) {
        // Get products customer has ordered before
        $query = "SELECT p.*, c.category_name,
                  COUNT(oi.order_item_id) as times_ordered,
                  MAX(o.created_at) as last_ordered
                  FROM products p
                  JOIN order_items oi ON p.product_id = oi.product_id
                  JOIN orders o ON oi.order_id = o.order_id
                  JOIN categories c ON p.category_id = c.category_id
                  WHERE o.customer_name = :customer_name
                  AND p.status = 'active'
                  GROUP BY p.product_id
                  ORDER BY times_ordered DESC, last_ordered DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_name', $customer_name);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($favorites)) {
            return $this->getPopularProducts($limit);
        }
        
        return $favorites;
    }

    /**
     * Get frequently bought together products
     */
    public function getFrequentlyBoughtTogether($product_id, $limit = 4) {
        $query = "SELECT p.*, c.category_name, COUNT(*) as frequency
                  FROM products p
                  JOIN categories c ON p.category_id = c.category_id
                  JOIN order_items oi1 ON p.product_id = oi1.product_id
                  JOIN order_items oi2 ON oi1.order_id = oi2.order_id
                  WHERE oi2.product_id = :product_id
                  AND oi1.product_id != :product_id
                  AND p.status = 'active'
                  GROUP BY p.product_id
                  ORDER BY frequency DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get popular products (best sellers)
     */
    public function getPopularProducts($limit = 5) {
        $query = "SELECT p.*, c.category_name,
                  COUNT(oi.order_item_id) as times_sold,
                  SUM(oi.quantity) as total_quantity
                  FROM products p
                  JOIN categories c ON p.category_id = c.category_id
                  LEFT JOIN order_items oi ON p.product_id = oi.product_id
                  WHERE p.status = 'active'
                  GROUP BY p.product_id
                  ORDER BY times_sold DESC, total_quantity DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get trending products (popular in last 7 days)
     */
    public function getTrendingProducts($limit = 5) {
        $query = "SELECT p.*, c.category_name,
                  COUNT(oi.order_item_id) as recent_orders,
                  SUM(oi.quantity) as recent_quantity
                  FROM products p
                  JOIN categories c ON p.category_id = c.category_id
                  JOIN order_items oi ON p.product_id = oi.product_id
                  JOIN orders o ON oi.order_id = o.order_id
                  WHERE p.status = 'active'
                  AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  GROUP BY p.product_id
                  ORDER BY recent_orders DESC, recent_quantity DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get products by time of day preference
     */
    public function getProductsByTimeOfDay($hour = null, $limit = 5) {
        if ($hour === null) {
            $hour = date('H');
        }

        // Morning (6-11): Coffee, Breakfast items
        // Afternoon (12-17): Lunch, Cold drinks
        // Evening (18-23): Desserts, Light snacks
        
        $time_period = 'morning';
        if ($hour >= 12 && $hour < 18) {
            $time_period = 'afternoon';
        } elseif ($hour >= 18) {
            $time_period = 'evening';
        }

        $query = "SELECT p.*, c.category_name,
                  COUNT(oi.order_item_id) as orders_in_period
                  FROM products p
                  JOIN categories c ON p.category_id = c.category_id
                  JOIN order_items oi ON p.product_id = oi.product_id
                  JOIN orders o ON oi.order_id = o.order_id
                  WHERE p.status = 'active'
                  AND HOUR(o.created_at) BETWEEN :start_hour AND :end_hour
                  GROUP BY p.product_id
                  ORDER BY orders_in_period DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        
        if ($time_period == 'morning') {
            $stmt->bindValue(':start_hour', 6, PDO::PARAM_INT);
            $stmt->bindValue(':end_hour', 11, PDO::PARAM_INT);
        } elseif ($time_period == 'afternoon') {
            $stmt->bindValue(':start_hour', 12, PDO::PARAM_INT);
            $stmt->bindValue(':end_hour', 17, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':start_hour', 18, PDO::PARAM_INT);
            $stmt->bindValue(':end_hour', 23, PDO::PARAM_INT);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get AI insights for dashboard
     */
    public function getAIInsights() {
        $insights = [];

        // 1. Peak hours analysis
        $query = "SELECT HOUR(created_at) as hour, COUNT(*) as order_count, SUM(total_amount) as revenue
                  FROM orders
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY HOUR(created_at)
                  ORDER BY order_count DESC
                  LIMIT 3";
        $stmt = $this->conn->query($query);
        $peak_hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $insights['peak_hours'] = $peak_hours;

        // 2. Customer behavior patterns
        $query = "SELECT 
                  AVG(total_amount) as avg_order_value,
                  AVG(item_count) as avg_items_per_order
                  FROM (
                      SELECT o.order_id, o.total_amount, COUNT(oi.order_item_id) as item_count
                      FROM orders o
                      JOIN order_items oi ON o.order_id = oi.order_id
                      WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      GROUP BY o.order_id
                  ) as order_stats";
        $stmt = $this->conn->query($query);
        $insights['customer_behavior'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Category performance
        $query = "SELECT c.category_name, 
                  COUNT(DISTINCT o.order_id) as orders,
                  SUM(oi.subtotal) as revenue,
                  SUM(oi.quantity) as items_sold
                  FROM categories c
                  JOIN products p ON c.category_id = p.category_id
                  JOIN order_items oi ON p.product_id = oi.product_id
                  JOIN orders o ON oi.order_id = o.order_id
                  WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY c.category_id
                  ORDER BY revenue DESC";
        $stmt = $this->conn->query($query);
        $insights['category_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Product recommendations to promote
        $query = "SELECT p.product_name, 
                  COUNT(oi.order_item_id) as order_count,
                  SUM(oi.subtotal) as revenue,
                  p.price
                  FROM products p
                  LEFT JOIN order_items oi ON p.product_id = oi.product_id
                  WHERE p.status = 'active'
                  GROUP BY p.product_id
                  HAVING order_count < 5 OR order_count IS NULL
                  ORDER BY p.price DESC
                  LIMIT 5";
        $stmt = $this->conn->query($query);
        $insights['underperforming_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $insights;
    }

    /**
     * Calculate recommendation score for a product
     */
    private function calculateScore($product, $factors = []) {
        $score = 0;
        
        // Base score from order count
        $score += isset($product['order_count']) ? $product['order_count'] * 2 : 0;
        
        // Boost for recent orders
        if (isset($product['last_ordered'])) {
            $days_ago = (time() - strtotime($product['last_ordered'])) / (60 * 60 * 24);
            $score += max(0, 10 - $days_ago);
        }
        
        // Boost for high average quantity
        $score += isset($product['avg_quantity']) ? $product['avg_quantity'] * 1.5 : 0;
        
        // Featured products get a boost
        if (isset($product['is_featured']) && $product['is_featured']) {
            $score += 5;
        }
        
        return $score;
    }
}
?>
