-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  email VARCHAR(128) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin Password Resets Table
CREATE TABLE IF NOT EXISTS admin_password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Users Table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  first_name VARCHAR(64) NOT NULL,
  last_name VARCHAR(64) NOT NULL,
  email VARCHAR(128) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(32),
  country VARCHAR(64),
  referred_by VARCHAR(64),
  avatar VARCHAR(255),
  status ENUM('active','blocked','suspended') DEFAULT 'active',
  twofa_enabled TINYINT(1) DEFAULT 0,
  notify_email VARCHAR(128),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Balances Table
CREATE TABLE IF NOT EXISTS user_balances (
  user_id INT PRIMARY KEY,
  available_balance DECIMAL(18,2) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Plans Table
CREATE TABLE IF NOT EXISTS plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  description TEXT,
  lock_in_duration INT NOT NULL,
  min_investment DECIMAL(18,2) NOT NULL,
  max_investment DECIMAL(18,2) NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  currency VARCHAR(16) DEFAULT 'USD',
  roi_type VARCHAR(32),
  roi_mode VARCHAR(32),
  roi_value DECIMAL(8,2),
  referral_reward DECIMAL(8,2),
  daily_roi DECIMAL(8,2),
  monthly_roi DECIMAL(8,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Investments Table
CREATE TABLE IF NOT EXISTS investments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan_id INT NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  start_date DATETIME NOT NULL,
  end_date DATETIME,
  status ENUM('active','completed','cancelled') DEFAULT 'active',
  total_earned DECIMAL(18,2) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
);

-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('deposit','withdrawal','investment','reward') NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  status ENUM('pending','completed','failed') DEFAULT 'pending',
  description VARCHAR(255),
  proof VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password Resets Table
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password Reset Logs Table
CREATE TABLE IF NOT EXISTS password_reset_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  email VARCHAR(255) NOT NULL,
  event_type ENUM('request','email_sent','reset_success','reset_fail') NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  details TEXT NULL,
  INDEX(user_id),
  INDEX(email)
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User Stakes Table
CREATE TABLE IF NOT EXISTS user_stakes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan_id INT NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  interest_earned DECIMAL(18,2) DEFAULT 0,
  status ENUM('active','completed','cancelled') DEFAULT 'active',
  started_at DATETIME NOT NULL,
  ended_at DATETIME,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
);

-- User Rewards Table
CREATE TABLE IF NOT EXISTS user_rewards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  type VARCHAR(64),
  description VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Portfolio History Table
CREATE TABLE IF NOT EXISTS portfolio_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  portfolio_value DECIMAL(18,2) NOT NULL,
  recorded_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payment Gateways Table
CREATE TABLE IF NOT EXISTS payment_gateways (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  currency VARCHAR(16) NOT NULL,
  rate_to_usd DECIMAL(18,8) NOT NULL,
  min_amount DECIMAL(18,2) NOT NULL,
  max_amount DECIMAL(18,2) NOT NULL,
  instructions TEXT,
  user_data_label VARCHAR(128),
  status ENUM('enabled','disabled') DEFAULT 'enabled',
  thumbnail VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample Data Inserts
INSERT INTO admins (username, email, password_hash) VALUES (
  'admin',
  'admin@example.com',
  '$2y$10$wH1QwQwQwQwQwQwQwQwQOeQwQwQwQwQwQwQwQwQwQwQwQwQwQwQ' -- hash for 'admin123', replace with a real hash if needed
) ON DUPLICATE KEY UPDATE email=VALUES(email); 