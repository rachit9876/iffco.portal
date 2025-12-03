# IFFCO Vocational Training Portal ([Try Now](https://iffco-portal.page.gd/))

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)

![Status](https://img.shields.io/badge/Status-Active-success?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)
![Version](https://img.shields.io/badge/Version-1.0-brightgreen?style=flat-square)

## About

A comprehensive web portal for managing vocational training programs at IFFCO. This system facilitates student registration, document management, project submissions, and automated certificate generation.

## Features

- **Secure Authentication** - OAuth-based admin authentication
- **Student Management** - Complete student registration and profile management
- **Document Upload** - Secure document upload and verification system
- **Certificate Generation** - Automated certificate creation and download
- **Admin Dashboard** - Comprehensive admin panel for managing applications
- **Certificate Verification** - Public certificate verification system

## Tech Stack

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **Server:** XAMPP (Apache)
- **Styling:** Neobrutalist CSS

## Project Structure

```
iffco-portal/
├── admin/              # Admin panel and authentication
├── user/               # User dashboard and features
├── assets/             # Images and static resources
├── uploads/            # Uploaded documents and certificates
├── db_connect.php      # Database configuration
├── index.php           # Landing page
└── iffco.sql          # Database schema
```

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   ```

2. **Move to XAMPP directory**
   ```bash
   move iffco-portal C:\xampp\htdocs\
   ```

3. **Import database**
   - Open phpMyAdmin
   - Create a new database
   - Import `iffco.sql`

4. **Configure database connection**
   - Update `db_connect.php` with your credentials

5. **Start XAMPP**
   - Start Apache and MySQL services

6. **Access the portal**
   - Navigate to `http://localhost/iffco-portal`

## Usage

### For Students
- Register with required details
- Upload necessary documents
- Submit projects
- Download certificates upon completion

### For Admins
- Review student applications
- Verify documents
- Generate certificates
- Manage student records

## Security Features

- OAuth authentication for admin access
- Secure file upload validation
- SQL injection prevention
- Session management

## License

This project is licensed under the MIT License.