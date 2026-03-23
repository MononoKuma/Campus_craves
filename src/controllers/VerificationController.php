<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Users.php';

class VerificationController {
    private $userModel;
    private $db;

    public function __construct() {
        $this->userModel = new User();
        $this->db = new Database();
    }

    public function uploadStudentId($userId, $file) {
        $errors = [];
        
        // Validate file
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            
            $errorMsg = isset($uploadErrors[$file['error']]) ? 
                $uploadErrors[$file['error']] : 
                'Unknown upload error.';
            $errors[] = $errorMsg;
            return ['success' => false, 'errors' => $errors];
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Only JPG and PNG images are allowed.';
        }

        // Check file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size must be less than 5MB.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'student_id_' . $userId . '_' . time() . '.' . $extension;
        
        // Try multiple path approaches for different server configurations
        $possiblePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/uploads/verification/',
            __DIR__ . '/../../public/uploads/verification/',
            dirname(__DIR__, 2) . '/public/uploads/verification/'
        ];
        
        $uploadDir = null;
        foreach ($possiblePaths as $path) {
            if (is_dir(dirname($path)) && is_writable(dirname($path))) {
                $uploadDir = $path;
                break;
            }
        }
        
        // Fallback to first option if none found writable
        if (!$uploadDir) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/verification/';
        }
        
        error_log("Using upload directory: " . $uploadDir);
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $errors[] = 'Failed to create upload directory. Please check permissions.';
                error_log("Failed to create directory: " . $uploadDir);
                return ['success' => false, 'errors' => $errors];
            }
            // Set proper permissions for web server
            chmod($uploadDir, 0755);
        }

        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            $errors[] = 'Upload directory is not writable. Please check permissions.';
            error_log("Directory not writable: " . $uploadDir);
            return ['success' => false, 'errors' => $errors];
        }

        $uploadPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Update user record
            $imagePath = 'uploads/verification/' . $filename;
            if ($this->userModel->updateStudentVerification($userId, $imagePath)) {
                return ['success' => true, 'message' => 'Student ID uploaded successfully. Pending verification.'];
            } else {
                unlink($uploadPath); // Remove uploaded file if database update fails
                $errors[] = 'Failed to save verification record. Please try again.';
            }
        } else {
            $errors[] = 'Failed to upload file. Please check directory permissions and try again.';
        }

        return ['success' => false, 'errors' => $errors];
    }

    public function verifyStudent($userId, $status, $reason = null) {
        if (!in_array($status, ['verified', 'rejected'])) {
            return ['success' => false, 'error' => 'Invalid verification status'];
        }

        if ($this->userModel->verifyStudent($userId, $status, $reason)) {
            $message = $status === 'verified' ? 
                'Student verification approved successfully.' : 
                'Student verification rejected.';
            return ['success' => true, 'message' => $message];
        }

        return ['success' => false, 'error' => 'Failed to update verification status'];
    }

    public function applyForSeller($userId, $reason) {
        if (empty($reason)) {
            return ['success' => false, 'error' => 'Please provide a reason for your seller application'];
        }

        $result = $this->userModel->applyForSeller($userId, $reason);
        
        if ($result['success']) {
            return ['success' => true, 'message' => 'Seller application submitted successfully. Pending approval.'];
        } else {
            return ['success' => false, 'error' => $result['error']];
        }
    }

    public function updateSellerApplication($userId, $status, $reason = null) {
        if (!in_array($status, ['approved', 'rejected'])) {
            return ['success' => false, 'error' => 'Invalid application status'];
        }

        if ($this->userModel->updateSellerApplication($userId, $status, $reason)) {
            $message = $status === 'approved' ? 
                'Seller application approved successfully.' : 
                'Seller application rejected.';
            return ['success' => true, 'message' => $message];
        }

        return ['success' => false, 'error' => 'Failed to update seller application'];
    }

    public function getPendingVerifications() {
        return $this->userModel->getPendingVerifications();
    }

    public function getPendingSellerApplications() {
        return $this->userModel->getPendingSellerApplications();
    }

    public function getVerificationStatus($userId) {
        $user = $this->userModel->getUserById($userId);
        if (!$user) {
            return null;
        }

        return [
            'student_verification_status' => $user['student_verification_status'],
            'student_id_image' => $user['student_id_image'],
            'verification_rejection_reason' => $user['verification_rejection_reason'],
            'verified_at' => $user['verified_at'],
            'seller_application_status' => $user['seller_application_status'],
            'seller_application_reason' => $user['seller_application_reason'],
            'seller_rejection_reason' => $user['seller_rejection_reason'],
            'applied_for_seller_at' => $user['applied_for_seller_at'],
            'became_seller_at' => $user['became_seller_at']
        ];
    }
}
?>
