<?php
/**
 * Production Database Setup Script
 * Upload this file to your production server and access it via browser
 * URL: https://yourdomain.com/production_setup.php
 * 
 * IMPORTANT: Delete this file after running it successfully!
 */

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your database configuration
require_once __DIR__ . '/src/config/database.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .warning { color: orange; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <h1>🔧 Production Database Setup</h1>
    
    <div class="warning">
        <strong>⚠️ IMPORTANT:</strong> This script will set up the system_settings table in your production database. 
        After successful completion, please delete this file for security reasons.
    </div>

    <?php
    try {
        echo "<div class='info'>🔄 Connecting to database...</div>";
        
        $db = new Database();
        $pdo = $db->connect();
        
        if (!$pdo) {
            throw new Exception("Failed to connect to database");
        }
        
        echo "<div class='success'>✅ Database connection successful!</div>";
        
        // Check if table already exists
        echo "<div class='info'>🔍 Checking if system_settings table exists...</div>";
        
        $checkTable = $pdo->prepare("SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'system_settings'
        )");
        $checkTable->execute();
        $tableExists = $checkTable->fetchColumn();
        
        if ($tableExists) {
            echo "<div class='info'>ℹ️ system_settings table already exists</div>";
        } else {
            // Create the table
            echo "<div class='info'>🏗️ Creating system_settings table...</div>";
            
            $createTableSQL = "
                CREATE TABLE system_settings (
                    key VARCHAR(100) PRIMARY KEY,
                    value TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            
            $pdo->exec($createTableSQL);
            echo "<div class='success'>✅ system_settings table created successfully!</div>";
        }
        
        // Insert default settings
        echo "<div class='info'>⚙️ Setting up default system settings...</div>";
        
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
            echo "<div class='success'>✓ Set '$key' = '$value'</div>";
        }
        
        // Verify all settings
        echo "<div class='info'>🔍 Verifying all settings...</div>";
        $verifyStmt = $pdo->query("SELECT key, value FROM system_settings ORDER BY key");
        
        echo "<h3>📋 Current System Settings:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Setting</th><th>Value</th></tr>";
        
        while ($row = $verifyStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td><strong>" . htmlspecialchars($row['key']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['value']) . "</td></tr>";
        }
        echo "</table>";
        
        echo "<div class='success'>";
        echo "<h2>🎉 Setup Complete!</h2>";
        echo "<p>The system_settings table has been successfully created and configured.</p>";
        echo "<p>Your store visibility feature should now work correctly!</p>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<strong>🔒 SECURITY REMINDER:</strong> Please delete this file (production_setup.php) from your server immediately!";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h2>❌ Error Occurred</h2>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Please check your database connection settings and try again.</p>";
        echo "</div>";
        
        // Show debug info
        echo "<h3>🐛 Debug Information:</h3>";
        echo "<pre>";
        echo "Database Type: " . (get_class($pdo) ?? 'Unknown') . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Server: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "\n";
        echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
        echo "</pre>";
    }
    ?>
    
    <hr>
    <p><small>This script was created to set up the system_settings table for the Capus Craves application.</small></p>
</body>
</html>
