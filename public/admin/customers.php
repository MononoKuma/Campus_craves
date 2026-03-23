<?php
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/controllers/AdminController.php';

if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$adminController = new AdminController();

// Get filter parameters from GET request
$filters = [
    'role' => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? '',
    'orderBy' => $_GET['orderBy'] ?? 'created_at',
    'orderDirection' => $_GET['orderDirection'] ?? 'DESC',
    'limit' => $_GET['limit'] ?? '',
    'search' => $_GET['search'] ?? '',
    'email_search' => $_GET['email_search'] ?? ''
];

// Clean up empty filters
$filters = array_filter($filters, function($value) {
    return $value !== '';
});

$users = $adminController->getAllUsersWithStatus($filters);

// Handle ban/suspend actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $reason = $_POST['reason'] ?? null;
    $duration = $_POST['duration'] ?? 7;
    
    if ($userId && $action) {
        $response = ['success' => false, 'message' => ''];
        
        try {
            switch ($action) {
                case 'ban':
                    $result = $adminController->banUser($userId, $reason);
                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'User banned successfully.';
                        setFlashMessage('User banned successfully.', 'success');
                    } else {
                        $response['message'] = 'Failed to ban user.';
                        setFlashMessage('Failed to ban user.', 'error');
                    }
                    break;
                case 'suspend':
                    $result = $adminController->suspendUser($userId, $reason, $duration);
                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = "User suspended for $duration days.";
                        setFlashMessage("User suspended for $duration days.", 'success');
                    } else {
                        $response['message'] = 'Failed to suspend user.';
                        setFlashMessage('Failed to suspend user.', 'error');
                    }
                    break;
                case 'unban':
                    $result = $adminController->unbanUser($userId);
                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'User unbanned successfully.';
                        setFlashMessage('User unbanned successfully.', 'success');
                    } else {
                        $response['message'] = 'Failed to unban user.';
                        setFlashMessage('Failed to unban user.', 'error');
                    }
                    break;
                case 'unsuspend':
                    $result = $adminController->unsuspendUser($userId);
                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'User unsuspended successfully.';
                        setFlashMessage('User unsuspended successfully.', 'success');
                    } else {
                        $response['message'] = 'Failed to unsuspend user.';
                        setFlashMessage('Failed to unsuspend user.', 'error');
                    }
                    break;
                default:
                    $response['message'] = 'Invalid action.';
                    setFlashMessage('Invalid action.', 'error');
            }
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
            setFlashMessage('Error: ' . $e->getMessage(), 'error');
        }
        
        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Return JSON response for AJAX requests
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            // Redirect for regular form submissions
            header('Location: /admin/customers.php');
            exit();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../src/views/partials/header.php'; ?>

<script>
// Global functions for modal handling - must be defined before HTML that uses them
function showBanModal(userId, username) {
    try {
        document.getElementById('banUserId').value = userId;
        document.getElementById('banUsername').textContent = username;
        document.getElementById('banModal').style.display = 'flex';
    } catch (error) {
        console.error('Error showing ban modal:', error);
        alert('Error opening ban modal: ' + error.message);
    }
}

function closeBanModal() {
    try {
        document.getElementById('banModal').style.display = 'none';
    } catch (error) {
        console.error('Error closing ban modal:', error);
    }
}

function showSuspendModal(userId, username) {
    try {
        document.getElementById('suspendUserId').value = userId;
        document.getElementById('suspendUsername').textContent = username;
        document.getElementById('suspendModal').style.display = 'flex';
    } catch (error) {
        console.error('Error showing suspend modal:', error);
        alert('Error opening suspend modal: ' + error.message);
    }
}

function closeSuspendModal() {
    try {
        document.getElementById('suspendModal').style.display = 'none';
    } catch (error) {
        console.error('Error closing suspend modal:', error);
    }
}

