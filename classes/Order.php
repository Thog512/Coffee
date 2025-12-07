<?php
require_once __DIR__ . '/Database.php';

class Order {
    private $conn;
    private $table = 'orders';

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function create($orderData, $items) {
        $this->conn->beginTransaction();
        
        try {
            // Insert order
            $query = "INSERT INTO " . $this->table . " 
                      (customer_id, customer_name, customer_phone, customer_address, order_type, 
                       table_id, subtotal, discount, tax, delivery_fee, total_amount, created_by) 
                      VALUES (:customer_id, :customer_name, :customer_phone, :customer_address, :order_type, 
                              :table_id, :subtotal, :discount, :tax, :delivery_fee, :total_amount, :created_by)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($orderData);
            $orderId = $this->conn->lastInsertId();

            // Insert order items
            $itemQuery = "INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) 
                          VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price, :subtotal)";
            $itemStmt = $this->conn->prepare($itemQuery);

            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                $itemStmt->execute($item);
            }

            $this->conn->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getById($id) {
        $query = "SELECT o.*, t.table_number, u.full_name as staff_name
                  FROM " . $this->table . " o
                  LEFT JOIN tables t ON o.table_id = t.table_id
                  LEFT JOIN users u ON o.created_by = u.user_id
                  WHERE o.order_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrderItems($orderId) {
        $query = "SELECT * FROM order_items WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus($orderId, $status) {
        $query = "UPDATE " . $this->table . " SET order_status = :status WHERE order_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $orderId);
        $result = $stmt->execute();
        
        // Auto-deduct ingredients when order is completed
        if ($result && $status === 'completed') {
            require_once __DIR__ . '/Inventory.php';
            $inventory = new Inventory();
            $inventory->deductIngredientsForOrder($orderId);
            
            // Free up table if this was a dine-in order
            $this->freeTableForOrder($orderId);
        }
        
        return $result;
    }
    
    /**
     * Free up table when order is completed
     */
    private function freeTableForOrder($orderId) {
        try {
            // Get order details to find table_id
            $query = "SELECT table_id, order_type FROM " . $this->table . " WHERE order_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $orderId);
            $stmt->execute();
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If dine-in order with table, set table back to available
            if ($order && $order['order_type'] === 'dine_in' && $order['table_id']) {
                require_once __DIR__ . '/Table.php';
                $table_manager = new Table();
                $table_manager->updateStatus($order['table_id'], 'available');
            }
        } catch (Exception $e) {
            error_log('Error freeing table: ' . $e->getMessage());
        }
    }

    public function getAll($limit = 50, $offset = 0) {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOrdersWithoutDelivery($limit = 50) {
        $query = "SELECT o.*
                  FROM " . $this->table . " o
                  LEFT JOIN deliveries d ON o.order_id = d.order_id
                  WHERE d.delivery_id IS NULL 
                    AND o.order_status IN ('pending', 'confirmed', 'preparing', 'ready', 'completed')
                  ORDER BY o.created_at DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
