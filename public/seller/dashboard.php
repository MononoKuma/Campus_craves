<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/helpers/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/SellerController.php';

// Strict seller check
if (!isSeller()) {
    redirect('/dashboard.php');
}

$sellerController = new SellerController();
$sellerId = $_SESSION['user_id'];
$metrics = $sellerController->getDashboardMetrics($sellerId);
$storeStatus = $sellerController->getStoreStatus($sellerId);
$profile = $sellerController->getSellerProfile($sellerId);
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/header.php'; ?>

<main class="seller-dashboard-main">
    <div class="seller-dashboard-container">
        <!-- Store Status Banner -->
        <div class="store-status-card">
            <div class="store-info">
                <div class="store-details">
                    <h2 class="store-name"><?= htmlspecialchars($profile['store_name'] ?? 'My Store') ?></h2>
                    <div class="store-status-indicator">
                        <span class="status-dot <?= $storeStatus === 'available' ? 'active' : 'inactive' ?>"></span>
                        <span class="status-text"><?= $storeStatus === 'available' ? 'Store Available' : 'Store Unavailable' ?></span>
                    </div>
                </div>
                <div class="store-description">
                    <p>Manage your store settings and view performance metrics</p>
                </div>
            </div>
            <div class="store-actions">
                <a href="/seller/store-profile.php" class="modern-button primary">Manage Store</a>
            </div>
        </div>

        <!-- Seller Dashboard Grid -->
        <div class="seller-dashboard-grid">
            <!-- Metrics Overview Card -->
            <div class="seller-dashboard-card metrics-overview">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </div>
                    <h2 class="card-title">Performance Metrics</h2>
                </div>
                <div class="card-content">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <div class="metric-content">
                                <div class="metric-value">Php <?= number_format($metrics['total_sales'], 2) ?></div>
                                <div class="metric-title">Total Sales</div>
                            </div>
                        </div>
                        
                        <div class="metric-card">
                            <div class="metric-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                                </svg>
                            </div>
                            <div class="metric-content">
                                <div class="metric-value"><?= $metrics['total_products'] ?></div>
                                <div class="metric-title">My Products</div>
                            </div>
                        </div>
                        
                        <div class="metric-card">
                            <div class="metric-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10 9 9 9 8 9"/>
                                </svg>
                            </div>
                            <div class="metric-content">
                                <div class="metric-value"><?= $metrics['pending_orders'] ?></div>
                                <div class="metric-title">Pending Orders</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="seller-dashboard-card seller-actions">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 9.54l4.24 4.24M20.46 14.46l4.24 4.24M1.54 14.46l4.24 4.24"/>
                        </svg>
                    </div>
                    <h2 class="card-title">Seller Actions</h2>
                </div>
                <div class="card-content">
                    <div class="seller-action-grid">
                        <a href="/seller/products.php" class="seller-action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h3>Manage Products</h3>
                                <p>Add, edit, and remove products</p>
                            </div>
                        </a>
                        
                        <a href="/seller/orders.php" class="seller-action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10 9 9 9 8 9"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h3>View Orders</h3>
                                <p>Manage customer orders</p>
                            </div>
                        </a>
                        
                        <a href="/seller/add-product.php" class="seller-action-item primary">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="16"/>
                                    <line x1="8" y1="12" x2="16" y2="12"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h3>Add New Product</h3>
                                <p>List a new item for sale</p>
                            </div>
                        </a>
                        
                        <a href="/seller/store-profile.php" class="seller-action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h3>Store Profile</h3>
                                <p>Update store information</p>
                            </div>
                        </a>
                        
                        <a href="/seller/reports.php" class="seller-action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="20" x2="18" y2="10"/>
                                    <line x1="12" y1="20" x2="12" y2="4"/>
                                    <line x1="6" y1="20" x2="6" y2="14"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h3>Sales Reports</h3>
                                <p>View detailed analytics</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Card -->
            <div class="seller-dashboard-card recent-orders">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <h2 class="card-title">Recent Orders</h2>
                    <div class="card-actions">
                        <a href="/seller/orders.php" class="view-all-link">View All</a>
                    </div>
                </div>
                <div class="card-content">
                    <div class="orders-table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($metrics['recent_orders'] as $order): ?>
                                <tr>
                                    <td><span class="order-id">#<?= $order['id'] ?></span></td>
                                    <td><?= htmlspecialchars($order['username']) ?></td>
                                    <td class="amount">Php <?= number_format($order['total_amount'], 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <a href="/seller/orders.php?view=<?= $order['id'] ?>" class="table-action-btn">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Seller Dashboard Layout */
.seller-dashboard-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 200px);
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.seller-dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Seller Header */
.seller-dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
}

