<?php
require_once __DIR__ . '/src/helpers/functions.php';
require_once __DIR__ . '/src/controllers/ReviewController.php';
require_once __DIR__ . '/src/controllers/ProductController.php';
require_once __DIR__ . '/src/helpers/review_helpers.php';

// Get product ID from URL parameter
$productId = $_GET['product_id'] ?? null;
if (!$productId) {
    redirect('/products.php');
}

// Initialize controllers
$reviewController = new ReviewController();
$productController = new ProductController();

// Get product info
$product = $productController->getProductById($productId);
if (!$product) {
    redirect('/products.php');
}

// Get review data
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';

$reviews = $reviewController->getProductReviews($productId, $page, $limit, $sortBy, $sortOrder);
$stats = $reviewController->getProductRatingStats($productId);

// Check if user can review
$canReview = false;
$userReview = null;
if (isLoggedIn()) {
    $canReviewInfo = $reviewController->canUserReview($productId, $_SESSION['user_id']);
    $canReview = $canReviewInfo['can_review'];
    $userReview = $reviewController->getUserReview($productId, $_SESSION['user_id']);
}
?>

<?php require_once __DIR__ . '/src/views/partials/header.php'; ?>

<div class="modern-panel">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="page-title">Product Reviews</h1>
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

    <div class="product-reviews-container">
        <!-- Product Info Section -->
        <div class="product-info-section">
            <div class="product-image-section">
                <img src="<?= getProductImageUrl($product['image_path'] ?? 'default.jpg') ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     class="product-image-large">
            </div>
            <div class="product-details-section">
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <?php displayProductRating($product, true, 'large'); ?>
                <p class="product-description-large"><?= htmlspecialchars($product['description']) ?></p>
                <div class="product-meta-large">
                    <span class="price-large"><?= formatPrice($product['price']) ?></span>
                    <span class="stock-status <?= $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <?= $product['stock_quantity'] > 0 ? $product['stock_quantity'] . ' in stock' : 'out of stock' ?>
                    </span>
                </div>
                <div class="product-actions-large">
                    <?php if (isLoggedIn() && $product['stock_quantity'] > 0): ?>
                        <a href="/submit-review.php?product_id=<?= $productId ?>" class="modern-button primary">
                            <?= $userReview ? 'Edit Your Review' : 'Write a Review' ?>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="modern-button secondary" onclick="history.back()">
                        Back to Shopping
                    </button>
                </div>
            </div>
        </div>

        <!-- Review Summary -->
        <div class="reviews-section">
            <h3 class="section-title">Customer Reviews</h3>
            <?php displayReviewSummary($stats); ?>

            <!-- Review Sorting -->
            <div class="review-sorting">
                <label for="sort-select">Sort by:</label>
                <select id="sort-select" class="modern-select" onchange="sortReviews(this.value)">
                    <option value="created_at-DESC" <?= ($sortBy === 'created_at' && $sortOrder === 'DESC') ? 'selected' : '' ?>>
                        Most Recent
                    </option>
                    <option value="created_at-ASC" <?= ($sortBy === 'created_at' && $sortOrder === 'ASC') ? 'selected' : '' ?>>
                        Oldest First
                    </option>
                    <option value="rating-DESC" <?= ($sortBy === 'rating' && $sortOrder === 'DESC') ? 'selected' : '' ?>>
                        Highest Rating
                    </option>
                    <option value="rating-ASC" <?= ($sortBy === 'rating' && $sortOrder === 'ASC') ? 'selected' : '' ?>>
                        Lowest Rating
                    </option>
                    <option value="helpful_count-DESC" <?= ($sortBy === 'helpful_count' && $sortOrder === 'DESC') ? 'selected' : '' ?>>
                        Most Helpful
                    </option>
                </select>
            </div>

            <!-- Reviews List -->
            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <div class="no-reviews">
                        <div class="no-reviews-icon">📝</div>
                        <h3>No Reviews Yet</h3>
                        <p>Be the first to share your experience with this product!</p>
                        <?php if (isLoggedIn() && $canReview): ?>
                            <a href="/submit-review.php?product_id=<?= $productId ?>" class="modern-button primary">
                                Write First Review
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <?php 
                        $canEdit = isLoggedIn() && $review['user_id'] == $_SESSION['user_id'];
                        displayReviewCard($review, $canEdit); 
                        ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($stats['total_reviews'] > $limit): ?>
                <div class="pagination">
                    <?php
                    $totalPages = ceil($stats['total_reviews'] / $limit);
                    for ($i = 1; $i <= $totalPages; $i++):
                        $active = $i === $page;
                        $url = "?product_id={$productId}&page={$i}&sort={$sortBy}&order={$sortOrder}";
                    ?>
                        <a href="<?= $url ?>" class="<?= $active ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.product-reviews-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.product-info-section {
    background: var(--white);
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    height: fit-content;
}

.product-image-section {
    margin-bottom: 1.5rem;
}

.product-image-large {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid var(--medium-gray);
}

.product-details-section h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1rem 0;
}

.product-description-large {
    color: var(--text-secondary);
    line-height: 1.6;
    margin: 1rem 0;
}

.product-meta-large {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin: 1.5rem 0;
}

.price-large {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary-blue);
}

.product-actions-large {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 2rem;
}

.reviews-section {
    background: var(--white);
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.review-sorting {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--medium-gray);
}

.review-sorting label {
    font-weight: 600;
    color: var(--text-primary);
}

.reviews-list {
    margin-bottom: 2rem;
}

.no-reviews {
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}

.no-reviews-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-reviews h3 {
    font-size: 1.5rem;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination a {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--medium-gray);
    border-radius: 6px;
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.2s ease;
}

.pagination a:hover {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

.pagination a.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .product-reviews-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .product-info-section {
        order: 2;
    }
    
    .reviews-section {
        order: 1;
    }
}

@media (max-width: 768px) {
    .product-reviews-container {
        gap: 1rem;
    }
    
    .product-info-section,
    .reviews-section {
        padding: 1.5rem;
    }
    
    .product-actions-large {
        flex-direction: column;
    }
    
    .review-sorting {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<script>
function sortReviews(value) {
    const [sort, order] = value.split('-');
    const url = new URL(window.location);
    url.searchParams.set('sort', sort);
    url.searchParams.set('order', order);
    url.searchParams.delete('page'); // Reset to first page
    window.location.href = url.toString();
}

function getProductImageUrl(imagePath) {
    if (!imagePath) {
        return '/images/products/default.jpg';
    }
    if (imagePath.includes('products/')) {
        return '/images/' + imagePath;
    }
    return '/images/products/' + imagePath;
}
</script>

<?php require_once __DIR__ . '/src/views/partials/footer.php'; ?>
