<?php
require_once __DIR__ . '/src/helpers/functions.php';
require_once __DIR__ . '/src/controllers/AuthController.php';

$authController = new AuthController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'middle_name' => sanitizeInput($_POST['middle_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'birthday' => sanitizeInput($_POST['birthday'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'password' => sanitizeInput($_POST['password'] ?? ''),
        'confirm_password' => sanitizeInput($_POST['confirm_password'] ?? ''),
        'role' => 'seller'
    ];
    
    $result = $authController->register($data);
    
    if ($result['success']) {
        setFlashMessage('Seller registration successful! You can now login.', 'success');
        redirect('/login.php');
    } else {
        $errors = $result['errors'];
    }
}
?>

<?php require_once __DIR__ . '/src/views/partials/header.php'; ?>

<div class="brass-panel register-seller">
    <h1 class="gears-title">⚙️ Become a Seller</h1>
    <div class="copper-divider"></div>
    
    <p class="intro-text">Join our marketplace of steampunk craftsmen and sell your unique creations!</p>

    <?php displayErrors($errors); ?>

    <form method="POST" class="steam-form">
        <div class="form-section">
            <h3><span class="gear-icon">👤</span> Personal Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required 
                           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" 
                           value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required 
                           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="birthday">Birthday *</label>
                    <input type="date" id="birthday" name="birthday" required 
                           value="<?= htmlspecialchars($_POST['birthday'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3><span class="gear-icon">📍</span> Contact Information</h3>
            <div class="form-group">
                <label for="address">Address *</label>
                <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>

        <div class="form-section">
            <h3><span class="gear-icon">🔐</span> Account Information</h3>
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <small>This will be your public seller name</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="steam-button primary">
                <span class="gear-icon">⚙️</span> Register as Seller
            </button>
            <a href="/register.php" class="steam-button secondary">Register as Customer</a>
            <a href="/login.php" class="steam-button secondary">Already have an account?</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/src/views/partials/footer.php'; ?>
