<?php
require_once __DIR__ . '/../config/database.php';

class Complaint {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function createComplaint($data) {
        $stmt = $this->db->prepare("
            INSERT INTO complaints 
            (complainant_id, respondent_id, complaint_type, subject, description, order_id, product_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['complainant_id'],
            $data['respondent_id'],
            $data['complaint_type'],
            $data['subject'],
            $data['description'],
            $data['order_id'] ?? null,
            $data['product_id'] ?? null,
            'pending'
        ]);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function getAllComplaints() {
        $stmt = $this->db->query("
            SELECT c.*, 
                   u1.username as complainant_name,
                   u2.username as respondent_name,
                   u1.role as complainant_role,
                   u2.role as respondent_role
            FROM complaints c
            JOIN users u1 ON c.complainant_id = u1.id
            JOIN users u2 ON c.respondent_id = u2.id
            ORDER BY c.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getComplaintsByType($type) {
        // Handle special cases for buyer/seller filtering
        if ($type === 'buyer') {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       u1.username as complainant_name,
                       u2.username as respondent_name,
                       u1.role as complainant_role,
                       u2.role as respondent_role
                FROM complaints c
                JOIN users u1 ON c.complainant_id = u1.id
                JOIN users u2 ON c.respondent_id = u2.id
                WHERE c.complainant_id IN (
                    SELECT id FROM users WHERE role = 'customer'
                )
                ORDER BY c.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($type === 'seller') {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       u1.username as complainant_name,
                       u2.username as respondent_name,
                       u1.role as complainant_role,
                       u2.role as respondent_role
                FROM complaints c
                JOIN users u1 ON c.complainant_id = u1.id
                JOIN users u2 ON c.respondent_id = u2.id
                WHERE c.respondent_id IN (
                    SELECT id FROM users WHERE role = 'seller'
                )
                ORDER BY c.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Original behavior for specific complaint types
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       u1.username as complainant_name,
                       u2.username as respondent_name,
                       u1.role as complainant_role,
                       u2.role as respondent_role
                FROM complaints c
                JOIN users u1 ON c.complainant_id = u1.id
                JOIN users u2 ON c.respondent_id = u2.id
                WHERE c.complaint_type = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$type]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getComplaintById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   u1.username as complainant_name,
                   u2.username as respondent_name,
                   u1.role as complainant_role,
                   u2.role as respondent_role
            FROM complaints c
            JOIN users u1 ON c.complainant_id = u1.id
            JOIN users u2 ON c.respondent_id = u2.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateComplaintStatus($complaintId, $status, $adminResponse = null) {
        $stmt = $this->db->prepare("
            UPDATE complaints 
            SET status = ?, 
                admin_response = ?,
                resolved_at = " . ($status === 'resolved' ? 'CURRENT_TIMESTAMP' : 'NULL') . ",
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$status, $adminResponse, $complaintId]);
    }

    public function addComplaintResponse($complaintId, $responderId, $message) {
        $stmt = $this->db->prepare("
            INSERT INTO complaint_responses 
            (complaint_id, responder_id, message)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$complaintId, $responderId, $message]);
    }

    public function getComplaintResponses($complaintId) {
        $stmt = $this->db->prepare("
            SELECT cr.*, u.username
            FROM complaint_responses cr
            JOIN users u ON cr.responder_id = u.id
            WHERE cr.complaint_id = ?
            ORDER BY cr.created_at ASC
        ");
        $stmt->execute([$complaintId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserComplaints($userId) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   u1.username as complainant_name,
                   u1.role as complainant_role,
                   u2.username as respondent_name,
                   u2.role as respondent_role
            FROM complaints c
            JOIN users u1 ON c.complainant_id = u1.id
            JOIN users u2 ON c.respondent_id = u2.id
            WHERE c.complainant_id = ? OR c.respondent_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId, $userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add other_party_name and other_party_role for each complaint
        foreach ($results as &$complaint) {
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
        
        return $results;
    }
}
?>
