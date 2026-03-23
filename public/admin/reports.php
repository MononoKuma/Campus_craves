<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/helpers/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/AdminController.php';

// Strict admin check
if (!isAdmin()) {
    redirect('/dashboard.php');
}

$adminController = new AdminController();

// Optionally, filter by status or date range in the future
$sales = $adminController->getAllOrders('completed');

?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/header.php'; ?>

<div class="admin-container">
    <div class="admin-header brass-panel">
        <h1 class="gears-title">📊 Sales Report</h1>
    </div>

    <div class="brass-panel">
        <h2>Completed Sales</h2>
        <table class="steam-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr><td colspan="5">No sales found.</td></tr>
                <?php else: ?>
                    <?php foreach ($sales as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['username']) ?></td>
                        <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                        <td>Php <?= number_format($order['total_amount'], 2) ?></td>
                        <td><?= ucfirst($order['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/footer.php'; ?> 