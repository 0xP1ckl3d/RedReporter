# RedReporter

**RedReporter** is a web-based tool designed to streamline the creation, management, and generation of penetration test reports. The project is in active development, with a focus on providing an intuitive UI for managing reusable finding templates, associating them with projects, and eventually generating styled exportable reports.

---

## 🚧 Current Status
- Project is under active development
- Current functionality is focused on finding template management
- Future milestones include full project lifecycle handling and report generation/exporting

---

## ✅ Current Features
- Secure login system with role-based access (admin, consultant, client)
- Markdown-based finding template creation and editing
- Template listing with:
  - Markdown preview
  - Role-based action buttons: edit, clone, disable, delete
  - Visual indicators for disabled templates
- CSRF protection, session handling, and basic frontend enhancements (dark mode, theme toggle)

---

## 🛠️ Setup Instructions

### 1. Clone the Repository
```bash
git clone https://github.com/0xP1ckl3d/RedReporter.git
cd redreporter
```

### 2. Database Setup
Run the provided setup script:
```bash
chmod +x setup.sh
./setup.sh
```
This will:
- Create the MySQL database `redreporter2`
- Create the user `redreporter` with default password `R3dT34m5R3p0rt`
- Insert a default admin user with credentials:
  - **Username:** `admin`
  - **Password:** `admin`

You can modify these values inside `setup.sh` before running it.

### 3. Configuration File
Ensure the `config.php` values match the database credentials used in `setup.sh`:
```php
$DB_HOST = 'localhost';
$DB_NAME = 'redreporter2';
$DB_USER = 'redreporter';
$DB_PASS = 'R3dT34m5R3p0rt';
```

---

## 🔍 Intended Features (Roadmap)
- [ ] Full project management UI (clients, projects, consultants)
- [ ] Project-to-template assignment
- [ ] Report builder/exporter (PDF/HTML) with custom branding
- [ ] Client-access view with filtered visibility
- [ ] Inline comment/review workflows
- [ ] Activity logs and change tracking

---

## 🔒 Security Notes
- Role enforcement occurs at both UI and API level
- CSRF token validation is required for all form submissions
- Passwords are stored as bcrypt hashes

---

## 🤝 Contributions
Contributions are welcome once core functionality stabilises. For now, issues and feature suggestions are appreciated.

---

## 📜 License
This project is under a private or to-be-decided license during early development.

---

**RedReporter** aims to become a lightweight, offline-friendly, security-focused reporting platform tailored for penetration testing consultants and teams.