.seller-dashboard-header .dashboard-title {
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
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.header-action-btn:not(.secondary) {
    background: var(--primary-blue);
    color: white;
}

.header-action-btn.secondary {
    background: var(--white);
    color: var(--primary-blue);
    border: 2px solid var(--primary-blue);
}

.header-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.header-action-btn.secondary:hover {
    background: var(--primary-blue);
    color: white;
}

/* Store Status Card */
.store-status-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.05), rgba(205, 133, 63, 0.05));
    border: 2px solid rgba(139, 69, 19, 0.2);
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.store-info {
    flex: 1;
}

.store-details {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.store-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #8B4513;
    margin: 0;
}

.store-status-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.status-dot.active {
    background: #22c55e;
}

.status-dot.inactive {
    background: #ef4444;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.status-text {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.store-description p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 0.95rem;
}

.store-actions {
    flex-shrink: 0;
}

.modern-button {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
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

/* Seller Dashboard Grid */
.seller-dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    grid-template-rows: auto auto;
    gap: 2rem;
    flex: 1;
}

.metrics-overview {
    grid-column: 1 / -1;
}

.seller-actions {
    grid-column: 2;
    grid-row: 2;
}

.recent-orders {
    grid-column: 1;
    grid-row: 2;
}

/* Seller Dashboard Cards */
.seller-dashboard-card {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
}

.seller-dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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

.view-all-link:hover {
    text-decoration: underline;
}

.card-content {
    padding: 1.5rem;
    flex: 1;
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.metric-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.05), rgba(96, 165, 250, 0.05));
    border: 1px solid rgba(37, 99, 235, 0.1);
    border-radius: 12px;
    transition: all 0.2s ease;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
}

.metric-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.metric-content {
    flex: 1;
}

.metric-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.metric-title {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 500;
    margin: 0;
}

/* Seller Action Grid */
.seller-action-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.seller-action-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--medium-gray);
    border-radius: 12px;
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.2s ease;
}

.seller-action-item:hover {
    background: rgba(37, 99, 235, 0.05);
    border-color: var(--primary-blue);
    transform: translateY(-1px);
}

.seller-action-item.primary {
    border-color: rgba(34, 197, 94, 0.3);
    background: rgba(34, 197, 94, 0.02);
}

.seller-action-item.primary:hover {
    background: rgba(34, 197, 94, 0.05);
    border-color: #22c55e;
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

.seller-action-item.primary .action-icon {
    background: linear-gradient(135deg, #22c55e, #16a34a);
}

.action-content h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
}

.action-content p {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 0;
}

/* Orders Table */
.orders-table-container {
    overflow-x: auto;
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
    padding: 0.5rem 1rem;
    background: var(--primary-blue);
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.table-action-btn:hover {
    background: var(--dark-blue);
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .seller-dashboard-grid {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
    }
    
    .seller-actions {
        grid-column: 1;
        grid-row: 3;
    }
    
    .recent-orders {
        grid-column: 1;
        grid-row: 2;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .seller-dashboard-main {
        padding: 1rem;
    }
    
    .seller-dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .seller-dashboard-header .dashboard-title {
        font-size: 1.5rem;
    }
    
    .store-status-card {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .store-details {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .metric-card {
        flex-direction: column;
        text-align: center;
    }
}

@media (min-width: 1400px) {
    .metrics-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/footer.php'; ?>
