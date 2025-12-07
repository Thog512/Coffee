<?php
require_once __DIR__ . '/Database.php';

class Reservation {
    private $conn;
    private $table = 'reservations';

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function getAll() {
        $query = "SELECT r.*, t.table_number, r.STATUS as status
                  FROM " . $this->table . " r
                  LEFT JOIN tables t ON r.table_id = t.table_id
                  ORDER BY r.reservation_date DESC, r.reservation_time DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE reservation_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (table_id, customer_name, customer_phone, guest_count, reservation_date, reservation_time, status, special_requests) 
                  VALUES (:table_id, :name, :phone, :guests, :date, :time, :status, :notes)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':table_id', $data['table_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['customer_name']);
        $stmt->bindParam(':phone', $data['customer_phone']);
        $stmt->bindParam(':guests', $data['guest_count'], PDO::PARAM_INT);
        $stmt->bindParam(':date', $data['reservation_date']);
        $stmt->bindParam(':time', $data['reservation_time']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':notes', $data['special_requests']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                    table_id = :table_id, 
                    customer_name = :name, 
                    customer_phone = :phone, 
                    guest_count = :guests, 
                    reservation_date = :date, 
                    reservation_time = :time, 
                    status = :status, 
                    special_requests = :notes 
                  WHERE reservation_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':table_id', $data['table_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['customer_name']);
        $stmt->bindParam(':phone', $data['customer_phone']);
        $stmt->bindParam(':guests', $data['guest_count'], PDO::PARAM_INT);
        $stmt->bindParam(':date', $data['reservation_date']);
        $stmt->bindParam(':time', $data['reservation_time']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':notes', $data['special_requests']);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE reservation_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Check table availability for a specific date, time and duration
     * @param int $table_id Table ID to check
     * @param string $date Reservation date (Y-m-d)
     * @param string $time Reservation time (H:i:s)
     * @param int $duration Duration in hours (default 2)
     * @param int|null $exclude_reservation_id Exclude this reservation from check (for updates)
     * @return bool True if available, false otherwise
     */
    public function checkAvailability($table_id, $date, $time, $duration = 2, $exclude_reservation_id = null) {
        // Calculate time range
        $start_time = date('H:i:s', strtotime($time));
        $end_time = date('H:i:s', strtotime($time) + ($duration * 3600));
        
        $query = "SELECT COUNT(*) as conflict_count 
                  FROM " . $this->table . " 
                  WHERE table_id = :table_id 
                  AND reservation_date = :date 
                  AND status NOT IN ('cancelled', 'completed', 'no_show')
                  AND (
                      (reservation_time >= :start_time AND reservation_time < :end_time)
                      OR (DATE_ADD(CONCAT(reservation_date, ' ', reservation_time), INTERVAL 2 HOUR) > :start_time2)
                  )";
        
        if ($exclude_reservation_id) {
            $query .= " AND reservation_id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':table_id', $table_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        $stmt->bindParam(':start_time2', $start_time);
        
        if ($exclude_reservation_id) {
            $stmt->bindParam(':exclude_id', $exclude_reservation_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['conflict_count'] == 0;
    }

    /**
     * Get available tables for a specific date, time and guest count
     * @param string $date Reservation date
     * @param string $time Reservation time
     * @param int $guest_count Number of guests
     * @return array List of available tables
     */
    public function getAvailableTables($date, $time, $guest_count = 1) {
        $query = "SELECT t.* 
                  FROM tables t
                  WHERE t.capacity >= :guest_count
                  AND t.status = 'available'
                  AND t.table_id NOT IN (
                      SELECT r.table_id 
                      FROM " . $this->table . " r
                      WHERE r.reservation_date = :date
                      AND r.table_id IS NOT NULL
                      AND r.status NOT IN ('cancelled', 'completed', 'no_show')
                      AND (
                          (r.reservation_time >= :start_time AND r.reservation_time < :end_time)
                          OR (DATE_ADD(CONCAT(r.reservation_date, ' ', r.reservation_time), INTERVAL 2 HOUR) > :start_time2)
                      )
                  )
                  ORDER BY t.capacity ASC, t.floor_level ASC";
        
        $start_time = date('H:i:s', strtotime($time));
        $end_time = date('H:i:s', strtotime($time) + 7200); // +2 hours
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':guest_count', $guest_count, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        $stmt->bindParam(':start_time2', $start_time);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reservations by date
     */
    public function getByDate($date) {
        $query = "SELECT r.*, t.table_number, r.STATUS as status
                  FROM " . $this->table . " r
                  LEFT JOIN tables t ON r.table_id = t.table_id
                  WHERE r.reservation_date = :date
                  ORDER BY r.reservation_time ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reservations by date range
     */
    public function getByDateRange($start_date, $end_date) {
        $query = "SELECT r.*, t.table_number, r.STATUS as status
                  FROM " . $this->table . " r
                  LEFT JOIN tables t ON r.table_id = t.table_id
                  WHERE r.reservation_date BETWEEN :start_date AND :end_date
                  ORDER BY r.reservation_date ASC, r.reservation_time ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Quick update reservation status
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " 
                  SET status = :status, 
                      updated_at = NOW() 
                  WHERE reservation_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    }

    /**
     * Assign table to reservation
     */
    public function assignTable($reservation_id, $table_id) {
        $query = "UPDATE " . $this->table . " 
                  SET table_id = :table_id, 
                      updated_at = NOW() 
                  WHERE reservation_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $stmt->bindParam(':table_id', $table_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get statistics
     */
    public function getStatistics($start_date = null, $end_date = null) {
        $where = '';
        if ($start_date && $end_date) {
            $where = "WHERE reservation_date BETWEEN '$start_date' AND '$end_date'";
        }
        
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'arrived' THEN 1 ELSE 0 END) as arrived,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show
                  FROM " . $this->table . " $where";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
