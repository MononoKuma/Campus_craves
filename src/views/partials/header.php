<?php
// Session should already be started in the calling page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Craves</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="//fonts.googleapis.com" crossorigin>
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="//fonts.gstatic.com" crossorigin>
</head>
<body class="modern-theme">
    <header class="modern-header">
        <nav class="header-nav">
            <div class="nav-container">
                <div class="nav-brand">
                    <a href="/" class="brand-logo">
                        <img src="/images/logo.png" alt="Campus Craves" class="logo-image">
                        <span class="logo-text">Campus Craves</span>
                    </a>
                </div>
                
                <div class="nav-menu">
                    <div class="nav-links">
                        <a href="/" class="nav-link">Home</a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="/admin/dashboard.php" class="nav-link">Admin</a>
                            <?php else: ?>
                                <a href="/dashboard.php" class="nav-link">Dashboard</a>
                                <a href="/profile.php" class="nav-link">Profile</a>
                            <?php endif; ?>
                            <a href="/logout.php" class="nav-link logout-link">Logout</a>
                        <?php else: ?>
                            <a href="/login.php" class="nav-link">Login</a>
                            <a href="/register.php" class="nav-link nav-cta">Get Started</a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </button>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="main-content">