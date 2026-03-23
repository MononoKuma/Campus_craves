<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/helpers/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/AdminController.php';

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

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/header.php'; ?>

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

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/footer.php'; ?>

<style>
.applications-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.application-card {
    background: var(--white);
    border: 1px solid var(--medium-gray);
    border-radius: 16px;
    padding: 2rem;
    position: relative;
    box-shadow: 0 4px 6px var(--shadow);
    transition: all 0.3s ease;
    overflow: hidden;
}

.application-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
}

.application-card:hover {
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
    transform: translateY(-2px);
}

.application-header {
    margin-bottom: 1.5rem;
}

.application-header h3 {
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.application-header p {
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.application-header small {
    color: var(--text-secondary);
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.application-header small::before {
    content: '📅';
    font-size: 0.9rem;
}

.application-reason {
    margin: 1.5rem 0;
    padding: 1.25rem;
    background: var(--light-gray);
    border-radius: 12px;
    border: 1px solid var(--medium-gray);
}

.application-reason h4 {
    color: var(--text-primary);
    margin-bottom: 1rem;
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.application-reason h4::before {
    content: '💼';
    font-size: 1.1rem;
}

.application-reason p {
    color: var(--text-primary);
    line-height: 1.6;
    font-size: 0.95rem;
    white-space: pre-wrap;
    margin: 0;
    padding: 0;
}

.application-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.application-form {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.rejection-form {
    flex-direction: column;
    align-items: stretch;
    flex: 1;
}

.rejection-reason {
    margin-bottom: 0.75rem;
}

.rejection-reason textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--medium-gray);
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.9rem;
    resize: vertical;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    background: var(--white);
}

.rejection-reason textarea:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.modern-button.small {
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
    font-weight: 500;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.modern-button.small:not(.danger) {
    background: var(--primary-blue);
    color: var(--white);
}

.modern-button.small:not(.danger):hover {
    background: var(--dark-blue);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.modern-button.small.danger {
    background: #ef4444;
    color: var(--white);
}

.modern-button.small.danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

@media (max-width: 768px) {
    .applications-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .application-actions {
        flex-direction: column;
    }
    
    .application-form {
        justify-content: stretch;
    }
    
    .modern-button.small {
        width: 100%;
        justify-content: center;
    }
    
    .application-card {
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .application-card {
        padding: 1rem;
    }
    
    .applications-grid {
        gap: 0.75rem;
    }
}
</style>
