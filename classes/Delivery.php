<?php
/**
 * Delivery Management Class
 * Handles delivery operations, shipper management, and tracking
 */

require_once __DIR__ . '/Database.php';

class Delivery {
    private $conn;
    private $deliveries_table = 'deliveries';
    private $shippers_table = 'shippers';
    private $tracking_table = 'delivery_tracking';
    private $zones_table = 'delivery_zones';
    
    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    // ==================== SHIPPER MANAGEMENT ====================
    
    /**
     * Get all shippers
     */
    public function getAllShippers($status = null) {
        $query = "SELECT * FROM " . $this->shippers_table;
        
        if ($status) {
            $query .= " WHERE status = :status";
        }
        
        $query .= " ORDER BY rating DESC, total_deliveries DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get available shippers
     */
    public function getAvailableShippers() {
        $query = "SELECT * FROM " . $this->shippers_table . "
                  WHERE status = 'available' AND current_orders < max_orders
                  ORDER BY current_orders ASC, rating DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get shipper by ID
     */
    public function getShipperById($id) {
        $query = "SELECT * FROM " . $this->shippers_table . " WHERE shipper_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Create new shipper
     */
    public function createShipper($data) {
        $query = "INSERT INTO " . $this->shippers_table . "
                  (user_id, full_name, phone, vehicle_type, license_plate, max_orders)
                  VALUES (:user_id, :full_name, :phone, :vehicle_type, :license_plate, :max_orders)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':vehicle_type', $data['vehicle_type']);
        $stmt->bindParam(':license_plate', $data['license_plate']);
        $stmt->bindParam(':max_orders', $data['max_orders']);
        
        return $stmt->execute();
    }
    
    /**
     * Update shipper
     */
    public function updateShipper($id, $data) {
        $query = "UPDATE " . $this->shippers_table . "
                  SET full_name = :full_name,
                      phone = :phone,
                      vehicle_type = :vehicle_type,
                      license_plate = :license_plate,
                      max_orders = :max_orders,
                      status = :status
                  WHERE shipper_id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':vehicle_type', $data['vehicle_type']);
        $stmt->bindParam(':license_plate', $data['license_plate']);
        $stmt->bindParam(':max_orders', $data['max_orders']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete shipper
     */
    public function deleteShipper($id) {
        $query = "DELETE FROM " . $this->shippers_table . " WHERE shipper_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Update shipper status
     */
    public function updateShipperStatus($id, $status) {
        $query = "UPDATE " . $this->shippers_table . "
                  SET status = :status, last_active = NOW()
                  WHERE shipper_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    // ==================== DELIVERY MANAGEMENT ====================
    
    /**
     * Get all deliveries
     */
    public function getAllDeliveries($status = null, $limit = 100) {
        $query = "SELECT d.*, s.full_name as shipper_name, s.phone as shipper_phone, s.vehicle_type
                  FROM " . $this->deliveries_table . " d
                  LEFT JOIN " . $this->shippers_table . " s ON d.shipper_id = s.shipper_id";
        
        if ($status) {
            $query .= " WHERE d.status = :status";
        }
        
        $query .= " ORDER BY d.created_at DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get delivery by ID
     */
    public function getDeliveryById($id) {
        $query = "SELECT d.*, s.full_name as shipper_name, s.phone as shipper_phone, s.vehicle_type
                  FROM " . $this->deliveries_table . " d
                  LEFT JOIN " . $this->shippers_table . " s ON d.shipper_id = s.shipper_id
                  WHERE d.delivery_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get deliveries by order ID
     */
    public function getDeliveryByOrderId($order_id) {
        $query = "SELECT d.*, s.full_name as shipper_name, s.phone as shipper_phone
                  FROM " . $this->deliveries_table . " d
                  LEFT JOIN " . $this->shippers_table . " s ON d.shipper_id = s.shipper_id
                  WHERE d.order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get deliveries by shipper
     */
    public function getDeliveriesByShipper($shipper_id, $status = null) {
        $query = "SELECT * FROM " . $this->deliveries_table . " WHERE shipper_id = :shipper_id";
        
        if ($status) {
            $query .= " AND status = :status";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shipper_id', $shipper_id);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Create delivery
     */
    public function createDelivery($data) {
        $query = "INSERT INTO " . $this->deliveries_table . "
                  (order_id, customer_name, customer_phone, delivery_address, delivery_notes,
                   distance, delivery_fee, payment_method, cod_amount, pickup_location, estimated_delivery_time)
                  VALUES (:order_id, :customer_name, :customer_phone, :delivery_address, :delivery_notes,
                          :distance, :delivery_fee, :payment_method, :cod_amount, :pickup_location, :estimated_delivery_time)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind with proper NULL handling using bindValue
        $stmt->bindValue(':order_id', $data['order_id'], $data['order_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':customer_name', $data['customer_name'], PDO::PARAM_STR);
        $stmt->bindValue(':customer_phone', $data['customer_phone'], PDO::PARAM_STR);
        $stmt->bindValue(':delivery_address', $data['delivery_address'], PDO::PARAM_STR);
        $stmt->bindValue(':delivery_notes', $data['delivery_notes'], PDO::PARAM_STR);
        $stmt->bindValue(':distance', $data['distance'], PDO::PARAM_STR);
        $stmt->bindValue(':delivery_fee', $data['delivery_fee'], PDO::PARAM_STR);
        $stmt->bindValue(':payment_method', $data['payment_method'], PDO::PARAM_STR);
        $stmt->bindValue(':cod_amount', $data['cod_amount'], PDO::PARAM_STR);
        $stmt->bindValue(':pickup_location', $data['pickup_location'], PDO::PARAM_STR);
        $stmt->bindValue(':estimated_delivery_time', $data['estimated_delivery_time'], $data['estimated_delivery_time'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        
        try {
            if ($stmt->execute()) {
                $delivery_id = $this->conn->lastInsertId();
                
                // Add tracking record
                $this->addTracking($delivery_id, 'pending', $data['pickup_location'], 'Đơn hàng mới tạo', $data['created_by'] ?? null);
                
                return $delivery_id;
            }
        } catch (PDOException $e) {
            error_log("Delivery creation failed: " . $e->getMessage());
            throw $e;
        }
        
        return false;
    }
    
    /**
     * Assign delivery to shipper
     */
    public function assignDelivery($delivery_id, $shipper_id, $user_id = null) {
        $query = "UPDATE " . $this->deliveries_table . "
                  SET shipper_id = :shipper_id,
                      status = 'assigned',
                      assigned_at = NOW()
                  WHERE delivery_id = :delivery_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shipper_id', $shipper_id);
        $stmt->bindParam(':delivery_id', $delivery_id);
        
        if ($stmt->execute()) {
            // Get shipper info
            $shipper = $this->getShipperById($shipper_id);
            
            // Add tracking
            $this->addTracking($delivery_id, 'assigned', null, 'Đã phân công cho shipper: ' . $shipper['full_name'], $user_id);
            
            // Update shipper orders count (manual trigger replacement)
            $this->updateShipperOrders($shipper_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Update delivery status
     */
    public function updateDeliveryStatus($delivery_id, $status, $location = null, $notes = null, $user_id = null) {
        $timestamp_field = null;
        
        switch ($status) {
            case 'picked_up':
                $timestamp_field = 'picked_up_at';
                break;
            case 'in_transit':
                $timestamp_field = 'in_transit_at';
                break;
            case 'delivered':
                $timestamp_field = 'delivered_at';
                break;
        }
        
        $query = "UPDATE " . $this->deliveries_table . " SET status = :status";
        
        if ($timestamp_field) {
            $query .= ", " . $timestamp_field . " = NOW()";
        }
        
        if ($location) {
            $query .= ", current_location = :location";
        }
        
        $query .= " WHERE delivery_id = :delivery_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':delivery_id', $delivery_id);
        
        if ($location) {
            $stmt->bindParam(':location', $location);
        }
        
        if ($stmt->execute()) {
            // Add tracking
            $this->addTracking($delivery_id, $status, $location, $notes, $user_id);
            
            // Update shipper orders if status changed to completed/cancelled/failed
            if (in_array($status, ['delivered', 'cancelled', 'failed'])) {
                $delivery = $this->getDeliveryById($delivery_id);
                if ($delivery && $delivery['shipper_id']) {
                    $this->updateShipperOrders($delivery['shipper_id']);
                    
                    // Update shipper statistics
                    if ($status == 'delivered') {
                        $this->conn->prepare("UPDATE " . $this->shippers_table . " 
                                             SET successful_deliveries = successful_deliveries + 1,
                                                 total_deliveries = total_deliveries + 1
                                             WHERE shipper_id = :shipper_id")
                                   ->execute([':shipper_id' => $delivery['shipper_id']]);
                    } else if ($status == 'cancelled') {
                        $this->conn->prepare("UPDATE " . $this->shippers_table . " 
                                             SET cancelled_deliveries = cancelled_deliveries + 1,
                                                 total_deliveries = total_deliveries + 1
                                             WHERE shipper_id = :shipper_id")
                                   ->execute([':shipper_id' => $delivery['shipper_id']]);
                    }
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Cancel delivery
     */
    public function cancelDelivery($delivery_id, $cancelled_by, $reason, $user_id = null) {
        $query = "UPDATE " . $this->deliveries_table . "
                  SET status = 'cancelled',
                      cancelled_by = :cancelled_by,
                      cancellation_reason = :reason
                  WHERE delivery_id = :delivery_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cancelled_by', $cancelled_by);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':delivery_id', $delivery_id);
        
        if ($stmt->execute()) {
            $this->addTracking($delivery_id, 'cancelled', null, 'Đã hủy: ' . $reason, $user_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Rate delivery
     */
    public function rateDelivery($delivery_id, $rating, $feedback = null) {
        $query = "UPDATE " . $this->deliveries_table . "
                  SET customer_rating = :rating,
                      customer_feedback = :feedback
                  WHERE delivery_id = :delivery_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':feedback', $feedback);
        $stmt->bindParam(':delivery_id', $delivery_id);
        
        if ($stmt->execute()) {
            // Update shipper rating
            $delivery = $this->getDeliveryById($delivery_id);
            if ($delivery && $delivery['shipper_id']) {
                $this->updateShipperRating($delivery['shipper_id']);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Update shipper rating based on all deliveries
     */
    private function updateShipperRating($shipper_id) {
        $query = "UPDATE " . $this->shippers_table . " s
                  SET rating = (
                      SELECT AVG(customer_rating)
                      FROM " . $this->deliveries_table . "
                      WHERE shipper_id = :shipper_id AND customer_rating IS NOT NULL
                  )
                  WHERE shipper_id = :shipper_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shipper_id', $shipper_id);
        return $stmt->execute();
    }
    
    // ==================== TRACKING ====================
    
    /**
     * Add tracking record
     */
    public function addTracking($delivery_id, $status, $location = null, $notes = null, $user_id = null) {
        $query = "INSERT INTO " . $this->tracking_table . "
                  (delivery_id, status, location, notes, created_by)
                  VALUES (:delivery_id, :status, :location, :notes, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':delivery_id', $delivery_id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':created_by', $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get tracking history
     */
    public function getTrackingHistory($delivery_id) {
        $query = "SELECT t.*, u.full_name as updated_by_name
                  FROM " . $this->tracking_table . " t
                  LEFT JOIN users u ON t.created_by = u.user_id
                  WHERE t.delivery_id = :delivery_id
                  ORDER BY t.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':delivery_id', $delivery_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ==================== ZONES ====================
    
    /**
     * Get all delivery zones
     */
    public function getAllZones() {
        $query = "SELECT * FROM " . $this->zones_table . " WHERE is_active = TRUE ORDER BY max_distance ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Calculate delivery fee
     */
    public function calculateDeliveryFee($distance, $order_value = 0) {
        $zones = $this->getAllZones();
        
        foreach ($zones as $zone) {
            if ($distance >= $zone['min_distance'] && $distance <= $zone['max_distance']) {
                // Check free shipping threshold
                if ($zone['free_shipping_threshold'] && $order_value >= $zone['free_shipping_threshold']) {
                    return 0;
                }
                
                // Calculate fee
                $fee = $zone['base_fee'];
                if ($distance > $zone['min_distance']) {
                    $extra_distance = $distance - $zone['min_distance'];
                    $fee += $extra_distance * $zone['per_km_fee'];
                }
                
                return $fee;
            }
        }
        
        return 0;
    }
    
    // ==================== STATISTICS ====================
    
    /**
     * Get delivery statistics
     */
    public function getStatistics($start_date = null, $end_date = null) {
        $where = "1=1";
        
        if ($start_date && $end_date) {
            $where .= " AND DATE(created_at) BETWEEN :start_date AND :end_date";
        }
        
        $query = "SELECT 
                    COUNT(*) as total_deliveries,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as successful,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                    COUNT(CASE WHEN status IN ('pending', 'assigned', 'picked_up', 'in_transit') THEN 1 END) as in_progress,
                    SUM(delivery_fee) as total_fees,
                    AVG(customer_rating) as avg_rating
                  FROM " . $this->deliveries_table . "
                  WHERE " . $where;
        
        $stmt = $this->conn->prepare($query);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get shipper performance (without view)
     */
    public function getShipperPerformance() {
        $query = "SELECT 
                    s.shipper_id,
                    s.full_name,
                    s.phone,
                    s.status,
                    s.current_orders,
                    s.max_orders,
                    s.rating,
                    s.total_deliveries,
                    s.successful_deliveries,
                    ROUND((s.successful_deliveries / NULLIF(s.total_deliveries, 0) * 100), 2) as success_rate,
                    s.total_earnings,
                    COUNT(CASE WHEN d.status IN ('assigned', 'picked_up', 'in_transit') THEN 1 END) as active_deliveries,
                    s.last_active
                  FROM " . $this->shippers_table . " s
                  LEFT JOIN " . $this->deliveries_table . " d ON s.shipper_id = d.shipper_id 
                        AND d.status IN ('assigned', 'picked_up', 'in_transit')
                  GROUP BY s.shipper_id
                  ORDER BY s.rating DESC, s.total_deliveries DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get active deliveries (without view)
     */
    public function getActiveDeliveries() {
        $query = "SELECT 
                    d.delivery_id,
                    d.order_id,
                    d.customer_name,
                    d.customer_phone,
                    d.delivery_address,
                    d.status,
                    d.delivery_fee,
                    d.cod_amount,
                    d.estimated_delivery_time,
                    s.shipper_id,
                    s.full_name as shipper_name,
                    s.phone as shipper_phone,
                    s.vehicle_type,
                    d.created_at,
                    d.assigned_at,
                    d.picked_up_at,
                    d.in_transit_at
                  FROM " . $this->deliveries_table . " d
                  LEFT JOIN " . $this->shippers_table . " s ON d.shipper_id = s.shipper_id
                  WHERE d.status IN ('pending', 'assigned', 'picked_up', 'in_transit')
                  ORDER BY d.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Update shipper current orders manually (since no trigger)
     */
    public function updateShipperOrders($shipper_id) {
        // Count active deliveries
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->deliveries_table . " 
                  WHERE shipper_id = :shipper_id 
                  AND status IN ('assigned', 'picked_up', 'in_transit')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shipper_id', $shipper_id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        $current_orders = $result['count'];
        
        // Update shipper
        $update = "UPDATE " . $this->shippers_table . " 
                   SET current_orders = :current_orders,
                       status = CASE 
                           WHEN :current_orders >= max_orders THEN 'busy' 
                           WHEN :current_orders < max_orders AND status = 'busy' THEN 'available'
                           ELSE status 
                       END
                   WHERE shipper_id = :shipper_id";
        
        $stmt = $this->conn->prepare($update);
        $stmt->bindParam(':current_orders', $current_orders);
        $stmt->bindParam(':shipper_id', $shipper_id);
        
        return $stmt->execute();
    }
}
?>
