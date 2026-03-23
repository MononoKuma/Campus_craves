<?php
// Database setup script for PostgreSQL
require_once '../src/config/database.php';

echo "🚀 Setting up PostgreSQL database...\n\n";

try {
    $db = new Database();
    $conn = $db->connect();
    echo "✅ Database connected successfully!\n\n";
    
    // Read the PostgreSQL schema file
    $schemaFile = '../postgresql-init.sql';
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
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
}
?>
