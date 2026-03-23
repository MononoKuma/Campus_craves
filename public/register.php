<?php
require_once __DIR__ . '/src/controllers/AuthController.php';
require_once __DIR__ . '/src/helpers/functions.php';

$auth = new AuthController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->register($_POST);
    if ($result['success']) {
        header('Location: login.php');
        exit;
    } else {
        $errors = $result['errors'];
    }
}

require_once __DIR__ . '/src/views/partials/header.php';
?>

<script src="/js/register-form.js" defer></script>

<div class="modern-panel">
    <div class="register-header">
        <h1 class="modern-title">Create Your Account</h1>
        <p class="register-subtitle">Join Campus Craves and start your culinary journey</p>
        <div class="copper-divider"></div>
    </div>
    
    <form method="POST" class="modern-form register-form" id="registerForm">
        <div class="form-section">
            <h3 class="section-title">Personal Information</h3>
            <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required class="modern-input">
            </div>
            
            <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name" class="modern-input">
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required class="modern-input">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="birthday">Birthday *</label>
                <input type="date" id="birthday" name="birthday" required class="modern-input">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="(123) 456-7890" class="modern-input">
            </div>
        </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">Account Details</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required class="modern-input" placeholder="your.email@example.com">
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required class="modern-input" placeholder="Choose a unique username">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required class="modern-input" placeholder="Min. 8 characters">
                <small class="form-hint">Use a strong password with letters, numbers, and symbols</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="modern-input" placeholder="Re-enter your password">
            </div>
        </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">Dietary Preferences</h3>
            <div class="form-group">
                <label class="checkbox-main-label">Allergens (Please check any that apply to you)</label>
                <div class="allergen-checkboxes">
                <label class="checkbox-label">
                    <input type="checkbox" name="allergens[]" value="nuts"> Nuts
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="allergens[]" value="dairy"> Dairy
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="allergens[]" value="gluten"> Gluten
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="allergens[]" value="eggs"> Eggs
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="allergens[]" value="soy"> Soy
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="allergens[]" value="shellfish"> Shellfish
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="allergens[]" value="sesame"> Sesame
                </label>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="modern-button">Create Account</button>
            <p class="login-prompt">Already have an account? <a href="login.php" class="login-link">Sign in here</a></p>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/src/views/partials/footer.php'; ?>