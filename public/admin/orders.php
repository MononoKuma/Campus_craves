<?php
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';
require_once __DIR__ . '/../src/models/Orders.php';

if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$adminController = new AdminController();
$orderModel = new Order();

// Show order details if 'view' param is set
$orderDetails = null;
$orderItems = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $orderDetails = $orderModel->getOrderById($_GET['view']);
    $orderItems = $orderModel->getOrderItems($_GET['view']);
}

// Status updates are not allowed for admin - they can only view status

// Get filtered orders
$statusFilter = $_GET['status'] ?? null;
$orders = $adminController->getAllOrders($statusFilter);
?>

<?php require_once __DIR__ . '/../../src/views/partials/header.php'; ?>

<div class="admin-container">
    <div class="admin-header brass-panel">
        <h1 class="gears-title">📦 Order Management</h1>
        
    </div>

    <?php if ($orderDetails): ?>
    <div class="order-details-container">
        <div class="order-details-header">
            <div class="order-info">
                <h2 class="order-title">Order #<?= $orderDetails['id'] ?></h2>
                <div class="order-meta">
                    <span class="order-date"><?= date('M j, Y', strtotime($orderDetails['created_at'])) ?></span>
                    <span class="status-badge status-<?= $orderDetails['status'] ?>"><?= ucfirst($orderDetails['status']) ?></span>
                </div>
            </div>
            <div class="order-summary">
                <div class="summary-item">
                    <span class="summary-label">Customer</span>
                    <span class="summary-value"><?= htmlspecialchars($orderDetails['username']) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Amount</span>
                    <span class="summary-value amount">Php <?= number_format($orderDetails['total_amount'], 2) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Delivery Method</span>
                    <span class="summary-value"><?= ucfirst(htmlspecialchars($orderDetails['delivery_mode'] ?? 'delivery')) ?></span>
                </div>
                <?php if ($orderDetails['delivery_mode'] === 'delivery'): ?>
                    <div class="delivery-section delivery-mode">
                        <h4 class="delivery-section-title">Campus Delivery</h4>
                        <div class="delivery-details-grid">
                            <div class="delivery-field">
                                <span class="field-label">School Building</span>
                                <span class="field-value"><?= 
                                    $orderDetails['delivery_address'] && $orderDetails['delivery_address'] !== 'To be specified' 
                                        ? (explode(' ', $orderDetails['delivery_address'], 2)[0] ?? 'Not specified') 
                                        : 'Not specified' 
                                ?></span>
                            </div>
                            <div class="delivery-field">
                                <span class="field-label">Room Number</span>
                                <span class="field-value"><?= 
                                    $orderDetails['delivery_address'] && $orderDetails['delivery_address'] !== 'To be specified' 
                                        ? (explode(' ', $orderDetails['delivery_address'], 2)[1] ?? 'Not specified') 
                                        : 'Not specified' 
                                ?></span>
                            </div>
                            <div class="delivery-field">
                                <span class="field-label">Preferred Delivery Time</span>
                                <span class="field-value"><?= htmlspecialchars($orderDetails['delivery_notes'] ?: 'Any time') ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($orderDetails['delivery_mode'] === 'meetup'): ?>
                    <div class="delivery-section meetup-mode">
                        <h4 class="delivery-section-title">Campus Meet-up</h4>
                        <div class="delivery-details-grid">
                            <div class="delivery-field">
                                <span class="field-label">Preferred Meetup Time</span>
                                <span class="field-value"><?= $orderDetails['meetup_time'] ? date('m/d/Y g:i A', strtotime($orderDetails['meetup_time'])) : 'Not specified' ?></span>
                            </div>
                            <div class="delivery-field">
                                <span class="field-label">Meetup Location</span>
                                <span class="field-value"><?= htmlspecialchars($orderDetails['meetup_place'] ?: 'Not specified') ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="order-items-section">
            <h3 class="section-title">Order Items</h3>
            <div class="items-grid">
                <?php foreach ($orderItems as $item): ?>
                    <div class="order-item-card">
                        <div class="item-info">
                            <h4 class="item-name"><?= htmlspecialchars($item['name']) ?></h4>
                            <div class="item-details">
                                <span class="item-quantity">Quantity: <?= $item['quantity'] ?></span>
                                <span class="item-price">Php <?= number_format($item['unit_price'], 2) ?></span>
                            </div>
                            <?php if ($item['seller_name']): ?>
                                <div class="seller-info">
                                    <span class="seller-label">Seller:</span>
                                    <a href="/seller.php?user=<?= $item['seller_id'] ?>" target="_blank" class="seller-link">
                                        <?= htmlspecialchars($item['seller_name']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="item-total">
                            <span class="total-label">Item Total</span>
                            <span class="total-value">Php <?= number_format($item['quantity'] * $item['unit_price'], 2) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="order-actions">
            <a href="/admin/orders.php" class="modern-btn secondary">Back to Orders</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Order Filters -->
    <div class="brass-panel">
        <h2>Order Filters</h2>
        <div class="filter-options">
            <a href="/admin/orders.php" class="piston-button small<?= !$statusFilter ? ' active' : '' ?>">All</a>
            <a href="/admin/orders.php?status=pending" class="piston-button small<?= $statusFilter === 'pending' ? ' active' : '' ?>">Pending</a>
            <a href="/admin/orders.php?status=completed" class="piston-button small<?= $statusFilter === 'completed' ? ' active' : '' ?>">Completed</a>
            <a href="/admin/orders.php?status=shipped" class="piston-button small<?= $statusFilter === 'shipped' ? ' active' : '' ?>">Shipped</a>
            <a href="/admin/orders.php?status=cancelled" class="piston-button small<?= $statusFilter === 'cancelled' ? ' active' : '' ?>">Cancelled</a>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="brass-panel">
        <h2>Order List</h2>
        <table class="steam-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Seller(s)</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <?php $orderItems = $orderModel->getOrderItems($order['id']); ?>
                <?php $sellerNames = array_filter(array_map(function($item) { return $item['seller_name']; }, $orderItems)); ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['username']) ?></td>
                    <td><?= htmlspecialchars(!empty($sellerNames) ? implode(', ', $sellerNames) : 'N/A') ?></td>
                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                    <td>Php <?= number_format($order['total_amount'], 2) ?></td>
                    <td>
                        <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                    </td>
                    <td>
                        <a href="/admin/orders.php?view=<?= $order['id'] ?>" class="piston-button small">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Structure -->
