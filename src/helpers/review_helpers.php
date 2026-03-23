<?php
require_once __DIR__ . '/../controllers/ReviewController.php';

function displayProductRating($product, $showCount = true, $size = 'small') {
    $rating = $product['average_rating'] ?? 0;
    $count = $product['review_count'] ?? 0;
    $productId = $product['id'] ?? 0;
    $sizeClass = $size === 'large' ? 'rating-large' : 'rating-small';
    $interactiveClass = isLoggedIn() ? 'rating-interactive' : '';
    
    echo '<div class="product-rating ' . $sizeClass . ' ' . $interactiveClass . '" data-product-id="' . $productId . '" data-current-rating="' . $rating . '">';
    echo '<div class="rating-stars">';
    
    for ($i = 1; $i <= 5; $i++) {
        $starClass = $i <= $rating ? 'star-filled' : 'star-empty';
        echo '<span class="rating-star ' . $starClass . '" data-star-value="' . $i . '" data-product-id="' . $productId . '">★</span>';
    }
    
    echo '</div>';
    
    if ($showCount && $count > 0) {
        echo '<span class="rating-count">(' . number_format($rating, 1) . ')</span>';
        if ($size === 'large') {
            echo '<span class="rating-text">' . $count . ' reviews</span>';
        }
    }
    
    echo '</div>';
}

function displayReviewCard($review, $canEdit = false) {
    $rating = $review['rating'];
    $username = htmlspecialchars($review['username'] ?? 'Anonymous');
    $title = htmlspecialchars($review['title']);
    $comment = htmlspecialchars($review['comment']);
    $date = date('M d, Y', strtotime($review['created_at']));
    $helpfulCount = $review['helpful_votes'] ?? 0;
    $verified = $review['verified_purchase'] ?? false;
    
    echo '<div class="review-card">';
    echo '<div class="review-header">';
    
    echo '<div class="reviewer-info">';
    echo '<div class="reviewer-avatar">' . strtoupper(substr($username, 0, 1)) . '</div>';
    echo '<div class="reviewer-details">';
    echo '<div class="reviewer-name">' . $username . '</div>';
    echo '<div class="review-date">' . $date . '</div>';
    if ($verified) {
        echo '<div class="verified-purchase">✓ Verified Purchase</div>';
    }
    echo '</div>';
    echo '</div>';
    
    echo '<div class="review-rating">';
    for ($i = 1; $i <= 5; $i++) {
        $starClass = $i <= $rating ? 'star-filled' : 'star-empty';
        echo '<span class="rating-star ' . $starClass . '">★</span>';
    }
    echo '</div>';
    
    echo '</div>'; // review-header
    
    echo '<div class="review-content">';
    echo '<h4 class="review-title">' . $title . '</h4>';
    echo '<p class="review-comment">' . $comment . '</p>';
    echo '</div>';
    
    echo '<div class="review-actions">';
    if (isLoggedIn()) {
        echo '<button class="helpful-btn" onclick="markHelpful(' . $review['id'] . ')">';
        echo '👍 Helpful (' . $helpfulCount . ')';
        echo '</button>';
    }
    
    if ($canEdit) {
        echo '<button class="edit-review-btn" onclick="editReview(' . $review['id'] . ')">Edit</button>';
        echo '<button class="delete-review-btn" onclick="deleteReview(' . $review['id'] . ')">Delete</button>';
    }
    echo '</div>';
    
    echo '</div>'; // review-card
}

function displayReviewSummary($stats) {
    $totalReviews = $stats['total_reviews'] ?? 0;
    $averageRating = $stats['average_rating'] ?? 0;
    
    if ($totalReviews === 0) {
        echo '<div class="review-summary-empty">';
        echo '<p>No reviews yet. Be the first to review this product!</p>';
        echo '</div>';
        return;
    }
    
    echo '<div class="review-summary">';
    echo '<div class="summary-main">';
    echo '<div class="average-rating">' . number_format($averageRating, 1) . '</div>';
    echo '<div class="rating-stars-large">';
    for ($i = 1; $i <= 5; $i++) {
        $starClass = $i <= $averageRating ? 'star-filled' : 'star-empty';
        echo '<span class="rating-star ' . $starClass . '">★</span>';
    }
    echo '</div>';
    echo '<div class="total-reviews">' . $totalReviews . ' Reviews</div>';
    echo '</div>';
    
    echo '<div class="rating-breakdown">';
    $ratings = [5, 4, 3, 2, 1];
    foreach ($ratings as $rating) {
        $count = $stats[$rating . '_star'] ?? 0;
        $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
        
        echo '<div class="rating-row">';
        echo '<span class="rating-label">' . $rating . ' ★</span>';
        echo '<div class="rating-bar-container">';
        echo '<div class="rating-bar" style="width: ' . $percentage . '%"></div>';
        echo '</div>';
        echo '<span class="rating-count">' . $count . '</span>';
        echo '</div>';
    }
    echo '</div>';
    
    echo '</div>';
}

function getReviewController() {
    static $controller = null;
    if ($controller === null) {
        $controller = new ReviewController();
    }
    return $controller;
}
?>
