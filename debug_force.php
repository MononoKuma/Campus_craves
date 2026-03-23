<?php
// Force output - bypass all potential issues
ini_set('output_buffering', 'Off');
ini_set('zlib.output_compression', 'Off');

// Ensure headers are sent
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Immediate output
echo "=== FORCED DEBUG OUTPUT ===\n";
flush();

// Test basic functionality
echo "1. PHP Version: " . PHP_VERSION . "\n";
flush();

echo "2. Current Time: " . date('Y-m-d H:i:s') . "\n";
flush();

echo "3. Memory Limit: " . ini_get('memory_limit') . "\n";
flush();

echo "4. Max Execution Time: " . ini_get('max_execution_time') . "\n";
flush();

// Test file paths
echo "5. File Path Tests:\n";
echo "   __DIR__: " . __DIR__ . "\n";
echo "   getcwd(): " . getcwd() . "\n";
flush();

// Check if required files exist
echo "6. File Existence:\n";
$files = [
    '/.env',
    '/src/config/database.php',
    '/src/controllers/ComplaintController.php',
    '/src/models/Complaint.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . $file;
    $exists = file_exists($fullPath);
    echo "   $file: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    flush();
}

// Test environment variables by loading .env directly
echo "7. Environment Variables:\n";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
    echo "   ✓ .env file loaded\n";
} else {
    echo "   ✗ .env file not found\n";
}
flush();

$envVars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_TYPE'];
foreach ($envVars as $var) {
    echo "   $var: " . (getenv($var) ?: 'NOT SET') . "\n";
    flush();
}

// Try database connection with detailed error reporting
echo "8. Database Test:\n";
try {
    echo "   Including database.php...\n";
    flush();
    
    require_once __DIR__ . '/src/config/database.php';
    echo "   ✓ database.php included\n";
    flush();
    
    echo "   Creating Database object...\n";
    flush();
    
    $db = new Database();
    echo "   ✓ Database class instantiated\n";
    flush();
    
    echo "   Connecting...\n";
    flush();
    
    $conn = $db->connect();
    echo "   ✓ Database connected successfully\n";
    flush();
    
} catch (ParseError $e) {
    echo "   ✗ Parse Error: " . $e->getMessage() . "\n";
    echo "   ✗ Line: " . $e->getLine() . "\n";
    flush();
} catch (Error $e) {
    echo "   ✗ Fatal Error: " . $e->getMessage() . "\n";
    echo "   ✗ Line: " . $e->getLine() . "\n";
    flush();
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
    echo "   ✗ Code: " . $e->getCode() . "\n";
    echo "   ✗ Line: " . $e->getLine() . "\n";
    flush();
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "If you see this, the script is working!\n";
flush();

// Force script completion
exit(0);
?>
