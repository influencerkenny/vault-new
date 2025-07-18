-- Add proof column to transactions table
ALTER TABLE transactions ADD COLUMN proof VARCHAR(255) AFTER description; 