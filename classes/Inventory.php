<?php
require_once __DIR__ . '/Database.php';

class Inventory {
    private $conn;
    private $table = 'inventory';

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (item_name, item_type, quantity, unit, min_quantity, expiration_date, supplier, cost_per_unit) 
                  VALUES (:item_name, :item_type, :quantity, :unit, :min_quantity, :expiration_date, :supplier, :cost_per_unit)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':item_name', $data['item_name']);
        $stmt->bindParam(':item_type', $data['item_type']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':min_quantity', $data['min_quantity']);
        $stmt->bindParam(':expiration_date', $data['expiration_date']);
        $stmt->bindParam(':supplier', $data['supplier']);
        $stmt->bindParam(':cost_per_unit', $data['cost_per_unit']);
        
        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY item_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE inventory_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getLowStock() {
        $query = "SELECT * FROM " . $this->table . " WHERE quantity <= min_quantity ORDER BY quantity ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getExpiringSoon($days = 7) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE expiration_date IS NOT NULL 
                  AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  AND expiration_date >= CURDATE()
                  ORDER BY expiration_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET item_name = :item_name,
                      item_type = :item_type,
                      quantity = :quantity,
                      unit = :unit,
                      min_quantity = :min_quantity,
                      expiration_date = :expiration_date,
                      supplier = :supplier,
                      cost_per_unit = :cost_per_unit,
                      status = :status
                  WHERE inventory_id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':item_name', $data['item_name']);
        $stmt->bindParam(':item_type', $data['item_type']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':min_quantity', $data['min_quantity']);
        $stmt->bindParam(':expiration_date', $data['expiration_date']);
        $stmt->bindParam(':supplier', $data['supplier']);
        $stmt->bindParam(':cost_per_unit', $data['cost_per_unit']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE inventory_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateQuantity($id, $quantityChange, $action, $reason, $userId) {
        $this->conn->beginTransaction();
        
        try {
            // Get current quantity
            $current = $this->getById($id);
            if (!$current) {
                throw new Exception("Item not found");
            }
            
            $newQuantity = $current['quantity'] + $quantityChange;
            
            // Update inventory
            $query = "UPDATE " . $this->table . " SET quantity = :quantity, last_restocked = NOW() WHERE inventory_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quantity', $newQuantity);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Update status based on quantity
            $this->updateStatus($id);

            // Log the change
            $logQuery = "INSERT INTO inventory_logs (inventory_id, action_type, quantity_change, quantity_after, reason, performed_by) 
                         VALUES (:inventory_id, :action_type, :quantity_change, :quantity_after, :reason, :performed_by)";
            $logStmt = $this->conn->prepare($logQuery);
            $logStmt->bindParam(':inventory_id', $id);
            $logStmt->bindParam(':action_type', $action);
            $logStmt->bindParam(':quantity_change', $quantityChange);
            $logStmt->bindParam(':quantity_after', $newQuantity);
            $logStmt->bindParam(':reason', $reason);
            $logStmt->bindParam(':performed_by', $userId);
            $logStmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function updateStatus($id) {
        $item = $this->getById($id);
        if (!$item) return false;
        
        $status = 'available';
        
        // Check if expired
        if ($item['expiration_date'] && strtotime($item['expiration_date']) < time()) {
            $status = 'expired';
        }
        // Check if out of stock
        elseif ($item['quantity'] <= 0) {
            $status = 'out_of_stock';
        }
        // Check if low stock
        elseif ($item['quantity'] <= $item['min_quantity']) {
            $status = 'low_stock';
        }
        
        $query = "UPDATE " . $this->table . " SET status = :status WHERE inventory_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAlerts() {
        $alerts = [];
        
        // Low stock alerts
        $lowStock = $this->getLowStock();
        foreach ($lowStock as $item) {
            $alerts[] = [
                'type' => 'low_stock',
                'severity' => 'warning',
                'item_id' => $item['inventory_id'],
                'item_name' => $item['item_name'],
                'message' => $item['item_name'] . ' sắp hết (còn ' . $item['quantity'] . ' ' . $item['unit'] . ')',
                'quantity' => $item['quantity'],
                'min_quantity' => $item['min_quantity']
            ];
        }
        
        // Expiring soon alerts
        $expiring = $this->getExpiringSoon(7);
        foreach ($expiring as $item) {
            $daysLeft = ceil((strtotime($item['expiration_date']) - time()) / 86400);
            $alerts[] = [
                'type' => 'expiring_soon',
                'severity' => $daysLeft <= 3 ? 'danger' : 'warning',
                'item_id' => $item['inventory_id'],
                'item_name' => $item['item_name'],
                'message' => $item['item_name'] . ' sắp hết hạn (' . $daysLeft . ' ngày)',
                'expiration_date' => $item['expiration_date'],
                'days_left' => $daysLeft
            ];
        }
        
        return $alerts;
    }

    public function getLogs($inventoryId = null, $limit = 50) {
        if ($inventoryId) {
            $query = "SELECT il.*, i.item_name, u.full_name as performed_by_name
                      FROM inventory_logs il
                      LEFT JOIN inventory i ON il.inventory_id = i.inventory_id
                      LEFT JOIN users u ON il.performed_by = u.user_id
                      WHERE il.inventory_id = :inventory_id
                      ORDER BY il.created_at DESC
                      LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':inventory_id', $inventoryId, PDO::PARAM_INT);
        } else {
            $query = "SELECT il.*, i.item_name, u.full_name as performed_by_name
                      FROM inventory_logs il
                      LEFT JOIN inventory i ON il.inventory_id = i.inventory_id
                      LEFT JOIN users u ON il.performed_by = u.user_id
                      ORDER BY il.created_at DESC
                      LIMIT :limit";
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getStatistics() {
        $stats = [];
        
        // Total items
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->query($query);
        $stats['total_items'] = $stmt->fetch()['total'];
        
        // Low stock count
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE quantity <= min_quantity";
        $stmt = $this->conn->query($query);
        $stats['low_stock_count'] = $stmt->fetch()['count'];
        
        // Expiring soon count
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE expiration_date IS NOT NULL 
                  AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  AND expiration_date >= CURDATE()";
        $stmt = $this->conn->query($query);
        $stats['expiring_soon_count'] = $stmt->fetch()['count'];
        
        // Total inventory value
        $query = "SELECT SUM(quantity * cost_per_unit) as total_value FROM " . $this->table;
        $stmt = $this->conn->query($query);
        $stats['total_value'] = $stmt->fetch()['total_value'] ?? 0;
        
        return $stats;
    }
    
    // ==================== RECIPE MANAGEMENT ====================
    
    /**
     * Get product recipe (công thức sản phẩm)
     */
    public function getProductRecipe($product_id) {
        $query = "SELECT pr.*, i.item_name, i.unit as inventory_unit, i.cost_per_unit
                  FROM product_recipes pr
                  JOIN inventory i ON pr.ingredient_id = i.inventory_id
                  WHERE pr.product_id = :product_id
                  ORDER BY pr.created_at";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add ingredient to recipe
     */
    public function addRecipeIngredient($product_id, $ingredient_id, $quantity, $unit, $notes = '') {
        $query = "INSERT INTO product_recipes (product_id, ingredient_id, quantity, unit, notes)
                  VALUES (:product_id, :ingredient_id, :quantity, :unit, :notes)
                  ON DUPLICATE KEY UPDATE 
                      quantity = VALUES(quantity), 
                      unit = VALUES(unit), 
                      notes = VALUES(notes)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':ingredient_id', $ingredient_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':unit', $unit);
        $stmt->bindParam(':notes', $notes);
        
        return $stmt->execute();
    }
    
    /**
     * Remove ingredient from recipe
     */
    public function removeRecipeIngredient($recipe_id) {
        $query = "DELETE FROM product_recipes WHERE recipe_id = :recipe_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':recipe_id', $recipe_id);
        return $stmt->execute();
    }
    
    /**
     * Convert unit for cost calculation
     */
    private function convertUnitForCost($quantity, $from_unit, $to_unit) {
        $from = strtolower(trim($from_unit));
        $to = strtolower(trim($to_unit));
        
        if ($from === $to) {
            return $quantity;
        }
        
        $conversions = [
            'kg_to_g' => 1000, 'g_to_kg' => 0.001,
            'l_to_ml' => 1000, 'lít_to_ml' => 1000,
            'ml_to_l' => 0.001, 'ml_to_lít' => 0.001,
            'kg_to_ml' => 1000, 'ml_to_kg' => 0.001,
            'l_to_kg' => 1, 'lít_to_kg' => 1,
            'kg_to_l' => 1, 'kg_to_lít' => 1,
            'g_to_ml' => 1, 'ml_to_g' => 1,
            'ml_to_lon' => 0.001, 'lon_to_ml' => 1000
        ];
        
        $key = $from . '_to_' . $to;
        
        if (isset($conversions[$key])) {
            return $quantity * $conversions[$key];
        }
        
        error_log("Warning: No conversion found from $from_unit to $to_unit");
        return $quantity;
    }
    
    /**
     * Calculate ingredient cost with unit conversion
     */
    public function calculateIngredientCost($recipe_quantity, $recipe_unit, $inventory_unit, $inventory_cost) {
        $converted_quantity = $this->convertUnitForCost($recipe_quantity, $recipe_unit, $inventory_unit);
        return $converted_quantity * $inventory_cost;
    }
    
    /**
     * Get product cost (giá vốn dựa trên nguyên liệu) WITH UNIT CONVERSION
     */
    public function getProductCost($product_id) {
        $query = "SELECT pr.quantity, pr.unit as recipe_unit, 
                         i.cost_per_unit, i.unit as inventory_unit
                  FROM product_recipes pr
                  JOIN inventory i ON pr.ingredient_id = i.inventory_id
                  WHERE pr.product_id = :product_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        $total_cost = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $converted_quantity = $this->convertUnitForCost(
                $row['quantity'], 
                $row['recipe_unit'], 
                $row['inventory_unit']
            );
            $total_cost += $converted_quantity * $row['cost_per_unit'];
        }
        
        return $total_cost;
    }
    
    /**
     * Deduct ingredients for order (gọi stored procedure)
     */
    public function deductIngredientsForOrder($order_id) {
        try {
            $stmt = $this->conn->prepare("CALL sp_deduct_ingredients_for_order(:order_id)");
            $stmt->bindParam(':order_id', $order_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error deducting ingredients: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get ingredient usage for order
     */
    public function getIngredientUsageByOrder($order_id) {
        $query = "SELECT iul.*, i.item_name, p.product_name
                  FROM ingredient_usage_log iul
                  JOIN inventory i ON iul.ingredient_id = i.inventory_id
                  JOIN products p ON iul.product_id = p.product_id
                  WHERE iul.order_id = :order_id
                  ORDER BY iul.usage_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
