<?php
require_once __DIR__ . '/src/helpers/functions.php';
require_once __DIR__ . '/src/controllers/CartController.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}
// Redirect admin users to the admin dashboard
if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

$cartController = new CartController();
$cartItems = $cartController->getCart();

// Handle remove/update actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove']) && isset($_POST['product_id'])) {
        $cartController->removeFromCart($_POST['product_id']);
        setFlashMessage('Item removed from cart.', 'success');
        header('Location: /cart.php');
        exit();
    }
    if (isset($_POST['update']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $qty = max(1, (int)$_POST['quantity']);
        $cartController->updateCartItem($_POST['product_id'], $qty);
        setFlashMessage('Cart updated.', 'success');
        header('Location: /cart.php');
        exit();
    }
    if (isset($_POST['checkout'])) {
        // Get delivery information from form
        $deliveryMode = $_POST['pickup_mode'] ?? 'delivery';
        $deliveryAddress = '';
        $deliveryNotes = '';
        $meetupTime = null;
        $meetupPlace = '';
        
        if ($deliveryMode === 'delivery') {
            $dormBuilding = $_POST['dorm_building'] ?? '';
            $roomNumber = $_POST['room_number'] ?? '';
            $deliveryTime = $_POST['delivery_time'] ?? '';
            
            // Build comprehensive delivery address
            $deliveryAddress = trim("$dormBuilding $roomNumber");
            $deliveryNotes = trim($deliveryTime);
            
            // Ensure we have some delivery info even if minimal
            if (empty($deliveryAddress)) {
                $deliveryAddress = 'To be specified';
            }
            if (empty($deliveryNotes)) {
                $deliveryNotes = 'Any time';
            }
        } elseif ($deliveryMode === 'meetup') {
            $meetupTime = $_POST['meetup_time'] ?? '';
            $meetupPlace = $_POST['meetup_place'] ?? '';
            
            // Ensure we have some meetup info even if minimal
            if (empty($meetupPlace)) {
                $meetupPlace = 'To be specified';
            }
        }
        
        // For demo, use 'cash' as payment method
        $result = $cartController->checkout($_SESSION['user_id'], 'cash', [
            'delivery_mode' => $deliveryMode,
            'delivery_address' => $deliveryAddress,
            'delivery_notes' => $deliveryNotes,
            'meetup_time' => $meetupTime,
            'meetup_place' => $meetupPlace
        ]);
        if ($result['success']) {
            setFlashMessage('Order placed successfully! Order ID: ' . $result['order_id'], 'success');
            header('Location: /orders.php');
            exit();
        } else {
            setFlashMessage($result['error'] ?? 'Checkout failed.', 'error');
        }
    }
    // Refresh cart items after any action
    $cartItems = $cartController->getCart();
}

function getProductImageUrl($imagePath) {
    if (!$imagePath) {
        return '/images/products/default.jpg';
    }
    if (strpos($imagePath, 'data:image/') === 0) {
        return $imagePath;
    }
    if (strpos($imagePath, 'products/') === 0) {
        return '/images/' . $imagePath;
    }
    return '/images/products/' . $imagePath;
}
?>

<?php require_once __DIR__ . '/src/views/partials/header.php'; ?>

<link rel="stylesheet" href="/css/cart.css">
<script src="/js/cart.js" defer></script>

