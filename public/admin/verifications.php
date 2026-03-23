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

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'verify_student' && isset($_POST['user_id']) && isset($_POST['status'])) {
        $userId = $_POST['user_id'];
        $status = $_POST['status'];
        $reason = $_POST['reason'] ?? null;
        
        $result = $adminController->verifyStudent($userId, $status, $reason);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessages[] = $result['error'];
        }
    }
}

$pendingVerifications = $adminController->getPendingVerifications();
?>

<?php require_once __DIR__ . '/../../src/views/partials/header.php'; ?>

<div class="brass-panel admin-dashboard">
    <div class="admin-header-flex">
        <h1 class="modern-title">⚙️ Student Verification Management</h1>
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
        <h2><span class="gear-icon">🎓</span> Pending Student Verifications</h2>
        
        <?php if (empty($pendingVerifications)): ?>
            <p>No pending student verifications at this time.</p>
        <?php else: ?>
            <div class="verification-grid">
                <?php foreach ($pendingVerifications as $verification): ?>
                    <div class="verification-card">
                        <div class="verification-header">
                            <div class="user-info">
                                <h3><?php echo htmlspecialchars($verification['username']); ?></h3>
                                <p><?php echo htmlspecialchars($verification['email']); ?></p>
                                <small>Applied: <?php echo date('F j, Y', strtotime($verification['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="verification-content">
                            <?php if ($verification['student_id_image']): ?>
                                <div class="id-preview">
                                    <h4>Student ID Preview:</h4>
                                    <img src="<?php echo htmlspecialchars($verification['student_id_image']); ?>" 
                                         alt="Student ID" 
                                         class="id-image"
                                         onclick="window.open(this.src, '_blank')">
                                    <p><small>Click image to view full size</small></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="verification-actions">
                            <form method="post" class="verification-form">
                                <input type="hidden" name="action" value="verify_student">
                                <input type="hidden" name="user_id" value="<?php echo $verification['id']; ?>">
                                <input type="hidden" name="status" value="verified">
                                <button type="submit" class="modern-button small" onclick="return confirm('Approve this student verification?')">
                                    ✅ Approve
                                </button>
                            </form>
                            
                            <form method="post" class="verification-form rejection-form">
                                <input type="hidden" name="action" value="verify_student">
                                <input type="hidden" name="user_id" value="<?php echo $verification['id']; ?>">
                                <input type="hidden" name="status" value="rejected">
                                <div class="rejection-reason">
                                    <textarea name="reason" placeholder="Reason for rejection (optional)" rows="2"></textarea>
                                </div>
                                <button type="submit" class="modern-button small danger" onclick="return confirm('Reject this student verification?')">
                                    ❌ Reject
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
.verification-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.verification-card {
    background: var(--parchment);
    border: 2px solid var(--copper);
    border-radius: 8px;
    padding: 1.5rem;
    position: relative;
}

.verification-header h3 {
    color: var(--copper);
    margin-bottom: 0.5rem;
}

.verification-header p {
    color: var(--dark-wood);
    margin-bottom: 0.25rem;
}

.verification-header small {
    color: #666;
    font-style: italic;
}

.id-preview {
    margin: 1rem 0;
}

.id-preview h4 {
    color: var(--dark-wood);
    margin-bottom: 0.5rem;
}

.id-image {
    max-width: 100%;
    max-height: 200px;
    border: 1px solid var(--copper);
    border-radius: 4px;
    cursor: pointer;
    transition: transform 0.2s;
}

.id-image:hover {
    transform: scale(1.05);
}

.verification-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.verification-form {
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
    .verification-grid {
        grid-template-columns: 1fr;
    }
    
    .verification-actions {
        flex-direction: column;
    }
    
    .verification-form {
        justify-content: stretch;
    }
    
    .modern-button.small {
        width: 100%;
    }
}
</style>
