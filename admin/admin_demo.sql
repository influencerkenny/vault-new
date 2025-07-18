-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  email VARCHAR(128) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admin_password_resets table
CREATE TABLE IF NOT EXISTS admin_password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Insert demo admin user (password: admin123)
INSERT INTO admins (username, email, password_hash) VALUES (
  'admin',
  'admin@example.com',
  '$2y$10$wH1QwQwQwQwQwQwQwQwQOeQwQwQwQwQwQwQwQwQwQwQwQwQwQwQ' -- hash for 'admin123', replace with a real hash if needed
) ON DUPLICATE KEY UPDATE email=VALUES(email); 