<main class="cart-main">
    <div class="cart-container">
        <!-- Cart Header -->
        <div class="cart-header">
            <div class="cart-title-section">
                <h1 class="cart-title">🛒 Your Cart</h1>
                <p class="cart-subtitle">Review your items and proceed to checkout</p>
            </div>
            <div class="cart-actions">
                <a href="/dashboard.php" class="continue-shopping-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Continue Shopping
                </a>
            </div>
        </div>

        <?php displayFlashMessage(); ?>
        
        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <div class="empty-cart-content">
                    <div class="empty-cart-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <path d="M9 2L3 9v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9l-6-7z"/>
                            <line x1="3" y1="9" x2="21" y2="9"/>
                            <path d="M9 22V12h6v10"/>
                        </svg>
                    </div>
                    <h2 class="empty-cart-title">Your cart is empty</h2>
                    <p class="empty-cart-description">Looks like you haven't added any items yet</p>
                    <a href="/dashboard.php" class="modern-button primary large">Browse Products</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <!-- Cart Items Section -->
                <div class="cart-items-section">
                    <div class="section-header">
                        <h2 class="section-title">Cart Items (<?= count($cartItems) ?>)</h2>
                        <button type="button" class="clear-cart-btn" onclick="clearAllItems()">Clear All</button>
                    </div>
                    
                    <div class="cart-items-grid">
                        <?php $total = 0; ?>
                        <?php foreach ($cartItems as $productId => $item): ?>
                            <?php $subtotal = $item['product']['price'] * $item['quantity']; $total += $subtotal; ?>
                            <div class="cart-item-card">
                                <div class="item-image">
                                    <img src="<?= htmlspecialchars(getProductImageUrl($item['product']['image_path'] ?? 'default.jpg')) ?>" 
                                         alt="<?= htmlspecialchars($item['product']['name']) ?>" 
                                         class="product-img">
                                </div>
                                <div class="item-details">
                                    <h3 class="item-name"><?= htmlspecialchars($item['product']['name']) ?></h3>
                                    <p class="item-price"><?= formatPrice($item['product']['price']) ?></p>
                                    <?php if ($item['product']['stock_quantity'] < 10): ?>
                                        <p class="stock-warning low-stock">Only <?= $item['product']['stock_quantity'] ?> left in stock</p>
                                    <?php endif; ?>
                                </div>
                                <div class="item-quantity">
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-btn decrease" data-product-id="<?= $productId ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="5" y1="12" x2="19" y2="12"/>
                                            </svg>
                                        </button>
                                        <input type="number" class="quantity-input" value="<?= $item['quantity'] ?>" 
                                               min="1" max="<?= $item['product']['stock_quantity'] ?>" 
                                               data-product-id="<?= $productId ?>" readonly>
                                        <button type="button" class="quantity-btn increase" data-product-id="<?= $productId ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="5" x2="12" y2="19"/>
                                                <line x1="5" y1="12" x2="19" y2="12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="item-subtotal">
                                    <div class="subtotal-amount"><?= formatPrice($subtotal) ?></div>
                                    <button type="button" class="remove-item-btn" onclick="removeItem(<?= $productId ?>)">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary Section -->
                <div class="order-summary-section">
                    <div class="summary-card">
                        <h2 class="summary-title">Order Summary</h2>
                        
                        <div class="summary-item">
                            <span class="summary-label">Subtotal (<?= count($cartItems) ?> items)</span>
                            <span class="summary-value"><?= formatPrice($total) ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span class="summary-label">Delivery Fee</span>
                            <span class="summary-value">Free</span>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-item total">
                            <span class="summary-label">Total</span>
                            <span class="summary-value total-amount"><?= formatPrice($total) ?></span>
                        </div>

                        <form method="post" class="checkout-form">
                            <div class="pickup-options">
                                <h3 class="pickup-title">Delivery Options</h3>
                                <div class="pickup-methods">
                                    <label class="pickup-option">
                                        <input type="radio" name="pickup_mode" value="delivery" checked class="pickup-radio">
                                        <div class="pickup-content">
                                            <div class="pickup-icon">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="1" y="3" width="15" height="13"/>
                                                    <polygon points="16,8 20,8 23,11 23,16 16,16"/>
                                                    <circle cx="5.5" cy="18.5" r="2.5"/>
                                                    <circle cx="18.5" cy="18.5" r="2.5"/>
                                                </svg>
                                            </div>
                                            <div class="pickup-details">
                                                <div class="pickup-name">Campus Delivery</div>
                                                <div class="pickup-description">Deliver to your dorm or campus location</div>
                                            </div>
                                        </div>
                                    </label>
                                    
                                    <label class="pickup-option">
                                        <input type="radio" name="pickup_mode" value="meetup" class="pickup-radio">
                                        <div class="pickup-content">
                                            <div class="pickup-icon">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                    <circle cx="12" cy="10" r="3"/>
                                                </svg>
                                            </div>
                                            <div class="pickup-details">
                                                <div class="pickup-name">Campus Meet-up</div>
                                                <div class="pickup-description">Meet at a convenient campus location</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div id="meetup-options" class="meetup-details" style="display: none;">
                                    <div class="form-group">
                                        <label for="meetup_time" class="form-label">Preferred Meetup Time *</label>
                                        <input type="datetime-local" id="meetup_time" name="meetup_time" class="modern-input">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="meetup_place" class="form-label">Meetup Location *</label>
                                        <select id="meetup_place" name="meetup_place" class="modern-input" style="max-width: 250px;">
                                            <option value="">Select a location</option>
                                            <option value="Library">Library</option>
                                            <option value="Student Center">Student Center</option>
                                            <option value="Cafeteria">Cafeteria</option>
                                            <option value="Main Entrance">Main Entrance</option>
                                            <option value="Basketball Court">Basketball Court</option>
                                            <option value="Science Building">Science Building</option>
                                            <option value="Arts Building">Arts Building</option>
                                            <option value="Other">Other (specify in notes)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Delivery Information (shown when delivery is selected) -->
                                <div id="delivery-options" class="delivery-details">
                                    <div class="form-group">
                                        <label for="dorm_building" class="form-label">School Building *</label>
                                        <select id="dorm_building" name="dorm_building" class="modern-input" required>
                                            <option value="">Select your building</option>
                                            <option value="Main">Main</option>
                                            <option value="Tech">Tech</option>
                                            <option value="Enb">Enb</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="room_number" class="form-label">Room Number *</label>
                                        <input type="text" id="room_number" name="room_number" placeholder="e.g., 203, A-105, Suite 4" class="modern-input" style="max-width: 150px;" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="delivery_time" class="form-label">Preferred Delivery Time</label>
                                        <select id="delivery_time" name="delivery_time" class="modern-input">
                                            <option value="">Any time</option>
                                            <option value="morning">Morning (8 AM - 12 PM)</option>
                                            <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                                            <option value="evening">Evening (5 PM - 9 PM)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="checkout" class="modern-button primary full-width large">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                                Proceed to Checkout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
