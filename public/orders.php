<?php
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/Orders.php';
require_once __DIR__ . '/../src/models/Review.php';
require_once __DIR__ . '/../src/controllers/ReviewController.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}
// Redirect admin users to the admin dashboard
if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

$orderModel = new Order();
$reviewModel = new Review();
$reviewController = new ReviewController();
$userId = $_SESSION['user_id'];

// Handle order cancellation
$cancelMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $orderId = intval($_POST['cancel_order_id']);
    // Fetch the order and check ownership and status
    $order = $orderModel->getOrderById($orderId);
    if ($order && $order['user_id'] == $userId && $order['status'] === 'pending') {
        $orderModel->updateOrderStatus($orderId, 'cancelled');
        $cancelMessage = 'Order #' . htmlspecialchars($orderId) . ' has been cancelled.';
        // Refresh orders list
        $userOrders = $orderModel->getUserOrders($userId);
    } else {
        $cancelMessage = 'Unable to cancel this order.';
    }
}

// Handle review submission
$reviewMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $result = $reviewController->submitReview();
    $reviewMessage = $result['message'];
    if ($result['success']) {
        // Refresh orders list to update review status
        $userOrders = $orderModel->getUserOrders($userId);
    }
}

// Handle review update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_review'])) {
    $result = $reviewController->updateReview();
    $reviewMessage = $result['message'];
    if ($result['success']) {
        // Refresh orders list to update review status
        $userOrders = $orderModel->getUserOrders($userId);
    }
}

$userOrders = $orderModel->getUserOrders($userId);

function formatOrderStatus($status) {
    switch ($status) {
        case 'completed': return '<span class="order-status completed">Completed</span>';
        case 'pending': return '<span class="order-status pending">Pending</span>';
        case 'cancelled': return '<span class="order-status cancelled">Cancelled</span>';
        default: return htmlspecialchars($status);
    }
}
?>

<?php require_once __DIR__ . '/../src/views/partials/header.php'; ?>