function unbanUser(userId) {
    if (confirm('Are you sure you want to unban this user?')) {
        // Create AJAX request instead of form submission
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/admin/customers.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // This identifies it as AJAX
        
        xhr.onload = function() {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to unban user: ' + response.message);
                }
            } catch (e) {
                alert('Failed to unban user. Please try again.');
            }
        };
        
        xhr.onerror = function() {
            alert('Failed to unban user. Please try again.');
        };
        
        // Send the unban request
        const params = new URLSearchParams({
            'user_id': userId,
            'action': 'unban'
        });
        xhr.send(params.toString());
    }
}

function unsuspendUser(userId) {
    if (confirm('Are you sure you want to unsuspend this user?')) {
        // Create AJAX request instead of form submission
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/admin/customers.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // This identifies it as AJAX
        
        xhr.onload = function() {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to unsuspend user: ' + response.message);
                }
            } catch (e) {
                alert('Failed to unsuspend user. Please try again.');
            }
        };
        
        xhr.onerror = function() {
            alert('Failed to unsuspend user. Please try again.');
        };
        
        // Send the unsuspend request
        const params = new URLSearchParams({
            'user_id': userId,
            'action': 'unsuspend'
        });
        xhr.send(params.toString());
    }
}

function handleBanSubmit(event) {
    // Prevent any default behavior
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }
    
    const formData = new FormData(document.getElementById('banForm'));
    const userId = formData.get('user_id');
    const reason = formData.get('reason');
    
    // Create and submit form via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/customers.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // This identifies it as AJAX
    
    xhr.onload = function() {
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to ban user: ' + response.message);
            }
        } catch (e) {
            alert('Failed to ban user. Please try again.');
        }
    };
    
    xhr.onerror = function() {
        alert('Failed to ban user. Please try again.');
    };
    
    // Send the form data
    const params = new URLSearchParams(formData);
    xhr.send(params.toString());
    
    // Close modal immediately
    closeBanModal();
    
    // Prevent any form submission
    return false;
}

function handleSuspendSubmit(event) {
    // Prevent any default behavior
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }
    
    const formData = new FormData(document.getElementById('suspendForm'));
    const userId = formData.get('user_id');
    const reason = formData.get('reason');
    const duration = formData.get('duration');
    
    // Create and submit form via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/customers.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // This identifies it as AJAX
    
    xhr.onload = function() {
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to suspend user: ' + response.message);
            }
        } catch (e) {
            alert('Failed to suspend user. Please try again.');
        }
    };
    
    xhr.onerror = function() {
        alert('Failed to suspend user. Please try again.');
    };
    
    // Send the form data
    const params = new URLSearchParams(formData);
    xhr.send(params.toString());
    
    // Close modal immediately
    closeSuspendModal();
    
    // Prevent any form submission
    return false;
}

