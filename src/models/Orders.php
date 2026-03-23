<?php
require_once __DIR__ . '/../config/database.php';

class Order {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getDb() {
        return $this->db->connect();
    }

    public function createOrder($data) {
        $stmt = $this->db->prepare("
            INSERT INTO orders 
            (user_id, total_amount, payment_method, status, delivery_mode, delivery_address, delivery_notes, meetup_time, meetup_place)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $params = [
            $data['user_id'],
            $data['total_amount'],
            $data['payment_method'],
            $data['status'] ?? 'pending',
            $data['delivery_mode'] ?? 'delivery',
            $data['delivery_address'] ?? '',
            $data['delivery_notes'] ?? '',
            $data['meetup_time'] ?? null,
            $data['meetup_place'] ?? ''
        ];
        
        $stmt->execute($params);
        
        return $this->db->lastInsertId();
    }

    public function addOrderItem($data) {
        $stmt = $this->db->prepare("
            INSERT INTO order_items 
            (order_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['order_id'],
            $data['product_id'],
            $data['quantity'],
            $data['unit_price']
        ]);
    }

    public function getOrderById($id) {
        $stmt = $this->db->prepare("
            SELECT o.*, u.username 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name, p.description, p.seller_id, u.username as seller_name 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN users u ON p.seller_id = u.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserOrders($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM orders 
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateOrderStatus($orderId, $status) {
        $stmt = $this->db->prepare("
            UPDATE orders SET
            status = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        return $stmt->execute([$status, $orderId]);
    }
}
?>