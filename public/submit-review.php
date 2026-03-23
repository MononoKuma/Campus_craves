<?php
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/controllers/ReviewController.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}

$reviewController = new ReviewController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle quick rating from product cards
    if (isset($_POST['quick_rating']) && $_POST['quick_rating'] === 'true') {
        $productId = $_POST['product_id'] ?? 0;
        $rating = $_POST['rating'] ?? 0;
        
        if ($productId && $rating >= 1 && $rating <= 5) {
            // Check if user can review (has purchased the product)
            $canReviewResult = $reviewController->canUserReview($productId, $_SESSION['user_id']);
            
            if ($canReviewResult['can_review']) {
                // Check if user already has a review
                $existingReview = $reviewController->getUserReview($productId, $_SESSION['user_id']);
                
                if ($existingReview) {
                    // Update existing review with just rating (keep title and comment)
                    $result = $reviewController->updateQuickRating($productId, $rating);
                } else {
                    // Create new quick review with just rating
                    $result = $reviewController->submitQuickRating($productId, $rating);
                }
                
                // Get updated product stats
                $productStats = $reviewController->getProductRatingStats($productId);
                if ($productStats) {
                    $result['new_average_rating'] = $productStats['average_rating'];
                    $result['new_review_count'] = $productStats['total_reviews'];
                }
            } else {
                $result = ['success' => false, 'error' => 'You must purchase this product before rating it.'];
            }
        } else {
            $result = ['success' => false, 'error' => 'Invalid rating data.'];
        }
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'submit':
                $result = $reviewController->submitReview();
                break;
            case 'update':
                $result = $reviewController->updateReview();
                break;
            case 'delete':
                $result = $reviewController->deleteReview();
                break;
            case 'helpful':
                $result = $reviewController->markHelpful();
                break;
            default:
                $result = ['success' => false, 'message' => 'Invalid action'];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Get product ID from URL parameter
$productId = $_GET['product_id'] ?? null;
if (!$productId) {
    redirect('/products.php');
}

// Check if user can review
$canReview = $reviewController->canUserReview($productId, $_SESSION['user_id']);
$userReview = $reviewController->getUserReview($productId, $_SESSION['user_id']);

// Get product info
require_once __DIR__ . '/../src/controllers/ProductController.php';
$productController = new ProductController();
$product = $productController->getProductById($productId);

if (!$product) {
    redirect('/products.php');
}
?>

<?php require_once __DIR__ . '/../src/views/partials/header.php'; ?>

<div class="modern-panel">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="page-title">Review Product</h1>
                <p class="page-subtitle"><?= htmlspecialchars($product['name']) ?></p>
            </div>
            <a href="/products.php" class="back-button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Products
            </a>
        </div>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert <?= $_GET['success'] ?? false ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($_GET['message']) ?>
        </div>
    <?php endif; ?>

    <div class="review-container">
        <?php if ($userReview): ?>
            <!-- Edit Existing Review -->
            <div class="review-form-section">
                <h2 class="section-title">Edit Your Review</h2>
                <form id="review-form" class="modern-form" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="review_id" value="<?= $userReview['id'] ?>">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <div class="rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" 
                                       <?= $i == $userReview['rating'] ? 'checked' : '' ?> class="rating-radio">
                                <label for="star<?= $i ?>" class="rating-label">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="title" class="form-label">Review Title</label>
                        <input type="text" id="title" name="title" 
                               value="<?= htmlspecialchars($userReview['title']) ?>"
                               class="form-input" required maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="comment" class="form-label">Your Review</label>
                        <textarea id="comment" name="comment" 
                                  class="form-textarea" required rows="6"
                                  placeholder="Share your experience with this product..."><?= htmlspecialchars($userReview['comment']) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="modern-button primary">
                            Update Review
                        </button>
                        <button type="button" class="modern-button secondary" onclick="deleteReview(<?= $userReview['id'] ?>)">
                            Delete Review
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif ($canReview['can_review']): ?>
            <!-- Submit New Review -->
            <div class="review-form-section">
                <h2 class="section-title">Write a Review</h2>
                <form id="review-form" class="modern-form" method="POST">
                    <input type="hidden" name="action" value="submit">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <div class="rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" class="rating-radio">
                                <label for="star<?= $i ?>" class="rating-label">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="title" class="form-label">Review Title</label>
                        <input type="text" id="title" name="title" 
                               class="form-input" required maxlength="255"
                               placeholder="Summarize your experience">
                    </div>

                    <div class="form-group">
                        <label for="comment" class="form-label">Your Review</label>
                        <textarea id="comment" name="comment" 
                                  class="form-textarea" required rows="6"
                                  placeholder="Share your experience with this product..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="modern-button primary">
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Cannot Review -->
            <div class="cannot-review">
                <div class="cannot-review-icon">📝</div>
                <h3>Unable to Review</h3>
                <p>
                    <?php if (!$canReview['has_purchased']): ?>
                        You must purchase this product before you can review it.
                    <?php else: ?>
                        You have already reviewed this product.
                    <?php endif; ?>
                </p>
                <a href="/products.php" class="modern-button primary">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.review-container {
    max-width: 800px;
    margin: 0 auto;
}

.review-form-section {
    background: var(--white);
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--primary-blue);
}

.rating-input {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.rating-radio {
    display: none;
}

.rating-label {
    font-size: 2rem;
    color: #d1d5db;
    cursor: pointer;
    transition: color 0.2s ease;
}

.rating-label:hover {
    color: #fbbf24;
}

.rating-radio:checked + .rating-label {
    color: #fbbf24;
}

.cannot-review {
    text-align: center;
    padding: 3rem;
    background: var(--white);
    border-radius: 12px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.cannot-review-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.cannot-review h3 {
    font-size: 1.5rem;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.cannot-review p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    font-weight: 500;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    color: #166534;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #991b1b;
    border: 1px solid rgba(239, 68, 68, 0.2);
}
</style>

<script>
document.getElementById('review-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('submit-review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'submit-review.php?product_id=<?= $productId ?>&success=1&message=' + encodeURIComponent(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});

function deleteReview(reviewId) {
    if (confirm('Are you sure you want to delete this review?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('review_id', reviewId);
        
        fetch('submit-review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'submit-review.php?product_id=<?= $productId ?>&success=1&message=' + encodeURIComponent(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
}
</script>

<?php require_once __DIR__ . '/../src/views/partials/footer.php'; ?>
