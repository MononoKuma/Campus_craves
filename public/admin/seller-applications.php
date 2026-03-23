<?php
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/controllers/AdminController.php';

// Strict admin check
if (!isAdmin()) {
    redirect('/dashboard.php');
}

$adminController = new AdminController();
$successMessage = '';
$errorMessages = [];

// Handle seller application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_seller_application' && isset($_POST['user_id']) && isset($_POST['status'])) {
        $userId = $_POST['user_id'];
        $status = $_POST['status'];
        $reason = $_POST['reason'] ?? null;
        
        $result = $adminController->updateSellerApplication($userId, $status, $reason);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessages[] = $result['error'];
        }
    }
}

$pendingApplications = $adminController->getPendingSellerApplications();
?>

<?php require_once __DIR__ . '/../../src/views/partials/header.php'; ?>

<div class="brass-panel admin-dashboard">
    <div class="admin-header-flex">
        <h1 class="modern-title">⚙️ Seller Application Management</h1>
        <a href="/admin/dashboard.php" class="modern-button header-small secondary">← Back to Dashboard</a>
    </div>
    <div class="copper-divider"></div>

    <?php if ($successMessage): ?>
        <div class="steam-alert success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errorMessages)): ?>
        <?php foreach ($errorMessages as $error): ?>
            <div class="steam-alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="dashboard-section">
        <h2><span class="gear-icon">🏪</span> Pending Seller Applications</h2>
        
        <?php if (empty($pendingApplications)): ?>
            <p>No pending seller applications at this time.</p>
        <?php else: ?>
            <div class="applications-grid">
                <?php foreach ($pendingApplications as $application): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="user-info">
                                <h3><?php echo htmlspecialchars($application['username']); ?></h3>
                                <p><?php echo htmlspecialchars($application['email']); ?></p>
                                <small>Applied: <?php echo date('F j, Y', strtotime($application['applied_for_seller_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="application-content">
                            <div class="application-reason">
                                <h4>Application Reason:</h4>
                                <p><?php echo nl2br(htmlspecialchars($application['seller_application_reason'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="application-actions">
                            <form method="post" class="application-form">
                                <input type="hidden" name="action" value="update_seller_application">
                                <input type="hidden" name="user_id" value="<?php echo $application['id']; ?>">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="modern-button small" onclick="return confirm('Approve this seller application? This will give the user seller privileges.')">
                                    ✅ Approve Application
                                </button>
                            </form>
                            
                            <form method="post" class="application-form rejection-form">
                                <input type="hidden" name="action" value="update_seller_application">
                                <input type="hidden" name="user_id" value="<?php echo $application['id']; ?>">
                                <input type="hidden" name="status" value="rejected">
                                <div class="rejection-reason">
                                    <textarea name="reason" placeholder="Reason for rejection (optional)" rows="2"></textarea>
                                </div>
                                <button type="submit" class="modern-button small danger" onclick="return confirm('Reject this seller application?')">
                                    ❌ Reject Application
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../src/views/partials/footer.php'; ?>

<style>
.applications-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.application-card {
    background: var(--parchment);
    border: 2px solid var(--copper);
    border-radius: 8px;
    padding: 1.5rem;
    position: relative;
}

.application-header h3 {
    color: var(--copper);
    margin-bottom: 0.5rem;
}

.application-header p {
    color: var(--dark-wood);
    margin-bottom: 0.25rem;
}

.application-header small {
    color: #666;
    font-style: italic;
}

.application-reason {
    margin: 1rem 0;
}

.application-reason h4 {
    color: var(--dark-wood);
    margin-bottom: 0.5rem;
}

.application-reason p {
    background: rgba(245, 231, 208, 0.5);
    padding: 0.75rem;
    border-radius: 4px;
    border-left: 3px solid var(--copper);
    white-space: pre-wrap;
}

.application-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.application-form {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rejection-form {
    flex-direction: column;
    align-items: stretch;
}

.rejection-reason {
    margin-bottom: 0.5rem;
}

.rejection-reason textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--copper);
    border-radius: 4px;
    font-family: inherit;
    resize: vertical;
}

@media (max-width: 768px) {
    .applications-grid {
        grid-template-columns: 1fr;
    }
    
    .application-actions {
        flex-direction: column;
    }
    
    .application-form {
        justify-content: stretch;
    }
    
    .modern-button.small {
        width: 100%;
    }
}
</style>
