<?php
function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isSeller() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('/dashboard.php');
    }
}

function requireSeller() {
    requireLogin();
    if (!isSeller()) {
        redirect('/dashboard.php');
    }
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data));
}

function displayErrors($errors) {
    if (!empty($errors)) {
        echo '<div class="steam-alert"><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
}

function formatPrice($price) {
    return 'Php ' . number_format($price, 2);
}

function getCartItemCount() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    
    try {
        require_once __DIR__ . '/../models/Cart.php';
        $cartModel = new Cart();
        
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = null;
        
        if (!$userId) {
            if (!isset($_SESSION['cart_session_id'])) {
                $_SESSION['cart_session_id'] = session_id();
            }
            $sessionId = $_SESSION['cart_session_id'];
        }
        
        return $cartModel->getCartItemCount($userId, $sessionId);
    } catch (Exception $e) {
        // Fallback to session storage
        return isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
    }
}

function getFlashMessage() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function setFlashMessage($message, $type = 'success') {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['flash_message'] = [
        'text' => $message,
        'type' => $type
    ];
}

function displayFlashMessage() {
    $message = getFlashMessage();
    if ($message) {
        echo '<div class="flash-message ' . htmlspecialchars($message['type']) . '">';
        echo htmlspecialchars($message['text']);
        echo '</div>';
    }
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'];
}

function getCurrentUrl() {
    return getBaseUrl() . $_SERVER['REQUEST_URI'];
}

function getPaginationLinks($totalItems, $itemsPerPage, $currentPage, $url) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $links = [];
    
    // Previous link
    if ($currentPage > 1) {
        $links[] = [
            'url' => $url . '?page=' . ($currentPage - 1),
            'label' => '&laquo; Previous',
            'active' => false
        ];
    }
    
    // Page links
    for ($i = 1; $i <= $totalPages; $i++) {
        $links[] = [
            'url' => $url . '?page=' . $i,
            'label' => $i,
            'active' => ($i == $currentPage)
        ];
    }
    
    // Next link
    if ($currentPage < $totalPages) {
        $links[] = [
            'url' => $url . '?page=' . ($currentPage + 1),
            'label' => 'Next &raquo;',
            'active' => false
        ];
    }
    
    return $links;
}
?>