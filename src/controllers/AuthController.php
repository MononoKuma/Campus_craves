<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Users.php';

class AuthController {
    private $db;
    private $userModel;

    public function __construct() {
        $this->db = new Database();
        $this->userModel = new User();
    }

    public function register($data) {
        $errors = [];
        
        // Validate required fields
        $required = ['first_name', 'last_name', 'birthday', 'email', 'username', 'password', 'confirm_password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Validate password match
        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match';
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Check if username/email exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists';
        }
        
        if (empty($errors)) {
            $plainPassword = $data['password'];
            
            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users 
                (first_name, middle_name, last_name, birthday, email, phone, username, password, allergens)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Handle allergens array
            $allergens = isset($data['allergens']) ? json_encode($data['allergens']) : null;
            
            $success = $stmt->execute([
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name'],
                $data['birthday'],
                $data['email'],
                $data['phone'] ?? null,
                $data['username'],
                $plainPassword,
                $allergens
            ]);
            
            if ($success) {
                return ['success' => true, 'user_id' => $this->db->lastInsertId()];
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
        
        return ['success' => false, 'errors' => $errors];
    }

    public function login($username, $password) {
        // session_start() removed; session should be started by the caller
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && isset($user['password']) && $password === $user['password']) {
            // Check if user is banned
            if ($user['status'] === 'banned') {
                return ['success' => false, 'error' => 'banned', 'ban_reason' => $user['ban_reason']];
            }
            
            // Check if user is suspended
            if ($user['status'] === 'suspended') {
                // Check if suspension has expired
                if ($user['suspension_ends'] && strtotime($user['suspension_ends']) < time()) {
                    // Auto-unsuspend if expired
                    $this->userModel->unsuspendUser($user['id']);
                    $user['status'] = 'active';
                } else {
                    return ['success' => false, 'error' => 'suspended', 'suspension_ends' => $user['suspension_ends'], 'ban_reason' => $user['ban_reason']];
                }
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'error' => 'Invalid username or password'];
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Preserve cart session ID before destroying user session
        $cartSessionId = $_SESSION['cart_session_id'] ?? null;
        
        // Clear user authentication data but preserve cart-related session data
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
        unset($_SESSION['username']);
        
        // Keep cart session ID for cart persistence
        if ($cartSessionId) {
            $_SESSION['cart_session_id'] = $cartSessionId;
        }
        
        return ['success' => true];
    }
    
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    public function sendPasswordReset($email) {
        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Return success even if user doesn't exist (security measure)
            return ['success' => true];
        }

        // Generate token (valid for 1 hour)
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token in database
        $stmt = $this->db->prepare("
            INSERT INTO password_resets (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user['id'], $token, $expires]);

        // Send email (implementation depends on your mail setup)
        $resetLink = "http://{$_SERVER['HTTP_HOST']}/reset-password.php?token=$token";
        $subject = "Steampunk Construction - Password Reset";
        $message = "Click this link to reset your password: $resetLink";
        
        // In production, use a proper mailer like PHPMailer
        // mail($email, $subject, $message);

        return ['success' => true];
    }

    public function resetPassword($token, $newPassword) {
        // Verify token
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets 
            WHERE token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $resetRequest = $stmt->fetch();

        if (!$resetRequest) {
            return ['success' => false, 'error' => 'Invalid or expired token'];
        }

        // Update password (plain text)
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newPassword, $resetRequest['user_id']]);

        // Delete used token
        $this->db->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

        return ['success' => true];
    }
}
?>