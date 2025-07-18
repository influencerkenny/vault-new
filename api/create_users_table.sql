CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    country VARCHAR(100),
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