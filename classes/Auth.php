<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';

class Auth {
    private $conn;
    private $userModel;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
        $this->userModel = new User();
    }

    public function login($username, $password) {
        $user = $this->userModel->getByUsername($username);
        
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        if ($user['status'] !== 'active') {
            return false;
        }

        // Update last login
        $query = "UPDATE users SET last_login = NOW() WHERE user_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $user['user_id']);
        $stmt->execute();

        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_id'] = $user['role_id'] ?? 6; // Default: Customer (role_id = 6)
        
        // Get role info
        if (isset($user['role_id'])) {
            $roleQuery = "SELECT role_name, description FROM roles WHERE role_id = :role_id";
            $stmt = $this->conn->prepare($roleQuery);
            $stmt->bindParam(':role_id', $user['role_id']);
            $stmt->execute();
            $role = $stmt->fetch();
            
            if ($role) {
                $_SESSION['role_name'] = $role['role_name'];
                $_SESSION['role_description'] = $role['description'];
            }
        }

        return true;
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public static function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/admin/index.php');
            exit;
        }
    }

    public static function requireManager() {
        self::checkAuth();
        // role_id = 1 is Manager
        if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
            header('Location: ' . APP_URL . '/admin/dashboard.php');
            exit;
        }
    }

    public static function isManager() {
        // role_id = 1 is Manager
        return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
    }

    public static function isCustomer() {
        // role_id = 6 is Customer
        return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 6;
    }
    
    public static function hasRole($roleId) {
        return isset($_SESSION['role_id']) && $_SESSION['role_id'] == $roleId;
    }
    
    public static function getRoleName() {
        return $_SESSION['role_name'] ?? 'Guest';
    }

    public function register($data) {
        // Check if username exists
        if ($this->userModel->getByUsername($data['username'])) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        // Check if email exists
        if ($this->userModel->getByEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['status'] = 'active';
        $data['role_id'] = 6; // Auto set as Customer (role_id = 6)

        if ($this->userModel->create($data)) {
            return ['success' => true, 'message' => 'User registered successfully'];
        }

        return ['success' => false, 'message' => 'Registration failed'];
    }
}
?>
