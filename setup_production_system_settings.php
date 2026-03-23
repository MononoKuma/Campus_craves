<?php
require_once __DIR__ . '/src/config/database.php';

echo "=== Setting up Production System Settings ===\n\n";

try {
    $db = new Database();
    $pdo = $db->connect();

    // Create the system_settings table
    echo "Creating system_settings table...\n";
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
    
    // Insert default settings
    echo "\nInserting default settings...\n";
    
    $defaultSettings = [
        'store_visibility_mode' => 'show_all',
        'maintenance_mode' => 'false',
        'allow_registrations' => 'true',
        'enable_reviews' => 'true'
    ];
    
    foreach ($defaultSettings as $key => $value) {
        $insertSQL = "
            INSERT INTO system_settings (key, value) 
            VALUES (?, ?)
            ON CONFLICT (key) DO UPDATE SET 
            value = EXCLUDED.value, 
            updated_at = CURRENT_TIMESTAMP
        ";
        
        $stmt = $pdo->prepare($insertSQL);
        $stmt->execute([$key, $value]);
        echo "✓ Setting '$key' = '$value'\n";
    }
    
    // Verify the settings
    echo "\nVerifying settings...\n";
    $verifyStmt = $pdo->query("SELECT key, value FROM system_settings ORDER BY key");
    while ($row = $verifyStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "✓ {$row['key']}: {$row['value']}\n";
    }
    
    echo "\n=== Production Setup Complete ===\n";
    echo "The system_settings table has been created and populated.\n";
    echo "Your store visibility feature should now work correctly!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your database connection and permissions.\n";
}
?>
