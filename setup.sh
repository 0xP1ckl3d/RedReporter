#!/usr/bin/env bash
set -euo pipefail

#–– Configuration (should match config.php) ––
DB_HOST="localhost"
DB_NAME="redreporter2"
DB_USER="redreporter"
DB_PASS="R3dT34m5R3p0rt"

#–– 1. Create database and grant user privileges ––
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

#–– 2. Create users table if missing ––
mysql -u root <<SQL
USE \`${DB_NAME}\`;
CREATE TABLE IF NOT EXISTS users (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  username       VARCHAR(50)  NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('admin','consultant','client') NOT NULL DEFAULT 'client',
  created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
SQL

#–– 3. Insert default admin user ––
#    Generates a password hash via PHP and then does an INSERT IGNORE
ADMIN_HASH=$(php -r "echo password_hash('admin', PASSWORD_DEFAULT);")
mysql -u root <<SQL
USE \`${DB_NAME}\`;
INSERT IGNORE INTO users (username, password_hash, role)
VALUES ('admin', '${ADMIN_HASH}', 'admin');
SQL

#–– 4. Create projects & assignments tables if missing ––
mysql -u root <<SQL
USE \`${DB_NAME}\`;

CREATE TABLE IF NOT EXISTS projects (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  owner_id   INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_assignments (
  project_id  INT NOT NULL,
  user_id     INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (user_id)    REFERENCES users(id)
) ENGINE=InnoDB;
SQL

#–– 5. Create templates table if missing ––
mysql -u root <<SQL
USE \`${DB_NAME}\`;

CREATE TABLE IF NOT EXISTS templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  remediation TEXT NOT NULL,
  risk_rating ENUM('Critical','High','Medium','Low','Informational') NOT NULL,
  is_disabled TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_by INT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;
SQL


echo "✓ Database & user setup complete."
echo "✓ Default admin account created (username: admin / password: admin)."