/* Cart Layout */
.cart-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 200px);
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.cart-container {
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Cart Header */
.cart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
}

.cart-title-section h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.cart-subtitle {
    color: var(--text-secondary);
    font-size: 1rem;
    margin: 0;
}

.continue-shopping-btn {
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
    transition: all 0.2s ease;
}

.continue-shopping-btn:hover {
    background: var(--primary-blue);
    color: white;
    transform: translateY(-1px);
}

/* Empty Cart */
.empty-cart {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.empty-cart-content {
    text-align: center;
    max-width: 400px;
}

.empty-cart-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 2rem;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(96, 165, 250, 0.1));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-blue);
}

.empty-cart-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.empty-cart-description {
    color: var(--text-secondary);
    margin: 0 0 2rem 0;
}

/* Cart Content */
.cart-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
}

/* Cart Items Section */
.cart-items-section {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    overflow: hidden;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--medium-gray);
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.clear-cart-btn {
    background: none;
    border: none;
    color: #ef4444;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.clear-cart-btn:hover {
    color: #dc2626;
    text-decoration: underline;
}

/* Cart Items Grid */
.cart-items-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
}

.cart-item-card {
    display: grid;
    grid-template-columns: 100px 1fr auto auto;
    gap: 1.5rem;
    padding: 1.5rem;
    border: 1px solid var(--medium-gray);
    border-radius: 12px;
    transition: all 0.2s ease;
    align-items: center;
}

