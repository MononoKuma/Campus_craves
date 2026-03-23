<?php
session_start();
require_once __DIR__ . '/src/helpers/functions.php';
require_once __DIR__ . '/src/controllers/ComplaintController.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}

// Redirect admin users to admin dashboard
if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

$complaintController = new ComplaintController();
$userId = $_SESSION['user_id'];

// Get user's complaints
$userComplaints = $complaintController->getUserComplaints($userId);

// Handle response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_response'])) {
    $complaintId = $_POST['complaint_id'] ?? null;
    $message = $_POST['message'] ?? null;
    
    if ($complaintId && $message) {
        $result = $complaintController->addComplaintResponse($complaintId, $userId, $message);
        
        if ($result['success']) {
            $success = $result['message'];
            // Refresh complaints to show new response
            $userComplaints = $complaintController->getUserComplaints($userId);
        } else {
            $error = $result['message'];
        }
    }
}

// Get specific complaint details if viewing one
$complaintDetails = null;
$responses = null;
if (isset($_GET['detail']) && is_numeric($_GET['detail'])) {
    $complaintDetails = $complaintController->getComplaintById($_GET['detail'], $userId);
    if ($complaintDetails) {
        $responses = $complaintController->getComplaintResponses($_GET['detail'], $userId);
    }
}

// Get complaint type labels
$complaintTypes = $complaintController->getComplaintTypes();
?>

<?php require_once __DIR__ . '/src/views/partials/header.php'; ?>

