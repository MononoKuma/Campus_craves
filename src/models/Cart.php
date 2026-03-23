<?php
require_once __DIR__ . '/../config/database.php';

class Cart {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get cart items for a user or session
     */
    public function getCartItems($userId = null, $sessionId = null) {
        $sql = "
            SELECT ci.*, p.name, p.price, p.image_path, p.stock_quantity, p.description
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE (ci.user_id = :user_id OR ci.session_id = :session_id)
            AND p.stock_quantity > 0
            ORDER BY ci.created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':session_id' => $sessionId
        ]);
        
        $cartItems = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cartItems[$row['product_id']] = [
                'product' => [
                    'id' => $row['product_id'],
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'image_path' => $row['image_path'],
                    'stock_quantity' => $row['stock_quantity'],
                    'description' => $row['description']
                ],
                'quantity' => $row['quantity'],
                'cart_item_id' => $row['id']
            ];
        }
        
        return $cartItems;
    }
    
    /**
     * Add or update item in cart
     */
    public function addToCart($productId, $quantity, $userId = null, $sessionId = null) {
        // Check if item already exists
        $sql = "
            SELECT id, quantity FROM cart_items 
            WHERE product_id = :product_id 
            AND ((user_id = :user_id AND user_id IS NOT NULL) OR (session_id = :session_id AND session_id IS NOT NULL))
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId,
            ':session_id' => $sessionId
        ]);
        
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing item
            $newQuantity = $existing['quantity'] + $quantity;
            $sql = "UPDATE cart_items SET quantity = :quantity WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':quantity' => $newQuantity,
                ':id' => $existing['id']
            ]);
        } else {
            // Insert new item
            $sql = "
                INSERT INTO cart_items (product_id, quantity, user_id, session_id) 
                VALUES (:product_id, :quantity, :user_id, :session_id)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':product_id' => $productId,
                ':quantity' => $quantity,
                ':user_id' => $userId,
                ':session_id' => $sessionId
            ]);
        }
        
        return true;
    }
    
    /**
     * Update item quantity in cart
     */
    public function updateCartItem($productId, $quantity, $userId = null, $sessionId = null) {
        if ($quantity <= 0) {
            return $this->removeFromCart($productId, $userId, $sessionId);
        }
        
        $sql = "
            UPDATE cart_items 
            SET quantity = :quantity 
            WHERE product_id = :product_id 
            AND ((user_id = :user_id AND user_id IS NOT NULL) OR (session_id = :session_id AND session_id IS NOT NULL))
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':quantity' => $quantity,
            ':product_id' => $productId,
            ':user_id' => $userId,
            ':session_id' => $sessionId
        ]);
    }
    
    /**
     * Remove item from cart
     */
    public function removeFromCart($productId, $userId = null, $sessionId = null) {
        $sql = "
            DELETE FROM cart_items 
            WHERE product_id = :product_id 
            AND ((user_id = :user_id AND user_id IS NOT NULL) OR (session_id = :session_id AND session_id IS NOT NULL))
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId,
            ':session_id' => $sessionId
        ]);
    }
    
    /**
     * Clear entire cart
     */
    public function clearCart($userId = null, $sessionId = null) {
        $sql = "
            DELETE FROM cart_items 
            WHERE (user_id = :user_id OR session_id = :session_id)
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':session_id' => $sessionId
        ]);
    }
    
    /**
     * Migrate cart items from session to user account
     */
    public function migrateCartToUser($sessionId, $userId) {
        // Get existing user cart items
        $userCart = $this->getCartItems($userId, null);
        
        // Get session cart items
        $sessionCart = $this->getCartItems(null, $sessionId);
        
        foreach ($sessionCart as $productId => $item) {
            if (isset($userCart[$productId])) {
                // Update quantity if product already in user cart
                $newQuantity = $userCart[$productId]['quantity'] + $item['quantity'];
                $this->updateCartItem($productId, $newQuantity, $userId, null);
            } else {
                // Move item to user cart
                $sql = "
                    UPDATE cart_items 
                    SET user_id = :user_id, session_id = NULL 
                    WHERE product_id = :product_id AND session_id = :session_id
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':product_id' => $productId,
                    ':session_id' => $sessionId
                ]);
            }
        }
        
        return true;
    }
    
    /**
     * Get cart item count
     */
    public function getCartItemCount($userId = null, $sessionId = null) {
        $sql = "
            SELECT COUNT(*) as count, SUM(quantity) as total_quantity
            FROM cart_items 
            WHERE (user_id = :user_id OR session_id = :session_id)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':session_id' => $sessionId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_quantity'] ?? 0;
    }
}
?>
