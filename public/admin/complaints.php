<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/helpers/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/AdminController.php';

if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$adminController = new AdminController();

// Handle AJAX request for responses
if (isset($_GET['action']) && $_GET['action'] === 'get_responses' && isset($_GET['complaint_id'])) {
    $complaintId = $_GET['complaint_id'];
    $responses = $adminController->getComplaintResponses($complaintId);
    header('Content-Type: application/json');
    echo json_encode(['responses' => $responses]);
    exit();
}

// Handle status updates and responses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = $_POST['complaint_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if ($complaintId && $action) {
        switch ($action) {
            case 'update_status':
                $status = $_POST['status'] ?? null;
                $adminResponse = $_POST['admin_response'] ?? null;
                $adminController->updateComplaintStatus($complaintId, $status, $adminResponse);
                break;
            case 'add_response':
                $message = $_POST['message'] ?? null;
                $adminId = $_SESSION['user_id'] ?? 1; // Assuming admin session
                if ($message) {
                    $adminController->addComplaintResponse($complaintId, $adminId, $message);
                }
                break;
        }
        // Redirect to refresh the page
        header('Location: /admin/complaints.php' . (isset($_GET['view']) ? '?view=' . $_GET['view'] : ''));
        exit();
    }
}

// Get complaints based on view type
$viewType = $_GET['view'] ?? 'all';

// Debug: Show what view type we're using
error_log("Filter view type: $viewType");

switch ($viewType) {
    case 'buyer':
        error_log("Getting buyer complaints...");
        $complaints = $adminController->getComplaintsByType('buyer');
        error_log("Buyer complaints count: " . count($complaints));
        break;
    case 'seller':
        error_log("Getting seller complaints...");
        $complaints = $adminController->getComplaintsByType('seller');
        error_log("Seller complaints count: " . count($complaints));
        break;
    default:
        error_log("Getting all complaints...");
        $complaints = $adminController->getAllComplaints();
        error_log("All complaints count: " . count($complaints));
        break;
}

// Get specific complaint details if viewing one
$complaintDetails = null;
$responses = null;
if (isset($_GET['detail']) && is_numeric($_GET['detail'])) {
    $complaintDetails = $adminController->getComplaintById($_GET['detail']);
    $responses = $adminController->getComplaintResponses($_GET['detail']);
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/header.php'; ?>

<div class="admin-container">
    <div class="admin-header">
        <div class="admin-header-content">
            <h1 class="page-title">
                <span class="title-icon">🗨️</span> Complaint Management
            </h1>
        </div>
    </div>

    <!-- Complaint Type Filters -->
    <div class="filter-section">
        <h2 class="section-title">Complaint Sections</h2>
        <div class="filter-options">
            <a href="/admin/complaints.php?view=all" class="filter-btn<?= $viewType === 'all' ? ' active' : '' ?>">All Complaints</a>
            <a href="/admin/complaints.php?view=buyer" class="filter-btn<?= $viewType === 'buyer' ? ' active' : '' ?>">Buyer Complaints</a>
            <a href="/admin/complaints.php?view=seller" class="filter-btn<?= $viewType === 'seller' ? ' active' : '' ?>">Seller Complaints</a>
        </div>
    </div>

    <!-- Complaints List -->
    <div class="complaints-table-section">
        <h2 class="section-title"><?= ucfirst($viewType) ?> Complaints</h2>
        
        <table class="complaints-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Complainant</th>
                    <th>Respondent</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($complaints)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <div class="empty-state">
                            <div class="empty-icon">🗨️</div>
                            <p>No complaints found.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($complaints as $complaint): ?>
                <tr>
                    <td>#<?= $complaint['id'] ?></td>
                    <td><?= htmlspecialchars(substr($complaint['subject'], 0, 50)) ?><?= strlen($complaint['subject']) > 50 ? '...' : '' ?></td>
                    <td><?= htmlspecialchars($complaint['complainant_name']) ?></td>
                    <td><?= htmlspecialchars($complaint['respondent_name']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($complaint['complaint_type'])) ?></td>
                    <td>
                        <span class="status-badge status-<?= $complaint['status'] ?>">
                            <?= ucfirst($complaint['status']) ?>
                        </span>
                    </td>
                    <td><?= date('M j, Y', strtotime($complaint['created_at'])) ?></td>
                    <td>
                        <button onclick="openComplaintModal(<?= $complaint['id'] ?>)" class="action-btn view-btn">View</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Complaint Detail Modal -->
<div id="complaintModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Complaint Details</h2>
            <button onclick="closeComplaintModal()" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="complaintDetails">
                <!-- Complaint details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Blue and White Admin Theme */
.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.admin-header {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e5e7eb;
}

.admin-header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.title-icon {
    font-size: 2.2rem;
}

.filter-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e5e7eb;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 1rem 0;
}

