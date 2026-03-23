<?php
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/models/Product.php';
require_once __DIR__ . '/src/models/Users.php';

echo "Testing Store Availability Feature\n";
echo "=================================\n\n";

$db = new Database();
$productModel = new Product();
$userModel = new User();

// Test 1: Check if store_status field exists
echo "1. Checking if store_status field exists in users table...\n";
try {
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasStoreStatus = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'store_status') {
            $hasStoreStatus = true;
            echo "   ✓ store_status field found\n";
            break;
        }
    }
    if (!$hasStoreStatus) {
        echo "   ✗ store_status field not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking table structure: " . $e->getMessage() . "\n";
}

// Test 2: Create test sellers with different store statuses
echo "\n2. Setting up test sellers...\n";
try {
    // Create test seller with available store
    $db->prepare("INSERT IGNORE INTO users (username, email, password, first_name, last_name, birthday, address, role, store_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
       ->execute(['test_seller_available', 'available@test.com', password_hash('test', PASSWORD_DEFAULT), 'Available', 'Seller', '2000-01-01', 'Test Address', 'seller', 'available']);
    
    // Create test seller with unavailable store  
    $db->prepare("INSERT IGNORE INTO users (username, email, password, first_name, last_name, birthday, address, role, store_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
       ->execute(['test_seller_unavailable', 'unavailable@test.com', password_hash('test', PASSWORD_DEFAULT), 'Unavailable', 'Seller', '2000-01-01', 'Test Address', 'seller', 'unavailable']);
    
    echo "   ✓ Test sellers created\n";
} catch (Exception $e) {
    echo "   ✗ Error creating test sellers: " . $e->getMessage() . "\n";
}

// Get seller IDs
$availableSeller = $db->query("SELECT id FROM users WHERE username = 'test_seller_available'")->fetch(PDO::FETCH_ASSOC);
$unavailableSeller = $db->query("SELECT id FROM users WHERE username = 'test_seller_unavailable'")->fetch(PDO::FETCH_ASSOC);

// Test 3: Create test products
echo "\n3. Creating test products...\n";
try {
    if ($availableSeller) {
        $db->prepare("INSERT IGNORE INTO products (name, description, price, stock_quantity, seller_id) VALUES (?, ?, ?, ?, ?)")
           ->execute(['Available Product', 'Product from available seller', 10.00, 5, $availableSeller['id']]);
        echo "   ✓ Product created for available seller\n";
    }
    
    if ($unavailableSeller) {
        $db->prepare("INSERT IGNORE INTO products (name, description, price, stock_quantity, seller_id) VALUES (?, ?, ?, ?, ?)")
           ->execute(['Unavailable Product', 'Product from unavailable seller', 15.00, 3, $unavailableSeller['id']]);
        echo "   ✓ Product created for unavailable seller\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error creating test products: " . $e->getMessage() . "\n";
}

// Test 4: Test getAllProducts method
echo "\n4. Testing getAllProducts() method...\n";
try {
    $allProducts = $productModel->getAllProducts();
    echo "   Total products returned: " . count($allProducts) . "\n";
    
    $hasAvailableProduct = false;
    $hasUnavailableProduct = false;
    
    foreach ($allProducts as $product) {
        if (strpos($product['name'], 'Available') !== false) {
            $hasAvailableProduct = true;
            echo "   ✓ Found product from available seller: " . $product['name'] . "\n";
        }
        if (strpos($product['name'], 'Unavailable') !== false) {
            $hasUnavailableProduct = true;
            echo "   ✗ Found product from unavailable seller: " . $product['name'] . " (should be hidden)\n";
        }
    }
    
    if ($hasAvailableProduct && !$hasUnavailableProduct) {
        echo "   ✓ Store availability filter is working correctly!\n";
    } else {
        echo "   ✗ Store availability filter is not working properly\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error testing getAllProducts: " . $e->getMessage() . "\n";
}

// Test 5: Test searchProducts method
echo "\n5. Testing searchProducts() method...\n";
try {
    $searchResults = $productModel->searchProducts('Product');
    echo "   Search results count: " . count($searchResults) . "\n";
    
    foreach ($searchResults as $product) {
        echo "   - " . $product['name'] . " (Seller: " . $product['seller_name'] . ")\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error testing searchProducts: " . $e->getMessage() . "\n";
}

// Test 6: Test direct SQL query to verify filter
echo "\n6. Testing direct SQL query...\n";
try {
    $stmt = $db->query("
        SELECT p.name, u.username, u.store_status 
        FROM products p
        LEFT JOIN users u ON p.seller_id = u.id
        WHERE p.name LIKE '%Product%'
        ORDER BY p.name
    ");
    $allProductsWithStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   All products with seller status:\n";
    foreach ($allProductsWithStatus as $product) {
        echo "   - " . $product['name'] . " (Seller: " . $product['username'] . ", Store: " . ($product['store_status'] ?? 'NULL') . ")\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error with direct query: " . $e->getMessage() . "\n";
}

echo "\n=================================\n";
echo "Test completed!\n";
?>
