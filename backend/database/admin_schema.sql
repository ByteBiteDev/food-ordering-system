-- Admin Schema Updates

-- Add status column to users if it doesn't exist
-- Using a stored procedure approach in a script might be tricky if we want idempotency without dynamic SQL in mysql,
-- But since it's a direct alter, we'll try to add it. To make it safe in PHP we can check if column exists first.
-- However, standard ALTER TABLE syntax:
-- ALTER TABLE users ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) NOT NULL,
    setting_value TEXT NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings if not exists
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('site_name', 'Food Ordering System'),
('contact_email', 'admin@example.com'),
('currency_symbol', '$');
