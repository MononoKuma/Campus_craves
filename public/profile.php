<?php
session_start();
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/controllers/AuthController.php';
require_once __DIR__ . '/src/controllers/VerificationController.php';
require_once __DIR__ . '/src/models/Users.php';

// Check if user is logged in
$authController = new AuthController();
if (!$authController->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$verificationController = new VerificationController();
$userModel = new User();
$userId = $_SESSION['user_id'];
$user = $userModel->getUserById($userId);
$verificationStatus = $verificationController->getVerificationStatus($userId);
$userAllergens = $userModel->getUserAllergens($userId);

$successMessage = '';
$errorMessages = [];

// Handle student ID upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload_student_id' && isset($_FILES['student_id'])) {
        $result = $verificationController->uploadStudentId($userId, $_FILES['student_id']);
        if ($result['success']) {
            $successMessage = $result['message'];
            $verificationStatus = $verificationController->getVerificationStatus($userId);
        } else {
            $errorMessages = $result['errors'];
        }
    } elseif ($_POST['action'] === 'apply_seller' && isset($_POST['seller_reason'])) {
        $result = $verificationController->applyForSeller($userId, $_POST['seller_reason']);
        if ($result['success']) {
            $successMessage = $result['message'];
            $verificationStatus = $verificationController->getVerificationStatus($userId);
        } else {
            $errorMessages[] = $result['error'];
        }
    } elseif ($_POST['action'] === 'update_allergens') {
        $allergens = isset($_POST['allergens']) ? $_POST['allergens'] : [];
        if ($userModel->updateAllergens($userId, $allergens)) {
            $successMessage = "Your allergen preferences have been updated successfully.";
            $user = $userModel->getUserById($userId); // Refresh user data
            $userAllergens = $userModel->getUserAllergens($userId); // Refresh allergens data
        } else {
            $errorMessages[] = "Failed to update allergen preferences. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Steampunk Construction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="modern-theme">
    <?php include __DIR__ . '/../src/views/partials/header.php'; ?>
    
    <main class="profile-main">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-placeholder">
                        <?php echo strtoupper(substr(htmlspecialchars($user['first_name']), 0, 1) . substr(htmlspecialchars($user['last_name']), 0, 1)); ?>
                    </div>
                </div>
                <div class="profile-user-info">
                    <h1 class="profile-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <p class="profile-role"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <div class="profile-actions">
                    <button class="edit-profile-btn">Edit Profile</button>
                </div>
            </div>

            <!-- Alerts Section -->
            <?php if ($successMessage || !empty($errorMessages)): ?>
                <div class="alerts-section">
                    <?php if ($successMessage): ?>
                        <div class="steam-alert success"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errorMessages)): ?>
                        <?php foreach ($errorMessages as $error): ?>
                            <div class="steam-alert error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Main Content Grid -->
            <div class="profile-grid">
                <!-- Dietary Preferences Card -->
                <div class="profile-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                                <path d="M12 8v4"/>
                                <path d="M12 16h.01"/>
                            </svg>
                        </div>
                        <h2 class="card-title">Dietary Preferences</h2>
                    </div>
                    <div class="card-content">
                        <p class="card-description">Manage your allergen preferences for personalized recommendations.</p>
                        
                        <form method="post" class="modern-form">
                            <input type="hidden" name="action" value="update_allergens">
                            <div class="form-group">
                                <label class="checkbox-main-label">Select your allergens:</label>
                                <div class="allergen-checkboxes compact">
                                    <?php 
                                    $allergenOptions = [
                                        'nuts' => 'Nuts',
                                        'dairy' => 'Dairy', 
                                        'gluten' => 'Gluten',
                                        'eggs' => 'Eggs',
                                        'soy' => 'Soy',
                                        'shellfish' => 'Shellfish',
                                        'sesame' => 'Sesame'
                                    ];
                                    ?>
                                    <?php foreach ($allergenOptions as $value => $label): ?>
                                        <label class="checkbox-label modern">
                                            <input type="checkbox" name="allergens[]" value="<?php echo htmlspecialchars($value); ?>"
                                                   <?php echo in_array($value, $userAllergens) ? 'checked' : ''; ?>>
                                            <span class="checkmark"></span>
                                            <?php echo htmlspecialchars($label); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button type="submit" class="modern-button primary">Update Preferences</button>
                        </form>
                        
                        <?php if (!empty($userAllergens)): ?>
                            <div class="current-allergens compact">
                                <h4>Your Current Allergens:</h4>
                                <div class="allergen-tags">
                                    <?php foreach ($userAllergens as $allergen): ?>
                                        <span class="allergen-tag">
                                            <?php echo htmlspecialchars(ucfirst($allergen)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-allergens compact">
                                <p><em>No allergens specified</em></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Student Verification Card -->
                <div class="profile-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            </svg>
                        </div>
                        <h2 class="card-title">Student Verification</h2>
                    </div>
                    <div class="card-content">
                        <?php if ($verificationStatus['student_verification_status'] === null): ?>
                            <p class="card-description">Upload your student ID to get verified and access exclusive benefits.</p>
                            <form method="post" enctype="multipart/form-data" class="modern-form compact">
                                <input type="hidden" name="action" value="upload_student_id">
                                <div class="form-group">
                                    <label for="student_id" class="file-label">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="17 8 12 3 7 8"/>
                                            <line x1="12" y1="3" x2="12" y2="15"/>
                                        </svg>
                                        Choose Student ID
                                        <input type="file" id="student_id" name="student_id" required accept="image/jpeg,image/png">
                                    </label>
                                </div>
                                <button type="submit" class="modern-button primary full-width">Upload Student ID</button>
                            </form>
                        <?php elseif ($verificationStatus['student_verification_status'] === 'pending'): ?>
                            <div class="verification-status compact pending">
                                <div class="status-header">
                                    <span class="status-badge pending">Pending Verification</span>
                                    <p>Your student ID is being reviewed</p>
                                </div>
                                <?php if ($verificationStatus['student_id_image']): ?>
                                    <a href="<?php echo htmlspecialchars($verificationStatus['student_id_image']); ?>" target="_blank" class="view-link">View Uploaded ID</a>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($verificationStatus['student_verification_status'] === 'verified'): ?>
                            <div class="verification-status compact verified">
                                <div class="status-header">
                                    <span class="status-badge verified">Verified Student</span>
                                    <p>Congratulations! Your student status is verified</p>
                                </div>
                                <p class="verified-date">Verified on <?php echo date('F j, Y', strtotime($verificationStatus['verified_at'])); ?></p>
                            </div>
                        <?php elseif ($verificationStatus['student_verification_status'] === 'rejected'): ?>
                            <div class="verification-status compact rejected">
                                <div class="status-header">
                                    <span class="status-badge rejected">Verification Rejected</span>
                                    <p>Your verification was rejected</p>
                                </div>
                                <?php if ($verificationStatus['verification_rejection_reason']): ?>
                                    <p class="rejection-reason"><strong>Reason:</strong> <?php echo htmlspecialchars($verificationStatus['verification_rejection_reason']); ?></p>
                                <?php endif; ?>
                                <form method="post" enctype="multipart/form-data" class="modern-form compact">
                                    <input type="hidden" name="action" value="upload_student_id">
                                    <div class="form-group">
                                        <label for="student_id" class="file-label">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="17 8 12 3 7 8"/>
                                                <line x1="12" y1="3" x2="12" y2="15"/>
                                            </svg>
                                            Re-upload Student ID
                                            <input type="file" id="student_id" name="student_id" required accept="image/jpeg,image/png">
                                        </label>
                                    </div>
                                    <button type="submit" class="modern-button primary full-width">Re-upload Student ID</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Seller Application Card -->
                <div class="profile-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                        </div>
                        <h2 class="card-title">Seller Status</h2>
                    </div>
                    <div class="card-content">
                        <?php if ($user['role'] === 'seller'): ?>
                            <div class="verification-status compact verified">
                                <div class="status-header">
                                    <span class="status-badge verified">Active Seller</span>
                                    <p>You are an active seller on our platform</p>
                                </div>
                                <?php if ($verificationStatus['became_seller_at']): ?>
                                    <p class="verified-date">Seller since <?php echo date('F j, Y', strtotime($verificationStatus['became_seller_at'])); ?></p>
                                <?php endif; ?>
                                <a href="seller-dashboard.php" class="modern-button primary full-width">Go to Seller Dashboard</a>
                            </div>
                        <?php elseif ($verificationStatus['seller_application_status'] === null): ?>
                            <p class="card-description">Become a seller and share your steampunk products with our community.</p>
                            
                            <?php if ($verificationStatus['student_verification_status'] !== 'verified'): ?>
                                <div class="verification-requirement">
                                    <div class="requirement-notice">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="12" y1="8" x2="12" y2="12"/>
                                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                                        </svg>
                                        <div>
                                            <strong>Student Verification Required</strong>
                                            <p>You must be a verified student before applying for seller status.</p>
                                        </div>
                                    </div>
                                    <?php if ($verificationStatus['student_verification_status'] === null): ?>
                                        <p class="verification-prompt">Please upload your student ID above to get verified first.</p>
                                    <?php elseif ($verificationStatus['student_verification_status'] === 'pending'): ?>
                                        <p class="verification-pending">Your student verification is pending review.</p>
                                    <?php elseif ($verificationStatus['student_verification_status'] === 'rejected'): ?>
                                        <p class="verification-rejected">Your student verification was rejected. Please re-upload your ID.</p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <form method="post" class="modern-form compact">
                                    <input type="hidden" name="action" value="apply_seller">
                                    <div class="form-group">
                                        <label for="seller_reason" class="textarea-label">Why do you want to become a seller?</label>
                                        <textarea id="seller_reason" name="seller_reason" class="modern-input" rows="3" required placeholder="Tell us about your interest in selling steampunk products..."></textarea>
                                    </div>
                                    <button type="submit" class="modern-button primary full-width">Apply to Become Seller</button>
                                </form>
                            <?php endif; ?>
                        <?php elseif ($verificationStatus['seller_application_status'] === 'pending'): ?>
                            <div class="verification-status compact pending">
                                <div class="status-header">
                                    <span class="status-badge pending">Application Pending</span>
                                    <p>Your seller application is being reviewed</p>
                                </div>
                                <p class="applied-date">Applied on <?php echo date('F j, Y', strtotime($verificationStatus['applied_for_seller_at'])); ?></p>
                                <?php if ($verificationStatus['seller_application_reason']): ?>
                                    <p class="application-reason"><strong>Your reason:</strong> <?php echo htmlspecialchars($verificationStatus['seller_application_reason']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($verificationStatus['seller_application_status'] === 'rejected'): ?>
                            <div class="verification-status compact rejected">
                                <div class="status-header">
                                    <span class="status-badge rejected">Application Rejected</span>
                                    <p>Your seller application was rejected</p>
                                </div>
                                <?php if ($verificationStatus['seller_rejection_reason']): ?>
                                    <p class="rejection-reason"><strong>Reason:</strong> <?php echo htmlspecialchars($verificationStatus['seller_rejection_reason']); ?></p>
                                <?php endif; ?>
                                <form method="post" class="modern-form compact">
                                    <input type="hidden" name="action" value="apply_seller">
                                    <div class="form-group">
                                        <label for="seller_reason" class="textarea-label">Re-apply with new reason:</label>
                                        <textarea id="seller_reason" name="seller_reason" class="modern-input" rows="3" required placeholder="Tell us why you should be reconsidered..."></textarea>
                                    </div>
                                    <button type="submit" class="modern-button primary full-width">Re-apply to Become Seller</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../src/views/partials/footer.php'; ?>

    <style>
        /* Modern Profile Layout */
        .profile-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 200px);
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding: 2rem;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            border: 1px solid var(--medium-gray);
        }

        .profile-avatar {
            flex-shrink: 0;
        }

        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .profile-user-info {
            flex: 1;
        }

        .profile-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
        }

        .profile-role {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-blue);
            margin: 0 0 0.25rem 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-email {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin: 0 0 0.25rem 0;
        }

        .profile-username {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin: 0;
            opacity: 0.8;
        }

        .profile-actions {
            flex-shrink: 0;
        }

        .edit-profile-btn {
            padding: 0.75rem 1.5rem;
            background: var(--white);
            color: var(--primary-blue);
            border: 2px solid var(--primary-blue);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .edit-profile-btn:hover {
            background: var(--primary-blue);
            color: white;
        }

        /* Alerts Section */
        .alerts-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Profile Grid */
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            flex: 1;
        }

        /* Profile Cards */
        .profile-card {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid var(--medium-gray);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            height: fit-content;
        }

        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-bottom: 1px solid var(--medium-gray);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .card-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .card-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }

        /* Compact Form Styles */
        .modern-form.compact {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .textarea-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .modern-input {
            padding: 0.75rem;
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s ease;
            background: var(--white);
        }

        .modern-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* File Upload Styles */
        .file-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 2px dashed var(--medium-gray);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .file-label:hover {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
        }

        .file-label input[type="file"] {
            display: none;
        }

        /* Button Styles */
        .modern-button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .modern-button.primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
        }

        .modern-button.primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .modern-button.full-width {
            width: 100%;
            box-sizing: border-box;
        }

        /* Compact Checkbox Styles */
        .allergen-checkboxes.compact {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.5rem;
        }

        .checkbox-label.modern {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border: 1px solid var(--medium-gray);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }

        .checkbox-label.modern:hover {
            background: rgba(37, 99, 235, 0.05);
            border-color: var(--primary-blue);
        }

        .checkbox-label.modern input[type="checkbox"] {
            margin-right: 0.5rem;
            width: 16px;
            height: 16px;
        }

        /* Compact Status Styles */
        .verification-status.compact {
            padding: 1rem;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .verification-status.compact.pending {
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid #fbbf24;
        }

        .verification-status.compact.verified {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid #22c55e;
        }

        .verification-status.compact.rejected {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
        }

        .status-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.pending {
            background: #fbbf24;
            color: #92400e;
        }

        .status-badge.verified {
            background: #22c55e;
            color: #166534;
        }

        .status-badge.rejected {
            background: #ef4444;
            color: #991b1b;
        }

        .status-header p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .view-link {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .view-link:hover {
            text-decoration: underline;
        }

        .verified-date,
        .applied-date {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .rejection-reason,
        .application-reason {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.4;
        }

        /* Compact Allergen Display */
        .current-allergens.compact,
        .no-allergens.compact {
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .current-allergens.compact {
            background: rgba(37, 99, 235, 0.05);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .current-allergens.compact h4 {
            margin: 0 0 0.5rem 0;
            color: var(--primary-blue);
            font-size: 0.9rem;
        }

        .no-allergens.compact {
            background: rgba(156, 163, 175, 0.1);
            text-align: center;
            color: var(--text-secondary);
        }

        .allergen-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .allergen-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--primary-blue);
            color: white;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .checkbox-main-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        /* Verification Requirement Styles */
        .verification-requirement {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .requirement-notice {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid #fbbf24;
            border-radius: 8px;
            color: #92400e;
        }

        .requirement-notice svg {
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .requirement-notice div {
            flex: 1;
        }

        .requirement-notice strong {
            display: block;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .requirement-notice p {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .verification-prompt,
        .verification-pending,
        .verification-rejected {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin: 0;
            padding: 0.75rem;
            border-radius: 8px;
            background: rgba(156, 163, 175, 0.1);
            text-align: center;
        }

        .verification-pending {
            background: rgba(251, 191, 36, 0.1);
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .verification-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .profile-grid {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .profile-main {
                padding: 1rem;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .profile-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .profile-title {
                font-size: 1.5rem;
            }

            .allergen-checkboxes.compact {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }
        }

        @media (min-width: 1400px) {
            .profile-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</body>
</html>
