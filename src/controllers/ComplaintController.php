<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/Users.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Orders.php';

class ComplaintController {
    private $complaintModel;
    private $userModel;
    private $productModel;
    private $orderModel;
    private $db;

    public function __construct() {
        $this->complaintModel = new Complaint();
        $this->userModel = new User();
        $this->productModel = new Product();
        $this->orderModel = new Order();
        $this->db = new Database();
    }

    public function createComplaint($data) {
        try {
            // Validate required fields
            $requiredFields = ['complainant_id', 'respondent_id', 'complaint_type', 'subject', 'description'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field {$field} is required"];
                }
            }

            // Validate that complainant and respondent exist
            $complainant = $this->userModel->getUserById($data['complainant_id']);
            $respondent = $this->userModel->getUserById($data['respondent_id']);
            
            if (!$complainant || !$respondent) {
                return ['success' => false, 'message' => 'Invalid user(s) specified'];
            }

            // Prevent users from filing complaints against themselves
            if ($data['complainant_id'] == $data['respondent_id']) {
                return ['success' => false, 'message' => 'You cannot file a complaint against yourself'];
            }

            // Validate order and product if provided
            if (!empty($data['order_id'])) {
                $order = $this->orderModel->getOrderById($data['order_id']);
                if (!$order) {
                    return ['success' => false, 'message' => 'Invalid order specified'];
                }
                // Verify the complainant is part of this order
                if ($order['user_id'] != $data['complainant_id']) {
                    return ['success' => false, 'message' => 'You can only file complaints for your own orders'];
                }
            }

            if (!empty($data['product_id'])) {
                $product = $this->productModel->getProductById($data['product_id']);
                if (!$product) {
                    return ['success' => false, 'message' => 'Invalid product specified'];
                }
            }

            // Check if user has already filed a similar complaint recently (within 24 hours)
            $existingComplaint = $this->checkDuplicateComplaint($data);
            if ($existingComplaint) {
                return ['success' => false, 'message' => 'You have already filed a similar complaint. Please wait for a response before filing another.'];
            }

            // Create the complaint
            $complaintId = $this->complaintModel->createComplaint($data);
            
            if ($complaintId) {
                return [
                    'success' => true, 
                    'message' => 'Complaint filed successfully',
                    'complaint_id' => $complaintId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to file complaint'];
            }
        } catch (Exception $e) {
            error_log("Complaint creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while filing the complaint. Please try again.'];
        }
    }

    private function checkDuplicateComplaint($data) {
        $stmt = $this->db->prepare("
            SELECT id FROM complaints 
            WHERE complainant_id = ? 
            AND respondent_id = ? 
            AND complaint_type = ? 
            AND (order_id = ? OR (order_id IS NULL AND ? IS NULL))
            AND (product_id = ? OR (product_id IS NULL AND ? IS NULL))
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND status IN ('pending', 'investigating')
        ");
        
        $stmt->execute([
            $data['complainant_id'],
            $data['respondent_id'],
            $data['complaint_type'],
            $data['order_id'] ?? null,
            $data['order_id'] ?? null,
            $data['product_id'] ?? null,
            $data['product_id'] ?? null
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserComplaints($userId) {
        return $this->complaintModel->getUserComplaints($userId);
    }

    public function getComplaintById($complaintId, $userId = null) {
        $complaint = $this->complaintModel->getComplaintById($complaintId);
        
        // If userId is provided, check if user has access to this complaint
        if ($userId && $complaint) {
            if ($complaint['complainant_id'] != $userId && $complaint['respondent_id'] != $userId) {
                return null; // User doesn't have access to this complaint
            }
            
            // Add other_party_name and other_party_role for user context
            if ($complaint['complainant_id'] == $userId) {
                // User is the complainant, so other party is respondent
                $complaint['other_party_name'] = $complaint['respondent_name'];
                $complaint['other_party_role'] = $complaint['respondent_role'];
            } else {
                // User is the respondent, so other party is complainant
                $complaint['other_party_name'] = $complaint['complainant_name'];
                $complaint['other_party_role'] = $complaint['complainant_role'];
            }
        }
        
        return $complaint;
    }

    public function getComplaintResponses($complaintId, $userId = null) {
        // First check if user has access to this complaint
        $complaint = $this->getComplaintById($complaintId, $userId);
        if (!$complaint) {
            return []; // User doesn't have access
        }
        
        return $this->complaintModel->getComplaintResponses($complaintId);
    }

    public function addComplaintResponse($complaintId, $responderId, $message) {
        // Check if user has access to this complaint
        $complaint = $this->getComplaintById($complaintId, $responderId);
        if (!$complaint) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        // Validate message
        if (empty(trim($message))) {
            return ['success' => false, 'message' => 'Message cannot be empty'];
        }

        // Add the response
        $result = $this->complaintModel->addComplaintResponse($complaintId, $responderId, $message);
        
        if ($result) {
            return ['success' => true, 'message' => 'Response added successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to add response'];
        }
    }

    public function getSellerComplaints($sellerId) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   u1.username as complainant_name,
                   u1.role as complainant_role,
                   p.name as product_name,
                   o.id as order_id
            FROM complaints c
            JOIN users u1 ON c.complainant_id = u1.id
            LEFT JOIN products p ON c.product_id = p.id
            LEFT JOIN orders o ON c.order_id = o.id
            WHERE c.respondent_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableRespondents($complainantId, $complaintType = null) {
        $stmt = $this->db->prepare("
            SELECT id, username, role, store_name
            FROM users 
            WHERE id != ? 
            AND status = 'active'
            AND role IN ('seller', 'admin')
            ORDER BY 
                CASE WHEN role = 'admin' THEN 1 ELSE 2 END,
                username
        ");
        $stmt->execute([$complainantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserOrders($userId) {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   STRING_AGG(p.name, ', ') as product_names
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ? 
            AND o.status IN ('completed', 'shipped', 'pending')
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getComplaintTypes() {
        return [
            'product_issue' => 'Product Issue',
            'service_issue' => 'Service Issue', 
            'payment_issue' => 'Payment Issue',
            'delivery_issue' => 'Delivery Issue',
            'other' => 'Other'
        ];
    }
}
?>
