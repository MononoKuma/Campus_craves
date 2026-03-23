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
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Form submission received: " . print_r($_POST, true));
    error_log("User ID from session: " . ($userId ?? 'NULL'));
    
    $data = [
        'complainant_id' => $userId,
        'respondent_id' => $_POST['respondent_id'] ?? null,
        'complaint_type' => $_POST['complaint_type'] ?? null,
        'subject' => $_POST['subject'] ?? null,
        'description' => $_POST['description'] ?? null,
        'order_id' => !empty($_POST['order_id']) ? $_POST['order_id'] : null,
        'product_id' => !empty($_POST['product_id']) ? $_POST['product_id'] : null
    ];

    error_log("Data being sent to controller: " . print_r($data, true));

    $result = $complaintController->createComplaint($data);
    
    error_log("Controller result: " . print_r($result, true));
    
    if ($result['success']) {
        $success = $result['message'];
        // Clear form data
        $_POST = [];
    } else {
        $error = $result['message'];
        error_log("Complaint submission error: " . $error);
    }
}

// Get data for form dropdowns
$availableRespondents = $complaintController->getAvailableRespondents($userId);
$userOrders = $complaintController->getUserOrders($userId);
$complaintTypes = $complaintController->getComplaintTypes();

// Get pre-filled data from URL parameters if provided
$preselectedRespondent = $_GET['respondent'] ?? null;
$preselectedProduct = $_GET['product'] ?? null;
$preselectedOrder = $_GET['order'] ?? null;
$preselectedType = $_GET['type'] ?? null;

// If product is preselected, get product details and seller info
$productDetails = null;
if ($preselectedProduct) {
    require_once __DIR__ . '/src/models/Product.php';
    $productModel = new Product();
    $productDetails = $productModel->getProductById($preselectedProduct);
    if ($productDetails && $productDetails['seller_id']) {
        $preselectedRespondent = $productDetails['seller_id'];
    }
}
?>

<?php require_once __DIR__ . '/src/views/partials/header.php'; ?>

