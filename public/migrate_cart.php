<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Cart.php';
require_once __DIR__ . '/../src/models/Product.php';

/**
 * Migrate existing session cart data to database
 * This script should be run once after implementing the persistent cart system
 */

try {
    $cartModel = new Cart();
    $productModel = new Product();
    
    // Start session to access existing cart data
    session_start();
    
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        echo "Found existing session cart data. Migrating to database...\n";
        
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        
        $migratedCount = 0;
        foreach ($_SESSION['cart'] as $productId => $item) {
            // Verify product still exists and has stock
            $product = $productModel->getProductById($productId);
            if ($product && $product['stock_quantity'] > 0) {
                // Add to database cart
                $cartModel->addToCart(
                    $productId, 
                    $item['quantity'], 
                    $userId, 
                    $sessionId
                );
                $migratedCount++;
                echo "Migrated product ID: $productId (Quantity: {$item['quantity']})\n";
            } else {
                echo "Skipped product ID: $productId (not available or out of stock)\n";
            }
        }
        
        // Clear session cart after successful migration
        unset($_SESSION['cart']);
        
        echo "Migration completed. Migrated $migratedCount items.\n";
    } else {
        echo "No existing session cart data found.\n";
    }
    
    echo "Cart migration script completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error during cart migration: " . $e->getMessage() . "\n";
}
?>
