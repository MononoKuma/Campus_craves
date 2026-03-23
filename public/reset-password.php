<?php
require_once __DIR__ . '/src/helpers/functions.php';
require_once __DIR__ . '/src/controllers/AuthController.php';

$auth = new AuthController();
$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->resetPassword($token, $password);
        if ($result['success']) {
            $success = true;
        } else {
            $error = $result['error'];
        }
    }
} elseif (empty($token)) {
    header('Location: /forgot-password.php');
    exit();
}
?>

<?php require_once __DIR__ . '/src/views/partials/header.php'; ?>

<div class="brass-panel password-reset-container">
    <?php if ($success): ?>
        <h1 class="gears-title">Password Reset</h1>
        <div class="steam-alert success">
            Your password has been updated successfully!
            <p><a href="/login.php">Login with your new password</a></p>
        </div>
    <?php else: ?>
        <h1 class="gears-title">Set New Password</h1>
        
        <?php if ($error): ?>
            <div class="steam-alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="steam-form">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required class="gear-input">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="gear-input">
            </div>
            
            <button type="submit" class="piston-button">Reset Password</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/src/views/partials/footer.php'; ?>