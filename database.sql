CREATE DATABASE IF NOT EXISTS civic_db;
USE civic_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('citizen','admin','worker') DEFAULT 'citizen'
);

CREATE TABLE issues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  category VARCHAR(50) NOT NULL,
  latitude DOUBLE NOT NULL,
  longitude DOUBLE NOT NULL,
  address VARCHAR(255),
  image_path VARCHAR(255),
  after_image_path VARCHAR(255),
  status ENUM('Reported','Verified','Assigned','In Progress','Resolved') DEFAULT 'Reported',
  severity ENUM('Low','Medium','High','Critical') DEFAULT 'Low',
  priority_score INT DEFAULT 0,
  report_count INT DEFAULT 1,
  upvotes INT DEFAULT 0,
  assigned_to VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  resolved_at DATETIME NULL,
  INDEX idx_cat_status (category, status)
);

CREATE TABLE reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  issue_id INT NOT NULL,
  user_id INT NULL,
  description TEXT,
  image_path VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE
);

CREATE TABLE status_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  issue_id INT NOT NULL,
  old_status VARCHAR(30),
  new_status VARCHAR(30),
  remark VARCHAR(255),
  changed_by VARCHAR(100),
  changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE
);