.filter-options {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.75rem 1.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.filter-btn:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}

.filter-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.complaints-table-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e5e7eb;
}

.complaints-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.complaints-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.complaints-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    color: #1f2937;
}

.complaints-table tr:hover {
    background: #f8fafc;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: rgba(251, 191, 36, 0.1);
    color: #92400e;
    border: 1px solid #fbbf24;
}

.status-investigating {
    background: rgba(59, 130, 246, 0.1);
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.status-resolved {
    background: rgba(34, 197, 94, 0.1);
    color: #166534;
    border: 1px solid #22c55e;
}

.status-dismissed {
    background: rgba(107, 114, 128, 0.1);
    color: #374151;
    border: 1px solid #6b7280;
}

.action-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.view-btn {
    background: #3b82f6;
    color: white;
}

.view-btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

/* Modal Styles */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 2rem;
    max-height: calc(90vh - 100px);
    overflow-y: auto;
}

.complaint-detail-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-label {
    font-weight: 600;
    color: #6b7280;
    font-size: 0.875rem;
}

.info-value {
    color: #1f2937;
    font-weight: 500;
}

.complaint-description-box {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
    margin: 1.5rem 0;
    line-height: 1.6;
    color: #374151;
}

.complaint-form {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1.5rem 0;
    border: 1px solid #e5e7eb;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    background: white;
    color: #1f2937;
    font-family: inherit;
    font-size: 0.875rem;
}

.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.responses-thread {
    margin-top: 2rem;
}

.responses-thread h3 {
    color: #1f2937;
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.response-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.response-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.response-message {
    color: #374151;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .admin-container {
        padding: 1rem;
    }
    
    .filter-options {
        flex-direction: column;
    }
    
    .filter-btn {
        text-align: center;
    }
    
    .complaints-table {
        font-size: 0.875rem;
    }
    
    .complaints-table th,
    .complaints-table td {
        padding: 0.5rem;
    }
    
    .modal-content {
        width: 95%;
        margin: 1rem;
    }
    
    .modal-header,
    .modal-body {
        padding: 1rem;
    }
}
</style>

<script>
// Store complaint data for modal
const complaintsData = <?= json_encode($complaints) ?>;

function openComplaintModal(complaintId) {
    console.log('Opening modal for complaint ID:', complaintId);
    console.log('Available complaints:', complaintsData);
    
    const complaint = complaintsData.find(c => c.id === complaintId);
    if (!complaint) {
        console.error('Complaint not found:', complaintId);
        return;
    }
    
    const modal = document.getElementById('complaintModal');
    const detailsContainer = document.getElementById('complaintDetails');
    
    if (!modal) {
        console.error('Modal not found');
        return;
    }
    
    if (!detailsContainer) {
        console.error('Details container not found');
        return;
    }
    
    console.log('Found complaint:', complaint);
    
    // Show loading state
    detailsContainer.innerHTML = '<div style="text-align: center; padding: 2rem;">Loading complaint details...</div>';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Fetch complaint responses via AJAX
    fetch(`/admin/complaints.php?action=get_responses&complaint_id=${complaintId}`)
        .then(response => {
            console.log('Fetch response:', response);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            const responses = data.responses || [];
            renderComplaintDetails(complaint, responses, detailsContainer);
        })
        .catch(error => {
            console.error('Error fetching responses:', error);
            // Still render complaint details even if responses fail
            renderComplaintDetails(complaint, [], detailsContainer);
        });
}

