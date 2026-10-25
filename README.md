# Hospital Database Management System 🏥

A PHP and MySQL-based hospital management web application designed to support patient and doctor workflows. It brings appointment booking, patient accounts, doctor dashboards, medical records, shared files, billing, and administrative data management into one web interface.

## Overview

The project demonstrates how a hospital information system can connect patients, doctors, and administrators to common workflows through a web application backed by MySQL.

> **Important:** This is an academic/demo project, not a production-ready clinical system. Before using it with real patient data, conduct a security and privacy review, configure secure authentication and authorization, protect uploads, and comply with applicable healthcare and data-protection requirements.

## Features

### Patient portal
- Register and sign in to a patient account
- Book appointments with available doctors
- View appointment-related information
- Upload files and share them with a selected doctor
- View medical records and bills
- View bill receipts and payment status

### Doctor portal
- Doctor sign-in and dashboard
- View appointments assigned to the logged-in doctor
- Mark appointments as completed
- Create and update patient medical records
- Generate bills for patients
- View bills and files shared by patients

### Administration
- Admin login and dashboard
- View and manage application data across supported tables
- Update records and delete records
- Export table data as CSV, with password fields masked in exports

### Website interface
- Hospital home page and information sections
- Doctor listing and appointment form
- Contact form
- Patient and doctor login pages
- Responsive layout using CSS and Bootstrap-style UI elements

## Tech Stack

| Technology | Purpose |
|---|---|
| PHP | Server-side logic, sessions, forms, and database operations |
| MySQL | Stores user accounts, appointments, records, and billing data |
| MySQLi | Connects PHP pages to the MySQL database |
| HTML5 | Page structure and forms |
| CSS3 | Styling and responsive layout |
| JavaScript | Client-side interactions and form behavior |
| Apache (XAMPP/WAMP/MAMP) | Local PHP web server |

## Project Structure

```text
Hospital-Database-Management-System/
├── files/
│   ├── index.php                 # Main hospital website
│   ├── patientsLogin.php         # Patient login and registration
│   ├── doctorsLogin.php          # Doctor login
│   ├── main.php                  # Patient dashboard
│   ├── doctor_dashboard.php      # Doctor dashboard
│   ├── admin.php                 # Admin management interface
│   ├── appoinmentForm.php        # Appointment booking
│   ├── complete.php              # Appointment completion handler
│   ├── receipt.php               # Bill receipt view
│   ├── contact_process.php       # Contact form handler
│   ├── forgot_password.php       # Password recovery flow
│   ├── script.js                 # Front-end interactions
│   ├── style.css                 # Main styles
│   └── uploads/                  # Uploaded images/files
└── images/                       # Website images and social icons
```

## Requirements

- PHP 8.x recommended
- MySQL or MariaDB
- Apache web server
- XAMPP, WAMP, or another local PHP/MySQL environment
- A modern browser

## Run Locally with XAMPP

### 1. Install and start services

Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL** from the XAMPP Control Panel.

### 2. Copy the project

Extract the repository and copy the project folder into XAMPP's `htdocs` directory. For example:

```text
C:\xampp\htdocs\Hospital-Database-Management-System\
```

The PHP files are inside the `files` folder, so the main page may be opened at:

```text
http://localhost/Hospital-Database-Management-System/files/index.php
```

### 3. Create the database

Open phpMyAdmin at `http://localhost/phpmyadmin` and create a database named:

```sql
CREATE DATABASE hospital_data;
```

The PHP files connect to a local database named `hospital_data`. Some pages create supporting tables automatically, but the project archive does not include a complete standalone SQL schema. Ensure all required tables and columns exist before using every feature. The application references tables including:

- `patients_account`
- `doctors_account`
- `appointments`
- `completed_appointments`
- `medical_records`
- `records`
- `bills`
- `contact_messages`

The `patients_account` and `doctors_account` tables need to be created and populated with appropriate columns and test data before login, appointment booking, and dashboards can work correctly.

### 4. Configure the database connection

The current PHP pages use a local MySQL connection similar to:

```php
$conn = new mysqli("localhost", "root", "", "hospital_data");
```

Update the host, username, password, and database name in the PHP files if your MySQL configuration differs. For a maintainable deployment, move credentials into environment-based configuration and avoid committing secrets.

### 5. Open the application

Visit:

```text
http://localhost/Hospital-Database-Management-System/files/index.php
```

Use test accounts and synthetic data while evaluating the project. Login and admin workflows require the corresponding database records and configuration.

## Database Concepts Demonstrated

- Relational tables and primary-key relationships
- Foreign-key relationships between patients, doctors, records, and bills
- CRUD operations (Create, Read, Update, Delete)
- Prepared statements for several database operations
- Session-based login state
- Appointment lifecycle: scheduled to completed
- Billing status and receipt generation

## Security Considerations

Before any public deployment, address the following:

- **Credentials:** The current code contains hard-coded database settings and an admin password constant. Replace these with secure configuration and rotate any exposed credentials.
- **Admin access:** Implement strong authentication, password hashing, rate limiting, and role-based authorization.
- **Sensitive information:** Restrict access to medical records and bills; use HTTPS and appropriate audit logs.
- **File uploads:** Validate file type and size, generate safe filenames, prevent executable uploads, and store sensitive files outside the public web root.
- **Database setup:** Use a least-privilege database account rather than the MySQL root user.
- **Error handling:** Avoid displaying database errors and internal paths to public users.
- **Production readiness:** Review CSRF protection, session cookie settings, input validation, access control, backups, and applicable privacy obligations.

## Possible Future Enhancements

- Add a versioned SQL schema and database migration scripts.
- Introduce centralized configuration using environment variables.
- Add email/SMS appointment reminders.
- Add appointment availability checks and conflict prevention.
- Add searchable patient history and reports.
- Add audit logs for administrative and clinical changes.
- Add automated unit, integration, and security tests.
- Improve accessibility and mobile usability.

## Author

**Chaitanya Joshi**

- GitHub: [@joshichaitanya58](https://github.com/joshichaitanya58)
- Portfolio: [my-portfolio-delta-virid-82.vercel.app](https://my-portfolio-delta-virid-82.vercel.app/)
- LinkedIn: [Chaitanya Joshi](https://www.linkedin.com/in/chaitanya-joshi-644b39298/)

---

<p align="center">Built with PHP, MySQL, HTML, CSS, and JavaScript.</p>
