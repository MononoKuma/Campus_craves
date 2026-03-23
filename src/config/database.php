<?php
// Load environment variables from .env file
function loadEnv($file) {
    if (!file_exists($file)) {
        return false;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
    return true;
}

// Load environment variables
loadEnv(__DIR__ . '/../../.env');

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
        $this->port = getenv('DB_PORT') ?: '5432';
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
            $this->db_type = 'pgsql'; // Default to PostgreSQL
        }
        
        $this->conn = null;
    }

    public function connect() {
        if ($this->conn === null) {
            try {
                // Check if DATABASE_URL is available (Render format)
                $databaseUrl = getenv('DATABASE_URL');
                if ($databaseUrl) {
                    // Parse DATABASE_URL to create DSN
                    $parsed = parse_url($databaseUrl);
                    $dbname = ltrim($parsed['path'], '/');
                    $dsn = "pgsql:host={$parsed['host']};port={$parsed['port']};dbname={$dbname};sslmode=require";
                    $username = $parsed['user'];
                    $password = $parsed['pass'];
                } else {
                    $dsn = $this->db_type === 'mysql' 
                        ? "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4"
                        : "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name};sslmode=prefer";
                    $username = $this->username;
                    $password = $this->password;
                }
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 10,
                ];
                
                if ($this->db_type === 'mysql' && defined('PDO::MYSQL_ATTR_RECONNECT')) {
                    $options[constant('PDO::MYSQL_ATTR_RECONNECT')] = true;
                }
                
                $this->conn = new PDO($dsn, $username, $password, $options);
                
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