function renderComplaintDetails(complaint, responses, container) {
    const complaintTypes = {
        'product_issue': 'Product Issue',
        'service_issue': 'Service Issue',
        'payment_issue': 'Payment Issue',
        'delivery_issue': 'Delivery Issue',
        'other': 'Other'
    };
    
    // Build responses HTML
    let responsesHtml = '';
    if (responses.length > 0) {
        responsesHtml = '<div class="responses-thread"><h3>Conversation History</h3>';
        responses.forEach(response => {
            const responseDate = new Date(response.created_at).toLocaleString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            responsesHtml += `
                <div class="response-item">
                    <div class="response-header">
                        <strong>${response.username}</strong>
                        <span class="response-date">${responseDate}</span>
                    </div>
                    <div class="response-message">
                        ${response.message.replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;
        });
        responsesHtml += '</div>';
    }
    
    container.innerHTML = `
        <div class="complaint-detail-info">
            <div class="info-item">
                <span class="info-label">Complaint ID:</span>
                <span class="info-value">#${complaint.id}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Subject:</span>
                <span class="info-value">${complaint.subject}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Type:</span>
                <span class="info-value">${complaintTypes[complaint.complaint_type] || complaint.complaint_type}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Complainant:</span>
                <span class="info-value">${complaint.complainant_name} (${complaint.complainant_role})</span>
            </div>
            <div class="info-item">
                <span class="info-label">Respondent:</span>
                <span class="info-value">${complaint.respondent_name} (${complaint.respondent_role})</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date Filed:</span>
                <span class="info-value">${new Date(complaint.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge status-${complaint.status}">${complaint.status.charAt(0).toUpperCase() + complaint.status.slice(1)}</span>
                </span>
            </div>
        </div>
        
        <div class="complaint-description-box">
            <h4 style="margin: 0 0 0.75rem 0; font-weight: 600; color: #1f2937;">Description:</h4>
            ${complaint.description.replace(/\n/g, '<br>')}
        </div>
        
        ${responsesHtml}
        
        <!-- Status Update Form -->
        <form method="POST" class="complaint-form">
            <input type="hidden" name="complaint_id" value="${complaint.id}">
            <input type="hidden" name="action" value="update_status">
            
            <div class="form-group">
                <label for="status_${complaint.id}">Update Status:</label>
                <select name="status" id="status_${complaint.id}">
                    <option value="pending" ${complaint.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="investigating" ${complaint.status === 'investigating' ? 'selected' : ''}>Investigating</option>
                    <option value="resolved" ${complaint.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                    <option value="dismissed" ${complaint.status === 'dismissed' ? 'selected' : ''}>Dismissed</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="admin_response_${complaint.id}">Admin Response:</label>
                <textarea name="admin_response" id="admin_response_${complaint.id}" rows="4" placeholder="Enter your response...">${complaint.admin_response || ''}</textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
        
        <!-- Add Response Form -->
        <form method="POST" class="complaint-form">
            <input type="hidden" name="complaint_id" value="${complaint.id}">
            <input type="hidden" name="action" value="add_response">
            
            <div class="form-group">
                <label for="message_${complaint.id}">Add Response:</label>
                <textarea name="message" id="message_${complaint.id}" rows="3" placeholder="Enter your response..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-secondary">Add Response</button>
            </div>
        </form>
    `;
}

function closeComplaintModal() {
    const modal = document.getElementById('complaintModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('complaintModal');
    if (event.target === modal) {
        closeComplaintModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeComplaintModal();
    }
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/footer.php'; ?>
