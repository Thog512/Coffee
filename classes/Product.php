<?php
require_once __DIR__ . '/Database.php';

class Product {
    private $conn;
    private $table = 'products';

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

        public function getAll() {
        $query = "SELECT p.product_id, p.product_name, p.price, p.image, p.status, c.category_name 
                  FROM " . $this->table . " p
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  ORDER BY c.category_name ASC, p.product_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT p.*, c.category_name 
                  FROM " . $this->table . " p
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  WHERE p.product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (category_id, product_name, description, price, image, status, is_featured) 
                  VALUES (:category_id, :product_name, :description, :price, :image, :status, :is_featured)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_name', $data['product_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':image', $data['image']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':is_featured', $data['is_featured'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET category_id = :category_id,
                      product_name = :product_name,
                      description = :description,
                      price = :price,
                      image = :image,
                      status = :status,
                      is_featured = :is_featured
                  WHERE product_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_name', $data['product_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':image', $data['image']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':is_featured', $data['is_featured'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE product_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0; // Return true only if a row was actually deleted
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error deleting product: " . $e->getMessage());
            return false;
        }
    }

    public function getAllCategories() {
        $query = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
