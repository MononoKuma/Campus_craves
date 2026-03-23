<?php
require_once __DIR__ . '/../config/database.php';

class Product {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getDb() {
        return $this->db->connect();
    }

    public function getAllProducts() {
        require_once __DIR__ . '/SystemSettings.php';
        $systemSettings = new SystemSettings();
        $visibilityMode = $systemSettings->getStoreVisibilityMode();
        
        if ($visibilityMode === 'available_only') {
            $stmt = $this->db->query("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE u.store_status = 'available' OR u.store_status IS NULL
                ORDER BY p.name
            ");
        } else {
            $stmt = $this->db->query("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                ORDER BY p.name
            ");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
            FROM products p
            LEFT JOIN users u ON p.seller_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createProduct($data) {
        $stmt = $this->db->prepare("
            INSERT INTO products 
            (name, description, price, image_path, stock_quantity, seller_id, allergens)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $allergensJson = isset($data['allergens']) ? json_encode($data['allergens']) : null;
        
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['image_path'] ?? null,
            $data['stock_quantity'],
            $data['seller_id'] ?? null,
            $allergensJson
        ]);
    }

    public function updateProduct($id, $data) {
        // Fetch current product data
        $current = $this->getProductById($id);
        if (!$current) return false;

        // Merge new data with current data
        $merged = array_merge($current, $data);
        
        // Handle allergens
        $allergensJson = isset($data['allergens']) ? json_encode($data['allergens']) : $current['allergens'];

        $stmt = $this->db->prepare("
            UPDATE products SET
            name = ?,
            description = ?,
            price = ?,
            image_path = ?,
            stock_quantity = ?,
            allergens = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        return $stmt->execute([
            $merged['name'],
            $merged['description'],
            $merged['price'],
            $merged['image_path'],
            $merged['stock_quantity'],
            $allergensJson,
            $id
        ]);
    }

    public function deleteProduct($id) {
        require_once __DIR__ . '/SystemSettings.php';
        $systemSettings = new SystemSettings();
        $visibilityMode = $systemSettings->getStoreVisibilityMode();
        
        if ($visibilityMode === 'available_only') {
            $stmt = $this->db->prepare("
                DELETE FROM products 
                WHERE id = ? AND seller_id IN (SELECT id FROM users WHERE store_status = 'available' OR store_status IS NULL)
            ");
        } else {
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        }
        return $stmt->execute([$id]);
    }

    public function searchProducts($query) {
        require_once __DIR__ . '/SystemSettings.php';
        $systemSettings = new SystemSettings();
        $visibilityMode = $systemSettings->getStoreVisibilityMode();
        
        if ($visibilityMode === 'available_only') {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE (p.name LIKE ? OR p.description LIKE ?) AND (u.store_status = 'available' OR u.store_status IS NULL)
                ORDER BY p.name
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.name LIKE ? OR p.description LIKE ?
                ORDER BY p.name
            ");
        }
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductsBySeller($sellerId) {
        require_once __DIR__ . '/SystemSettings.php';
        $systemSettings = new SystemSettings();
        $visibilityMode = $systemSettings->getStoreVisibilityMode();
        
        if ($visibilityMode === 'available_only') {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.seller_id = ? AND (u.store_status = 'available' OR u.store_status IS NULL)
                ORDER BY p.name
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.seller_id = ?
                ORDER BY p.name
            ");
        }
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductsByAllergens($allergens) {
        require_once __DIR__ . '/SystemSettings.php';
        $systemSettings = new SystemSettings();
        $visibilityMode = $systemSettings->getStoreVisibilityMode();
        
        if (empty($allergens)) {
            return $this->getAllProducts();
        }
        
        // For PostgreSQL, build a simpler condition using JSONB operators
        $allergenList = "'" . implode("','", array_map('addslashes', $allergens)) . "'";
        
        if ($visibilityMode === 'available_only') {
            $stmt = $this->db->query("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.allergens::jsonb ?| array[$allergenList] AND (u.store_status = 'available' OR u.store_status IS NULL)
                ORDER BY p.name
            ");
        } else {
            $stmt = $this->db->query("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.allergens::jsonb ?| array[$allergenList]
                ORDER BY p.name
            ");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopRatedProducts($limit = 10) {
        require_once __DIR__ . '/SystemSettings.php';
        $systemSettings = new SystemSettings();
        $visibilityMode = $systemSettings->getStoreVisibilityMode();
        
        if ($visibilityMode === 'available_only') {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.review_count > 0 AND (u.store_status = 'available' OR u.store_status IS NULL)
                ORDER BY p.average_rating DESC, p.review_count DESC
                LIMIT ?
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username as seller_name, COALESCE(p.average_rating, 0) as average_rating, COALESCE(p.review_count, 0) as review_count 
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.review_count > 0
                ORDER BY p.average_rating DESC, p.review_count DESC
                LIMIT ?
            ");
        }
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateProductRating($productId) {
        require_once __DIR__ . '/Review.php';
        $reviewModel = new Review();
        return $reviewModel->updateProductRating($productId);
    }
}
?>