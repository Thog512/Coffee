<?php
require_once __DIR__ . '/Database.php';

class Category {
    private $conn;
    private $table = 'categories';

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function getAll() {
        $query = "SELECT c.*, COUNT(p.product_id) as product_count 
                  FROM " . $this->table . " c
                  LEFT JOIN products p ON c.category_id = p.category_id
                  GROUP BY c.category_id
                  ORDER BY c.display_order ASC, c.category_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE category_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (category_name, description, image, display_order, status) VALUES (:name, :desc, :img, :order, :status)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['category_name']);
        $stmt->bindParam(':desc', $data['description']);
        $stmt->bindParam(':img', $data['image']);
        $stmt->bindParam(':order', $data['display_order'], PDO::PARAM_INT);
        $stmt->bindParam(':status', $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET category_name = :name, description = :desc, image = :img, display_order = :order, status = :status WHERE category_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['category_name']);
        $stmt->bindParam(':desc', $data['description']);
        $stmt->bindParam(':img', $data['image']);
        $stmt->bindParam(':order', $data['display_order'], PDO::PARAM_INT);
        $stmt->bindParam(':status', $data['status']);
        return $stmt->execute();
    }

    public function delete($id) {
        // Check if any products are using this category
        $product_check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = :id";
        $product_stmt = $this->conn->prepare($product_check_query);
        $product_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $product_stmt->execute();
        $result = $product_stmt->fetch();

        if ($result['count'] > 0) {
            // Cannot delete category if products are assigned to it
            return false;
        }

        $query = "DELETE FROM " . $this->table . " WHERE category_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>
