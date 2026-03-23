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

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/header.php'; ?>

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

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/footer.php'; ?>

<style>
.verification-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.verification-card {
    background: var(--white);
    border: 1px solid var(--medium-gray);
    border-radius: 16px;
    padding: 2rem;
    position: relative;
    box-shadow: 0 4px 6px var(--shadow);
    transition: all 0.3s ease;
    overflow: hidden;
}

.verification-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
}

.verification-card:hover {
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
    transform: translateY(-2px);
}

.verification-header {
    margin-bottom: 1.5rem;
}

.verification-header h3 {
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.verification-header p {
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.verification-header small {
    color: var(--text-secondary);
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.verification-header small::before {
    content: '📅';
    font-size: 0.9rem;
}

.id-preview {
    margin: 1.5rem 0;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: 12px;
    border: 1px solid var(--medium-gray);
}

.id-preview h4 {
    color: var(--text-primary);
    margin-bottom: 1rem;
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.id-preview h4::before {
    content: '🎓';
    font-size: 1.1rem;
}

.id-image {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid var(--medium-gray);
}

.id-image:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-blue);
}

.id-preview p {
    margin-top: 0.75rem;
    color: var(--text-secondary);
    font-size: 0.85rem;
    text-align: center;
    font-style: italic;
}

.verification-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.verification-form {
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
    .verification-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .verification-actions {
        flex-direction: column;
    }
    
    .verification-form {
        justify-content: stretch;
    }
    
    .modern-button.small {
        width: 100%;
        justify-content: center;
    }
    
    .verification-card {
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .verification-card {
        padding: 1rem;
    }
    
    .verification-grid {
        gap: 0.75rem;
    }
}
</style>
