<?php
// Database connection test
require_once __DIR__ . '/src/config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    echo "✅ Database connection successful!\n";
    
    // Check if users table exists
    $stmt = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_name = 'users'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        // Test query on users table
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "📊 Users table count: " . $result['count'] . "\n";
        echo "🎉 Database is ready!\n";
    } else {
        echo "⚠️ Tables not created yet. Run the PostgreSQL init script first.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    
    // Debug environment variables
    echo "\n🔍 Environment Variables:\n";
    echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
    echo "DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "\n";
    echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "\n";
    echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT SET') . "\n";
    echo "DB_TYPE: " . (getenv('DB_TYPE') ?: 'NOT SET') . "\n";
}
?>