<main class="complaint-main">
    <div class="complaint-container">
        <div class="complaint-header">
            <h1>File a Complaint</h1>
            <p>Report issues with products, services, or other concerns</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <?= htmlspecialchars($success) ?>
                <br><br>
                <a href="/my-complaints.php" class="btn btn-primary">View Your Complaints</a>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <div class="complaint-form-container">
            <form method="POST" class="complaint-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="complaint_type">Complaint Type *</label>
                        <select name="complaint_type" id="complaint_type" class="form-control" required>
                            <option value="">Select complaint type</option>
                            <?php foreach ($complaintTypes as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($preselectedType === $value) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="respondent_id">Report Against *</label>
                        <select name="respondent_id" id="respondent_id" class="form-control" required>
                            <option value="">Select who this complaint is about</option>
                            <?php foreach ($availableRespondents as $respondent): ?>
                                <option value="<?= $respondent['id'] ?>" 
                                        <?= ($preselectedRespondent == $respondent['id']) ? 'selected' : '' ?>
                                        data-role="<?= $respondent['role'] ?>">
                                    <?= htmlspecialchars($respondent['username']) ?>
                                    <?php if ($respondent['role'] === 'seller' && $respondent['store_name']): ?>
                                        (<?= htmlspecialchars($respondent['store_name']) ?>)
                                    <?php endif; ?>
                                    - <?= ucfirst($respondent['role']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="order_id">Related Order (Optional)</label>
                        <select name="order_id" id="order_id" class="form-control">
                            <option value="">Select order (if applicable)</option>
                            <?php foreach ($userOrders as $order): ?>
                                <option value="<?= $order['id'] ?>" 
                                        <?= ($preselectedOrder == $order['id']) ? 'selected' : '' ?>
                                        data-products="<?= htmlspecialchars($order['product_names'] ?? '') ?>">
                                    Order #<?= $order['id'] ?> - 
                                    <?= formatPrice($order['total_amount']) ?> -
                                    <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                    <?php if ($order['product_names']): ?>
                                        (<?= htmlspecialchars(substr($order['product_names'], 0, 50)) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="product_id">Related Product (Optional)</label>
                        <select name="product_id" id="product_id" class="form-control">
                            <option value="">Select product (if applicable)</option>
                            <?php if ($productDetails): ?>
                                <option value="<?= $productDetails['id'] ?>" selected>
                                    <?= htmlspecialchars($productDetails['name']) ?> - 
                                    <?= formatPrice($productDetails['price']) ?>
                                </option>
                            <?php endif; ?>
                        </select>
                        <small class="form-text">Products will be loaded based on selected order</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" name="subject" id="subject" class="form-control" 
                           value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                           placeholder="Brief summary of your complaint" required>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea name="description" id="description" class="form-control" rows="6" 
                              placeholder="Please provide detailed information about your complaint, including what happened, when it occurred, and any steps you've already taken to resolve it."
                              required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Complaint</button>
                    <a href="/dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="complaint-guidelines">
            <h3>Guidelines for Filing a Complaint</h3>
            <ul>
                <li>Be specific and provide as much detail as possible</li>
                <li>Include relevant dates, times, and order numbers</li>
                <li>Remain professional and respectful in your description</li>
                <li>Only file one complaint per issue - duplicate complaints will be rejected</li>
                <li>You'll receive updates on your complaint status via your dashboard</li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</main>

<style>
.complaint-main {
    flex: 1;
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: calc(100vh - 200px);
}

.complaint-container {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.complaint-header {
    text-align: center;
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
}

.complaint-header h1 {
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
}

.complaint-header p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 1.1rem;
}

.complaint-form-container {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
}

.complaint-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: var(--text-primary);
}

.form-control {
    padding: 0.75rem;
    border: 2px solid var(--medium-gray);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-blue);
}

.form-text {
    color: var(--text-secondary);
    font-size: 0.85rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1rem;
    border-top: 1px solid var(--medium-gray);
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
    justify-content: center;
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

.complaint-guidelines {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid var(--medium-gray);
}

.complaint-guidelines h3 {
    color: var(--text-primary);
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
}

.complaint-guidelines ul {
    margin: 0;
    padding-left: 1.5rem;
    color: var(--text-secondary);
    line-height: 1.6;
}

.complaint-guidelines li {
    margin-bottom: 0.5rem;
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
    .complaint-main {
        padding: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .complaint-header h1 {
        font-size: 1.5rem;
    }
}
</style>

<script>
// Dynamic product loading based on order selection
document.addEventListener('DOMContentLoaded', function() {
    const orderSelect = document.getElementById('order_id');
    const productSelect = document.getElementById('product_id');
    const respondentSelect = document.getElementById('respondent_id');
    
    // Store original product options
    const originalProductOptions = productSelect.innerHTML;
    
    orderSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const productsText = selectedOption.getAttribute('data-products');
        
        // Clear and reset product select
        productSelect.innerHTML = '<option value="">Select product (if applicable)</option>';
        
        if (productsText && productsText.trim()) {
            // Add products from the order
            const products = productsText.split(',').map(p => p.trim());
            products.forEach(product => {
                if (product) {
                    const option = document.createElement('option');
                    option.value = product; // This would need to be product ID in real implementation
                    option.textContent = product;
                    productSelect.appendChild(option);
                }
            });
        } else {
            // Restore original options
            productSelect.innerHTML = originalProductOptions;
        }
    });
    
    // Auto-select respondent based on complaint type
    const complaintTypeSelect = document.getElementById('complaint_type');
    complaintTypeSelect.addEventListener('change', function() {
        const complaintType = this.value;
        
        // If it's a product issue and we have a product preselected, try to find the seller
        if (complaintType === 'product_issue' && <?= $preselectedProduct ? 'true' : 'false' ?>) {
            // Look for seller in respondent options
            for (let option of respondentSelect.options) {
                if (option.value === <?= $preselectedRespondent ? $preselectedRespondent : 'null' ?>) {
                    respondentSelect.value = option.value;
                    break;
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/src/views/partials/footer.php'; ?>
