<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Users.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Orders.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../controllers/VerificationController.php';

class AdminController {
    private $userModel;
    private $productModel;
    private $orderModel;
    private $complaintModel;
    private $db;
    private $verificationController;

    public function __construct() {
        $this->userModel = new User();
        $this->productModel = new Product();
        $this->orderModel = new Order();
        $this->complaintModel = new Complaint();
        $this->db = new Database();
        $this->verificationController = new VerificationController();
    }

    public function getDashboardMetrics() {
        // Get total sales
        $salesStmt = $this->db->query("
            SELECT SUM(total_amount) as total_sales 
            FROM orders 
            WHERE status = 'completed'
        ");
        $totalSales = $salesStmt->fetchColumn();
        if ($totalSales === null) {
            $totalSales = 0.0;
        }

        // Get total customers
        $customersStmt = $this->db->query("
            SELECT COUNT(*) as total_customers 
            FROM users 
            WHERE role = 'customer'
        ");
        $totalCustomers = $customersStmt->fetchColumn();

        // Get total products
        $productsStmt = $this->db->query("
            SELECT COUNT(*) as total_products 
            FROM products
        ");
        $totalProducts = $productsStmt->fetchColumn();

        // Get recent orders with seller information
        $recentOrdersStmt = $this->db->query("
            SELECT o.*, u.username, 
                   STRING_AGG(DISTINCT s.username, ', ') as seller_names
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN users s ON p.seller_id = s.id
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_sales' => $totalSales,
            'total_customers' => $totalCustomers,
            'total_products' => $totalProducts,
            'recent_orders' => $recentOrders
        ];
    }

    // In AdminController.php
    public function getAllCustomers() {
        $stmt = $this->db->query("
            SELECT id, username, email, role, status, created_at 
            FROM users 
            WHERE role = 'customer'
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUsersWithStatus($filters = []) {
        return $this->userModel->getAllUsersWithStatus($filters);
    }

    public function banUser($userId, $reason = null) {
        return $this->userModel->banUser($userId, $reason);
    }

    public function suspendUser($userId, $reason = null, $durationDays = 7) {
        return $this->userModel->suspendUser($userId, $reason, $durationDays);
    }

    public function unbanUser($userId) {
        return $this->userModel->unbanUser($userId);
    }

    public function unsuspendUser($userId) {
        return $this->userModel->unsuspendUser($userId);
    }

    public function getAllProducts() {
        $stmt = $this->db->query("SELECT * FROM products ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredProducts($search = '', $category = '', $price_min = '', $price_max = '', $stock_status = '') {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];
        
        // Add search filter
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $searchParam = "%" . $search . "%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        // Add category filter
        if (!empty($category)) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        // Add price range filters
        if (!empty($price_min) && is_numeric($price_min)) {
            $sql .= " AND price >= ?";
            $params[] = (float)$price_min;
        }
        
        if (!empty($price_max) && is_numeric($price_max)) {
            $sql .= " AND price <= ?";
            $params[] = (float)$price_max;
        }
        
        // Add stock status filter
        if (!empty($stock_status)) {
            switch ($stock_status) {
                case 'in_stock':
                    $sql .= " AND stock_quantity > 0";
                    break;
                case 'out_of_stock':
                    $sql .= " AND stock_quantity = 0";
                    break;
                case 'low_stock':
                    $sql .= " AND stock_quantity > 0 AND stock_quantity <= 5";
                    break;
            }
        }
        
        $sql .= " ORDER BY name";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCategories() {
        $stmt = $this->db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $categories ?: [];
    }

    public function getAllOrders($status = null) {
        $sql = "
            SELECT o.*, u.username 
            FROM orders o
            JOIN users u ON o.user_id = u.id
        ";
        if ($status) {
            $sql .= " WHERE o.status = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status]);
        } else {
            // Exclude 'cart' orders by default
            $sql .= " WHERE o.status != 'cart'";
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createProduct($data) {
        // Basic validation
        if (empty($data['name']) || empty($data['price']) || empty($data['stock_quantity'])) {
            return false;
        }
        return $this->productModel->createProduct($data);
    }

    public function updateProduct($id, $data) {
        if (empty($id) || empty($data['name']) || empty($data['price']) || empty($data['stock_quantity'])) {
            return false;
        }
        return $this->productModel->updateProduct($id, $data);
    }

    public function deleteProduct($id) {
        if (empty($id)) {
            return false;
        }
        return $this->productModel->deleteProduct($id);
    }

    public function getProductById($id) {
        if (empty($id)) {
            return null;
        }
        return $this->productModel->getProductById($id);
    }

    public function updateOrderStatus($orderId, $status) {
        if (empty($orderId) || empty($status)) {
            return false;
        }
        return $this->orderModel->updateOrderStatus($orderId, $status);
    }

    public function getPendingVerifications() {
        return $this->verificationController->getPendingVerifications();
    }

    public function getPendingSellerApplications() {
        return $this->verificationController->getPendingSellerApplications();
    }

    public function verifyStudent($userId, $status, $reason = null) {
        return $this->verificationController->verifyStudent($userId, $status, $reason);
    }

    public function updateSellerApplication($userId, $status, $reason = null) {
        return $this->verificationController->updateSellerApplication($userId, $status, $reason);
    }

    public function getAllComplaints() {
        return $this->complaintModel->getAllComplaints();
    }

    public function getComplaintsByType($type) {
        return $this->complaintModel->getComplaintsByType($type);
    }

    public function getComplaintById($id) {
        return $this->complaintModel->getComplaintById($id);
    }

    public function updateComplaintStatus($complaintId, $status, $adminResponse = null) {
        return $this->complaintModel->updateComplaintStatus($complaintId, $status, $adminResponse);
    }

    public function addComplaintResponse($complaintId, $responderId, $message) {
        return $this->complaintModel->addComplaintResponse($complaintId, $responderId, $message);
    }

    public function getComplaintResponses($complaintId) {
        return $this->complaintModel->getComplaintResponses($complaintId);
    }
}
?>