<main class="orders-main">
    <div class="orders-container">
        <!-- Orders Header -->
        <div class="orders-header">
            <div class="header-content">
                <h1 class="page-title">Your Orders</h1>
                <p class="page-subtitle">Track and manage your purchases</p>
            </div>
            <div class="header-actions">
                <a href="/products.php" class="header-action-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                    Browse Products
                </a>
            </div>
        </div>

        <?php if (!empty($cancelMessage)): ?>
            <div class="alert alert-success"><?= $cancelMessage ?></div>
        <?php endif; ?>

        <?php if (!empty($reviewMessage)): ?>
            <div class="alert alert-<?= strpos($reviewMessage, 'successfully') !== false ? 'success' : 'error' ?>"><?= $reviewMessage ?></div>
        <?php endif; ?>

        <?php if (empty($userOrders)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                </div>
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders. Start shopping to see your orders here.</p>
                <a href="/products.php" class="primary-button">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($userOrders as $order): ?>
                    <div class="order-card">
                        <div class="order-header-info">
                            <div class="order-info">
                                <h3 class="order-id">Order #<?= htmlspecialchars($order['id']) ?></h3>
                                <p class="order-date"><?= date('F j, Y', strtotime($order['created_at'])) ?></p>
                            </div>
                            <div class="order-status">
                                <?= formatOrderStatus($order['status']) ?>
                            </div>
                        </div>
                        
                        <div class="order-content">
                            <div class="order-summary">
                                <div class="total-amount">
                                    <span class="amount-label">Total:</span>
                                    <span class="amount-value"><?= formatPrice($order['total_amount']) ?></span>
                                </div>
                            </div>
                            
                            <div class="order-actions">
                                <button class="secondary-button details-btn" data-order-id="<?= $order['id'] ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    View Details
                                </button>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <form method="POST" action="" class="cancel-order-form">
                                        <input type="hidden" name="cancel_order_id" value="<?= $order['id'] ?>">
                                        <button type="button" class="danger-button cancel-btn" data-order-id="<?= $order['id'] ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                            Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Details Modal -->
                    <div id="order-modal-<?= $order['id'] ?>" class="order-modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2>Order Details #<?= htmlspecialchars($order['id']) ?></h2>
                                <button class="modal-close" data-order-id="<?= $order['id'] ?>">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="modal-body">
                                <div class="order-info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Order Date:</span>
                                        <span class="info-value"><?= date('F j, Y, g:i A', strtotime($order['created_at'])) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value"><?= formatOrderStatus($order['status']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total Amount:</span>
                                        <span class="info-value amount"><?= formatPrice($order['total_amount']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Delivery Method:</span>
                                        <span class="info-value"><?= ucfirst(htmlspecialchars($order['delivery_mode'] ?? 'delivery')) ?></span>
                                    </div>
                                    
                                    <?php if ($order['delivery_mode'] === 'delivery'): ?>
                                        <div class="delivery-section delivery-mode">
                                            <h4 class="delivery-section-title">Campus Delivery</h4>
                                            <div class="delivery-details-grid">
                                                <div class="delivery-field">
                                                    <span class="field-label">School Building</span>
                                                    <span class="field-value"><?= 
                                                        $order['delivery_address'] && $order['delivery_address'] !== 'To be specified' 
                                                            ? (explode(' ', $order['delivery_address'], 2)[0] ?? 'Not specified') 
                                                            : 'Not specified' 
                                                    ?></span>
                                                </div>
                                                <div class="delivery-field">
                                                    <span class="field-label">Room Number</span>
                                                    <span class="field-value"><?= 
                                                        $order['delivery_address'] && $order['delivery_address'] !== 'To be specified' 
                                                            ? (explode(' ', $order['delivery_address'], 2)[1] ?? 'Not specified') 
                                                            : 'Not specified' 
                                                    ?></span>
                                                </div>
                                                <div class="delivery-field">
                                                    <span class="field-label">Preferred Delivery Time</span>
                                                    <span class="field-value"><?= htmlspecialchars($order['delivery_notes'] ?: 'Any time') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['delivery_mode'] === 'meetup'): ?>
                                        <div class="delivery-section meetup-mode">
                                            <h4 class="delivery-section-title">Campus Meet-up</h4>
                                            <div class="delivery-details-grid">
                                                <div class="delivery-field">
                                                    <span class="field-label">Preferred Meetup Time</span>
                                                    <span class="field-value"><?= $order['meetup_time'] ? date('m/d/Y g:i A', strtotime($order['meetup_time'])) : 'Not specified' ?></span>
                                                </div>
                                                <div class="delivery-field">
                                                    <span class="field-label">Meetup Location</span>
                                                    <span class="field-value"><?= htmlspecialchars($order['meetup_place'] ?: 'Not specified') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-items-section">
                                    <h3>Order Items</h3>
                                    <?php 
                                    $items = $orderModel->getOrderItems($order['id']);
                                    if (empty($items)) {
                                        echo '<p class="no-items">No items found for this order.</p>';
                                    } else {
                                        foreach ($items as $item) {
                                            $subtotal = $item['unit_price'] * $item['quantity'];
                                            $userReview = $reviewModel->getUserReview($item['product_id'], $userId);
                                            $canReview = $reviewModel->canUserReview($item['product_id'], $userId);
                                            
                                            echo '<div class="order-item">';
                                            echo '<div class="item-details">';
                                            echo '<h4 class="item-name">' . htmlspecialchars($item['name']) . '</h4>';
                                            echo '<p class="item-description">' . htmlspecialchars(substr($item['description'] ?? '', 0, 100)) . (strlen($item['description'] ?? '') > 100 ? '...' : '') . '</p>';
                                            echo '<div class="item-meta">';
                                            echo '<span class="item-price">' . formatPrice($item['unit_price']) . '</span>';
                                            echo '<span class="item-quantity">× ' . $item['quantity'] . '</span>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '<div class="item-total">';
                                            echo '<span class="total-label">Subtotal:</span>';
                                            echo '<span class="total-amount">' . formatPrice($subtotal) . '</span>';
                                            echo '</div>';
                                            
                                            // Review section for completed orders
                                            if ($order['status'] === 'completed') {
                                                echo '<div class="item-review-section">';
                                                if ($userReview) {
                                                    echo '<div class="review-status reviewed">';
                                                    echo '<span class="review-badge">Reviewed</span>';
                                                    echo '<div class="review-summary">';
                                                    echo '<div class="review-rating">';
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $userReview['rating'] ? '★' : '☆';
                                                    }
                                                    echo '</div>';
                                                    echo '<p class="review-title">' . htmlspecialchars($userReview['title']) . '</p>';
                                                    echo '</div>';
                                                    echo '<div class="review-actions">';
                                                    echo '<button class="review-btn edit-review-btn" data-product-id="' . $item['product_id'] . '" data-review-id="' . $userReview['id'] . '" data-rating="' . $userReview['rating'] . '" data-title="' . htmlspecialchars($userReview['title']) . '" data-comment="' . htmlspecialchars($userReview['comment']) . '">Edit Review</button>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                } elseif ($canReview['can_review']) {
                                                    echo '<div class="review-status not-reviewed">';
                                                    echo '<button class="review-btn add-review-btn" data-product-id="' . $item['product_id'] . '" data-product-name="' . htmlspecialchars($item['name']) . '">Write Review</button>';
                                                    echo '</div>';
                                                } else {
                                                    echo '<div class="review-status cannot-review">';
                                                    echo '<span class="review-disabled">Review not available</span>';
                                                    if (!$canReview['has_purchased']) {
                                                        echo '<small>Purchase required</small>';
                                                    } elseif ($canReview['has_reviewed']) {
                                                        echo '<small>Already reviewed</small>';
                                                    }
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                            }
                                            
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Review Modal -->
<div id="review-modal" class="review-modal">
    <div class="review-modal-content">
        <div class="review-modal-header">
            <h2 id="review-modal-title">Write a Review</h2>
            <button class="review-modal-close" id="close-review-modal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        
        <form id="review-form" method="POST">
            <input type="hidden" id="review-product-id" name="product_id">
            <input type="hidden" id="review-id" name="review_id">
            <input type="hidden" id="review-action" name="submit_review" value="1">
            
            <div class="review-form-body">
                <div class="product-info">
                    <h3 id="review-product-name">Product Name</h3>
                </div>
                
                <div class="form-group">
                    <label for="rating">Rating *</label>
                    <div class="rating-input">
                        <div class="stars" id="rating-stars">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" id="rating" name="rating" value="5" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="title">Review Title *</label>
                    <input type="text" id="title" name="title" required maxlength="255" placeholder="Summarize your experience">
                </div>
                
                <div class="form-group">
                    <label for="comment">Review *</label>
                    <textarea id="comment" name="comment" required rows="5" placeholder="Tell us about your experience with this product"></textarea>
                </div>
            </div>
            
            <div class="review-form-actions">
                <button type="button" class="secondary-button" id="cancel-review">Cancel</button>
                <button type="submit" class="primary-button" id="submit-review-btn">Submit Review</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal logic for order details
        document.querySelectorAll('.details-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const modal = document.getElementById('order-modal-' + orderId);
                if (modal) modal.style.display = 'flex';
            });
        });
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const modal = document.getElementById('order-modal-' + orderId);
                if (modal) modal.style.display = 'none';
            });
        });
        document.querySelectorAll('.order-modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
        // Cancel order confirmation
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const orderId = this.dataset.orderId;
                if (confirm('Are you sure you want to cancel order #' + orderId + '?')) {
                    this.closest('form').submit();
                }
            });
        });

        // Review modal functionality
        const reviewModal = document.getElementById('review-modal');
        const reviewForm = document.getElementById('review-form');
        const reviewModalTitle = document.getElementById('review-modal-title');
        const reviewProductName = document.getElementById('review-product-name');
        const reviewProductId = document.getElementById('review-product-id');
        const reviewId = document.getElementById('review-id');
        const reviewAction = document.getElementById('review-action');
        const ratingInput = document.getElementById('rating');
        const titleInput = document.getElementById('title');
        const commentInput = document.getElementById('comment');
        const submitBtn = document.getElementById('submit-review-btn');
        const ratingStars = document.querySelectorAll('.star');

        // Rating stars functionality
        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                updateStars(rating);
            });

            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                updateStars(rating);
            });
        });

        document.getElementById('rating-stars').addEventListener('mouseleave', function() {
            updateStars(parseInt(ratingInput.value));
        });

        function updateStars(rating) {
            ratingStars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // Open review modal for new review
        document.querySelectorAll('.add-review-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                
                reviewModalTitle.textContent = 'Write a Review';
                reviewProductName.textContent = productName;
                reviewProductId.value = productId;
                reviewId.value = '';
                reviewAction.name = 'submit_review';
                reviewAction.value = '1';
                submitBtn.textContent = 'Submit Review';
                
                // Reset form
                ratingInput.value = 5;
                titleInput.value = '';
                commentInput.value = '';
                updateStars(5);
                
                reviewModal.style.display = 'flex';
            });
        });

        // Open review modal for editing
        document.querySelectorAll('.edit-review-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const reviewIdValue = this.dataset.reviewId;
                const rating = this.dataset.rating;
                const title = this.dataset.title;
                const comment = this.dataset.comment;
                
                reviewModalTitle.textContent = 'Edit Your Review';
                reviewProductName.textContent = 'Edit your review for this product';
                reviewProductId.value = productId;
                reviewId.value = reviewIdValue;
                reviewAction.name = 'update_review';
                reviewAction.value = '1';
                submitBtn.textContent = 'Update Review';
                
                // Populate form
                ratingInput.value = rating;
                titleInput.value = title;
                commentInput.value = comment;
                updateStars(parseInt(rating));
                
                reviewModal.style.display = 'flex';
            });
        });

        // Close review modal
        document.getElementById('close-review-modal').addEventListener('click', function() {
            reviewModal.style.display = 'none';
        });

        document.getElementById('cancel-review').addEventListener('click', function() {
            reviewModal.style.display = 'none';
        });

        reviewModal.addEventListener('click', function(e) {
            if (e.target === reviewModal) {
                reviewModal.style.display = 'none';
            }
        });

        // Form submission
        reviewForm.addEventListener('submit', function(e) {
            if (!ratingInput.value || !titleInput.value.trim() || !commentInput.value.trim()) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
        });
        </script>

