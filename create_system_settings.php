<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== Creating system_settings table ===\n\n";

require_once __DIR__ . '/src/config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    echo "✓ Database connected\n";
    
    // Create table
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        key VARCHAR(100) PRIMARY KEY,
        value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✓ Table created\n";
    
    // Insert default values
    $inserts = [
        'store_visibility_mode' => 'show_all',
        'maintenance_mode' => 'false',
        'allow_registrations' => 'true',
        'enable_reviews' => 'true'
    ];
    
    foreach ($inserts as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO system_settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO NOTHING");
        $stmt->execute([$key, $value]);
        echo "✓ Inserted: $key\n";
    }
    
    // Verify
    $count = $pdo->query("SELECT COUNT(*) FROM system_settings")->fetchColumn();
    echo "✓ Table now has $count records\n";
    
    echo "\n=== SUCCESS! Table created ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
