<?php
/**
 * Permission Class
 * Advanced permission management and middleware
 */

require_once __DIR__ . '/../config/database.php';

class Permission {
    private $conn;
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }
    
    // ==================== PERMISSION CHECKING ====================
    
    /**
     * Check if user has permission
     */
    public static function has($permission) {
        if (!isset($_SESSION['permissions'])) {
            return false;
        }
        return in_array($permission, $_SESSION['permissions']);
    }
    
    /**
     * Check if user has role
     */
    public static function hasRole($role) {
        if (!isset($_SESSION['user_roles'])) {
            return false;
        }
        return in_array($role, $_SESSION['user_roles']);
    }
    
    /**
     * Check if user has ANY of the permissions
     */
    public static function hasAny($permissions) {
        if (!isset($_SESSION['permissions'])) {
            return false;
        }
        foreach ($permissions as $permission) {
            if (in_array($permission, $_SESSION['permissions'])) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has ALL permissions
     */
    public static function hasAll($permissions) {
        if (!isset($_SESSION['permissions'])) {
            return false;
        }
        foreach ($permissions as $permission) {
            if (!in_array($permission, $_SESSION['permissions'])) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Require permission or redirect
     */
    public static function require($permission, $redirect = null) {
        if (!self::has($permission)) {
            if ($redirect) {
                header('Location: ' . $redirect);
            } else {
                http_response_code(403);
                die('Access Denied: You do not have permission to access this page.');
            }
            exit;
        }
    }
    
    /**
     * Require role or redirect
     */
    public static function requireRole($role, $redirect = null) {
        if (!self::hasRole($role)) {
            if ($redirect) {
                header('Location: ' . $redirect);
            } else {
                http_response_code(403);
                die('Access Denied: This page requires ' . $role . ' role.');
            }
            exit;
        }
    }
    
    // ==================== PERMISSION MANAGEMENT ====================
    
    /**
     * Get all permissions
     */
    public function getAllPermissions($category = null) {
        $query = "SELECT * FROM permissions";
        
        if ($category) {
            $query .= " WHERE category = :category";
        }
        
        $query .= " ORDER BY category, permission_name";
        
        $stmt = $this->conn->prepare($query);
        
        if ($category) {
            $stmt->bindParam(':category', $category);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get permissions by category
     */
    public function getPermissionsByCategory() {
        $query = "SELECT * FROM permissions ORDER BY category, permission_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $permissions = $stmt->fetchAll();
        $grouped = [];
        
        foreach ($permissions as $perm) {
            $cat = $perm['category'] ?? 'other';
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $perm;
        }
        
        return $grouped;
    }
    
    /**
     * Get all roles
     */
    public function getAllRoles() {
        $query = "SELECT * FROM roles ORDER BY role_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get role permissions
     */
    public function getRolePermissions($role_id) {
        $query = "SELECT p.* FROM permissions p
                  JOIN role_permissions rp ON p.permission_id = rp.permission_id
                  WHERE rp.role_id = :role_id
                  ORDER BY p.category, p.permission_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Assign permission to role
     */
    public function assignPermissionToRole($role_id, $permission_id) {
        $query = "INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                  VALUES (:role_id, :permission_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->bindParam(':permission_id', $permission_id);
        return $stmt->execute();
    }
    
    /**
     * Remove permission from role
     */
    public function removePermissionFromRole($role_id, $permission_id) {
        $query = "DELETE FROM role_permissions 
                  WHERE role_id = :role_id AND permission_id = :permission_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->bindParam(':permission_id', $permission_id);
        return $stmt->execute();
    }
    
    /**
     * Sync role permissions (replace all)
     */
    public function syncRolePermissions($role_id, $permission_ids) {
        // Delete existing
        $this->conn->prepare("DELETE FROM role_permissions WHERE role_id = :role_id")
                   ->execute([':role_id' => $role_id]);
        
        // Insert new
        if (!empty($permission_ids)) {
            $query = "INSERT INTO role_permissions (role_id, permission_id) VALUES ";
            $values = [];
            foreach ($permission_ids as $perm_id) {
                $values[] = "($role_id, $perm_id)";
            }
            $query .= implode(', ', $values);
            $this->conn->exec($query);
        }
        
        return true;
    }
    
    /**
     * Assign role to user
     */
    public function assignRoleToUser($user_id, $role_id, $assigned_by = null) {
        $query = "INSERT IGNORE INTO user_roles (user_id, role_id, assigned_by) 
                  VALUES (:user_id, :role_id, :assigned_by)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->bindParam(':assigned_by', $assigned_by);
        return $stmt->execute();
    }
    
    /**
     * Remove role from user
     */
    public function removeRoleFromUser($user_id, $role_id) {
        $query = "DELETE FROM user_roles 
                  WHERE user_id = :user_id AND role_id = :role_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':role_id', $role_id);
        return $stmt->execute();
    }
    
    /**
     * Get user roles
     */
    public function getUserRoles($user_id) {
        $query = "SELECT r.* FROM roles r
                  JOIN user_roles ur ON r.role_id = ur.role_id
                  WHERE ur.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ==================== AUDIT LOG ====================
    
    /**
     * Log action
     */
    public static function logAction($action, $entity_type, $entity_id = null, $old_values = null, $new_values = null) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $database = Database::getInstance();
        $conn = $database->getConnection();
        
        $query = "INSERT INTO audit_logs 
                  (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                  VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip, :ua)";
        
        $stmt = $conn->prepare($query);
        
        $user_id = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $old_json = $old_values ? json_encode($old_values) : null;
        $new_json = $new_values ? json_encode($new_values) : null;
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':entity_type', $entity_type);
        $stmt->bindParam(':entity_id', $entity_id);
        $stmt->bindParam(':old_values', $old_json);
        $stmt->bindParam(':new_values', $new_json);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':ua', $ua);
        
        return $stmt->execute();
    }
    
    /**
     * Get audit logs
     */
    public function getAuditLogs($filters = [], $limit = 100) {
        $query = "SELECT al.*, u.username, u.full_name
                  FROM audit_logs al
                  LEFT JOIN users u ON al.user_id = u.user_id
                  WHERE 1=1";
        
        if (!empty($filters['user_id'])) {
            $query .= " AND al.user_id = :user_id";
        }
        
        if (!empty($filters['action'])) {
            $query .= " AND al.action = :action";
        }
        
        if (!empty($filters['entity_type'])) {
            $query .= " AND al.entity_type = :entity_type";
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(al.created_at) >= :date_from";
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(al.created_at) <= :date_to";
        }
        
        $query .= " ORDER BY al.created_at DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($filters as $key => $value) {
            if ($value) {
                $stmt->bindValue(":$key", $value);
            }
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
