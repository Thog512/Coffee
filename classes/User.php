<?php
require_once __DIR__ . '/Database.php';

class User {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

            public function create($data) {
        if (empty($data['password'])) {
            return false; // Password is required for new users
        }
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role_id to 6 (Customer) if not provided
        if (!isset($data['role_id'])) {
            $data['role_id'] = 6;
        }

        $query = "INSERT INTO " . $this->table . " (username, email, password_hash, full_name, phone, status, role_id) VALUES (:username, :email, :password_hash, :full_name, :phone, :status, :role_id)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password_hash', $data['password_hash']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':role_id', $data['role_id']);

        return $stmt->execute();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

        public function getByUsername($username) {
        $query = "SELECT user_id, username, password_hash, full_name, status, role_id FROM " . $this->table . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

        public function update($id, $data) {
        $set_clauses = [];
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $set_clauses[] = 'password_hash = :password_hash';
        }
        
        if (isset($data['role_id'])) {
            $set_clauses[] = 'role_id = :role_id';
        }

        $query = "UPDATE " . $this->table . " SET username = :username, email = :email, full_name = :full_name, phone = :phone, status = :status";
        if (!empty($set_clauses)) {
            $query .= ", " . implode(', ', $set_clauses);
        }
        $query .= " WHERE user_id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':status', $data['status']);

        if (isset($data['password_hash'])) {
            $stmt->bindParam(':password_hash', $data['password_hash']);
        }
        
        if (isset($data['role_id'])) {
            $stmt->bindParam(':role_id', $data['role_id']);
        }

        return $stmt->execute();
    }

    public function delete($id) {
        try {
            // Start transaction
            $this->conn->beginTransaction();
            
            // Temporarily disable foreign key checks
            $this->conn->exec('SET FOREIGN_KEY_CHECKS=0');
            
            // Delete related records that reference this user
            // Delete promotions created by this user
            $deletePromo = "DELETE FROM promotions WHERE created_by = :id";
            $stmtPromo = $this->conn->prepare($deletePromo);
            $stmtPromo->bindParam(':id', $id);
            $stmtPromo->execute();
            
            // Try to update related tables (ignore errors if columns don't exist)
            try {
                $updateOrders = "UPDATE orders SET customer_id = NULL WHERE customer_id = :id";
                $stmtOrders = $this->conn->prepare($updateOrders);
                $stmtOrders->bindParam(':id', $id);
                $stmtOrders->execute();
            } catch (PDOException $e) {
                // Column might not exist, ignore
            }
            
            try {
                $updateReservations = "UPDATE reservations SET customer_id = NULL WHERE customer_id = :id";
                $stmtReservations = $this->conn->prepare($updateReservations);
                $stmtReservations->bindParam(':id', $id);
                $stmtReservations->execute();
            } catch (PDOException $e) {
                // Column might not exist, ignore
            }
            
            try {
                $updateDeliveries = "UPDATE deliveries SET shipper_id = NULL WHERE shipper_id = :id";
                $stmtDeliveries = $this->conn->prepare($updateDeliveries);
                $stmtDeliveries->bindParam(':id', $id);
                $stmtDeliveries->execute();
            } catch (PDOException $e) {
                // Column might not exist, ignore
            }
            
            try {
                $deleteShipper = "DELETE FROM shippers WHERE user_id = :id";
                $stmtShipper = $this->conn->prepare($deleteShipper);
                $stmtShipper->bindParam(':id', $id);
                $stmtShipper->execute();
            } catch (PDOException $e) {
                // Table might not exist, ignore
            }
            
            // Delete the user
            $query = "DELETE FROM " . $this->table . " WHERE user_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Re-enable foreign key checks
            $this->conn->exec('SET FOREIGN_KEY_CHECKS=1');
            
            // Commit transaction
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            // Rollback on error
            $this->conn->rollBack();
            
            // Re-enable foreign key checks
            try {
                $this->conn->exec('SET FOREIGN_KEY_CHECKS=1');
            } catch (Exception $ex) {
                // Ignore
            }
            
            // Log error for debugging
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }

    public function getAll($limit = 50, $offset = 0) {
        $query = "SELECT u.*, r.role_name, r.description as role_description 
                  FROM " . $this->table . " u 
                  LEFT JOIN roles r ON u.role_id = r.role_id 
                  ORDER BY u.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
