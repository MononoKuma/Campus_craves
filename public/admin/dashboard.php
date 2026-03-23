<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/helpers/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/AdminController.php';

// Strict admin check
if (!isAdmin()) {
    redirect('/dashboard.php');
}

$adminController = new AdminController();
$metrics = $adminController->getDashboardMetrics();
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/header.php'; ?>

<main class="admin-dashboard-main">
    <div class="admin-dashboard-container">
        <!-- Admin Dashboard Grid -->
        <div class="admin-dashboard-grid">
            <!-- Metrics Overview Card -->
            <div class="admin-dashboard-card metrics-overview">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </div>
                    <h2 class="card-title">System Metrics</h2>
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
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <div class="metric-content">
                                <div class="metric-value"><?= $metrics['total_customers'] ?></div>
                                <div class="metric-title">Customers</div>
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
                                <div class="metric-title">Products</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Card - MOVED UP -->
            <div class="admin-dashboard-card recent-orders">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <h2 class="card-title">Recent Orders</h2>
                    <div class="card-actions">
                        <a href="/admin/orders.php" class="view-all-link">View All</a>
                    </div>
                </div>
                <div class="card-content">
                    <div class="orders-table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Seller(s)</th>
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
                                    <td><?= htmlspecialchars($order['seller_names'] ?: 'N/A') ?></td>
                                    <td class="amount">Php <?= number_format($order['total_amount'], 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <a href="/admin/orders.php?view=<?= $order['id'] ?>" class="table-action-btn">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="admin-dashboard-card admin-actions">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 9.54l4.24 4.24M20.46 14.46l4.24 4.24M1.54 14.46l4.24 4.24"/>
                        </svg>
                    </div>
                    <h2 class="card-title">Admin Actions</h2>
                </div>
                <div class="card-content">
                    <div class="admin-action-grid">
                        <a href="/admin/orders.php" class="admin-action-item">
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
                        
                        <a href="/admin/customers.php" class="admin-action-item">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h3>Manage Users</h3>
                                <p>User accounts and permissions</p>
                            </div>
                        </a>
                        
                        <a href="/admin/verifications.php" class="admin-action-item priority">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h3>Student Verifications</h3>
                                <p>Review student ID submissions</p>
                            </div>
                        </a>
                        
                        <a href="/admin/seller-applications.php" class="admin-action-item priority">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h3>Seller Applications</h3>
                                <p>Review seller requests</p>
                            </div>
                        </a>
                        
                        <a href="/admin/complaints.php" class="admin-action-item priority">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h3>Complaint Management</h3>
                                <p>Handle user complaints</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Admin Dashboard Layout */
.admin-dashboard-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 200px);
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.admin-dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Admin Header */
.admin-dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
}

.admin-dashboard-header .dashboard-title {
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

/* Admin Dashboard Grid */
.admin-dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    grid-template-rows: auto auto;
    gap: 2rem;
    flex: 1;
}

.metrics-overview {
    grid-column: 1 / -1;
}

.recent-orders {
    grid-column: 1;
    grid-row: 2;
}

.admin-actions {
    grid-column: 2;
    grid-row: 2;
}

/* Admin Dashboard Cards */
.admin-dashboard-card {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
}

.admin-dashboard-card:hover {
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

/* Admin Action Grid */
.admin-action-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.admin-action-item {
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

.admin-action-item:hover {
    background: rgba(37, 99, 235, 0.05);
    border-color: var(--primary-blue);
    transform: translateY(-1px);
}

.admin-action-item.priority {
    border-color: rgba(251, 191, 36, 0.3);
    background: rgba(251, 191, 36, 0.02);
}

.admin-action-item.priority:hover {
    background: rgba(251, 191, 36, 0.05);
    border-color: #fbbf24;
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

.admin-action-item.priority .action-icon {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
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
    .admin-dashboard-grid {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
    }
    
    .recent-orders {
        grid-column: 1;
        grid-row: 2;
    }
    
    .admin-actions {
        grid-column: 1;
        grid-row: 3;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .admin-dashboard-main {
        padding: 1rem;
    }
    
    .admin-dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .admin-dashboard-header .dashboard-title {
        font-size: 1.5rem;
    }
    
    .metric-card {
        flex-direction: column;
        text-align: center;
    }
    
    .admin-dashboard-grid {
        gap: 1rem;
    }
}

@media (min-width: 1400px) {
    .metrics-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/footer.php'; ?>