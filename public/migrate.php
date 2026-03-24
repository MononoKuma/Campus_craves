<?php
/**
 * Database Migration Script for Render Deployment
 * 
 * Deploy this file, then visit it ONCE in your browser:
 *   https://your-app.onrender.com/migrate.php
 * 
 * It will create any missing tables and insert default data.
 * After running successfully, you can delete this file for security.
 */

// Load database config
require_once __DIR__ . '/src/config/database.php';  // In Render container: /var/www/html/src/config/database.php

// Secret key protection - change this or remove after running
$secret = isset($_GET['key']) ? $_GET['key'] : '';
if ($secret !== 'campuscraves2026') {
    http_response_code(403);
    die('Access denied. Use ?key=campuscraves2026 to run migrations.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Campus Craves - Database Migration</h1>";
echo "<pre>";

try {
    $db = new Database();
    $conn = $db->connect();
    echo "✅ Database connected successfully.\n\n";

    // ── All migration SQL statements ──
    $migrations = [
        'system_settings table' => "
            CREATE TABLE IF NOT EXISTS system_settings (
                key VARCHAR(100) PRIMARY KEY,
                value TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ",
        'system_settings defaults' => "
            INSERT INTO system_settings (key, value) VALUES 
                ('store_visibility_mode', 'show_all'),
                ('maintenance_mode', 'false'),
                ('allow_registrations', 'true'),
                ('enable_reviews', 'true')
            ON CONFLICT (key) DO NOTHING;
        ",
        'reviews table' => "
            CREATE TABLE IF NOT EXISTS reviews (
                id SERIAL PRIMARY KEY,
                product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                title VARCHAR(255) NOT NULL,
                comment TEXT NOT NULL,
                verified_purchase BOOLEAN DEFAULT FALSE,
                helpful_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (product_id, user_id)
            );
        ",
        'review_helpful_votes table' => "
            CREATE TABLE IF NOT EXISTS review_helpful_votes (
                id SERIAL PRIMARY KEY,
                review_id INT NOT NULL REFERENCES reviews(id) ON DELETE CASCADE,
                user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (review_id, user_id)
            );
        ",
        'cart_items table' => "
            CREATE TABLE IF NOT EXISTS cart_items (
                id SERIAL PRIMARY KEY,
                user_id INT NULL REFERENCES users(id) ON DELETE CASCADE,
                session_id VARCHAR(255) NULL,
                product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                quantity INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ",
        'complaints table' => "
            CREATE TABLE IF NOT EXISTS complaints (
                id SERIAL PRIMARY KEY,
                complainant_id INT NOT NULL REFERENCES users(id),
                respondent_id INT NOT NULL REFERENCES users(id),
                complaint_type VARCHAR(30) NOT NULL CHECK (complaint_type IN ('buyer', 'seller', 'product_issue', 'service_issue', 'payment_issue', 'delivery_issue', 'other')),
                subject VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                order_id INT NULL REFERENCES orders(id) ON DELETE SET NULL,
                product_id INT NULL REFERENCES products(id) ON DELETE SET NULL,
                status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'investigating', 'resolved', 'dismissed')),
                admin_response TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                resolved_at TIMESTAMP NULL DEFAULT NULL
            );
        ",
        'complaint_responses table' => "
            CREATE TABLE IF NOT EXISTS complaint_responses (
                id SERIAL PRIMARY KEY,
                complaint_id INT NOT NULL REFERENCES complaints(id) ON DELETE CASCADE,
                responder_id INT NOT NULL REFERENCES users(id),
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ",
        'password_resets table' => "
            CREATE TABLE IF NOT EXISTS password_resets (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                token VARCHAR(64) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ",
        'products rating columns' => "
            ALTER TABLE products ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3,2) DEFAULT 0.00;
        ",
        'products review_count column' => "
            ALTER TABLE products ADD COLUMN IF NOT EXISTS review_count INT DEFAULT 0;
        ",
        'index: reviews_product_id' => "CREATE INDEX IF NOT EXISTS idx_reviews_product_id ON reviews(product_id);",
        'index: reviews_user_id' => "CREATE INDEX IF NOT EXISTS idx_reviews_user_id ON reviews(user_id);",
        'index: reviews_rating' => "CREATE INDEX IF NOT EXISTS idx_reviews_rating ON reviews(rating);",
        'index: reviews_created_at' => "CREATE INDEX IF NOT EXISTS idx_reviews_created_at ON reviews(created_at);",
        'index: users_status' => "CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);",
        'index: users_suspension_ends' => "CREATE INDEX IF NOT EXISTS idx_users_suspension_ends ON users(suspension_ends);",
        'index: cart_user' => "CREATE INDEX IF NOT EXISTS idx_cart_user ON cart_items(user_id);",
        'index: cart_session' => "CREATE INDEX IF NOT EXISTS idx_cart_session ON cart_items(session_id);",
        'index: cart_product' => "CREATE INDEX IF NOT EXISTS idx_cart_product ON cart_items(product_id);",
        'products image_path to TEXT' => "ALTER TABLE products ALTER COLUMN image_path TYPE TEXT;",
    ];

    // Run each migration
    $success = 0;
    $failed = 0;
    foreach ($migrations as $name => $sql) {
        try {
            $conn->exec($sql);
            echo "✅ $name — OK\n";
            $success++;
        } catch (PDOException $e) {
            echo "❌ $name — FAILED: " . $e->getMessage() . "\n";
            $failed++;
        }
    }

    echo "\n──────────────────────────────────\n";
    echo "Done! $success succeeded, $failed failed.\n";
    
    // Verify system_settings exists
    echo "\n── Verifying system_settings ──\n";
    $stmt = $conn->query("SELECT key, value FROM system_settings ORDER BY key");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($settings as $row) {
        echo "  {$row['key']} = {$row['value']}\n";
    }
    echo "\n✅ Migration complete! You can now delete this file for security.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
