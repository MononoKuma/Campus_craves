<?php
require_once __DIR__ . '/../config/database.php';

class Review {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getDb() {
        return $this->db->connect();
    }

    public function createReview($data) {
        $stmt = $this->db->prepare("
            INSERT INTO reviews (product_id, user_id, rating, title, comment, verified_purchase)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['product_id'],
            $data['user_id'],
            $data['rating'],
            $data['title'],
            $data['comment'],
            $data['verified_purchase'] ?? false
        ]);
    }

    public function getReviewsByProduct($productId, $limit = 10, $offset = 0, $sortBy = 'created_at', $sortOrder = 'DESC') {
        $allowedSortFields = ['created_at', 'rating', 'helpful_count'];
        $allowedSortOrders = ['ASC', 'DESC'];
        
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'created_at';
        $sortOrder = in_array($sortOrder, $allowedSortOrders) ? $sortOrder : 'DESC';
        
        $stmt = $this->db->prepare("
            SELECT r.*, u.username, u.first_name, u.last_name,
                   (SELECT COUNT(*) FROM review_helpful_votes WHERE review_id = r.id) as helpful_votes
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ?
            ORDER BY r.{$sortBy} {$sortOrder}
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$productId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductRatingStats($productId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM reviews
            WHERE product_id = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserReview($productId, $userId) {
        $stmt = $this->db->prepare("
            SELECT r.*, u.username, u.first_name, u.last_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ? AND r.user_id = ?
        ");
        $stmt->execute([$productId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateReview($reviewId, $data) {
        $stmt = $this->db->prepare("
            UPDATE reviews SET
                rating = ?,
                title = ?,
                comment = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([
            $data['rating'],
            $data['title'],
            $data['comment'],
            $reviewId,
            $data['user_id']
        ]);
    }

    public function deleteReview($reviewId, $userId) {
        $stmt = $this->db->prepare("
            DELETE FROM reviews WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$reviewId, $userId]);
    }

    public function canUserReview($productId, $userId) {
        // Check if user has purchased the product
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as has_purchased
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
        ");
        $stmt->execute([$userId, $productId]);
        $hasPurchased = $stmt->fetch(PDO::FETCH_ASSOC)['has_purchased'] > 0;
        
        // Check if user has already reviewed
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as has_reviewed
            FROM reviews
            WHERE product_id = ? AND user_id = ?
        ");
        $stmt->execute([$productId, $userId]);
        $hasReviewed = $stmt->fetch(PDO::FETCH_ASSOC)['has_reviewed'] > 0;
        
        return [
            'can_review' => $hasPurchased && !$hasReviewed,
            'has_purchased' => $hasPurchased,
            'has_reviewed' => $hasReviewed
        ];
    }

    public function markHelpful($reviewId, $userId) {
        // Check if user has already voted
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as has_voted
            FROM review_helpful_votes
            WHERE review_id = ? AND user_id = ?
        ");
        $stmt->execute([$reviewId, $userId]);
        $hasVoted = $stmt->fetch(PDO::FETCH_ASSOC)['has_voted'] > 0;
        
        if ($hasVoted) {
            return false;
        }
        
        // Add helpful vote
        $stmt = $this->db->prepare("
            INSERT INTO review_helpful_votes (review_id, user_id)
            VALUES (?, ?)
        ");
        $result = $stmt->execute([$reviewId, $userId]);
        
        if ($result) {
            // Update helpful count
            $stmt = $this->db->prepare("
                UPDATE reviews SET helpful_count = helpful_count + 1
                WHERE id = ?
            ");
            $stmt->execute([$reviewId]);
        }
        
        return $result;
    }

    public function getReviewsWithImages($productId, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT r.*, u.username, u.first_name, u.last_name,
                   (SELECT COUNT(*) FROM review_helpful_votes WHERE review_id = r.id) as helpful_votes
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ? AND r.rating >= 4
            ORDER BY r.helpful_count DESC, r.rating DESC
            LIMIT ?
        ");
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateProductRating($productId) {
        $stats = $this->getProductRatingStats($productId);
        
        $stmt = $this->db->prepare("
            UPDATE products SET
                average_rating = ?,
                review_count = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $stats['average_rating'] ?? 0,
            $stats['total_reviews'] ?? 0,
            $productId
        ]);
    }
}
?>
