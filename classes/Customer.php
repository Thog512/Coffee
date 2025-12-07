<?php
/**
 * Customer Class
 * Handles customer portal, loyalty program, and customer management
 */

require_once __DIR__ . '/../config/database.php';

class Customer {
    private $conn;
    private $customers_table = "customers";
    private $addresses_table = "customer_addresses";
    private $loyalty_trans_table = "loyalty_transactions";
    private $rewards_table = "loyalty_rewards";
    private $redemptions_table = "reward_redemptions";
    private $favorites_table = "customer_favorites";
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }
    
    // ==================== CUSTOMER MANAGEMENT ====================
    
    /**
     * Get all customers
     */
    public function getAllCustomers($search = null, $tier = null, $status = null) {
        $query = "SELECT * FROM " . $this->customers_table . " WHERE 1=1";
        
        if ($search) {
            $query .= " AND (full_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
        }
        
        if ($tier) {
            $query .= " AND loyalty_tier = :tier";
        }
        
        if ($status) {
            $query .= " AND status = :status";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($search) {
            $search_param = "%{$search}%";
            $stmt->bindParam(':search', $search_param);
        }
        
        if ($tier) {
            $stmt->bindParam(':tier', $tier);
        }
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get customer by ID
     */
    public function getCustomerById($id) {
        $query = "SELECT * FROM " . $this->customers_table . " WHERE customer_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get customer by email
     */
    public function getCustomerByEmail($email) {
        $query = "SELECT * FROM " . $this->customers_table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get customer by user_id
     */
    public function getCustomerByUserId($user_id) {
        $query = "SELECT * FROM " . $this->customers_table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Create new customer
     */
    public function createCustomer($data) {
        $query = "INSERT INTO " . $this->customers_table . "
                  (user_id, full_name, email, phone, date_of_birth, gender, address, city, district, status)
                  VALUES (:user_id, :full_name, :email, :phone, :dob, :gender, :address, :city, :district, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':dob', $data['date_of_birth']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':district', $data['district']);
        $status = $data['status'] ?? 'active';
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update customer
     */
    public function updateCustomer($id, $data) {
        $query = "UPDATE " . $this->customers_table . "
                  SET full_name = :full_name,
                      phone = :phone,
                      date_of_birth = :dob,
                      gender = :gender,
                      address = :address,
                      city = :city,
                      district = :district
                  WHERE customer_id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':dob', $data['date_of_birth']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':district', $data['district']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Update customer status
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->customers_table . " SET status = :status WHERE customer_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    // ==================== ADDRESSES ====================
    
    /**
     * Get customer addresses
     */
    public function getCustomerAddresses($customer_id) {
        $query = "SELECT * FROM " . $this->addresses_table . " 
                  WHERE customer_id = :customer_id 
                  ORDER BY is_default DESC, created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Add address
     */
    public function addAddress($data) {
        // If this is default, unset other defaults
        if ($data['is_default']) {
            $this->conn->prepare("UPDATE " . $this->addresses_table . " 
                                 SET is_default = FALSE 
                                 WHERE customer_id = :customer_id")
                      ->execute([':customer_id' => $data['customer_id']]);
        }
        
        $query = "INSERT INTO " . $this->addresses_table . "
                  (customer_id, address_label, recipient_name, recipient_phone, 
                   address_line, city, district, ward, postal_code, is_default)
                  VALUES (:customer_id, :label, :name, :phone, :address, :city, :district, :ward, :postal, :is_default)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':customer_id', $data['customer_id']);
        $stmt->bindParam(':label', $data['address_label']);
        $stmt->bindParam(':name', $data['recipient_name']);
        $stmt->bindParam(':phone', $data['recipient_phone']);
        $stmt->bindParam(':address', $data['address_line']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':district', $data['district']);
        $stmt->bindParam(':ward', $data['ward']);
        $stmt->bindParam(':postal', $data['postal_code']);
        $stmt->bindParam(':is_default', $data['is_default']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete address
     */
    public function deleteAddress($address_id) {
        $query = "DELETE FROM " . $this->addresses_table . " WHERE address_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $address_id);
        return $stmt->execute();
    }
    
    // ==================== LOYALTY POINTS ====================
    
    /**
     * Add loyalty points (called after order completion)
     */
    public function addLoyaltyPoints($customer_id, $order_id, $order_amount) {
        try {
            $stmt = $this->conn->prepare("CALL sp_add_loyalty_points(:customer_id, :order_id, :amount)");
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':amount', $order_amount);
            return $stmt->execute();
        } catch (Exception $e) {
            // Fallback if stored procedure doesn't exist
            $points = floor($order_amount / 10000);
            return $this->addPointsManual($customer_id, $points, $order_id, "Tích điểm từ đơn hàng #{$order_id}");
        }
    }
    
    /**
     * Add points manually
     */
    public function addPointsManual($customer_id, $points, $order_id = null, $description = '') {
        $customer = $this->getCustomerById($customer_id);
        $new_balance = $customer['loyalty_points'] + $points;
        
        // Add transaction
        $query = "INSERT INTO " . $this->loyalty_trans_table . "
                  (customer_id, transaction_type, points, order_id, description, balance_after)
                  VALUES (:customer_id, 'earn', :points, :order_id, :description, :balance)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':points', $points);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':balance', $new_balance);
        $stmt->execute();
        
        // Update customer
        $this->conn->prepare("UPDATE " . $this->customers_table . " 
                             SET loyalty_points = loyalty_points + :points 
                             WHERE customer_id = :id")
                   ->execute([':points' => $points, ':id' => $customer_id]);
        
        return true;
    }
    
    /**
     * Get loyalty transactions
     */
    public function getLoyaltyTransactions($customer_id, $limit = 50) {
        $query = "SELECT * FROM " . $this->loyalty_trans_table . "
                  WHERE customer_id = :customer_id
                  ORDER BY created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ==================== REWARDS ====================
    
    /**
     * Get all rewards
     */
    public function getAllRewards($active_only = true) {
        $query = "SELECT * FROM " . $this->rewards_table;
        
        if ($active_only) {
            $query .= " WHERE is_active = TRUE";
        }
        
        $query .= " ORDER BY points_required ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Redeem reward
     */
    public function redeemReward($customer_id, $reward_id) {
        try {
            $stmt = $this->conn->prepare("CALL sp_redeem_reward(:customer_id, :reward_id, @success, @message)");
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':reward_id', $reward_id);
            $stmt->execute();
            
            $result = $this->conn->query("SELECT @success as success, @message as message")->fetch();
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get customer redemptions
     */
    public function getCustomerRedemptions($customer_id) {
        $query = "SELECT rr.*, lr.reward_name, lr.reward_type, lr.reward_value
                  FROM " . $this->redemptions_table . " rr
                  JOIN " . $this->rewards_table . " lr ON rr.reward_id = lr.reward_id
                  WHERE rr.customer_id = :customer_id
                  ORDER BY rr.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ==================== FAVORITES ====================
    
    /**
     * Get customer favorites
     */
    public function getFavorites($customer_id) {
        $query = "SELECT cf.*, p.product_name, p.price, p.image_url
                  FROM " . $this->favorites_table . " cf
                  JOIN products p ON cf.product_id = p.product_id
                  WHERE cf.customer_id = :customer_id
                  ORDER BY cf.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Add to favorites
     */
    public function addFavorite($customer_id, $product_id) {
        $query = "INSERT IGNORE INTO " . $this->favorites_table . " (customer_id, product_id) 
                  VALUES (:customer_id, :product_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }
    
    /**
     * Remove from favorites
     */
    public function removeFavorite($customer_id, $product_id) {
        $query = "DELETE FROM " . $this->favorites_table . " 
                  WHERE customer_id = :customer_id AND product_id = :product_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }
    
    // ==================== STATISTICS ====================
    
    /**
     * Get customer statistics
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
                    COUNT(CASE WHEN loyalty_tier = 'platinum' THEN 1 END) as platinum_members,
                    COUNT(CASE WHEN loyalty_tier = 'gold' THEN 1 END) as gold_members,
                    COUNT(CASE WHEN loyalty_tier = 'silver' THEN 1 END) as silver_members,
                    COUNT(CASE WHEN loyalty_tier = 'bronze' THEN 1 END) as bronze_members,
                    SUM(total_spent) as total_revenue,
                    SUM(loyalty_points) as total_points_issued,
                    AVG(total_orders) as avg_orders_per_customer
                  FROM " . $this->customers_table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get top customers
     */
    public function getTopCustomers($limit = 10) {
        $query = "SELECT * FROM " . $this->customers_table . "
                  WHERE status = 'active'
                  ORDER BY total_spent DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get customer order history
     */
    public function getOrderHistory($customer_id, $limit = 20) {
        $query = "SELECT o.*, 
                         COUNT(oi.item_id) as total_items,
                         SUM(oi.quantity) as total_quantity
                  FROM orders o
                  LEFT JOIN order_items oi ON o.order_id = oi.order_id
                  WHERE o.customer_id = :customer_id
                  GROUP BY o.order_id
                  ORDER BY o.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
