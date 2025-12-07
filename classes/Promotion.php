<?php
/**
 * Promotion Management Class
 * Handles all promotion-related operations
 */

require_once __DIR__ . '/Database.php';

class Promotion {
    private $conn;
    private $table = 'promotions';
    
    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    /**
     * Get all promotions
     */
    public function getAll($status = null) {
        $query = "SELECT p.*, u.full_name as created_by_name
                  FROM " . $this->table . " p
                  LEFT JOIN users u ON p.created_by = u.user_id";
        
        if ($status) {
            $query .= " WHERE p.status = :status";
        }
        
        $query .= " ORDER BY p.priority DESC, p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get active promotions
     */
    public function getActive() {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE status = 'active'
                  AND start_date <= CURDATE()
                  AND end_date >= CURDATE()
                  ORDER BY priority DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get promotion by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE promotion_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get promotion by voucher code
     */
    public function getByVoucherCode($code) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE voucher_code = :code 
                  AND status = 'active'
                  AND start_date <= CURDATE()
                  AND end_date >= CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Create new promotion
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (promotion_name, description, promotion_type, discount_value, 
                   min_order_value, max_discount, buy_quantity, get_quantity,
                   applicable_to, applicable_ids, voucher_code, usage_limit, 
                   usage_per_customer, start_date, end_date, start_time, end_time,
                   applicable_days, payment_methods, customer_type, status, priority, created_by)
                  VALUES 
                  (:promotion_name, :description, :promotion_type, :discount_value,
                   :min_order_value, :max_discount, :buy_quantity, :get_quantity,
                   :applicable_to, :applicable_ids, :voucher_code, :usage_limit,
                   :usage_per_customer, :start_date, :end_date, :start_time, :end_time,
                   :applicable_days, :payment_methods, :customer_type, :status, :priority, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':promotion_name', $data['promotion_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':promotion_type', $data['promotion_type']);
        $stmt->bindParam(':discount_value', $data['discount_value']);
        $stmt->bindParam(':min_order_value', $data['min_order_value']);
        $stmt->bindParam(':max_discount', $data['max_discount']);
        $stmt->bindParam(':buy_quantity', $data['buy_quantity']);
        $stmt->bindParam(':get_quantity', $data['get_quantity']);
        $stmt->bindParam(':applicable_to', $data['applicable_to']);
        $stmt->bindParam(':applicable_ids', $data['applicable_ids']);
        $stmt->bindParam(':voucher_code', $data['voucher_code']);
        $stmt->bindParam(':usage_limit', $data['usage_limit']);
        $stmt->bindParam(':usage_per_customer', $data['usage_per_customer']);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':end_date', $data['end_date']);
        $stmt->bindParam(':start_time', $data['start_time']);
        $stmt->bindParam(':end_time', $data['end_time']);
        $stmt->bindParam(':applicable_days', $data['applicable_days']);
        $stmt->bindParam(':payment_methods', $data['payment_methods']);
        $stmt->bindParam(':customer_type', $data['customer_type']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        return $stmt->execute();
    }
    
    /**
     * Update promotion
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET promotion_name = :promotion_name,
                      description = :description,
                      promotion_type = :promotion_type,
                      discount_value = :discount_value,
                      min_order_value = :min_order_value,
                      max_discount = :max_discount,
                      buy_quantity = :buy_quantity,
                      get_quantity = :get_quantity,
                      applicable_to = :applicable_to,
                      applicable_ids = :applicable_ids,
                      voucher_code = :voucher_code,
                      usage_limit = :usage_limit,
                      usage_per_customer = :usage_per_customer,
                      start_date = :start_date,
                      end_date = :end_date,
                      start_time = :start_time,
                      end_time = :end_time,
                      applicable_days = :applicable_days,
                      payment_methods = :payment_methods,
                      customer_type = :customer_type,
                      status = :status,
                      priority = :priority
                  WHERE promotion_id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':promotion_name', $data['promotion_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':promotion_type', $data['promotion_type']);
        $stmt->bindParam(':discount_value', $data['discount_value']);
        $stmt->bindParam(':min_order_value', $data['min_order_value']);
        $stmt->bindParam(':max_discount', $data['max_discount']);
        $stmt->bindParam(':buy_quantity', $data['buy_quantity']);
        $stmt->bindParam(':get_quantity', $data['get_quantity']);
        $stmt->bindParam(':applicable_to', $data['applicable_to']);
        $stmt->bindParam(':applicable_ids', $data['applicable_ids']);
        $stmt->bindParam(':voucher_code', $data['voucher_code']);
        $stmt->bindParam(':usage_limit', $data['usage_limit']);
        $stmt->bindParam(':usage_per_customer', $data['usage_per_customer']);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':end_date', $data['end_date']);
        $stmt->bindParam(':start_time', $data['start_time']);
        $stmt->bindParam(':end_time', $data['end_time']);
        $stmt->bindParam(':applicable_days', $data['applicable_days']);
        $stmt->bindParam(':payment_methods', $data['payment_methods']);
        $stmt->bindParam(':customer_type', $data['customer_type']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete promotion
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE promotion_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Validate promotion applicability
     */
    public function validatePromotion($promotionId, $orderData) {
        $promotion = $this->getById($promotionId);
        
        if (!$promotion) {
            return ['valid' => false, 'message' => 'Khuyến mãi không tồn tại'];
        }
        
        // Check status
        if ($promotion['status'] !== 'active') {
            return ['valid' => false, 'message' => 'Khuyến mãi không còn hiệu lực'];
        }
        
        // Check date range
        $today = date('Y-m-d');
        if ($today < $promotion['start_date'] || $today > $promotion['end_date']) {
            return ['valid' => false, 'message' => 'Khuyến mãi đã hết hạn'];
        }
        
        // Check time range (for happy hour)
        if ($promotion['start_time'] && $promotion['end_time']) {
            $now = date('H:i:s');
            if ($now < $promotion['start_time'] || $now > $promotion['end_time']) {
                return ['valid' => false, 'message' => 'Ngoài khung giờ áp dụng'];
            }
        }
        
        // Check day of week
        if ($promotion['applicable_days']) {
            $today_dow = date('N'); // 1=Monday, 7=Sunday
            $days = json_decode($promotion['applicable_days'], true);
            if (!in_array($today_dow, $days)) {
                return ['valid' => false, 'message' => 'Không áp dụng cho ngày hôm nay'];
            }
        }
        
        // Check minimum order value
        if ($orderData['subtotal'] < $promotion['min_order_value']) {
            return ['valid' => false, 'message' => 'Đơn hàng chưa đủ giá trị tối thiểu'];
        }
        
        // Check usage limit
        if ($promotion['usage_limit'] && $promotion['usage_count'] >= $promotion['usage_limit']) {
            return ['valid' => false, 'message' => 'Khuyến mãi đã hết lượt sử dụng'];
        }
        
        return ['valid' => true, 'promotion' => $promotion];
    }
    
    /**
     * Calculate discount amount
     */
    public function calculateDiscount($promotion, $orderData) {
        $discount = 0;
        
        switch ($promotion['promotion_type']) {
            case 'percentage':
                $discount = $orderData['subtotal'] * ($promotion['discount_value'] / 100);
                if ($promotion['max_discount'] && $discount > $promotion['max_discount']) {
                    $discount = $promotion['max_discount'];
                }
                break;
                
            case 'fixed_amount':
                $discount = $promotion['discount_value'];
                break;
                
            case 'buy_x_get_y':
                // Calculate based on cheapest items
                // This is simplified - you may need more complex logic
                $discount = $promotion['discount_value'];
                break;
                
            case 'combo':
                $discount = $promotion['discount_value'];
                break;
        }
        
        return $discount;
    }
    
    /**
     * Log promotion usage
     */
    public function logUsage($promotionId, $orderId, $customerId, $discountAmount) {
        $query = "INSERT INTO promotion_usage 
                  (promotion_id, order_id, customer_id, discount_amount)
                  VALUES (:promotion_id, :order_id, :customer_id, :discount_amount)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':promotion_id', $promotionId);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':discount_amount', $discountAmount);
        
        if ($stmt->execute()) {
            // Update usage count
            $updateQuery = "UPDATE " . $this->table . " 
                           SET usage_count = usage_count + 1 
                           WHERE promotion_id = :promotion_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':promotion_id', $promotionId);
            $updateStmt->execute();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get promotion statistics
     */
    public function getStatistics($promotionId = null) {
        if ($promotionId) {
            $query = "SELECT 
                        COUNT(*) as total_usage,
                        SUM(discount_amount) as total_discount,
                        AVG(discount_amount) as avg_discount
                      FROM promotion_usage
                      WHERE promotion_id = :promotion_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':promotion_id', $promotionId);
        } else {
            $query = "SELECT 
                        p.promotion_name,
                        COUNT(pu.usage_id) as total_usage,
                        SUM(pu.discount_amount) as total_discount
                      FROM promotions p
                      LEFT JOIN promotion_usage pu ON p.promotion_id = pu.promotion_id
                      GROUP BY p.promotion_id
                      ORDER BY total_usage DESC
                      LIMIT 10";
            
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        return $promotionId ? $stmt->fetch() : $stmt->fetchAll();
    }
    
    /**
     * Auto-update expired promotions
     */
    public function updateExpiredPromotions() {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'expired' 
                  WHERE status = 'active' 
                  AND end_date < CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>
