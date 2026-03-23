<?php
require_once __DIR__ . '/src/helpers/functions.php';
require_once __DIR__ . '/src/controllers/ProductController.php';
require_once __DIR__ . '/src/controllers/CartController.php';
require_once __DIR__ . '/src/helpers/review_helpers.php';
require_once __DIR__ . '/src/models/Users.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}
// Redirect admin users to admin dashboard
if (isAdmin()) {
    redirect('/admin/dashboard.php');
}
// Redirect seller users to seller dashboard
if (isSeller()) {
    redirect('/seller/dashboard.php');
}

// Check user status for ban/suspension notices
$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);
$showBanNotice = false;
$showSuspensionNotice = false;
$suspensionEnds = null;
$banReason = '';

if ($currentUser['status'] === 'banned') {
    $showBanNotice = true;
    $banReason = $currentUser['ban_reason'];
} elseif ($currentUser['status'] === 'suspended') {
    // Check if suspension has expired
    if ($currentUser['suspension_ends'] && strtotime($currentUser['suspension_ends']) < time()) {
        // Auto-unsuspend if expired
        $userModel->unsuspendUser($_SESSION['user_id']);
    } else {
        $showSuspensionNotice = true;
        $suspensionEnds = $currentUser['suspension_ends'];
        $banReason = $currentUser['ban_reason'];
    }
}

$productController = new ProductController();
$cartController = new CartController();

// Get featured products
$featuredProducts = $productController->index();

// Get cart items
$cartItems = $cartController->getCart();

// Determine correct image path for each product
function getProductImageUrl($imagePath) {
    // Debug: Log what we're receiving
    error_log("Image path from DB: " . ($imagePath ?? 'NULL'));
    
    if (!$imagePath) {
        return '/images/products/default.jpg';
    }
    // If path already contains 'products/', just prepend '/images/'
    if (strpos($imagePath, 'products/') === 0) {
        $finalPath = '/images/' . $imagePath;
    } else {
        // Otherwise, assume it's just a filename
        $finalPath = '/images/products/' . $imagePath;
    }
    
    error_log("Final image URL: " . $finalPath);
    return $finalPath;
}

?>

<?php require_once __DIR__ . '/src/views/partials/header.php'; ?>

<!-- Ban Notice Modal -->
<?php if ($showBanNotice): ?>
<div id="ban-notice" class="ban-notice-overlay">
    <div class="ban-notice-modal">
        <div class="ban-notice-icon">🚫</div>
        <h2>Account Banned</h2>
        <p>Your account has been banned.</p>
        <?php if ($banReason): ?>
            <p><strong>Reason:</strong> <?= htmlspecialchars($banReason) ?></p>
        <?php endif; ?>
        <p>You will now be logged out.</p>
        <button onclick="handleBanLogout()" class="ban-notice-btn">Close</button>
    </div>
</div>
<?php endif; ?>

<!-- Suspension Notice Modal -->
<?php if ($showSuspensionNotice): ?>
<div id="suspension-notice" class="suspension-notice-overlay">
    <div class="suspension-notice-modal">
        <div class="suspension-notice-icon">⏰</div>
        <h2>Account Suspended</h2>
        <p>Your account is temporarily suspended.</p>
        <?php if ($banReason): ?>
            <p><strong>Reason:</strong> <?= htmlspecialchars($banReason) ?></p>
        <?php endif; ?>
        <p><strong>Suspension ends in:</strong></p>
        <div class="countdown-timer" id="suspension-countdown" data-end="<?= htmlspecialchars($suspensionEnds) ?>">
            Loading...
        </div>
        <button onclick="handleSuspensionClose()" class="suspension-notice-btn" id="suspension-close-btn" disabled>
            Close (when timer ends)
        </button>
    </div>
</div>
<?php endif; ?>

