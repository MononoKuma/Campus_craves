-- PostgreSQL database schema for Capus Craves

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    birthday DATE NOT NULL,
    address TEXT NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'customer' CHECK (role IN ('customer', 'admin', 'seller')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'banned', 'suspended')),
    ban_reason TEXT NULL,
    banned_at TIMESTAMP NULL DEFAULT NULL,
    suspended_at TIMESTAMP NULL DEFAULT NULL,
    suspension_ends TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Student verification fields
    student_verification_status VARCHAR(20) DEFAULT NULL CHECK (student_verification_status IN ('pending', 'verified', 'rejected')),
    student_id_image VARCHAR(255) DEFAULT NULL,
    verification_rejection_reason TEXT DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Seller application fields
    seller_application_status VARCHAR(20) DEFAULT NULL CHECK (seller_application_status IN ('pending', 'approved', 'rejected')),
    seller_application_reason TEXT DEFAULT NULL,
    seller_rejection_reason TEXT DEFAULT NULL,
    applied_for_seller_at TIMESTAMP NULL DEFAULT NULL,
    became_seller_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Store profile fields
    store_status VARCHAR(20) DEFAULT 'available' CHECK (store_status IN ('available', 'unavailable')),
    store_name VARCHAR(100) NULL,
    store_description TEXT NULL,
    store_banner VARCHAR(255) NULL,
    
    -- User dietary preferences
    allergens JSONB NULL
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path TEXT,
    stock_quantity INT NOT NULL,
    seller_id INT REFERENCES users(id),
    allergens JSONB NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id),
    status VARCHAR(20) DEFAULT 'cart' CHECK (status IN ('cart', 'pending', 'completed', 'shipped', 'cancelled')),
    total_amount DECIMAL(10,2),
    payment_method VARCHAR(50),
    delivery_mode VARCHAR(20) DEFAULT 'delivery' CHECK (delivery_mode IN ('delivery', 'meetup')),
    delivery_address TEXT,
    delivery_notes TEXT,
    meetup_time TIMESTAMP,
    meetup_place VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL REFERENCES orders(id),
    product_id INT NOT NULL REFERENCES products(id),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Complaints table
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

-- Complaint responses table
CREATE TABLE IF NOT EXISTS complaint_responses (
    id SERIAL PRIMARY KEY,
    complaint_id INT NOT NULL REFERENCES complaints(id) ON DELETE CASCADE,
    responder_id INT NOT NULL REFERENCES users(id),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reviews table
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

-- Review helpful votes table
CREATE TABLE IF NOT EXISTS review_helpful_votes (
    id SERIAL PRIMARY KEY,
    review_id INT NOT NULL REFERENCES reviews(id) ON DELETE CASCADE,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (review_id, user_id)
);

-- System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    key VARCHAR(100) PRIMARY KEY,
    value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT INTO system_settings (key, value) VALUES 
    ('store_visibility_mode', 'show_all'),
    ('maintenance_mode', 'false'),
    ('allow_registrations', 'true'),
    ('enable_reviews', 'true')
ON CONFLICT (key) DO NOTHING;

-- Cart items table
CREATE TABLE IF NOT EXISTS cart_items (
    id SERIAL PRIMARY KEY,
    user_id INT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255) NULL,
    product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add rating columns to products table
ALTER TABLE products ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3,2) DEFAULT 0.00;
ALTER TABLE products ADD COLUMN IF NOT EXISTS review_count INT DEFAULT 0;

-- Change image_path to TEXT to support base64 data URIs
ALTER TABLE products ALTER COLUMN image_path TYPE TEXT;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_reviews_product_id ON reviews(product_id);
CREATE INDEX IF NOT EXISTS idx_reviews_user_id ON reviews(user_id);
CREATE INDEX IF NOT EXISTS idx_reviews_rating ON reviews(rating);
CREATE INDEX IF NOT EXISTS idx_reviews_created_at ON reviews(created_at);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_suspension_ends ON users(suspension_ends);
CREATE INDEX IF NOT EXISTS idx_cart_user ON cart_items(user_id);
CREATE INDEX IF NOT EXISTS idx_cart_session ON cart_items(session_id);
CREATE INDEX IF NOT EXISTS idx_cart_product ON cart_items(product_id);

-- Insert default admin user
INSERT INTO users (first_name, last_name, birthday, address, email, phone, username, password, role) 
VALUES ('Admin', 'Admin', '1990-01-01', 'Admin Address', 'admin2@campuscraves.com', '555-0000', 'admin', 'admin', 'admin')
ON CONFLICT (username) DO NOTHING;
