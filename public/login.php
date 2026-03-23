<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/helpers/functions.php';

$auth = new AuthController();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->login($_POST['username'], $_POST['password']);
    if ($result['success']) {
        header('Location: dashboard.php');
        exit;
    } else {
        if ($result['error'] === 'banned') {
            $error = 'Your account has been banned. Reason: ' . htmlspecialchars($result['ban_reason'] ?: 'No reason provided');
        } elseif ($result['error'] === 'suspended') {
            $error = 'Your account is suspended. Reason: ' . htmlspecialchars($result['ban_reason'] ?: 'No reason provided') . '. Suspension ends: ' . date('M j, Y H:i', strtotime($result['suspension_ends']));
        } else {
            $error = $result['error'];
        }
    }
}

require_once dirname(__DIR__) . '/src/views/partials/header.php';
?>

<div class="login-container">
    <h1 class="modern-title">Login to Your Campus Craves Account</h1>
    <?php if ($error): ?>
        <div class="steam-alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" class="modern-form">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required class="modern-input">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required class="modern-input">
        </div>
        
        <button type="submit" class="modern-button">Login</button>
    </form>
    
    <div class="steam-links">
        <a href="forgot-password.php">Forgot Password?</a>
        <a href="register.php">Create New Account</a>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/src/views/partials/footer.php'; ?>