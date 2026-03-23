-- Production System Settings Setup
-- Run this SQL script directly on your production database

-- Create system_settings table
CREATE TABLE IF NOT EXISTS system_settings (
    key VARCHAR(100) PRIMARY KEY,
    value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO system_settings (key, value) VALUES 
    ('store_visibility_mode', 'show_all'),
    ('maintenance_mode', 'false'),
    ('allow_registrations', 'true'),
    ('enable_reviews', 'true')
ON CONFLICT (key) DO UPDATE SET 
    value = EXCLUDED.value, 
    updated_at = CURRENT_TIMESTAMP;

-- Verify the settings (optional)
SELECT key, value FROM system_settings ORDER BY key;