<div id="receipt-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div style="background:linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); color:#333; border-radius:8px; padding:2rem; min-width:320px; max-width:90vw; box-shadow:0 4px 32px #0008; position:relative; border: 1px solid var(--medium-gray);">
    <button id="close-receipt-modal" style="position:absolute; top:8px; right:12px; background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    <h2>Order Receipt</h2>
    <div id="receipt-content"></div>
  </div>
</div>

<script>
document.querySelectorAll('.view-receipt-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var order = JSON.parse(this.getAttribute('data-order'));
        var html = '';
        html += '<p><strong>Order ID:</strong> #' + order.id + '</p>';
        html += '<p><strong>Customer:</strong> ' + order.username + '</p>';
        html += '<p><strong>Date:</strong> ' + order.created_at + '</p>';
        html += '<p><strong>Amount:</strong> $' + order.total_amount + '</p>';
        html += '<p><strong>Status:</strong> ' + order.status + '</p>';
        html += '<h3>Items</h3>';
        if(order.items && order.items.length > 0) {
            html += '<table style="width:100%;border-collapse:collapse;margin-bottom:1em;">';
            html += '<thead><tr><th style="text-align:left;">Name</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead><tbody>';
            order.items.forEach(function(item) {
                html += '<tr>' +
                    '<td>' + item.name + '</td>' +
                    '<td style="text-align:center;">' + item.quantity + '</td>' +
                    '<td style="text-align:right;">$' + item.unit_price + '</td>' +
                    '<td style="text-align:right;">$' + item.subtotal + '</td>' +
                '</tr>';
            });
            html += '</tbody></table>';
        } else {
            html += '<p>No items found for this order.</p>';
        }
        document.getElementById('receipt-content').innerHTML = html;
        document.getElementById('receipt-modal').style.display = 'flex';
    });
});
document.getElementById('close-receipt-modal').onclick = function() {
    document.getElementById('receipt-modal').style.display = 'none';
};
document.getElementById('receipt-modal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/../../src/views/partials/footer.php'; ?>

<style>
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
    display: inline-block;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-completed {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-shipped {
    background-color: #cce5ff;
    color: #004085;
    border: 1px solid #99d6ff;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-cart {
    background-color: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

.piston-button.small {
    padding: 4px 8px;
    font-size: 0.8rem;
    background-color: #007bff;
    border-color: #007bff;
    color: #ffffff;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}

.piston-button.small:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

.filter-options {
    display: flex;
    gap: 10px;
    margin-bottom: 1rem;
}

.filter-options .piston-button.small.active {
    background-color: #28a745;
    border-color: #28a745;
}

/* Modern Order Details Styles */
.order-details-container {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    margin-bottom: 2rem;
    overflow: hidden;
}

.order-details-header {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    padding: 2rem;
    border-bottom: 1px solid var(--medium-gray);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.order-info {
    flex: 1;
}

.order-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 1rem 0;
}

.order-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.order-date {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

.order-summary {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 200px;
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.summary-label {
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.summary-value.amount {
    color: #059669;
    font-size: 1.25rem;
}

.order-items-section {
    padding: 2rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title::before {
    content: '';
    width: 4px;
    height: 24px;
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    border-radius: 2px;
}

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.order-item-card {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.02), rgba(96, 165, 250, 0.02));
    border: 1px solid rgba(37, 99, 235, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.2s ease;
}

.order-item-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.1);
    border-color: var(--primary-blue);
}

.item-info {
    margin-bottom: 1rem;
}

.item-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.75rem 0;
    line-height: 1.4;
}

.item-details {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.item-quantity,
.item-price {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.item-price {
    color: var(--primary-blue);
    font-weight: 600;
}

.seller-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    border-top: 1px solid var(--medium-gray);
}

.seller-label {
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.seller-link {
    font-size: 0.9rem;
    color: var(--primary-blue);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}

.seller-link:hover {
    color: var(--dark-blue);
    text-decoration: underline;
}

.item-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--medium-gray);
}

.total-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.total-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #059669;
}

/* Admin Delivery Information Styling */
.summary-item.delivery-info {
    background: rgba(5, 150, 105, 0.05);
    border: 1px solid rgba(5, 150, 105, 0.1);
    border-radius: 6px;
    padding: 0.75rem;
}

.summary-item.delivery-info .summary-label {
    color: #059669;
    font-weight: 600;
}

.summary-item.delivery-info .summary-value {
    color: #047857;
    font-weight: 500;
}

.summary-item.meetup-info {
    background: rgba(37, 99, 235, 0.05);
    border: 1px solid rgba(37, 99, 235, 0.1);
    border-radius: 6px;
    padding: 0.75rem;
}

.summary-item.meetup-info .summary-label {
    color: var(--primary-blue);
    font-weight: 600;
}

.summary-item.meetup-info .summary-value {
    color: #1d4ed8;
    font-weight: 500;
}

/* Delivery Information Section Styling */
.delivery-section {
    margin-top: 1rem;
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

.order-actions {
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-top: 1px solid var(--medium-gray);
    display: flex;
    justify-content: center;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.modern-btn.primary {
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    color: white;
}

.modern-btn.secondary {
    background: white;
    color: var(--primary-blue);
    border-color: var(--primary-blue);
}

.modern-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.modern-btn.secondary:hover {
    background: var(--primary-blue);
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .order-details-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .order-summary {
        min-width: auto;
        width: 100%;
    }
    
    .items-grid {
        grid-template-columns: 1fr;
    }
    
    .order-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>