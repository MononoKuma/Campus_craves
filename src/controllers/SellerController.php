<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Users.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Orders.php';

class SellerController {
    private $userModel;
    private $productModel;
    private $orderModel;
    private $db;

    public function __construct() {
        $this->userModel = new User();
        $this->productModel = new Product();
        $this->orderModel = new Order();
        $this->db = new Database();
    }

    public function getDashboardMetrics($sellerId) {
        // Get seller's total sales
        $salesStmt = $this->db->prepare("
            SELECT SUM(oi.quantity * oi.unit_price) as total_sales 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE p.seller_id = ? AND o.status = 'completed'
        ");
        $salesStmt->execute([$sellerId]);
        $totalSales = $salesStmt->fetchColumn();
        if ($totalSales === null) {
            $totalSales = 0.0;
        }

        // Get seller's product count
        $productsStmt = $this->db->prepare("
            SELECT COUNT(*) as total_products 
            FROM products
            WHERE seller_id = ?
        ");
        $productsStmt->execute([$sellerId]);
        $totalProducts = $productsStmt->fetchColumn();

        // Get seller's pending orders
        $pendingStmt = $this->db->prepare("
            SELECT COUNT(DISTINCT o.id) as pending_orders
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.seller_id = ? AND o.status = 'pending'
        ");
        $pendingStmt->execute([$sellerId]);
        $pendingOrders = $pendingStmt->fetchColumn();

        // Get seller's recent orders
        $recentOrdersStmt = $this->db->prepare("
            SELECT DISTINCT o.*, u.username 
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            JOIN users u ON o.user_id = u.id
            WHERE p.seller_id = ? AND o.status != 'cart'
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $recentOrdersStmt->execute([$sellerId]);
        $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_sales' => $totalSales,
            'total_products' => $totalProducts,
            'pending_orders' => $pendingOrders,
            'recent_orders' => $recentOrders
        ];
    }

    public function getSellerProducts($sellerId) {
        $stmt = $this->db->prepare("
            SELECT * FROM products 
            WHERE seller_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSellerOrders($sellerId, $status = null) {
        $sql = "
            SELECT DISTINCT o.*, u.username 
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            JOIN users u ON o.user_id = u.id
            WHERE p.seller_id = ?
        ";
        
        $params = [$sellerId];
        
        if ($status && in_array($status, ['pending', 'completed', 'shipped', 'cancelled'])) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        } else {
            $sql .= " AND o.status != 'cart'";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredSellerOrders($sellerId, $search = '', $status = '', $date_from = '', $date_to = '', $amount_min = '', $amount_max = '') {
        $sql = "
            SELECT DISTINCT o.*, u.username 
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            JOIN users u ON o.user_id = u.id
            WHERE p.seller_id = ? AND o.status != 'cart'
        ";
        
        $params = [$sellerId];
        
        // Add search filter (Order ID or Customer name)
        if (!empty($search)) {
            $sql .= " AND (o.id LIKE ? OR u.username LIKE ?)";
            $searchParam = "%" . $search . "%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        // Add status filter
        if (!empty($status) && in_array($status, ['pending', 'completed', 'shipped', 'cancelled'])) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }
        
        // Add date range filters
        if (!empty($date_from)) {
            $sql .= " AND DATE(o.created_at) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $sql .= " AND DATE(o.created_at) <= ?";
            $params[] = $date_to;
        }
        
        // Add amount range filters
        if (!empty($amount_min) && is_numeric($amount_min)) {
            $sql .= " AND o.total_amount >= ?";
            $params[] = (float)$amount_min;
        }
        
        if (!empty($amount_max) && is_numeric($amount_max)) {
            $sql .= " AND o.total_amount <= ?";
            $params[] = (float)$amount_max;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderDetails($orderId, $sellerId) {
        // Get order basic info
        $orderStmt = $this->db->prepare("
            SELECT DISTINCT o.*, u.username, u.email, u.phone
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ? AND p.seller_id = ? AND o.status != 'cart'
        ");
        $orderStmt->execute([$orderId, $sellerId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $itemsStmt = $this->db->prepare("
            SELECT oi.*, p.name as product_name, p.description, p.image_path
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ? AND p.seller_id = ?
        ");
        $itemsStmt->execute([$orderId, $sellerId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'order' => $order,
            'items' => $items
        ];
    }

    public function updateOrderStatus($orderId, $status, $sellerId) {
        // Verify order belongs to seller
        $verifyStmt = $this->db->prepare("
            SELECT o.id
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = ? AND p.seller_id = ? AND o.status != 'cart'
        ");
        $verifyStmt->execute([$orderId, $sellerId]);
        
        if (!$verifyStmt->fetch()) {
            return false; // Order doesn't belong to seller
        }
        
        // Validate status transition
        $currentStatusStmt = $this->db->prepare("SELECT status FROM orders WHERE id = ?");
        $currentStatusStmt->execute([$orderId]);
        $currentStatus = $currentStatusStmt->fetchColumn();
        
        // Define valid status transitions
        $validTransitions = [
            'pending' => ['shipped', 'cancelled'],
            'shipped' => ['completed', 'cancelled'],
            'completed' => [], // Can't change completed orders
            'cancelled' => []  // Can't change cancelled orders
        ];
        
        if (!in_array($status, $validTransitions[$currentStatus] ?? [])) {
            return false; // Invalid transition
        }
        
        // Update order status
        $updateStmt = $this->db->prepare("
            UPDATE orders 
            SET status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        return $updateStmt->execute([$status, $orderId]);
    }

    public function createProduct($sellerId, $data) {
        // Basic validation
        if (empty($data['name']) || empty($data['price']) || empty($data['stock_quantity'])) {
            return false;
        }
        
        $data['seller_id'] = $sellerId;
        return $this->productModel->createProduct($data);
    }

    public function getProductById($productId) {
        return $this->productModel->getProductById($productId);
    }

    public function updateProduct($productId, $data) {
        return $this->productModel->updateProduct($productId, $data);
    }

    public function deleteProduct($productId, $sellerId) {
        // Verify product belongs to seller
        $product = $this->productModel->getProductById($productId);
        if (!$product || $product['seller_id'] != $sellerId) {
            return false;
        }
        
        return $this->productModel->deleteProduct($productId);
    }

    public function getSellerStats($sellerId) {
        // Get monthly sales for the last 6 months
        $monthlySalesStmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(o.created_at, '%Y-%m') as month,
                SUM(oi.quantity * oi.unit_price) as sales
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.seller_id = ? AND o.status = 'completed'
                AND o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
            ORDER BY month
        ");
        $monthlySalesStmt->execute([$sellerId]);
        return $monthlySalesStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopProducts($sellerId, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.name,
                p.price,
                SUM(oi.quantity) as total_sold,
                SUM(oi.quantity * oi.unit_price) as total_revenue
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE p.seller_id = ? AND (o.status = 'completed' OR o.status IS NULL)
            GROUP BY p.id, p.name, p.price
            ORDER BY total_sold DESC
            LIMIT ?
        ");
        $stmt->execute([$sellerId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStoreStatus($sellerId, $status) {
        return $this->userModel->updateStoreStatus($sellerId, $status);
    }

    public function getStoreStatus($sellerId) {
        $profile = $this->userModel->getSellerProfile($sellerId);
        return $profile ? $profile['store_status'] : 'available';
    }

    public function updateStoreProfile($sellerId, $data) {
        // Handle banner image upload
        if (isset($_FILES['store_banner']) && $_FILES['store_banner']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['store_banner']['type'], $allowedTypes)) {
                $uploadDir = '/images/stores/';
                $fileName = uniqid() . '_' . basename($_FILES['store_banner']['name']);
                $uploadPath = __DIR__ . '/../../public' . $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['store_banner']['tmp_name'], $uploadPath)) {
                    $data['store_banner'] = $uploadDir . $fileName;
                }
            }
        }
        
        return $this->userModel->updateStoreProfile($sellerId, $data);
    }

    public function getSellerProfile($sellerId) {
        return $this->userModel->getSellerProfile($sellerId);
    }
}
?>
