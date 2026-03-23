<?php
require_once __DIR__ . '/src/config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Create persistent cart table
    $sql = "
    CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        session_id VARCHAR(255) NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $conn->exec($sql);
    echo "Cart table created successfully.\n";
    
    // Create indexes
    $indexes = [
        "CREATE INDEX idx_cart_user ON cart_items(user_id)",
        "CREATE INDEX idx_cart_session ON cart_items(session_id)",
        "CREATE INDEX idx_cart_product ON cart_items(product_id)"
    ];
    
    foreach ($indexes as $indexSql) {
        try {
            $conn->exec($indexSql);
            echo "Index created successfully.\n";
        } catch (Exception $e) {
            echo "Index might already exist: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Cart table setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error creating cart table: " . $e->getMessage() . "\n";
}
?>
