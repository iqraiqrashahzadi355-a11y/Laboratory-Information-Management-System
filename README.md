# 🧪 LIMS — Laboratory Information Management System

A secure, role-based web application for managing laboratory operations — built with PHP, MariaDB, and modern web technologies.

**Live Demo:** [limsiqra.infinityfreeapp.com](https://limsiqra.infinityfreeapp.com/welcome.php)

---

## 📌 Overview

LIMS digitizes and streamlines laboratory workflows — replacing manual, paper-based processes with a centralized, secure platform. It supports multi-branch operations, real-time sample tracking, automated billing, and patient report generation.

---

## ✨ Features

| Feature | Description |
|---|---|
| 🔐 Role-Based Access | Admin, Technician, Manager, Doctor — each with tailored dashboards |
| 🧪 Sample Tracking | Registered → Testing → Completed pipeline with status updates |
| 📄 PDF Reports | Professional patient reports, printable as PDF |
| 📋 Audit Logging | Every action logged with user, IP, and timestamp |
| 💰 Billing System | Auto-invoicing per test, Cash/Card payment tracking |
| 📅 Appointments | Online booking with staff confirmation workflow |
| 🏥 Multi-Branch | Manage multiple lab branches from one system |
| 🔑 Account Security | bcrypt hashing, CSRF protection, account lockout after 3 failed attempts |
| 📧 Email Notifications | Automated emails for test results, account lock, new users |
| 🔖 Barcode Generation | Unique scannable barcode for every sample |
| 👤 Patient Portal | Patients log in to view results and download reports |

---

## 🛠️ Tech Stack

- **Backend:** PHP 8.0
- **Database:** MariaDB (MySQL compatible)
- **Server:** Apache via XAMPP
- **Email:** PHPMailer + Gmail SMTP
- **Frontend:** HTML5, CSS3, JavaScript (vanilla)
- **Security:** Prepared statements, bcrypt, CSRF tokens, session timeout

---

## 👥 User Roles

| Role | Access |
|---|---|
| ⚙️ Admin | Full access — users, patients, tests, reports, audit log, billing, branches |
| 🧪 Technician | Add/edit patients & tests, sample tracking, billing |
| 📊 Manager | View reports, analytics, billing, tracking |
| 👨‍⚕️ Doctor | Read-only — view results, download reports |

**Default credentials (for testing):**
```
Username: admin     Password: lims1234
Username: tech1     Password: lims1234
Username: manager1  Password: lims1234
Username: doctor1   Password: lims1234
```

---

## 🗄️ Database Schema

```
Patients      — patient records with branch and login
Users         — staff accounts with roles and security fields
LabTests      — test records with status and barcode
AuditLog      — complete action history
Appointments  — patient appointment bookings
Billing       — invoice and payment records
TestPrices    — configurable price list per test
Branches      — multi-branch management
```

---

## 🚀 Local Setup

```bash
# 1. Clone the repo
git clone https://github.com/iqraiqrashahzadi355-a11y/Laboratory-Information-Management-System.git

# 2. Move to XAMPP htdocs
# Copy folder to C:\xampp\htdocs\LIMS

# 3. Import database
# Run the SQL setup in phpMyAdmin (see database/setup.sql)

# 4. Configure environment
# Edit env.php with your DB credentials and email settings

# 5. Start XAMPP (Apache + MySQL)
# Visit: http://localhost/LIMS/welcome.php
```

---

## 📁 Project Structure

```
LIMS/
├── auth_login.php          # Staff login
├── auth_check.php          # Session & CSRF management
├── auth_nav.php            # Role-based navigation
├── dashboard_admin.php     # Admin dashboard
├── dashboard_technician.php
├── dashboard_manager.php
├── dashboard_doctor.php
├── add_patient.php         # Register new patient
├── add_test.php            # Add lab test
├── view_patients.php       # Search & manage patients
├── view_tests.php          # Search & manage tests
├── track_samples.php       # Sample status pipeline
├── generate_barcode.php    # Barcode generation
├── manage_users.php        # User management (Admin)
├── manage_billing.php      # Billing & invoices
├── manage_appointments.php # Appointment management
├── manage_branches.php     # Branch management
├── view_audit_log.php      # Audit trail
├── reports/
│   ├── patient_reports.php
│   └── download_patient_report.php
├── login.php               # Patient portal login
├── patient_dashboard.php   # Patient dashboard
├── welcome.php             # Public landing page
├── mailer.php              # Email helper (PHPMailer)
├── config.php              # DB connection
└── env.php                 # Environment config (not tracked)
```

---

## 🔒 Security Features

- Prepared statements — SQL injection prevention
- bcrypt password hashing
- CSRF tokens on all forms
- Account lockout after 3 failed login attempts (30 min)
- Session timeout (2 hours inactivity)
- Role-based access enforcement on every page
- Full audit logging

---

## 📧 Email Notifications

- ✅ Test result ready → Doctor notified
- ✅ Account locked → Admin alerted
- ✅ New staff account → Welcome email with credentials
- ✅ Appointment confirmed/cancelled → Patient notified

---

## 👩‍💻 Developer

**Iqra Shahzadi**
[![GitHub](https://img.shields.io/badge/GitHub-iqraiqrashahzadi355--a11y-181717?style=flat&logo=github)](https://github.com/iqraiqrashahzadi355-a11y)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-Iqra%20Shahzadi-0A66C2?style=flat&logo=linkedin)](https://www.linkedin.com/in/iqra-shahzadi-haji/)

---

## 📄 License

This project is for academic and educational purposes.