<main class="dashboard-main">
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1 class="dashboard-title">Welcome Back, <?= htmlspecialchars($_SESSION['username'] ?? 'Engineer') ?></h1>
            </div>
            <div class="header-actions">
                <!-- Shopping Cart Icon with Counter -->
                <div class="header-cart">
                    <a href="/cart.php" class="cart-link">
                        <div class="cart-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"/>
                                <circle cx="20" cy="21" r="1"/>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                            </svg>
                            <?php if (!empty($cartItems)): ?>
                                <span class="cart-counter"><?= count($cartItems) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
                
                <a href="/profile.php" class="header-action-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Profile
                </a>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Featured Products Card -->
            <div class="dashboard-card featured-products">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                    </div>
                    <h2 class="card-title">Featured Products</h2>
                    <div class="card-actions">
                        <a href="/products.php" class="view-all-link">View All</a>
                    </div>
                </div>
                <div class="card-content">
                    <div class="product-grid">
                        <?php foreach (array_slice($featuredProducts, 0, 6) as $product): ?>
                            <div class="product-item">
                                <div class="product-image-container">
                                    <img src="<?= getProductImageUrl($product['image_path'] ?? 'default.jpg') ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="product-image">
                                    <div class="product-overlay">
                                        <?php if ($product['stock_quantity'] > 0): ?>
                                            <button type="button" class="add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                                                Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <span class="out-of-stock-badge">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                    <?php displayProductRating($product, true, 'small'); ?>
                                    <div class="product-meta">
                                        <span class="product-price"><?= formatPrice($product['price']) ?></span>
                                        <span class="stock-status <?= $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                            <?= $product['stock_quantity'] > 0 ? $product['stock_quantity'] . ' in stock' : 'out of stock' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="dashboard-card quick-actions">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 9.54l4.24 4.24M20.46 14.46l4.24 4.24M1.54 14.46l4.24 4.24"/>
                        </svg>
                    </div>
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div class="card-content">
                    <div class="action-grid">
                        <a href="/products.php" class="action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="9" y1="9" x2="15" y2="9"/>
                                    <line x1="9" y1="15" x2="15" y2="15"/>
                                </svg>
                            </div>
                            <h3>Browse All Products</h3>
                            <p>Explore our full catalog</p>
                        </a>
                        
                        <a href="/cart.php" class="action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1"/>
                                    <circle cx="20" cy="21" r="1"/>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                </svg>
                            </div>
                            <h3>View Your Cart</h3>
                            <p><?= !empty($cartItems) ? count($cartItems) . ' items' : 'Your shopping cart' ?></p>
                            <?php if (!empty($cartItems)): ?>
                                <span class="action-badge"><?= count($cartItems) ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <a href="/orders.php" class="action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10 9 9 9 8 9"/>
                                </svg>
                            </div>
                            <h3>Your Orders</h3>
                            <p>Track your purchases</p>
                        </a>
                        
                        <a href="/profile.php" class="action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <h3>Account Settings</h3>
                            <p>Manage your profile</p>
                        </a>
                        
                        <a href="/my-complaints.php" class="action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10 9 9 9 8 9"/>
                                </svg>
                            </div>
                            <h3>My Complaints</h3>
                            <p>Track your reports</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Quantity Modal -->
<div id="quantity-modal" class="modal" style="display:none; position:fixed; z-index:2000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
    <div style="background:#fff; padding:2rem; border-radius:8px; min-width:300px; text-align:center; position:relative;">
        <h2>Select Quantity</h2>
        <input type="number" id="modal-quantity-input" min="1" value="1" style="width:80px; font-size:1.2rem; margin:1rem 0;">
        <div style="margin-top:1rem;">
            <button id="modal-add-btn" class="piston-button">Add</button>
            <button id="modal-cancel-btn" class="piston-button" style="background:#ccc; color:#333;">Cancel</button>
        </div>
    </div>
</div>

<style>
/* Modern Dashboard Layout */
.dashboard-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 200px);
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Dashboard Header */
.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
}

.welcome-section h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.dashboard-subtitle {
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
    background: var(--white);
    color: var(--primary-blue);
    border: 2px solid var(--primary-blue);
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
}

.header-cart {
    flex: 0 0 auto;
}

.cart-link {
    display: block;
    text-decoration: none;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.cart-link:hover {
    background: rgba(37, 99, 235, 0.1);
    transform: translateY(-1px);
}

.cart-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    color: var(--primary-blue);
    transition: all 0.2s ease;
}

.cart-link:hover .cart-icon {
    color: var(--dark-blue);
}

