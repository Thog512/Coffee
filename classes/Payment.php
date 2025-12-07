<?php
require_once __DIR__ . '/Database.php';

class Payment {
    private $conn;
    private $table = 'payments';

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (order_id, payment_method, amount, transaction_id, payment_status) 
                  VALUES (:order_id, :payment_method, :amount, :transaction_id, :payment_status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':order_id', $data['order_id']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':transaction_id', $data['transaction_id']);
        $stmt->bindParam(':payment_status', $data['payment_status']);
        
        return $stmt->execute();
    }

    public function getByOrderId($orderId) {
        $query = "SELECT * FROM " . $this->table . " WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus($paymentId, $status) {
        $query = "UPDATE " . $this->table . " 
                  SET payment_status = :status, payment_date = NOW() 
                  WHERE payment_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $paymentId);
        return $stmt->execute();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE payment_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
