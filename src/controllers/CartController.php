<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Orders.php';
require_once __DIR__ . '/../models/Users.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController {
    private $productModel;
    private $orderModel;
    private $userModel;
    private $cartModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->orderModel = new Order();
        $this->userModel = new User();
        $this->cartModel = new Cart();
    }

    private function getCartIdentifier() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = null;
        
        if (!$userId) {
            // Use session ID for non-logged-in users
            if (!isset($_SESSION['cart_session_id'])) {
                $_SESSION['cart_session_id'] = session_id();
            }
            $sessionId = $_SESSION['cart_session_id'];
        }
        
        return ['user_id' => $userId, 'session_id' => $sessionId];
    }

    public function addToCart($productId, $quantity = 1) {
        $cartId = $this->getCartIdentifier();
        
        $product = $this->productModel->getProductById($productId);
        
        if (!$product) {
            return ['success' => false, 'error' => 'Product not found'];
        }

        // Check stock
        if ($product['stock_quantity'] < $quantity) {
            return ['success' => false, 'error' => 'Not enough stock available'];
        }

        $this->cartModel->addToCart($productId, $quantity, $cartId['user_id'], $cartId['session_id']);

        return ['success' => true, 'cart' => $this->getCart()];
    }

    public function removeFromCart($productId) {
        $cartId = $this->getCartIdentifier();
        
        $result = $this->cartModel->removeFromCart($productId, $cartId['user_id'], $cartId['session_id']);
        
        if ($result) {
            return ['success' => true, 'cart' => $this->getCart()];
        }

        return ['success' => false, 'error' => 'Product not in cart'];
    }

    public function updateCartItem($productId, $quantity) {
        $cartId = $this->getCartIdentifier();
        
        $result = $this->cartModel->updateCartItem($productId, $quantity, $cartId['user_id'], $cartId['session_id']);
        
        if ($result) {
            return ['success' => true, 'cart' => $this->getCart()];
        }

        return ['success' => false, 'error' => 'Product not in cart'];
    }

    public function getCart() {
        $cartId = $this->getCartIdentifier();
        
        // Try to get from database first
        try {
            $cartItems = $this->cartModel->getCartItems($cartId['user_id'], $cartId['session_id']);
            
            // If user is logged in and has session cart items, migrate them
            if ($cartId['user_id'] && $cartId['session_id']) {
                $this->cartModel->migrateCartToUser($cartId['session_id'], $cartId['user_id']);
                // Get updated cart after migration
                $cartItems = $this->cartModel->getCartItems($cartId['user_id'], null);
            }
            
            return $cartItems;
        } catch (Exception $e) {
            // Fallback to session storage if database fails
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            return $_SESSION['cart'] ?? [];
        }
    }

    public function checkout($userId, $paymentMethod, $deliveryInfo = []) {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        
        $cartId = $this->getCartIdentifier();
        $cartItems = $this->cartModel->getCartItems($userId, null);
        
        if (empty($cartItems)) {
            return ['success' => false, 'error' => 'Cart is empty'];
        }

        if (empty($userId)) {
            return ['success' => false, 'error' => 'User ID is missing or invalid'];
        }

        // Check if user is verified
        $user = $this->userModel->getUserById($userId);
        if (!$user || $user['student_verification_status'] !== 'verified') {
            return ['success' => false, 'error' => 'You must be verified before placing an order. Please upload your student ID for verification.'];
        }

        try {
            // Calculate total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item['product']['price'] * $item['quantity'];
            }

            // Create order
            $orderData = [
                'user_id' => $userId,
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'status' => 'pending', 
                'delivery_mode' => $deliveryInfo['delivery_mode'] ?? 'delivery',
                'delivery_address' => $deliveryInfo['delivery_address'] ?? '',
                'delivery_notes' => $deliveryInfo['delivery_notes'] ?? '',
                'meetup_time' => $deliveryInfo['meetup_time'] ?? null,
                'meetup_place' => $deliveryInfo['meetup_place'] ?? ''
            ];
            
            $orderId = $this->orderModel->createOrder($orderData);

            if (empty($orderId) || !is_numeric($orderId) || $orderId <= 0) {
                return [
                    'success' => false,
                    'error' => 'Order creation failed. Invalid order ID.'
                ];
            }

            // Add order items
            foreach ($cartItems as $productId => $item) {
                $addItemResult = $this->orderModel->addOrderItem([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['product']['price']
                ]);
                if (!$addItemResult) {
                    return [
                        'success' => false,
                        'error' => 'Failed to add order item.'
                    ];
                }

                // Update stock
                $this->productModel->updateProduct($productId, [
                    'stock_quantity' => $item['product']['stock_quantity'] - $item['quantity']
                ]);
            }

            // Clear cart from database
            $this->cartModel->clearCart($userId, null);
            // Also clear any session cart
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }

            return ['success' => true, 'order_id' => $orderId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>