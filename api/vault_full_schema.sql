-- Full Vault Platform Database Schema

-- USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255),
    bio TEXT,
    status ENUM('active', 'suspended', 'deactivated') DEFAULT 'active',
    referral_code VARCHAR(20),
    referred_by INT,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id)
);

-- ADMINS TABLE
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'admin') DEFAULT 'admin',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- PLANS TABLE
CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    daily_roi DECIMAL(5,2),
    monthly_roi DECIMAL(5,2),
    lock_in_duration INT, -- in days
    min_investment DECIMAL(18,8),
    max_investment DECIMAL(18,8),
    bonus DECIMAL(18,8),
    referral_reward DECIMAL(18,8),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- DEPOSITS TABLE
CREATE TABLE deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(18,8) NOT NULL,
    method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    tx_id VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME,
    admin_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- WITHDRAWALS TABLE
CREATE TABLE withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(18,8) NOT NULL,
    method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    tx_id VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME,
    admin_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- INVESTMENTS (PLAN SUBSCRIPTIONS) TABLE
CREATE TABLE investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(18,8) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('active', 'suspended', 'completed') DEFAULT 'active',
    last_earning_date DATETIME,
    total_earned DECIMAL(18,8) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- TRANSACTIONS TABLE
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal', 'earning', 'bonus', 'referral'),
    amount DECIMAL(18,8) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed'),
    related_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- SETTINGS TABLE
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT
);

-- EMAIL LOGS TABLE
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    email VARCHAR(100),
    subject VARCHAR(255),
    body TEXT,
    sent_at DATETIME,
    status ENUM('sent', 'failed'),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- PAYMENT METHODS TABLE
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('deposit', 'withdrawal'),
    name VARCHAR(50) NOT NULL,
    details JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    min_amount DECIMAL(18,8),
    max_amount DECIMAL(18,8),
    fee DECIMAL(18,8)
);

-- SUPPORT TICKETS TABLE
CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255),
    message TEXT,
    status ENUM('open', 'closed', 'archived') DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ACTIVITY LOGS TABLE
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(255),
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
); 