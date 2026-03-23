<?php
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../helpers/functions.php';

class ReviewController {
    private $reviewModel;
    private $productModel;

    public function __construct() {
        $this->reviewModel = new Review();
        $this->productModel = new Product();
    }

    public function submitReview() {
        if (!isLoggedIn()) {
            return ['success' => false, 'message' => 'You must be logged in to submit a review.'];
        }

        $productId = $_POST['product_id'] ?? null;
        $userId = $_SESSION['user_id'];
        $rating = $_POST['rating'] ?? null;
        $title = $_POST['title'] ?? '';
        $comment = $_POST['comment'] ?? '';

        // Validate input
        if (!$productId || !$rating || empty($title) || empty($comment)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5.'];
        }

        // Check if user can review
        $canReview = $this->reviewModel->canUserReview($productId, $userId);
        if (!$canReview['can_review']) {
            if (!$canReview['has_purchased']) {
                return ['success' => false, 'message' => 'You must purchase this product before reviewing.'];
            } else {
                return ['success' => false, 'message' => 'You have already reviewed this product.'];
            }
        }

        // Check if it's a verified purchase
        $verifiedPurchase = $canReview['has_purchased'];

        $reviewData = [
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'verified_purchase' => $verifiedPurchase
        ];

        if ($this->reviewModel->createReview($reviewData)) {
            // Update product rating
            $this->productModel->updateProductRating($productId);
            
            return ['success' => true, 'message' => 'Review submitted successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to submit review. Please try again.'];
        }
    }

    public function updateReview() {
        if (!isLoggedIn()) {
            return ['success' => false, 'message' => 'You must be logged in to update a review.'];
        }

        $reviewId = $_POST['review_id'] ?? null;
        $userId = $_SESSION['user_id'];
        $rating = $_POST['rating'] ?? null;
        $title = $_POST['title'] ?? '';
        $comment = $_POST['comment'] ?? '';

        // Validate input
        if (!$reviewId || !$rating || empty($title) || empty($comment)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5.'];
        }

        $reviewData = [
            'user_id' => $userId,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment
        ];

        if ($this->reviewModel->updateReview($reviewId, $reviewData)) {
            // Get product ID to update rating
            $review = $this->reviewModel->getUserReview($reviewId, $userId);
            if ($review) {
                $this->productModel->updateProductRating($review['product_id']);
            }
            
            return ['success' => true, 'message' => 'Review updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to update review. Please try again.'];
        }
    }

    public function deleteReview() {
        if (!isLoggedIn()) {
            return ['success' => false, 'message' => 'You must be logged in to delete a review.'];
        }

        $reviewId = $_POST['review_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$reviewId) {
            return ['success' => false, 'message' => 'Review ID is required.'];
        }

        // Get product ID before deleting
        $review = $this->reviewModel->getUserReview($reviewId, $userId);
        
        if ($this->reviewModel->deleteReview($reviewId, $userId)) {
            // Update product rating
            if ($review) {
                $this->productModel->updateProductRating($review['product_id']);
            }
            
            return ['success' => true, 'message' => 'Review deleted successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete review. Please try again.'];
        }
    }

    public function markHelpful() {
        if (!isLoggedIn()) {
            return ['success' => false, 'message' => 'You must be logged in to vote.'];
        }

        $reviewId = $_POST['review_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$reviewId) {
            return ['success' => false, 'message' => 'Review ID is required.'];
        }

        if ($this->reviewModel->markHelpful($reviewId, $userId)) {
            return ['success' => true, 'message' => 'Marked as helpful!'];
        } else {
            return ['success' => false, 'message' => 'You have already voted for this review.'];
        }
    }

    public function getProductReviews($productId, $page = 1, $limit = 10, $sortBy = 'created_at', $sortOrder = 'DESC') {
        $offset = ($page - 1) * $limit;
        return $this->reviewModel->getReviewsByProduct($productId, $limit, $offset, $sortBy, $sortOrder);
    }

    public function getProductRatingStats($productId) {
        return $this->reviewModel->getProductRatingStats($productId);
    }

    public function getUserReview($productId, $userId) {
        return $this->reviewModel->getUserReview($productId, $userId);
    }

    public function canUserReview($productId, $userId) {
        return $this->reviewModel->canUserReview($productId, $userId);
    }

    public function submitQuickRating($productId, $rating) {
        if (!isLoggedIn()) {
            return ['success' => false, 'error' => 'You must be logged in to rate a product.'];
        }

        $userId = $_SESSION['user_id'];

        // Validate input
        if (!$productId || !$rating) {
            return ['success' => false, 'error' => 'Product ID and rating are required.'];
        }

        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'error' => 'Rating must be between 1 and 5.'];
        }

        // Check if user can review
        $canReview = $this->reviewModel->canUserReview($productId, $userId);
        if (!$canReview['can_review']) {
            return ['success' => false, 'error' => 'You must purchase this product before rating it.'];
        }

        // Create quick review with just rating (auto-generate title and comment)
        $reviewData = [
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => $rating,
            'title' => 'Quick Rating',
            'comment' => 'User rated this product ' . $rating . ' stars.',
            'verified_purchase' => $canReview['has_purchased']
        ];

        if ($this->reviewModel->createReview($reviewData)) {
            // Update product rating
            $this->productModel->updateProductRating($productId);
            
            return ['success' => true, 'message' => 'Rating submitted successfully!'];
        } else {
            return ['success' => false, 'error' => 'Failed to submit rating. Please try again.'];
        }
    }

    public function updateQuickRating($productId, $rating) {
        if (!isLoggedIn()) {
            return ['success' => false, 'error' => 'You must be logged in to update a rating.'];
        }

        $userId = $_SESSION['user_id'];

        // Validate input
        if (!$productId || !$rating) {
            return ['success' => false, 'error' => 'Product ID and rating are required.'];
        }

        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'error' => 'Rating must be between 1 and 5.'];
        }

        // Get existing review
        $existingReview = $this->reviewModel->getUserReview($productId, $userId);
        if (!$existingReview) {
            return ['success' => false, 'error' => 'No existing review found.'];
        }

        // Update only the rating, keep existing title and comment
        $reviewData = [
            'user_id' => $userId,
            'rating' => $rating
        ];

        if ($this->reviewModel->updateReview($existingReview['id'], $reviewData)) {
            // Update product rating
            $this->productModel->updateProductRating($productId);
            
            return ['success' => true, 'message' => 'Rating updated successfully!'];
        } else {
            return ['success' => false, 'error' => 'Failed to update rating. Please try again.'];
        }
    }
}
?>
