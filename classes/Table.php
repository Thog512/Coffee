<?php
require_once __DIR__ . '/Database.php';

class Table {
    private $conn;
    private $table = 'tables';

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY floor_level ASC, table_number ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE table_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (table_number, capacity, floor_level, STATUS) VALUES (:number, :capacity, :floor, :status)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':number', $data['table_number']);
        $stmt->bindParam(':capacity', $data['capacity'], PDO::PARAM_INT);
        $stmt->bindParam(':floor', $data['floor_level'], PDO::PARAM_INT);
        $stmt->bindParam(':status', $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET table_number = :number, capacity = :capacity, floor_level = :floor, STATUS = :status WHERE table_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':number', $data['table_number']);
        $stmt->bindParam(':capacity', $data['capacity'], PDO::PARAM_INT);
        $stmt->bindParam(':floor', $data['floor_level'], PDO::PARAM_INT);
        $stmt->bindParam(':status', $data['status']);
        
        // Debug log BEFORE execute
        error_log("SQL: $query");
        error_log("Binding: ID=$id, Number={$data['table_number']}, Capacity={$data['capacity']}, Floor={$data['floor_level']}, Status={$data['status']}");
        
        $result = $stmt->execute();
        $rowCount = $stmt->rowCount();
        
        error_log("Execute result: " . ($result ? 'TRUE' : 'FALSE') . ", Rows affected: $rowCount");
        
        if (!$result) {
            error_log("Update failed: " . print_r($stmt->errorInfo(), true));
        }
        
        if ($rowCount === 0) {
            error_log("WARNING: No rows were updated! Maybe values are the same?");
        }
        
        return $result;
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " SET STATUS = :status WHERE table_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete($id) {
        // You might want to add checks here to prevent deleting a table that has active orders or reservations.
        $query = "DELETE FROM " . $this->table . " WHERE table_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getByFloor($floor_level) {
        $query = "SELECT * FROM " . $this->table . " WHERE floor_level = :floor ORDER BY table_number ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':floor', $floor_level, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableTables() {
        $query = "SELECT * FROM " . $this->table . " WHERE status = 'available' ORDER BY floor_level ASC, table_number ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFloors() {
        $query = "SELECT DISTINCT floor_level FROM " . $this->table . " ORDER BY floor_level ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
