CREATE DATABASE IF NOT EXISTS vulnerable_bank;
USE vulnerable_bank;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(10, 2) DEFAULT 0.00,
    profile_bio TEXT
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('credit', 'debit') NOT NULL,
    description TEXT,
    amount DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(10, 2),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS virtual_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    card_number VARCHAR(16),
    expiry VARCHAR(5),
    cvv VARCHAR(3),
    balance DECIMAL(10, 2) DEFAULT 0.00,
    type ENUM('standard', 'premium') DEFAULT 'standard',
    currency VARCHAR(3) DEFAULT 'USD',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    category VARCHAR(50),
    biller VARCHAR(100),
    amount DECIMAL(10, 2),
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';
ALTER TABLE users ADD COLUMN account_number VARCHAR(20) UNIQUE;
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT 'default.png';

-- Insert sample users
INSERT INTO users (username, password, balance, profile_bio, role, account_number) VALUES 
('user1', 'password123 ', 5000.00, 'I am the administrator of Vulnerable Bank.', 'admin', 'VB-000001'),
('user2', 'password123', 3000.00, 'Hello, I am Alice.', 'user', 'VB-000002'),
('user3', 'password456 ', 2000.00, 'Bob likes banking.', 'user', 'VB-000003');

-- Insert sample transactions
INSERT INTO transactions (user_id, description, amount) VALUES 
(1, 'Initial Deposit', 5000.00),
(2, 'Initial Deposit', 3000.00),
(3, 'Initial Deposit', 2000.00);

CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO feedback (user_id, message) VALUES 
(2, 'This application is very useful!'),
(3, 'I love the new virtual card feature.');
