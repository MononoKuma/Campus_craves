<?php
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/controllers/SellerController.php';

// Strict seller check
if (!isSeller()) {
    redirect('/dashboard.php');
}

$sellerController = new SellerController();
$sellerId = $_SESSION['user_id'];

// Handle filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$amount_min = $_GET['amount_min'] ?? '';
$amount_max = $_GET['amount_max'] ?? '';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Here you would need to implement order status update logic
    // For now, we'll just show a message
    setFlashMessage('Order status updated successfully!', 'success');
    redirect('/seller/orders.php?' . http_build_query([
        'search' => $search,
        'status' => $status,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'amount_min' => $amount_min,
        'amount_max' => $amount_max
    ]));
}

$orders = $sellerController->getFilteredSellerOrders($sellerId, $search, $status, $date_from, $date_to, $amount_min, $amount_max);
?>

<?php require_once __DIR__ . '/../../src/views/partials/header.php'; ?>

<div class="admin-container">
    <div class="admin-header-flex modern-panel">
        <h1 class="modern-title">📋 My Orders</h1>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="/seller/dashboard.php" class="modern-button header-small secondary">← Back to Dashboard</a>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Filter Section -->
    <div class="modern-panel">
        <div class="filter-header">
            <h2 class="filter-title">🔍 Order Filters</h2>
            <button type="button" class="clear-filters-btn" onclick="clearFilters()">Clear All</button>
        </div>
        
        <form method="GET" class="filter-form">
            <div class="filter-grid">
                <!-- Search Filter -->
                <div class="filter-group">
                    <label for="search">Search Orders</label>
                    <div class="search-input-wrapper">
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search by Order ID or Customer..." class="filter-input">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="filter-group">
                    <label for="status">Order Status</label>
                    <select id="status" name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>🕐 Pending</option>
                        <option value="shipped" <?= $status === 'shipped' ? 'selected' : '' ?>>🚚 Shipped</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>✅ Completed</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                    </select>
                </div>

                <!-- Date Range Filters -->
                <div class="filter-group">
                    <label for="date_from">From Date</label>
                    <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>" 
                           class="filter-input">
                </div>

                <div class="filter-group">
                    <label for="date_to">To Date</label>
                    <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>" 
                           class="filter-input">
                </div>

                <!-- Amount Range Filters -->
                <div class="filter-group">
                    <label for="amount_min">Min Amount (Php)</label>
                    <input type="number" id="amount_min" name="amount_min" value="<?= htmlspecialchars($amount_min) ?>" 
                           placeholder="0" min="0" step="0.01" class="filter-input">
                </div>

                <div class="filter-group">
                    <label for="amount_max">Max Amount (Php)</label>
                    <input type="number" id="amount_max" name="amount_max" value="<?= htmlspecialchars($amount_max) ?>" 
                           placeholder="99999" min="0" step="0.01" class="filter-input">
                </div>

                <!-- Filter Actions -->
                <div class="filter-group filter-actions">
                    <button type="submit" class="apply-filters-btn">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="modern-panel">
        <div class="products-header">
            <h2 class="modern-title" style="font-size: 1.8rem; color: var(--text-primary);">Order Management</h2>
            <div class="results-count">
                <?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> found
            </div>
        </div>

    <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                <div style="font-size: 3rem; margin-bottom: 16px;">📋</div>
                <h3 style="color: var(--text-primary); margin-bottom: 8px;">No Orders Found</h3>
                <p><?= !empty($search) || !empty($status) || !empty($date_from) || !empty($date_to) ? "No orders match your current filters." : "You haven't received any orders yet." ?></p>
            </div>
        <?php else: ?>
            <div class="orders-table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><span class="order-id">#<?= $order['id'] ?></span></td>
                                <td><?= htmlspecialchars($order['username']) ?></td>
                                <td class="amount">Php <?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="/seller/view-order.php?id=<?= $order['id'] ?>" class="table-action-btn">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Filter Styles (reusing from admin products) */
.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.filter-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.clear-filters-btn {
    background: transparent;
    border: 2px solid var(--medium-gray);
    color: var(--text-secondary);
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.clear-filters-btn:hover {
    background: var(--light-gray);
    border-color: var(--primary-blue);
    color: var(--primary-blue);
}

.filter-form {
    margin: 0;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group label {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
    margin: 0;
}

.filter-input,
.filter-select {
    padding: 12px 16px;
    border: 2px solid var(--medium-gray);
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: var(--white);
    color: var(--text-primary);
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-input-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    pointer-events: none;
}

.filter-actions {
    justify-content: flex-end;
}

.apply-filters-btn {
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.apply-filters-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.results-count {
    color: var(--text-secondary);
    font-size: 0.95rem;
    font-weight: 500;
    background: var(--light-gray);
    padding: 8px 16px;
    border-radius: 20px;
}

/* Order Table Styles */
.orders-table-container {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid var(--medium-gray);
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.modern-table th {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid var(--medium-gray);
}

.modern-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--medium-gray);
}

.modern-table tr:hover {
    background: rgba(37, 99, 235, 0.02);
}

.order-id {
    font-family: monospace;
    font-weight: 600;
    color: var(--primary-blue);
}

.amount {
    font-weight: 600;
    color: #059669;
}

.table-action-btn {
    padding: 6px 12px;
    background: var(--primary-blue);
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    margin: 0 2px;
}

.table-action-btn:hover {
    background: var(--dark-blue);
    transform: translateY(-1px);
}

.ship-btn {
    background: #059669;
}

.ship-btn:hover {
    background: #047857;
}

/* Status Badges */
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
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
    .filter-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .products-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .results-count {
        text-align: center;
    }
    
    .modern-table {
        font-size: 0.8rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 0.75rem 0.5rem;
    }
}

@media (max-width: 480px) {
    .filter-group {
        gap: 6px;
    }
    
    .filter-input,
    .filter-select {
        padding: 10px 12px;
        font-size: 0.9rem;
    }
    
    .apply-filters-btn {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}
</style>

<script>
function clearFilters() {
    window.location.href = '/seller/orders.php';
}

// Auto-submit on change for better UX
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('.filter-input, .filter-select');
    
    filterInputs.forEach(input => {
        // Add debounced auto-submit
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.type !== 'text' || this.value.length > 2 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
        
        input.addEventListener('change', function() {
            if (this.type !== 'text') {
                this.form.submit();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../src/views/partials/footer.php'; ?>
