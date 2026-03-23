CREATE DATABASE IF NOT EXISTS capus_craves;
USE capus_craves;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    birthday DATE NOT NULL,
    address TEXT NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin', 'seller') DEFAULT 'customer',
    status ENUM('active', 'banned', 'suspended') DEFAULT 'active',
    ban_reason TEXT NULL,
    banned_at TIMESTAMP NULL DEFAULT NULL,
    suspended_at TIMESTAMP NULL DEFAULT NULL,
    suspension_ends TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Student verification fields
    student_verification_status ENUM('pending', 'verified', 'rejected') DEFAULT NULL,
    student_id_image VARCHAR(255) DEFAULT NULL,
    verification_rejection_reason TEXT DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Seller application fields
    seller_application_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL,
    seller_application_reason TEXT DEFAULT NULL,
    seller_rejection_reason TEXT DEFAULT NULL,
    applied_for_seller_at TIMESTAMP NULL DEFAULT NULL,
    became_seller_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Store profile fields
    store_status ENUM('available', 'unavailable') DEFAULT 'available' COMMENT 'Store availability status for sellers',
    store_name VARCHAR(100) NULL COMMENT 'Micro store name',
    store_description TEXT NULL COMMENT 'Micro store description',
    store_banner VARCHAR(255) NULL COMMENT 'Store banner image path',
    
    -- User dietary preferences
    allergens JSON NULL COMMENT 'Array of allergens for user dietary preferences'
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    stock_quantity INT NOT NULL,
    seller_id INT,
    allergens JSON NULL COMMENT 'Array of allergens for filtering',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id)
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('cart', 'pending', 'completed', 'shipped', 'cancelled') DEFAULT 'cart',
    total_amount DECIMAL(10,2),
    payment_method VARCHAR(50),
    delivery_mode ENUM('delivery', 'meetup') DEFAULT 'delivery',
    delivery_address TEXT,
    delivery_notes TEXT,
    meetup_time DATETIME,
    meetup_place VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Complaints table
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complainant_id INT NOT NULL,
    respondent_id INT NOT NULL,
    complaint_type ENUM('buyer', 'seller', 'product_issue', 'service_issue', 'payment_issue', 'delivery_issue', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    order_id INT NULL,
    product_id INT NULL,
    status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (complainant_id) REFERENCES users(id),
    FOREIGN KEY (respondent_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Complaint responses table
CREATE TABLE IF NOT EXISTS complaint_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    responder_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (responder_id) REFERENCES users(id)
);

-- Reviews table for product review system
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    verified_purchase BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (product_id, user_id)
);

-- Review helpful votes table
CREATE TABLE IF NOT EXISTS review_helpful_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (review_id, user_id)
);

-- Cart items table for persistent cart functionality
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add rating columns to products table for performance
ALTER TABLE products ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00;
ALTER TABLE products ADD COLUMN review_count INT DEFAULT 0;

-- Create indexes for better performance
CREATE INDEX idx_reviews_product_id ON reviews(product_id);
CREATE INDEX idx_reviews_user_id ON reviews(user_id);
CREATE INDEX idx_reviews_rating ON reviews(rating);
CREATE INDEX idx_reviews_created_at ON reviews(created_at);

-- Indexes for user status and suspension
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_suspension_ends ON users(suspension_ends);

-- Indexes for cart table performance
CREATE INDEX idx_cart_user ON cart_items(user_id);
CREATE INDEX idx_cart_session ON cart_items(session_id);
CREATE INDEX idx_cart_product ON cart_items(product_id);

INSERT INTO users (first_name, last_name, birthday, address, email, phone, username, password, role) VALUES
('Admin', 'Admin', '1990-01-01', 'Admin Address', 'admin2@campuscraves.com', '555-0000', 'admin', 'admin', 'admin');

-- Update existing users to have 'active' status
UPDATE users SET status = 'active' WHERE status IS NULL;