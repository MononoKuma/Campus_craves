<?php
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $db_type;
    private $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'db';
        $this->port = getenv('DB_PORT') ?: '3306';
        $this->db_name = getenv('DB_NAME') ?: 'capus_craves';
        $this->username = getenv('DB_USER') ?: 'capus_user';
        $this->password = getenv('DB_PASSWORD') ?: 'your_secure_user_password';
        
        // Auto-detect database type based on host or environment variable
        $db_type = getenv('DB_TYPE');
        if ($db_type) {
            $this->db_type = $db_type;
        } elseif (strpos($this->host, 'dpg-') !== false || strpos($this->host, 'postgres') !== false) {
            $this->db_type = 'pgsql'; // Render PostgreSQL
        } else {
            $this->db_type = 'mysql'; // Default to MySQL
        }
        
        $this->conn = null;
    }

    public function connect() {
        if ($this->conn === null) {
            try {
                $dsn = $this->db_type === 'mysql' 
                    ? "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4"
                    : "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 10,
                ];
                
                if ($this->db_type === 'mysql' && defined('PDO::MYSQL_ATTR_RECONNECT')) {
                    $options[PDO::MYSQL_ATTR_RECONNECT] = true;
                }
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch(PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                // Show actual error in development, generic in production
                $error_message = "Database connection failed. Please check your configuration.";
                if (getenv('ENVIRONMENT') === 'development' || strpos($e->getMessage(), 'connection') !== false) {
                    $error_message .= " (" . $e->getMessage() . ")";
                }
                throw new Exception($error_message);
            }
        }
        return $this->conn;
    }

    public function prepare($sql) {
        return $this->connect()->prepare($sql);
    }

    public function query($sql) {
        return $this->connect()->query($sql);
    }

    public function lastInsertId() {
        return $this->connect()->lastInsertId();
    }
}
?>