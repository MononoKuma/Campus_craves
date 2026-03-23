<?php
// Database setup script for PostgreSQL
require_once __DIR__ . '/src/config/database.php';

echo "🚀 Setting up PostgreSQL database...\n\n";

try {
    $db = new Database();
    $conn = $db->connect();
    echo "✅ Database connected successfully!\n\n";
    
    // Show database info for debugging
    echo "🔍 Database Connection Info:\n";
    echo "   Host: " . getenv('DB_HOST') . "\n";
    echo "   Port: " . getenv('DB_PORT') . "\n";
    echo "   Database: " . getenv('DB_NAME') . "\n";
    echo "   User: " . getenv('DB_USER') . "\n";
    echo "   Type: " . (getenv('DB_TYPE') ?: 'auto-detected') . "\n\n";
    
    // Read the PostgreSQL schema file
    $schemaFile = __DIR__ . '/postgresql-init.sql';
    if (!file_exists($schemaFile)) {
        echo "❌ Schema file not found: $schemaFile\n";
        exit;
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "📝 Executing " . count($statements) . " SQL statements...\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $conn->exec($statement);
            echo "✅ " . substr($statement, 0, 50) . "...\n";
            $successCount++;
        } catch (PDOException $e) {
            // Check if it's a "already exists" error (which is OK)
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'duplicate key') !== false) {
                echo "⏭️  " . substr($statement, 0, 50) . "... (already exists)\n";
                $successCount++;
            } else {
                echo "❌ " . substr($statement, 0, 50) . "... ERROR: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n🎉 Database setup complete!\n";
    echo "✅ Successful: $successCount statements\n";
    echo "❌ Errors: $errorCount statements\n\n";
    
    // Test the setup
    echo "🧪 Testing database setup...\n";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Users in database: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "🎉 Database is ready for use!\n";
    } else {
        echo "⚠️  No users found - you may need to check the setup\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n\n";
    
    // Show debug info
    echo "🔍 Debug Information:\n";
    echo "   Environment Variables:\n";
    echo "   DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
    echo "   DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "\n";
    echo "   DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "\n";
    echo "   DB_USER: " . (getenv('DB_USER') ?: 'NOT SET') . "\n";
    echo "   DB_PASSWORD: " . (getenv('DB_PASSWORD') ? '*** SET ***' : 'NOT SET') . "\n";
    echo "   DB_TYPE: " . (getenv('DB_TYPE') ?: 'NOT SET') . "\n";
    
    // Test basic connection without database
    echo "\n🧪 Testing basic connection...\n";
    try {
        $testHost = getenv('DB_HOST');
        $testPort = getenv('DB_PORT') ?: '5432';
        $testUser = getenv('DB_USER');
        $testPass = getenv('DB_PASSWORD');
        
        $testConn = new PDO("pgsql:host=$testHost;port=$testPort;dbname=postgres", $testUser, $testPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        echo "✅ Basic connection works! Issue might be with 'capus_craves' database.\n";
    } catch (Exception $testE) {
        echo "❌ Basic connection failed: " . $testE->getMessage() . "\n";
    }
}
?>
