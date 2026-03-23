<?php
require_once __DIR__ . '/../../../src/helpers/functions.php';
require_once __DIR__ . '/../../../src/controllers/AdminController.php';

if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$adminController = new AdminController();
$userModel = new User();

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: /admin/customers.php');
    exit();
}

$user = $userModel->getUserById($userId);
if (!$user) {
    header('Location: /admin/customers.php');
    exit();
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $updateData = [
        'username' => $username,
        'email' => $email,
        'role' => $role
    ];
    // If password is set, update it
    if (!empty($_POST['password'])) {
        $updateData['password'] = $_POST['password'];
    }
    if ($userModel->updateUser($userId, $updateData)) {
        $success = true;
        // Refresh user data
        $user = $userModel->getUserById($userId);
    } else {
        $error = 'Failed to update user.';
    }
}
?>
<?php require_once __DIR__ . '/../../../src/views/partials/header.php'; ?>
<div class="admin-container">
    <div class="admin-header brass-panel">
        <h1 class="gears-title">✏️ Edit User</h1>
        <a href="/admin/customers.php" class="steam-btn">Back to Customers</a>
    </div>
    <div class="brass-panel">
        <?php if ($success): ?>
            <script>
                alert('User updated successfully!');
                window.location.href = '/admin/customers.php';
            </script>
        <?php elseif ($error): ?>
            <script>
                alert('<?= htmlspecialchars($error) ?>');
                window.location.href = '/admin/customers.php';
            </script>
        <?php endif; ?>
        <form method="POST" class="steam-form">
            <div class="form-group">
                <label for="username">Username:</label>
                <input id="username" type="text" name="username" class="gear-input" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input id="email" type="email" name="email" class="gear-input" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" class="gear-input">
                    <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="password">New Password (leave blank to keep current):</label>
                <input id="password" type="password" name="password" class="gear-input">
            </div>
            <button type="submit" class="piston-button">Update User</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../../src/views/partials/footer.php'; ?> 