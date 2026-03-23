<?php
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/controllers/SellerController.php';

// Strict seller check
if (!isSeller()) {
    redirect('/dashboard.php');
}

$sellerController = new SellerController();
$sellerId = $_SESSION['user_id'];
$errors = [];

// Get current store profile
$profile = $sellerController->getSellerProfile($sellerId);
$storeStatus = $sellerController->getStoreStatus($sellerId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle store status toggle
    if (isset($_POST['toggle_status'])) {
        $newStatus = $storeStatus === 'available' ? 'unavailable' : 'available';
        if ($sellerController->updateStoreStatus($sellerId, $newStatus)) {
            setFlashMessage('Store status updated successfully!', 'success');
            $storeStatus = $newStatus;
        } else {
            $errors[] = 'Failed to update store status.';
        }
    }
    
    }
?>

<?php require_once __DIR__ . '/../../src/views/partials/header.php'; ?>

<div class="brass-panel store-profile">
    <h1 class="gears-title">⚙️ My Micro Store</h1>
    <div class="copper-divider"></div>

    <?php displayFlashMessage(); ?>
    <?php displayErrors($errors); ?>

    <!-- Store Status Toggle -->
    <div class="store-status-section">
        <h2><span class="gear-icon">🏪</span> Store Status</h2>
        <div class="status-toggle">
            <form method="POST" class="inline-form">
                <input type="hidden" name="toggle_status" value="1">
                <div class="toggle-container">
                    <span class="status-label">Current Status:</span>
                    <span class="status-value <?= $storeStatus === 'available' ? 'status-available' : 'status-unavailable' ?>">
                        <?= $storeStatus === 'available' ? '🟢 Available' : '🔴 Unavailable' ?>
                    </span>
                    <button type="submit" class="steam-button small">
                        Toggle Status
                    </button>
                </div>
            </form>
        </div>
        <p class="status-description">
            <?= $storeStatus === 'available' 
                ? 'Your store is visible to customers and accepting orders.' 
                : 'Your store is hidden from customers and not accepting new orders.' ?>
        </p>
    </div>

    <!-- Back to Dashboard -->
    <div class="form-actions">
        <a href="/seller/dashboard.php" class="steam-button secondary">Back to Dashboard</a>
    </div>
</div>

<style>
.store-status-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: rgba(139, 69, 19, 0.1);
    border: 2px solid #8B4513;
    border-radius: 8px;
}

.status-toggle {
    margin: 1rem 0;
}

.inline-form {
    display: inline-block;
}

.toggle-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.status-label {
    font-weight: bold;
    color: #8B4513;
}

.status-value {
    font-weight: bold;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

.status-available {
    background: #d4edda;
    color: #155724;
}

.status-unavailable {
    background: #f8d7da;
    color: #721c24;
}

.status-description {
    margin-top: 0.5rem;
    color: #666;
    font-style: italic;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.9);
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #8B4513;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #8B4513;
}

.current-banner {
    margin-top: 1rem;
}

.current-banner img {
    border-radius: 8px;
    border: 2px solid #8B4513;
}

.steam-button.small {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}
</style>

<?php require_once __DIR__ . '/../../src/views/partials/footer.php'; ?>
