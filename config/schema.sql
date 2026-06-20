-- ============================================================
--  ICT Help Desk & Asset Management System
--  Techiman Metropolitan Assembly
--  Database Schema v1.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS ict_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ict_helpdesk;

-- --------------------------------------------------------
-- Table: departments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician', 'staff') NOT NULL DEFAULT 'staff',
    department_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: assets
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category ENUM('Computer','Printer','Router','Switch','UPS','Monitor','Other') NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100),
    status ENUM('Active','Under Maintenance','Decommissioned') DEFAULT 'Active',
    department_id INT,
    purchase_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: tickets
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_no VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('Internet','Printer','Software','Hardware','Network','Other') NOT NULL,
    priority ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    status ENUM('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
    created_by INT NOT NULL,
    department_id INT,
    asset_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: assignments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    technician_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: maintenance_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    ticket_id INT,
    technician_id INT,
    action_taken TEXT NOT NULL,
    maintenance_date DATE NOT NULL,
    next_maintenance_date DATE,
    cost DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
    FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: ticket_comments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS ticket_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Seed: Default admin account
-- Password: Admin@1234 (bcrypt hashed)
-- --------------------------------------------------------
INSERT INTO departments (name, description) VALUES
('ICT Department', 'Information and Communication Technology'),
('Finance', 'Finance and Accounts'),
('Administration', 'General Administration'),
('Works', 'Works and Infrastructure'),
('Planning', 'Planning and Development');

INSERT INTO users (full_name, email, username, password, role, department_id) VALUES
('System Administrator', 'admin@techiman.gov.gh', 'admin',
 '$2y$12$YKpGlbMRT2v5MhStZw3.COr7VSwG5L7RG7TDML3mSNGMZLM7kp46C',
 'admin', 1);
-- Default password: Admin@1234