.cart-item-card:hover {
    background: rgba(37, 99, 235, 0.02);
    border-color: var(--primary-blue);
}

.item-image {
    position: relative;
}

.product-img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    transition: transform 0.3s ease;
}

.cart-item-card:hover .product-img {
    transform: scale(1.05);
}

.item-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.item-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    line-height: 1.4;
}

.item-price {
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary-blue);
    margin: 0;
}

.stock-warning {
    font-size: 0.85rem;
    margin: 0;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    display: inline-block;
}

.stock-warning.low-stock {
    background: rgba(251, 191, 36, 0.1);
    color: #d97706;
}

/* Quantity Controls */
.item-quantity {
    display: flex;
    align-items: center;
}

.quantity-controls {
    display: flex;
    align-items: center;
    border: 1px solid var(--medium-gray);
    border-radius: 8px;
    overflow: hidden;
}

.quantity-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: var(--white);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    color: var(--text-secondary);
}

.quantity-btn:hover {
    background: var(--primary-blue);
    color: white;
}

.quantity-input {
    width: 50px;
    height: 36px;
    border: none;
    text-align: center;
    font-weight: 600;
    background: var(--white);
    color: var(--text-primary);
}

/* Item Subtotal */
.item-subtotal {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.subtotal-amount {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
}

.remove-item-btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: none;
    border: none;
    color: #ef4444;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.remove-item-btn:hover {
    color: #dc2626;
    transform: translateY(-1px);
}

/* Order Summary */
.order-summary-section {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.summary-card {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    padding: 2rem;
}

.summary-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1.5rem 0;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
}

.summary-label {
    color: var(--text-secondary);
    font-weight: 500;
}

.summary-value {
    font-weight: 600;
    color: var(--text-primary);
}

.summary-divider {
    height: 1px;
    background: var(--medium-gray);
    margin: 1rem 0;
}

.summary-item.total .summary-label {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.total-amount {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--primary-blue);
}

/* Pickup Options */
.pickup-options {
    margin: 2rem 0;
}

.pickup-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1rem 0;
}

.pickup-methods {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.pickup-option {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    transition: all 0.2s ease;
}

.pickup-option:hover .pickup-content {
    background: rgba(37, 99, 235, 0.05);
    border-color: var(--primary-blue);
}

.pickup-radio {
    margin-top: 1rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.pickup-content {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--medium-gray);
    border-radius: 12px;
    flex: 1;
    transition: all 0.2s ease;
    min-height: auto;
}

.pickup-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
    margin-top: 0;
}

.pickup-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
}

.pickup-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    line-height: 1.2;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.pickup-description {
    font-size: 0.85rem;
    color: var(--text-secondary);
    line-height: 1.3;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.meetup-details {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(37, 99, 235, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(37, 99, 235, 0.2);
}

.delivery-details {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(5, 150, 105, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(5, 150, 105, 0.2);
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.modern-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--medium-gray);
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    transition: border-color 0.2s ease;
}

.modern-input:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Buttons */
.modern-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    text-decoration: none;
    justify-content: center;
}

.modern-button.primary {
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    color: white;
}

.modern-button.primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.modern-button.full-width {
    width: 100%;
}

.modern-button.large {
    padding: 1rem 2rem;
    font-size: 1rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .cart-content {
        grid-template-columns: 1fr;
    }
    
    .order-summary-section {
        position: static;
        order: -1;
    }
}

@media (max-width: 768px) {
    .cart-main {
        padding: 1rem;
    }
    
    .cart-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .cart-item-card {
        grid-template-columns: 80px 1fr;
        gap: 1rem;
    }
    
    .item-quantity,
    .item-subtotal {
        grid-column: 2;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}

@media (min-width: 1400px) {
    .cart-content {
        grid-template-columns: 1fr 600px;
    }
}
</style>


<?php require_once __DIR__ . '/src/views/partials/footer.php'; ?>