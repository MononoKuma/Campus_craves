<?php
// Ultra-simple test - no dependencies
echo "STARTING TEST...\n";

try {
    echo "Checking PHP version: " . PHP_VERSION . "\n";
    echo "Current directory: " . __DIR__ . "\n";
    
    // Test if files exist
    if (file_exists(__DIR__ . '/src/config/database.php')) {
        echo "✓ database.php exists\n";
    } else {
        echo "✗ database.php missing\n";
    }
    
    // Test environment
    echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
    
    // Try to include database
    require_once __DIR__ . '/src/config/database.php';
    echo "✓ database.php included\n";
    
    $db = new Database();
    echo "✓ Database class instantiated\n";
    
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "TEST COMPLETED\n";
?>