<main class="complaints-main">
    <div class="complaints-container">
        <div class="complaints-header">
            <div class="header-content">
                <h1>My Complaints</h1>
                <p>Track and manage your complaint history</p>
            </div>
            <div class="header-actions">
                <a href="/file-complaint.php" class="btn btn-primary">
                    <span class="btn-icon">+</span>
                    File New Complaint
                </a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($complaintDetails): ?>
            <!-- Complaint Detail View -->
            <div class="complaint-detail-panel">
                <div class="detail-header">
                    <h2>Complaint #<?= $complaintDetails['id'] ?></h2>
                    <div class="status-badge status-<?= $complaintDetails['status'] ?>">
                        <?= ucfirst($complaintDetails['status']) ?>
                    </div>
                </div>

                <div class="detail-content">
                    <div class="complaint-info">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Subject:</label>
                                <span><?= htmlspecialchars($complaintDetails['subject']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Type:</label>
                                <span><?= htmlspecialchars($complaintTypes[$complaintDetails['complaint_type']] ?? $complaintDetails['complaint_type']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Reported Against:</label>
                                <span><?= htmlspecialchars($complaintDetails['other_party_name'] ?? 'Unknown') ?> (<?= ucfirst($complaintDetails['other_party_role'] ?? 'user') ?>)</span>
                            </div>
                            <div class="info-item">
                                <label>Date Filed:</label>
                                <span><?= date('M j, Y H:i', strtotime($complaintDetails['created_at'])) ?></span>
                            </div>
                            <?php if ($complaintDetails['resolved_at']): ?>
                            <div class="info-item">
                                <label>Resolved:</label>
                                <span><?= date('M j, Y H:i', strtotime($complaintDetails['resolved_at'])) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="description-section">
                            <label>Description:</label>
                            <div class="description-content">
                                <?= nl2br(htmlspecialchars($complaintDetails['description'])) ?>
                            </div>
                        </div>

                        <?php if ($complaintDetails['admin_response']): ?>
                        <div class="admin-response">
                            <label>Admin Response:</label>
                            <div class="response-content">
                                <?= nl2br(htmlspecialchars($complaintDetails['admin_response'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Response Form -->
                <?php if ($complaintDetails['status'] !== 'resolved' && $complaintDetails['status'] !== 'rejected'): ?>
                <div class="add-response-section">
                    <h3>Add Response</h3>
                    <form method="POST" class="response-form">
                        <input type="hidden" name="complaint_id" value="<?= $complaintDetails['id'] ?>">
                        <input type="hidden" name="add_response" value="1">
                        
                        <div class="form-group">
                            <textarea name="message" rows="4" placeholder="Enter your response or additional information..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Submit Response</button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Responses Thread -->
                <?php if ($responses && !empty($responses)): ?>
                <div class="responses-thread">
                    <h3>Conversation History</h3>
                    <?php foreach ($responses as $response): ?>
                    <div class="response-item <?= $response['responder_id'] == $userId ? 'user-response' : 'other-response' ?>">
                        <div class="response-header">
                            <div class="response-author">
                                <strong><?= htmlspecialchars($response['username']) ?></strong>
                                <?php if ($response['responder_id'] == $userId): ?>
                                    <span class="author-badge">You</span>
                                <?php endif; ?>
                            </div>
                            <div class="response-date">
                                <?= date('M j, Y H:i', strtotime($response['created_at'])) ?>
                            </div>
                        </div>
                        <div class="response-message">
                            <?= nl2br(htmlspecialchars($response['message'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="detail-actions">
                    <a href="/my-complaints.php" class="btn btn-secondary">← Back to List</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Complaints List -->
            <div class="complaints-list-panel">
                <?php if (empty($userComplaints)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <h3>No Complaints Yet</h3>
                        <p>You haven't filed any complaints yet. If you have an issue with a product, service, or another user, you can file a complaint to get help from our admin team.</p>
                        <a href="/file-complaint.php" class="btn btn-primary">File Your First Complaint</a>
                    </div>
                <?php else: ?>
                    <div class="complaints-grid">
                        <?php foreach ($userComplaints as $complaint): ?>
                        <div class="complaint-card" onclick="window.location.href='?detail=<?= $complaint['id'] ?>'">
                            <div class="card-header">
                                <div class="complaint-id">#<?= $complaint['id'] ?></div>
                                <div class="status-badge status-<?= $complaint['status'] ?>">
                                    <?= ucfirst($complaint['status']) ?>
                                </div>
                            </div>
                            
                            <div class="card-content">
                                <h3 class="complaint-subject"><?= htmlspecialchars($complaint['subject']) ?></h3>
                                
                                <div class="complaint-meta">
                                    <div class="meta-item">
                                        <span class="meta-label">Type:</span>
                                        <span class="meta-value"><?= htmlspecialchars($complaintTypes[$complaint['complaint_type']] ?? $complaint['complaint_type']) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Against:</span>
                                        <span class="meta-value"><?= htmlspecialchars($complaint['other_party_name'] ?? 'Unknown') ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Date:</span>
                                        <span class="meta-value"><?= date('M j, Y', strtotime($complaint['created_at'])) ?></span>
                                    </div>
                                </div>

                                <div class="complaint-preview">
                                    <?= htmlspecialchars(substr($complaint['description'], 0, 150)) ?><?= strlen($complaint['description']) > 150 ? '...' : '' ?>
                                </div>
                            </div>

                            <div class="card-footer">
                                <span class="view-details">View Details →</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.complaints-main {
    flex: 1;
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: calc(100vh - 200px);
}

.complaints-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.complaints-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
}

.header-content h1 {
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
}

.header-content p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 1.1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: var(--primary-blue);
    color: white;
}

.btn-primary:hover {
    background: var(--dark-blue);
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--medium-gray);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background: #d1d5db;
}

.btn-icon {
    font-size: 1.2rem;
    font-weight: 700;
}

.complaint-detail-panel {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
    overflow: hidden;
}

.detail-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--medium-gray);
}

.detail-header h2 {
    color: var(--text-primary);
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: rgba(251, 191, 36, 0.1);
    color: #92400e;
    border: 1px solid #fbbf24;
}

.status-under_review {
    background: rgba(59, 130, 246, 0.1);
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.status-resolved {
    background: rgba(34, 197, 94, 0.1);
    color: #166534;
    border: 1px solid #22c55e;
}

.status-rejected {
    background: rgba(239, 68, 68, 0.1);
    color: #991b1b;
    border: 1px solid #ef4444;
}

.detail-content {
    padding: 2rem;
}

.complaint-info {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item label {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.info-item span {
    color: var(--text-primary);
    font-weight: 500;
}

.description-section,
.admin-response {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.description-section label,
.admin-response label {
    font-weight: 600;
    color: var(--text-primary);
}

.description-content,
.response-content {
    background: var(--light-gray);
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid var(--primary-blue);
    line-height: 1.6;
    color: var(--text-primary);
}

.add-response-section {
    padding: 2rem;
    background: var(--light-gray);
    border-top: 1px solid var(--medium-gray);
}

.add-response-section h3 {
    color: var(--text-primary);
    margin: 0 0 1rem 0;
}

.response-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.response-form textarea {
    padding: 1rem;
    border: 2px solid var(--medium-gray);
    border-radius: 8px;
    font-family: inherit;
    font-size: 1rem;
    resize: vertical;
    min-height: 100px;
}

.response-form textarea:focus {
    outline: none;
    border-color: var(--primary-blue);
}

.responses-thread {
    padding: 2rem;
    border-top: 1px solid var(--medium-gray);
}

.responses-thread h3 {
    color: var(--text-primary);
    margin: 0 0 1.5rem 0;
}

.response-item {
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    border-radius: 8px;
}

.user-response {
    background: rgba(37, 99, 235, 0.05);
    border: 1px solid rgba(37, 99, 235, 0.2);
}

.other-response {
    background: var(--light-gray);
    border: 1px solid var(--medium-gray);
}

.response-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.response-author {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.author-badge {
    background: var(--primary-blue);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.response-date {
    color: var(--text-secondary);
    font-size: 0.85rem;
}

.response-message {
    color: var(--text-primary);
    line-height: 1.6;
}

.detail-actions {
    padding: 2rem;
    border-top: 1px solid var(--medium-gray);
    background: var(--light-gray);
}

.complaints-list-panel {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
    padding: 2rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: var(--text-primary);
    margin: 0 0 1rem 0;
    font-size: 1.5rem;
}

.empty-state p {
    margin: 0 0 2rem 0;
    line-height: 1.6;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.complaints-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.complaint-card {
    border: 1px solid var(--medium-gray);
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.complaint-card:hover {
    border-color: var(--primary-blue);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
    transform: translateY(-2px);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.complaint-id {
    font-weight: 600;
    color: var(--primary-blue);
}

.complaint-subject {
    color: var(--text-primary);
    margin: 0;
    font-size: 1.1rem;
    line-height: 1.4;
}

.complaint-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.meta-item {
    display: flex;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.meta-label {
    color: var(--text-secondary);
    font-weight: 500;
    min-width: 60px;
}

.meta-value {
    color: var(--text-primary);
    font-weight: 500;
}

.complaint-preview {
    color: var(--text-secondary);
    line-height: 1.5;
    font-size: 0.9rem;
}

.card-footer {
    margin-top: auto;
    text-align: right;
}

.view-details {
    color: var(--primary-blue);
    font-weight: 600;
    font-size: 0.9rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 2px solid #ef4444;
    color: #991b1b;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 2px solid #22c55e;
    color: #166534;
}

.alert-icon {
    font-size: 1.25rem;
}

@media (max-width: 768px) {
    .complaints-main {
        padding: 1rem;
    }
    
    .complaints-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .complaints-grid {
        grid-template-columns: 1fr;
    }
    
    .response-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<?php require_once __DIR__ . '/src/views/partials/footer.php'; ?>
