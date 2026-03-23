<?php
session_start();
require_once __DIR__ . './../src/config/database.php';
require_once __DIR__ . './../src/views/partials/header.php';
require_once __DIR__ . '/../src/helpers/functions.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    Delicious Food, <br>
                    <span class="gradient-text">Delivered Fast</span>
                </h1>
                <p class="hero-description">
                    Connect with fellow students and discover amazing homemade meals, snacks, and treats right here on campus. Order now and satisfy your cravings!
                </p>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Active Sellers</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">10K+</span>
                        <span class="stat-label">Happy Customers</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">4.8★</span>
                        <span class="stat-label">Average Rating</span>
                    </div>
                </div>
                <div class="hero-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="hero-button primary">Browse Menu</a>
                        <a href="profile.php" class="hero-button secondary">My Profile</a>
                    <?php else: ?>
                        <a href="register.php" class="hero-button primary">Get Started</a>
                        <a href="login.php" class="hero-button secondary">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-image-container">
                    <div class="floating-card card-1">
                        <div class="card-icon">🍕</div>
                        <div class="card-text">Fresh Pizza</div>
                    </div>
                    <div class="floating-card card-2">
                        <div class="card-icon">🥗</div>
                        <div class="card-text">Healthy Salads</div>
                    </div>
                    <div class="floating-card card-3">
                        <div class="card-icon">🍰</div>
                        <div class="card-text">Sweet Treats</div>
                    </div>
                    <div class="floating-card card-4">
                        <div class="card-icon">☕</div>
                        <div class="card-text">Coffee & More</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Why Choose Campus Craves?</h2>
            <p class="section-subtitle">The best food marketplace designed exclusively for students</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Student Prices</h3>
                <p>Affordable options that fit your budget. No more expensive delivery fees or markups.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Community Driven</h3>
                <p>Support fellow student entrepreneurs and discover homemade meals with love.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⭐</div>
                <h3>Quality Assured</h3>
                <p>Every seller is verified and rated. Enjoy only the best food from trusted sources.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="how-it-works">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">Get your favorite food in three simple steps</p>
        </div>
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Browse Menu</h3>
                    <p>Explore delicious options from student sellers near you</p>
                </div>
            </div>
            <div class="step-connector"></div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Place Order</h3>
                    <p>Choose your items and pay securely through our platform</p>
                </div>
            </div>
            <div class="step-connector"></div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Enjoy!</h3>
                    <p>Pick up your order or get it delivered right to your dorm</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Satisfy Your Cravings?</h2>
            <p>Join thousands of students already enjoying Campus Craves</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="cta-button">Start Ordering</a>
            <?php else: ?>
                <a href="register.php" class="cta-button">Join Now - It's Free!</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../src/views/partials/footer.php'; ?>