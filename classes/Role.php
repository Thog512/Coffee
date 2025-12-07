<?php
require_once __DIR__ . '/Database.php';

class Role {
    private $conn;
    private $table = 'roles';

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY role_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserRoles($user_id) {
        // Updated: Get role_id from users table instead of user_roles
        $query = "SELECT role_id FROM users WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $role_id = $stmt->fetchColumn();
        
        // Return as array for compatibility with old code
        return $role_id ? [$role_id] : [];
    }
}
?>
