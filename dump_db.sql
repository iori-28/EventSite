-- ----------------------
-- Database: eventsite
-- ----------------------
CREATE DATABASE IF NOT EXISTS eventsite CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE eventsite;

-- users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) DEFAULT NULL,
  role ENUM('admin','panitia','user') NOT NULL DEFAULT 'user',
  organization_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- organizations
CREATE TABLE organizations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  kode_fakultas VARCHAR(50) DEFAULT NULL,
  description TEXT,
  is_approved TINYINT(1) DEFAULT 0,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- events
CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  location VARCHAR(255),
  start_at DATETIME,
  end_at DATETIME,
  capacity INT DEFAULT 0,
  organization_id INT,
  status ENUM('draft','pending','approved','rejected','cancelled') DEFAULT 'draft',
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- participants (registrations)
CREATE TABLE participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('registered','checked_in','cancelled') DEFAULT 'registered',
  UNIQUE(user_id, event_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- notifications (log)
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  type VARCHAR(100),
  payload JSON,
  status ENUM('sent','failed','pending') DEFAULT 'pending',
  send_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- certificates (store generated cert links/meta)
CREATE TABLE certificates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  participant_id INT NOT NULL,
  file_path VARCHAR(255) NULL,
  issued_at TIMESTAMP NULL,
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
);

-- seed accounts
INSERT INTO users (name, email, password, role)
VALUES
('Admin Demo', 'admin@example.com', '$2y$10$e0NRG6xLqE2oi6rQbq1ZKeOqYt2KcF1zAaWv3pKx0r3u6E9w4M8aG', 'admin'), 
('Panitia Demo', 'panitia@example.com', '$2y$10$tQIwyvlH8N5lAX0pVZsbhOgqe9cWKA4eqUqjei3PoV2oQEUNf5.1G', 'panitia'), 
('User Demo', 'user@example.com', '$2y$10$ke3v9SmTWPRPr.rxQ9gyzuWm28nd3vM8w63xyCpH/bTVWD8.Dkj/C', 'user'); 

-- seed organization and event example
INSERT INTO organizations (name, description, is_approved, created_by)
VALUES ('UKM Bahasa', 'Unit Kegiatan Mahasiswa', 1, 2);

INSERT INTO events (title, description, location, start_at, end_at, capacity, organization_id, status, created_by)
VALUES ('Workshop Public Speaking', 'Belajar public speaking', 'Aula 1', '2025-12-20 09:00:00', '2025-12-20 12:00:00', 100, 1, 'approved', 2);