.cart-counter {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.4rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    animation: cartPulse 2s infinite;
}

@keyframes cartPulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    flex: 1;
}

/* Dashboard Cards */
.dashboard-card {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--medium-gray);
}

.card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1;
}

.card-actions {
    flex-shrink: 0;
}

.view-all-link {
    color: var(--primary-blue);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}

.card-content {
    padding: 1.5rem;
    flex: 1;
}

/* Featured Products Grid */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.product-item {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.product-image-container {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    background: var(--light-gray);
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.product-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    line-height: 1.4;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-price {
    font-weight: 600;
    color: var(--primary-blue);
    font-size: 1.1rem;
}

.stock-status {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-weight: 500;
}

.stock-status.in-stock {
    background: rgba(34, 197, 94, 0.1);
    color: #166534;
}

.stock-status.out-of-stock {
    background: rgba(239, 68, 68, 0.1);
    color: #991b1b;
}

/* Quick Actions Grid */
.action-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--medium-gray);
    border-radius: 12px;
    text-decoration: none;
    color: var(--text-primary);
    position: relative;
}

.action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.action-item h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
}

.action-item p {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 0;
}

.action-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: var(--primary-blue);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .product-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
}

@media (max-width: 768px) {
    .dashboard-main {
        padding: 1rem;
    }
    
    .dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .welcome-section h1 {
        font-size: 1.5rem;
    }
    
    .product-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
}

@media (min-width: 1400px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Ban/Suspension Notice Styles */
.ban-notice-overlay,
.suspension-notice-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ban-notice-modal,
.suspension-notice-modal {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.ban-notice-icon,
.suspension-notice-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.ban-notice-modal h2,
.suspension-notice-modal h2 {
    color: #dc2626;
    margin: 0 0 1rem 0;
    font-size: 1.5rem;
}

.suspension-notice-modal h2 {
    color: #d97706;
}

.ban-notice-modal p,
.suspension-notice-modal p {
    color: var(--text-secondary);
    margin: 0.5rem 0;
    line-height: 1.5;
}

.countdown-timer {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 2px solid #f59e0b;
    border-radius: 12px;
    padding: 1rem;
    margin: 1rem 0;
    font-size: 1.2rem;
    font-weight: 600;
    color: #92400e;
}

.ban-notice-btn,
.suspension-notice-btn {
    background: var(--primary-blue);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ban-notice-btn:hover,
.suspension-notice-btn:hover:not(:disabled) {
    background: var(--dark-blue);
    transform: translateY(-1px);
}

.suspension-notice-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

<?php require_once __DIR__ . '/src/views/partials/footer.php'; ?>

<script>
// Ban notice handling
function handleBanLogout() {
    // Logout and redirect to login page
    window.location.href = '/logout.php';
}

// Suspension countdown timer
function updateSuspensionCountdown() {
    const countdownElement = document.getElementById('suspension-countdown');
    const closeBtn = document.getElementById('suspension-close-btn');
    
    if (!countdownElement) return;
    
    const endTime = new Date(countdownElement.getAttribute('data-end')).getTime();
    
    function updateTimer() {
        const now = new Date().getTime();
        const distance = endTime - now;
        
        if (distance < 0) {
            countdownElement.innerHTML = "SUSPENSION ENDED";
            closeBtn.disabled = false;
            closeBtn.textContent = "Close";
            // Reload page to check status
            setTimeout(() => {
                window.location.reload();
            }, 2000);
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        let timeString = '';
        if (days > 0) timeString += days + 'd ';
        if (hours > 0) timeString += hours + 'h ';
        if (minutes > 0) timeString += minutes + 'm ';
        timeString += seconds + 's';
        
        countdownElement.innerHTML = timeString;
    }
    
    updateTimer();
    setInterval(updateTimer, 1000);
}

// Suspension notice handling
function handleSuspensionClose() {
    const closeBtn = document.getElementById('suspension-close-btn');
    if (closeBtn && !closeBtn.disabled) {
        document.getElementById('suspension-notice').style.display = 'none';
    }
}

// Initialize countdown if suspension notice is shown
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('suspension-countdown')) {
        updateSuspensionCountdown();
    }
});
</script>

<script src="/js/cart.js"></script>