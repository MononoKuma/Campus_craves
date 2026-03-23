<?php
// Start session if not already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../src/controllers/CartController.php';
require_once __DIR__ . '/../src/helpers/functions.php';

header('Content-Type: application/json');

// Debug logging
error_log("Add to cart request received");
error_log("Session status: " . session_status());
error_log("Session ID: " . session_id());
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

if (!isLoggedIn()) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
error_log("Input data: " . json_encode($input));

$productId = $input['productId'] ?? null;
$quantity = $input['quantity'] ?? 1;

if (!$productId) {
    error_log("No product ID provided");
    echo json_encode(['success' => false, 'error' => 'No product ID provided']);
    exit;
}

error_log("Adding product $productId with quantity $quantity");

try {
    $cartController = new CartController();
    $result = $cartController->addToCart($productId, $quantity);
    
    error_log("Cart controller result: " . json_encode($result));

    if ($result['success']) {
        echo json_encode(['success' => true, 'cart' => $result['cart']]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unknown error']);
    }
} catch (Exception $e) {
    error_log("Exception in add-to-cart: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>