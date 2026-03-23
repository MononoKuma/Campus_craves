<?php
// Start session at the very beginning
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';

$auth = new AuthController();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        $result = $auth->sendPasswordReset($email);
        if ($result['success']) {
            $message = 'If an account exists with this email, a password reset link has been sent.';
        } else {
            $error = $result['error'] ?? 'An error occurred';
        }
    }
}
?>

<?php require_once __DIR__ . '/../src/views/partials/header.php'; ?>

<div class="brass-panel password-reset-container">
    <h1 class="gears-title">Reset Your Password</h1>
    
    <?php if ($message): ?>
        <div class="steam-alert success">
            <?= htmlspecialchars($message) ?>
            <p><a href="/login.php">Return to Login</a></p>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="steam-alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="steam-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required class="gear-input" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <button type="submit" class="piston-button">Send Reset Link</button>
        </form>
        
        <div class="steam-links">
            <a href="/login.php" class="copper-link">Remember your password? Login</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/views/partials/footer.php'; ?>