<?php
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/controllers/SellerController.php';

// Strict seller check
if (!isSeller()) {
    redirect('/dashboard.php');
}

$sellerController = new SellerController();
$sellerId = $_SESSION['user_id'];

// Get order ID from URL
$orderId = $_GET['id'] ?? null;

if (!$orderId || !is_numeric($orderId)) {
    setFlashMessage('Invalid order ID', 'error');
    redirect('/seller/orders.php');
}

// Get order details
$orderDetails = $sellerController->getOrderDetails($orderId, $sellerId);

if (!$orderDetails) {
    setFlashMessage('Order not found or you do not have permission to view this order', 'error');
    redirect('/seller/orders.php');
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_status' && isset($_POST['status'])) {
        $newStatus = $_POST['status'];
        
        // Validate status
        $validStatuses = ['pending', 'shipped', 'completed', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            setFlashMessage('Invalid status', 'error');
        } else {
            $result = $sellerController->updateOrderStatus($orderId, $newStatus, $sellerId);
            
            if ($result) {
                setFlashMessage('Order status updated successfully!', 'success');
                // Refresh order details
                $orderDetails = $sellerController->getOrderDetails($orderId, $sellerId);
            } else {
                setFlashMessage('Failed to update order status', 'error');
            }
        }
    }
    
    // Prevent form resubmission
    redirect("/seller/view-order.php?id=$orderId");
}

?>

<?php require_once __DIR__ . '/../src/views/partials/header.php'; ?>

<div class="admin-container">
    <div class="admin-header-flex modern-panel">
        <h1 class="modern-title">📋 Order Details</h1>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="/seller/orders.php" class="modern-button header-small secondary">← Back to Orders</a>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Order Information -->
    <div class="modern-panel">
        <div class="order-header">
            <h2 class="order-title">Order #<?= $orderDetails['order']['id'] ?></h2>
            <span class="status-badge status-<?= $orderDetails['order']['status'] ?>">
                <?= ucfirst($orderDetails['order']['status']) ?>
            </span>
        </div>

        <div class="order-info-grid">
            <div class="info-group">
                <h3>Customer Information</h3>
                <div class="info-item">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?= htmlspecialchars($orderDetails['order']['username']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($orderDetails['order']['email']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?= htmlspecialchars($orderDetails['order']['phone'] ?? 'Not provided') ?></span>
                </div>
            </div>

            <div class="info-group">
                <h3>Order Information</h3>
                <div class="info-item">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value"><?= date('F j, Y, g:i A', strtotime($orderDetails['order']['created_at'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value amount">Php <?= number_format($orderDetails['order']['total_amount'], 2) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value"><?= ucfirst($orderDetails['order']['payment_method'] ?? 'Cash on Delivery') ?></span>
                </div>
            </div>

            <div class="info-group">
                <h3>Delivery Information</h3>
                <div class="info-item">
                    <span class="info-label">Delivery Mode:</span>
                    <span class="info-value"><?= ucfirst(isset($orderDetails['order']['delivery_mode']) ? $orderDetails['order']['delivery_mode'] : 'Standard Delivery') ?></span>
                </div>
                <?php if (isset($orderDetails['order']['delivery_mode']) && $orderDetails['order']['delivery_mode'] === 'delivery'): ?>
                    <div class="info-item">
                        <span class="info-label">Delivery Address:</span>
                        <span class="info-value"><?= htmlspecialchars($orderDetails['order']['delivery_address'] ?? 'Not provided') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Delivery Notes:</span>
                        <span class="info-value"><?= htmlspecialchars($orderDetails['order']['delivery_notes'] ?? 'No special instructions') ?></span>
                    </div>
                <?php elseif (isset($orderDetails['order']['delivery_mode']) && $orderDetails['order']['delivery_mode'] === 'meetup'): ?>
                    <div class="info-item">
                        <span class="info-label">Meetup Time:</span>
                        <span class="info-value"><?= $orderDetails['order']['meetup_time'] ? date('M j, Y, g:i A', strtotime($orderDetails['order']['meetup_time'])) : 'Not specified' ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Meetup Place:</span>
                        <span class="info-value"><?= htmlspecialchars($orderDetails['order']['meetup_place'] ?? 'Not specified') ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="modern-panel">
        <h2 class="section-title">Order Items</h2>
        
        <div class="order-items-container">
            <?php foreach ($orderDetails['items'] as $item): ?>
                <div class="order-item">
                    <div class="item-image">
                        <?php if ($item['image_path']): ?>
                            <img src="/images/products/<?= htmlspecialchars($item['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($item['product_name']) ?>">
                        <?php else: ?>
                            <div class="no-image">📦</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-details">
                        <h4 class="item-name"><?= htmlspecialchars($item['product_name']) ?></h4>
                        <p class="item-description"><?= htmlspecialchars(substr($item['description'] ?? '', 0, 100)) ?><?= strlen($item['description'] ?? '') > 100 ? '...' : '' ?></p>
                        <div class="item-meta">
                            <span class="item-price">Php <?= number_format($item['unit_price'], 2) ?></span>
                            <span class="item-quantity">× <?= $item['quantity'] ?></span>
                        </div>
                    </div>
                    
                    <div class="item-total">
                        <span class="total-label">Subtotal:</span>
                        <span class="total-amount">Php <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="order-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>Php <?= number_format($orderDetails['order']['total_amount'], 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery Fee:</span>
                <span>Php 0.00</span>
            </div>
            <div class="summary-row total">
                <span>Total:</span>
                <span>Php <?= number_format($orderDetails['order']['total_amount'], 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Order Actions -->
    <div class="modern-panel">
        <h2 class="section-title">Order Actions</h2>
        
        <div class="actions-container">
            <?php if ($orderDetails['order']['status'] === 'pending'): ?>
                <form method="POST" class="action-form">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="status" value="shipped">
                    <button type="submit" class="modern-button primary" 
                            onclick="return confirm('Mark this order as shipped?')">
                        🚚 Mark as Shipped
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($orderDetails['order']['status'] === 'shipped'): ?>
                <form method="POST" class="action-form">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="modern-button success" 
                            onclick="return confirm('Mark this order as completed?')">
                        ✅ Mark as Completed
                    </button>
                </form>
            <?php endif; ?>

            <?php if (in_array($orderDetails['order']['status'], ['pending', 'shipped'])): ?>
                <form method="POST" class="action-form">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="modern-button danger" 
                            onclick="return confirm('Are you sure you want to cancel this order?')">
                        ❌ Cancel Order
                    </button>
                </form>
            <?php endif; ?>

            <button type="button" class="modern-button secondary" onclick="window.print()">
                🖨️ Print Order
            </button>
        </div>
    </div>
</div>

<style>
/* Order Header */
.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--medium-gray);
}

.order-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

/* Order Info Grid */
.order-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 32px;
    margin-bottom: 32px;
}

