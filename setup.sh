#!/usr/bin/env bash
set -euo pipefail

# –– Configuration (must match config.php) ––
DB_NAME="redreporter2"
DB_USER="redreporter"
DB_PASS="R3dT34m5R3p0rt"

# –– 1. Create DB & user ––
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# –– 2. Core schema ––
mysql -u root <<'SQL'
USE `redreporter2`;

-- users --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','consultant','client') NOT NULL DEFAULT 'client',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- clients (organisations) --------------------------------------
CREATE TABLE IF NOT EXISTS clients (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  logo VARCHAR(255),
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- client contacts (no login) -----------------------------------
CREATE TABLE IF NOT EXISTS client_contacts (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  client_id  INT NOT NULL,
  full_name  VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  phone      VARCHAR(50),
  role       VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id)
) ENGINE=InnoDB;

-- projects ------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  client_id         INT NOT NULL,
  name              VARCHAR(255) NOT NULL,
  scope_assets      TEXT,
  engagement_start  DATE,
  engagement_end    DATE,
  executive_summary TEXT,
  status            ENUM('planning','active','complete') DEFAULT 'planning',
  created_by        INT NOT NULL,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;
SQL

mysql -u root <<'SQL'
USE `redreporter2`;

-- project members ----------------------------------------------
CREATE TABLE IF NOT EXISTS project_members (
  project_id      INT NOT NULL,
  user_id         INT NOT NULL,
  role_in_project ENUM('lead','tester','observer') DEFAULT 'tester',
  PRIMARY KEY (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (user_id)    REFERENCES users(id)
) ENGINE=InnoDB;

-- templates -----------------------------------------------------
CREATE TABLE IF NOT EXISTS templates (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(255) NOT NULL,
  description  TEXT NOT NULL,
  remediation  TEXT NOT NULL,
  risk_rating  ENUM('Critical','High','Medium','Low','Informational') NOT NULL,
  is_disabled  TINYINT(1) NOT NULL DEFAULT 0,
  created_by   INT NOT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_by   INT NOT NULL,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- project findings ---------------------------------------------
CREATE TABLE IF NOT EXISTS project_findings (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  project_id        INT NOT NULL,
  template_id       INT NOT NULL,
  severity_override ENUM('Critical','High','Medium','Low','Informational'),
  status            ENUM('open','resolved') DEFAULT 'open',
  FOREIGN KEY (project_id)  REFERENCES projects(id),
  FOREIGN KEY (template_id) REFERENCES templates(id)
) ENGINE=InnoDB;

-- evidence ------------------------------------------------------
CREATE TABLE IF NOT EXISTS evidence (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  project_finding_id INT NOT NULL,
  filename           VARCHAR(255) NOT NULL,
  caption            VARCHAR(255),
  uploaded_by        INT NOT NULL,
  uploaded_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_finding_id) REFERENCES project_findings(id),
  FOREIGN KEY (uploaded_by)        REFERENCES users(id)
) ENGINE=InnoDB;
SQL

# –– 3. Default admin user ––
ADMIN_HASH=$(php -r "echo password_hash('admin', PASSWORD_DEFAULT);")
mysql -u root <<SQL
USE \`${DB_NAME}\`;
INSERT IGNORE INTO users (username, password_hash, role)
VALUES ('admin', '${ADMIN_HASH}', 'admin');
SQL

echo "✓ Database & user setup complete."
echo "✓ Default admin account created (username: admin / password: admin)."
