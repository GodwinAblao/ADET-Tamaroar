-- Add user status fields to users table
ALTER TABLE users ADD COLUMN status ENUM('active', 'suspended') DEFAULT 'active';
ALTER TABLE users ADD COLUMN suspension_reason TEXT NULL;
ALTER TABLE users ADD COLUMN suspended_at TIMESTAMP NULL;

-- Update existing users to be active
UPDATE users SET status = 'active' WHERE status IS NULL; 