.info-group h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 16px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--medium-gray);
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(226, 232, 240, 0.5);
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.info-value {
    font-weight: 600;
    color: var(--text-primary);
    text-align: right;
    flex: 1;
    margin-left: 16px;
}

.info-value.amount {
    color: #059669;
    font-size: 1.1rem;
}

/* Order Items */
.order-items-container {
    margin-bottom: 32px;
}

.order-item {
    display: flex;
    gap: 16px;
    padding: 20px;
    border: 1px solid var(--medium-gray);
    border-radius: 12px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
}

.order-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: var(--primary-blue);
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--light-gray);
    display: flex;
    align-items: center;
    justify-content: center;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    font-size: 2rem;
    color: var(--text-secondary);
}

.item-details {
    flex: 1;
}

.item-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 8px 0;
}

.item-description {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0 0 12px 0;
    line-height: 1.4;
}

.item-meta {
    display: flex;
    gap: 16px;
    align-items: center;
}

.item-price {
    font-weight: 600;
    color: var(--text-primary);
}

.item-quantity {
    color: var(--text-secondary);
    background: var(--light-gray);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
}

.item-total {
    text-align: right;
    flex-shrink: 0;
    min-width: 120px;
}

.total-label {
    display: block;
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.total-amount {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
}

/* Order Summary */
.order-summary {
    border-top: 2px solid var(--medium-gray);
    padding-top: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 0.95rem;
}

.summary-row.total {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-primary);
    border-top: 1px solid var(--medium-gray);
    padding-top: 16px;
    margin-top: 8px;
}

/* Actions */
.actions-container {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.action-form {
    margin: 0;
}

.section-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 24px 0;
}

/* Status Badges (reusing from orders page) */
.status-badge {
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: rgba(251, 191, 36, 0.1);
    color: #d97706;
}

.status-shipped {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary-blue);
}

.status-completed {
    background: rgba(5, 150, 105, 0.1);
    color: #059669;
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

/* Responsive Design */
@media (max-width: 768px) {
    .order-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
        text-align: center;
    }
    
    .order-info-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .item-meta {
        justify-content: center;
    }
    
    .item-total {
        text-align: center;
        min-width: auto;
    }
    
    .actions-container {
        flex-direction: column;
    }
    
    .action-form {
        width: 100%;
    }
    
    .modern-button {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .order-item {
        padding: 16px;
    }
    
    .item-image {
        width: 60px;
        height: 60px;
        margin: 0 auto;
    }
}
</style>

<?php require_once __DIR__ . '/../src/views/partials/footer.php'; ?>
