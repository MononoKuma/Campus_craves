<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getDb() {
        return $this->db->connect();
    }

    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllUsers() {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateUser($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "$field = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        
        $stmt = $this->db->prepare("
            UPDATE users SET
            " . implode(', ', $fields) . ",
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        return $stmt->execute($values);
    }

    public function deleteUser($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateStudentVerification($userId, $imagePath) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET student_verification_status = 'pending', 
                student_id_image = ?, 
                verification_rejection_reason = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$imagePath, $userId]);
    }

    public function verifyStudent($userId, $status = 'verified', $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET student_verification_status = ?, 
                verified_at = " . ($status === 'verified' ? 'CURRENT_TIMESTAMP' : 'NULL') . ",
                verification_rejection_reason = ?
            WHERE id = ?
        ");
        return $stmt->execute([$status, $reason, $userId]);
    }

    public function applyForSeller($userId, $reason) {
        // Check if user is verified student first
        $stmt = $this->db->prepare("SELECT student_verification_status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || $user['student_verification_status'] !== 'verified') {
            return ['success' => false, 'error' => 'You must be a verified student to apply for seller status'];
        }
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET seller_application_status = 'pending', 
                seller_application_reason = ?, 
                seller_rejection_reason = NULL,
                applied_for_seller_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $success = $stmt->execute([$reason, $userId]);
        
        return ['success' => $success];
    }

    public function updateSellerApplication($userId, $status, $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET seller_application_status = ?, 
                seller_rejection_reason = ?,
                role = " . ($status === 'approved' ? "'seller'" : "'customer'") . ",
                became_seller_at = " . ($status === 'approved' ? 'CURRENT_TIMESTAMP' : 'NULL') . "
            WHERE id = ?
        ");
        return $stmt->execute([$status, $reason, $userId]);
    }

    public function getPendingVerifications() {
        $stmt = $this->db->query("
            SELECT id, username, email, student_id_image, created_at
            FROM users 
            WHERE student_verification_status = 'pending'
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingSellerApplications() {
        $stmt = $this->db->query("
            SELECT id, username, email, seller_application_reason, applied_for_seller_at
            FROM users 
            WHERE seller_application_status = 'pending'
            ORDER BY applied_for_seller_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStoreStatus($userId, $status) {
        if (!in_array($status, ['available', 'unavailable'])) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET store_status = ?
            WHERE id = ? AND role = 'seller'
        ");
        return $stmt->execute([$status, $userId]);
    }

    public function updateStoreProfile($userId, $data) {
        $fields = [];
        $values = [];
        
        $allowedFields = ['store_name', 'store_description', 'store_banner'];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $fields[] = "$field = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $userId;
        
        $stmt = $this->db->prepare("
            UPDATE users SET
            " . implode(', ', $fields) . ",
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND role = 'seller'
        ");
        
        return $stmt->execute($values);
    }

    public function getSellerProfile($userId) {
        $stmt = $this->db->prepare("
            SELECT id, username, email, store_status, store_name, store_description, store_banner
            FROM users 
            WHERE id = ? AND role = 'seller'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function banUser($userId, $reason = null) {
        try {
            error_log("Attempting to ban user ID: $userId with reason: " . ($reason ?? 'none'));
            $stmt = $this->db->prepare("
                UPDATE users SET
                status = 'banned',
                ban_reason = ?,
                banned_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $result = $stmt->execute([$reason, $userId]);
            error_log("Ban operation result: " . ($result ? 'SUCCESS' : 'FAILED'));
            error_log("Rows affected: " . $stmt->rowCount());
            return $result;
        } catch (Exception $e) {
            error_log("Ban user error: " . $e->getMessage());
            return false;
        }
    }

    public function suspendUser($userId, $reason = null, $durationDays = 7) {
        try {
            error_log("Attempting to suspend user ID: $userId with reason: " . ($reason ?? 'none'));
            $suspensionEnds = date('Y-m-d H:i:s', strtotime("+$durationDays days"));
            $stmt = $this->db->prepare("
                UPDATE users SET
                status = 'suspended',
                ban_reason = ?,
                suspended_at = CURRENT_TIMESTAMP,
                suspension_ends = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $result = $stmt->execute([$reason, $suspensionEnds, $userId]);
            error_log("Suspend operation result: " . ($result ? 'SUCCESS' : 'FAILED'));
            error_log("Rows affected: " . $stmt->rowCount());
            return $result;
        } catch (Exception $e) {
            error_log("Suspend user error: " . $e->getMessage());
            return false;
        }
    }

    public function unbanUser($userId) {
        $stmt = $this->db->prepare("
            UPDATE users SET
            status = 'active',
            ban_reason = NULL,
            banned_at = NULL,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    public function unsuspendUser($userId) {
        $stmt = $this->db->prepare("
            UPDATE users SET
            status = 'active',
            ban_reason = NULL,
            suspended_at = NULL,
            suspension_ends = NULL,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    public function getAllUsersWithStatus($filters = []) {
        $sql = "SELECT id, username, email, role, status, ban_reason, suspended_at, suspension_ends, created_at FROM users";
        $whereConditions = [];
        $params = [];
        
        // Apply role filter
        if (!empty($filters['role']) && in_array($filters['role'], ['customer', 'admin', 'seller'])) {
            $whereConditions[] = "role = ?";
            $params[] = $filters['role'];
        }
        
        // Apply username search filter
        if (!empty($filters['search'])) {
            $whereConditions[] = "username LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        
        // Apply email search filter
        if (!empty($filters['email_search'])) {
            $whereConditions[] = "email LIKE ?";
            $params[] = '%' . $filters['email_search'] . '%';
        }
        
        // Apply status filter
        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'banned', 'suspended'])) {
            $whereConditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        // Add WHERE clause if conditions exist
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        // Apply ordering
        $orderBy = !empty($filters['orderBy']) ? $filters['orderBy'] : 'created_at';
        $orderDirection = !empty($filters['orderDirection']) && strtoupper($filters['orderDirection']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Validate order by column
        $allowedColumns = ['id', 'username', 'email', 'role', 'created_at'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'created_at';
        }
        
        $sql .= " ORDER BY $orderBy $orderDirection";
        
        // Apply number limit
        if (!empty($filters['limit']) && is_numeric($filters['limit']) && $filters['limit'] > 0) {
            $limit = min((int)$filters['limit'], 1000); // Cap at 1000 for performance
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $users;
    }

    public function updateAllergens($userId, $allergens) {
        $allergensJson = !empty($allergens) ? json_encode($allergens) : null;
        $stmt = $this->db->prepare("
            UPDATE users 
            SET allergens = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$allergensJson, $userId]);
    }

    public function getUserAllergens($userId) {
        $stmt = $this->db->prepare("SELECT allergens FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['allergens']) {
            return json_decode($result['allergens'], true) ?: [];
        }
        return [];
    }
}
?>