// Search functionality
document.getElementById('customerSearch').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('#customerTableBody tr').forEach(row => {
        const username = row.getAttribute('data-username');
        const email = row.getAttribute('data-email');
        if (username.includes(search) || email.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// DOM ready event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add form submission prevention as backup
    const banForm = document.getElementById('banForm');
    const suspendForm = document.getElementById('suspendForm');
    
    if (banForm) {
        banForm.addEventListener('submit', function(event) {
            event.preventDefault();
            event.stopPropagation();
            return false;
        });
    }
    
    if (suspendForm) {
        suspendForm.addEventListener('submit', function(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            return false;
        });
    }
    
    // Add click listeners to modal backgrounds
    const banModal = document.getElementById('banModal');
    const suspendModal = document.getElementById('suspendModal');
    
    if (banModal) {
        banModal.addEventListener('click', function(event) {
            if (event.target === banModal) {
                closeBanModal();
            }
        });
    }
    
    if (suspendModal) {
        suspendModal.addEventListener('click', function(event) {
            if (event.target === suspendModal) {
                closeSuspendModal();
            }
        });
    }
});
</script>

<div class="admin-container">
    <div class="admin-header brass-panel">
        <div class="admin-header-flex">
            <h1 class="gears-title">
                <span style="font-size:2.2rem;">👥</span> User Management
            </h1>
                    </div>
    </div>

    <?php displayFlashMessage(); ?>

    <div class="search-bar brass-panel">
        <form method="GET" class="filter-form" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:center;">
            <input type="text" id="customerSearch" placeholder="Search users..." class="gear-input" style="width:200px;">
            
            <button type="button" class="piston-button small" onclick="this.form.submit()">Filter</button>
            <a href="/admin/customers.php" class="piston-button small secondary">Clear</a>
        </form>
    </div>

    <div class="admin-table-container brass-panel">
        <form method="GET" style="margin:0;">
        <table class="steam-table">
            <thead>
                <tr class="filter-row">
                    <th>
                        <input type="number" name="limit" placeholder="Limit" class="gear-input filter-input" value="<?= htmlspecialchars($filters['limit'] ?? '') ?>" style="width:60px;">
                    </th>
                    <th>
                        <input type="text" name="search" placeholder="Search..." class="gear-input filter-input" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width:100px;">
                    </th>
                    <th>
                        <input type="text" name="email_search" placeholder="Search email..." class="gear-input filter-input" value="<?= htmlspecialchars($_GET['email_search'] ?? '') ?>" style="width:120px;">
                    </th>
                    <th>
                        <select name="role" class="gear-input filter-input" style="width:100px;">
                            <option value="">All Roles</option>
                            <option value="customer" <?= ($filters['role'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="seller" <?= ($filters['role'] ?? '') === 'seller' ? 'selected' : '' ?>>Seller</option>
                        </select>
                    </th>
                    <th>
                        <select name="status" class="gear-input filter-input" style="width:100px;">
                            <option value="">All Status</option>
                            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="banned" <?= ($filters['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Banned</option>
                            <option value="suspended" <?= ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </th>
                    <th>
                        <select name="orderBy" class="gear-input filter-input" style="width:100px;">
                            <option value="created_at" <?= ($filters['orderBy'] ?? '') === 'created_at' ? 'selected' : '' ?>>Date</option>
                            <option value="username" <?= ($filters['orderBy'] ?? '') === 'username' ? 'selected' : '' ?>>Username</option>
                            <option value="email" <?= ($filters['orderBy'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                            <option value="role" <?= ($filters['orderBy'] ?? '') === 'role' ? 'selected' : '' ?>>Role</option>
                        </select>
                    </th>
                    <th>
                        <select name="orderDirection" class="gear-input filter-input" style="width:80px;">
                            <option value="DESC" <?= ($filters['orderDirection'] ?? '') === 'DESC' ? 'selected' : '' ?>>↓</option>
                            <option value="ASC" <?= ($filters['orderDirection'] ?? '') === 'ASC' ? 'selected' : '' ?>>↑</option>
                        </select>
                        <button type="button" class="piston-button small" style="margin-left:5px;" onclick="this.form.submit()">Go</button>
                    </th>
                </tr>
                <tr class="header-row">
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customerTableBody">
                <?php foreach ($users as $user): ?>
                <tr data-username="<?= strtolower(htmlspecialchars($user['username'])) ?>" data-email="<?= strtolower(htmlspecialchars($user['email'])) ?>">
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                    <td>
                        <span class="status-badge status-<?= $user['status'] ?>">
                            <?= ucfirst($user['status'] ?? 'active') ?>
                        </span>
                        <?php if ($user['status'] === 'suspended' && $user['suspension_ends']): ?>
                            <br><small>Ends: <?= date('M j, Y', strtotime($user['suspension_ends'])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($user['status'] === 'active'): ?>
                                <button type="button" class="piston-button small danger" onclick="event.preventDefault(); showBanModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">Ban</button>
                                <button type="button" class="piston-button small warning" onclick="event.preventDefault(); showSuspendModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">Suspend</button>
                            <?php elseif ($user['status'] === 'banned'): ?>
                                <button type="button" class="piston-button small success" onclick="event.preventDefault(); unbanUser(<?= $user['id'] ?>)">Unban</button>
                            <?php elseif ($user['status'] === 'suspended'): ?>
                                <button type="button" class="piston-button small success" onclick="event.preventDefault(); unsuspendUser(<?= $user['id'] ?>)">Unsuspend</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </form>
    </div>
</div>

<!-- Ban Modal -->
<div id="banModal" class="modal" style="display:none;">
    <div class="modal-content" onclick="event.stopPropagation(); console.log('Ban modal content clicked');">
        <h3>Ban User</h3>
        <p>Are you sure you want to ban <strong id="banUsername"></strong>?</p>
        <form method="POST" id="banForm">
            <input type="hidden" name="user_id" id="banUserId">
            <input type="hidden" name="action" value="ban">
            <div class="form-group">
                <label for="banReason">Reason (optional):</label>
                <textarea name="reason" id="banReason" rows="3"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="piston-button danger" onclick="handleBanSubmit(event)">Ban User</button>
                <button type="button" class="piston-button secondary" onclick="closeBanModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Suspend Modal -->
<div id="suspendModal" class="modal" style="display:none;">
    <div class="modal-content" onclick="event.stopPropagation(); console.log('Suspend modal content clicked');">
        <h3>Suspend User</h3>
        <p>Are you sure you want to suspend <strong id="suspendUsername"></strong>?</p>
        <form method="POST" id="suspendForm">
            <input type="hidden" name="user_id" id="suspendUserId">
            <input type="hidden" name="action" value="suspend">
            <div class="form-group">
                <label for="suspendDuration">Duration (days):</label>
                <input type="number" name="duration" id="suspendDuration" value="7" min="1" max="365">
            </div>
            <div class="form-group">
                <label for="suspendReason">Reason (optional):</label>
                <textarea name="reason" id="suspendReason" rows="3"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="piston-button warning" onclick="handleSuspendSubmit(event)">Suspend User</button>
                <button type="button" class="piston-button secondary" onclick="closeSuspendModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #ffffff;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 90%;
    color: #1f2937;
}

.modal h3 {
    margin-top: 0;
    margin-bottom: 16px;
    color: #2563eb;
    font-size: 1.25rem;
    font-weight: 600;
}

.modal p {
    margin-bottom: 20px;
    color: #6b7280;
}

.form-group {
    margin: 20px 0;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background-color: #ffffff;
    color: #1f2937;
    font-family: inherit;
    font-size: 0.95rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
    display: inline-block;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-banned {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-suspended {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.piston-button.small {
    padding: 8px 16px;
    font-size: 0.875rem;
    background-color: #2563eb;
    border: 1px solid #2563eb;
    color: #ffffff;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.piston-button.small:hover {
    background-color: #1d4ed8;
    border-color: #1d4ed8;
    transform: translateY(-1px);
}

.piston-button.danger {
    background-color: #ef4444;
    border-color: #ef4444;
}

.piston-button.danger:hover {
    background-color: #dc2626;
    border-color: #dc2626;
}

.piston-button.warning {
    background-color: #f59e0b;
    border-color: #f59e0b;
    color: #ffffff;
}

.piston-button.warning:hover {
    background-color: #d97706;
    border-color: #d97706;
}

.piston-button.success {
    background-color: #10b981;
    border-color: #10b981;
}

.piston-button.success:hover {
    background-color: #059669;
    border-color: #059669;
}

.piston-button.secondary {
    background-color: #ffffff;
    border-color: #d1d5db;
    color: #6b7280;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    cursor: pointer;
}

.piston-button.secondary:hover {
    background-color: #f9fafb;
    border-color: #9ca3af;
    color: #374151;
}

.filter-form {
    margin: 10px 0;
}

.filter-form .gear-input {
    padding: 6px 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background-color: #ffffff;
    color: #495057;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.filter-form .gear-input:focus {
    border-color: #007bff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.filter-row {
    background-color: #f8f9fa;
    border-bottom: 2px solid #007bff;
}

.filter-row th {
    padding: 8px;
    vertical-align: middle;
}

.filter-input {
    width: 100%;
    padding: 4px 6px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background-color: #ffffff;
    color: #495057;
    font-size: 0.85rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.filter-input:focus {
    border-color: #007bff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.header-row {
    background-color: #007bff;
    color: #ffffff;
}

.header-row th {
    padding: 10px 8px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 0.9rem;
}

.piston-button.secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #ffffff;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    cursor: pointer;
}

.piston-button.secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
}
</style>

<?php require_once __DIR__ . '/../../src/views/partials/footer.php'; ?>
