<?php
require_once __DIR__ . '/src/config/database.php';

echo "=== Setting up system_settings table ===\n\n";

$db = new Database();
$pdo = $db->connect();

try {
    // Create the system_settings table
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS system_settings (
            key VARCHAR(100) PRIMARY KEY,
            value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $pdo->exec($createTableSQL);
    echo "✓ system_settings table created successfully\n";
    
    // Insert default setting
    $insertSQL = "
        INSERT INTO system_settings (key, value) 
        VALUES ('store_visibility_mode', 'show_all')
        ON CONFLICT (key) DO UPDATE SET 
        value = EXCLUDED.value, 
        updated_at = CURRENT_TIMESTAMP
    ";
    
    $pdo->exec($insertSQL);
    echo "✓ Default store_visibility_mode setting inserted\n";
    
    echo "\n=== Setup completed successfully ===\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