<?php require_once __DIR__ . '/../src/views/partials/footer.php'; ?>

<style>
/* Orders Page Layout */
.orders-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 200px);
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.orders-container {
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Orders Header */
.orders-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
}

.header-content h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.page-subtitle {
    color: var(--text-secondary);
    font-size: 1rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.header-action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.header-action-btn:hover {
    background: var(--dark-blue);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Alert */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    margin-bottom: 1rem;
}

.alert-success {
    background: rgba(5, 150, 105, 0.1);
    color: #059669;
    border: 1px solid rgba(5, 150, 105, 0.2);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.empty-icon {
    margin-bottom: 2rem;
    color: var(--text-secondary);
}

.empty-state h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1rem 0;
}

.empty-state p {
    color: var(--text-secondary);
    margin: 0 0 2rem 0;
    font-size: 1rem;
}

.primary-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.primary-button:hover {
    background: var(--dark-blue);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Orders List */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Order Card */
.order-card {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    overflow: hidden;
    transition: all 0.2s ease;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.order-header-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--medium-gray);
}

.order-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.order-date {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0;
}

/* Order Status */
.order-status {
    display: flex;
    align-items: center;
}

.order-status span {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.order-status .completed {
    background: rgba(5, 150, 105, 0.1);
    color: #059669;
}

.order-status .pending {
    background: rgba(251, 191, 36, 0.1);
    color: #d97706;
}

.order-status .cancelled {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.order-content {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.total-amount {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.amount-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.amount-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-blue);
}

.order-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.secondary-button, .danger-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.secondary-button {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary-blue);
    border: 1px solid rgba(37, 99, 235, 0.2);
}

.secondary-button:hover {
    background: var(--primary-blue);
    color: white;
    transform: translateY(-1px);
}

.danger-button {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.danger-button:hover {
    background: #dc2626;
    color: white;
    transform: translateY(-1px);
}

.cancel-order-form {
    margin: 0;
}

/* Modal */
.order-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

.order-modal[style*="display: flex"] {
    display: flex !important;
}

.modal-content {
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--medium-gray);
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    color: var(--text-secondary);
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: var(--light-gray);
    color: var(--text-primary);
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.order-info-grid {
    display: grid;
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--light-gray);
    border-radius: 8px;
}

.info-label {
    font-weight: 500;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.info-value {
    font-weight: 600;
    color: var(--text-primary);
}

.info-value.amount {
    color: var(--primary-blue);
    font-size: 1.1rem;
}

/* Delivery Information Section Styling */
.delivery-section {
    margin-top: 1.5rem;
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid var(--medium-gray);
}

.delivery-section.delivery-mode {
    background: rgba(5, 150, 105, 0.05);
    border-color: rgba(5, 150, 105, 0.2);
}

.delivery-section.meetup-mode {
    background: rgba(37, 99, 235, 0.05);
    border-color: rgba(37, 99, 235, 0.2);
}

.delivery-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.delivery-section-title::before {
    content: '';
    width: 4px;
    height: 16px;
    border-radius: 2px;
}

.delivery-section.delivery-mode .delivery-section-title::before {
    background: #059669;
}

.delivery-section.meetup-mode .delivery-section-title::before {
    background: var(--primary-blue);
}

.delivery-details-grid {
    display: grid;
    gap: 0.75rem;
}

.delivery-field {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.field-label {
    font-weight: 500;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.field-value {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.delivery-section.delivery-mode .field-value {
    color: #047857;
}

.delivery-section.meetup-mode .field-value {
    color: #1d4ed8;
}

/* Legacy delivery info styling (keeping for compatibility) */
.info-item.delivery-info {
    background: rgba(5, 150, 105, 0.05);
    border: 1px solid rgba(5, 150, 105, 0.1);
}

.info-item.delivery-info .info-label {
    color: #059669;
    font-weight: 600;
}

.info-item.delivery-info .info-value {
    color: #047857;
    font-weight: 500;
}

.info-item.meetup-info {
    background: rgba(37, 99, 235, 0.05);
    border: 1px solid rgba(37, 99, 235, 0.1);
}

.info-item.meetup-info .info-label {
    color: var(--primary-blue);
    font-weight: 600;
}

.info-item.meetup-info .info-value {
    color: #1d4ed8;
    font-weight: 500;
}

.order-items-section h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1rem 0;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem;
    border: 1px solid var(--medium-gray);
    border-radius: 8px;
    margin-bottom: 1rem;
}

.item-details {
    flex: 1;
}

.item-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.item-description {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0 0 0.75rem 0;
    line-height: 1.4;
}

.item-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.item-price {
    font-weight: 600;
    color: var(--text-primary);
}

.item-quantity {
    color: var(--text-secondary);
    background: var(--light-gray);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
}

.item-total {
    text-align: right;
    flex-shrink: 0;
    margin-left: 1rem;
}

.total-label {
    display: block;
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}

.total-amount {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
}

.no-items {
    color: var(--text-secondary);
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .orders-main {
        padding: 1rem;
    }
    
    .orders-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .order-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .order-actions {
        justify-content: center;
    }
    
    .modal-content {
        width: 95vw;
        margin: 1rem;
    }
    
    .order-item {
        flex-direction: column;
        gap: 1rem;
    }
    
    .item-total {
        text-align: left;
        margin-left: 0;
    }
}

/* Alert Error */
.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Review Section Styles */
.item-review-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--medium-gray);
}

.review-status {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border-radius: 8px;
    background: var(--light-gray);
}

.review-status.reviewed {
    background: rgba(5, 150, 105, 0.1);
    border: 1px solid rgba(5, 150, 105, 0.2);
}

.review-status.not-reviewed {
    background: rgba(37, 99, 235, 0.1);
    border: 1px solid rgba(37, 99, 235, 0.2);
}

.review-status.cannot-review {
    background: rgba(156, 163, 175, 0.1);
    border: 1px solid rgba(156, 163, 175, 0.2);
}

.review-badge {
    padding: 0.25rem 0.75rem;
    background: rgba(5, 150, 105, 0.2);
    color: #059669;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.review-summary {
    flex: 1;
}

.review-rating {
    color: #f59e0b;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.review-title {
    font-size: 0.9rem;
    color: var(--text-primary);
    margin: 0;
    font-weight: 500;
}

.review-actions {
    display: flex;
    gap: 0.5rem;
}

.review-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--primary-blue);
    color: white;
}

.review-btn:hover {
    background: var(--dark-blue);
    transform: translateY(-1px);
}

.review-disabled {
    color: var(--text-secondary);
    font-size: 0.85rem;
    font-weight: 500;
}

.review-disabled small {
    display: block;
    color: var(--text-secondary);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* Review Modal Styles */
.review-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

.review-modal[style*="display: flex"] {
    display: flex !important;
}

.review-modal-content {
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.review-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--medium-gray);
}

.review-modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.review-modal-close {
    background: none;
    border: none;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    color: var(--text-secondary);
    transition: all 0.2s ease;
}

.review-modal-close:hover {
    background: var(--light-gray);
    color: var(--text-primary);
}

.review-form-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.product-info {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: 8px;
}

.product-info h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--medium-gray);
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.rating-input {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stars {
    display: flex;
    gap: 0.25rem;
}

.star {
    font-size: 1.5rem;
    color: #d1d5db;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}

.star:hover {
    color: #f59e0b;
    transform: scale(1.1);
}

.star.active {
    color: #f59e0b;
}

.review-form-actions {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--light-gray);
    border-top: 1px solid var(--medium-gray);
    justify-content: flex-end;
}

.review-form-actions .secondary-button {
    background: rgba(156, 163, 175, 0.1);
    color: var(--text-secondary);
    border: 1px solid rgba(156, 163, 175, 0.2);
}

.review-form-actions .secondary-button:hover {
    background: var(--text-secondary);
    color: white;
}

.review-form-actions .primary-button {
    background: var(--primary-blue);
    color: white;
    border: none;
}

.review-form-actions .primary-button:hover {
    background: var(--dark-blue);
    transform: translateY(-1px);
}

/* Responsive Design for Review Modal */
@media (max-width: 768px) {
    .review-modal-content {
        width: 95vw;
        margin: 1rem;
        max-height: 95vh;
    }
    
    .review-form-actions {
        flex-direction: column;
    }
    
    .review-form-actions button {
        width: 100%;
    }
    
    .review-status {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .review-actions {
        width: 100%;
    }
    
    .review-btn {
        width: 100%;
        text-align: center;
    }
}
</style> 