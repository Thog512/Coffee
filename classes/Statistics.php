<?php
require_once __DIR__ . '/Database.php';

class Statistics {
    private $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function getTotalRevenue() {
        $query = "SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'cancelled'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getTodayRevenue() {
        $query = "SELECT SUM(total_amount) as total FROM orders 
                  WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getTotalOrders() {
        $query = "SELECT COUNT(*) as total FROM orders";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getTodayOrders() {
        $query = "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getTotalProducts() {
        $query = "SELECT COUNT(*) as total FROM products";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getTotalCustomers() {
        $query = "SELECT COUNT(DISTINCT customer_name) as total FROM orders WHERE customer_name != 'Khách lẻ'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getRecentOrders($limit = 5) {
        $query = "SELECT o.*, t.table_number 
                  FROM orders o
                  LEFT JOIN tables t ON o.table_id = t.table_id
                  ORDER BY o.created_at DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopSellingProducts($limit = 5) {
        $query = "SELECT oi.product_name, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as revenue
                  FROM order_items oi
                  JOIN orders o ON oi.order_id = o.order_id
                  WHERE o.order_status != 'cancelled'
                  GROUP BY oi.product_name
                  ORDER BY total_sold DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get revenue by date range
    public function getRevenueByDateRange($start_date, $end_date) {
        $query = "SELECT DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders
                  FROM orders
                  WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                  AND order_status != 'cancelled'
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get revenue for last N days
    public function getRevenueLastDays($days = 7) {
        $query = "SELECT DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders
                  FROM orders
                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  AND order_status != 'cancelled'
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get revenue by month for current year
    public function getRevenueByMonth($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        $query = "SELECT MONTH(created_at) as month, SUM(total_amount) as revenue, COUNT(*) as orders
                  FROM orders
                  WHERE YEAR(created_at) = :year
                  AND order_status != 'cancelled'
                  GROUP BY MONTH(created_at)
                  ORDER BY month ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get order status distribution
    public function getOrderStatusDistribution() {
        $query = "SELECT order_status, COUNT(*) as count
                  FROM orders
                  GROUP BY order_status
                  ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get order type distribution
    public function getOrderTypeDistribution() {
        $query = "SELECT order_type, COUNT(*) as count, SUM(total_amount) as revenue
                  FROM orders
                  WHERE order_status != 'cancelled'
                  GROUP BY order_type
                  ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get category sales
    public function getCategorySales() {
        $query = "SELECT c.category_name, COUNT(oi.order_item_id) as items_sold, SUM(oi.subtotal) as revenue
                  FROM categories c
                  JOIN products p ON c.category_id = p.category_id
                  JOIN order_items oi ON p.product_id = oi.product_id
                  JOIN orders o ON oi.order_id = o.order_id
                  WHERE o.order_status != 'cancelled'
                  GROUP BY c.category_id
                  ORDER BY revenue DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
