<?php
require_once __DIR__ . '/../config/database.php';

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $db_config = get_database_config();
        $dsn = "mysql:host=" . $db_config['host'] . ";dbname=" . $db_config['dbname'] . ";charset=" . $db_config['charset'];
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $db_config['user'], $db_config['pass'], $options);
        } catch (PDOException $e) {
            // In a real app, you'd log this error and show a generic error page.
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // Prevent cloning and unserialization
    private function __clone() { }
    public function __wakeup() { }
}
?>
