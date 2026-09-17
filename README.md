# CampusCare — PHP Student Support Portal

CampusCare is a lightweight PHP + SQLite web application for managing student support requests in a university department.

## Features
- Secure session-based admin login
- Password hashing with `password_hash`
- Create, edit, and delete support requests
- Status and priority tracking
- Search and filter support tickets
- Dashboard metrics
- Server-side validation
- Prepared SQL statements with PDO
- Responsive UI

## Tech Stack
PHP 8+, SQLite, PDO, HTML5, CSS3

## Run Locally
```bash
php -S localhost:8000
```
Then open `http://localhost:8000/login.php`.

### Demo credentials
- Email: `admin@campuscare.local`
- Password: `Admin123!`

The SQLite database is created automatically on first run.

## Portfolio Talking Points
This project demonstrates PHP routing through page-level controllers, authentication and sessions, CRUD operations, prepared statements, input validation, filtering, and a responsive administrative dashboard.
