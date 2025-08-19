# IFFCO Vocational Training Portal

A comprehensive web-based portal for managing vocational training programs at IFFCO (Indian Farmers Fertiliser Cooperative Limited). This system facilitates student registration, project submission, certificate generation, and administrative oversight.

## 🚀 Features

### Student Features
- **User Registration & Authentication**: Secure registration with approval workflow
- **Profile Management**: Complete profile with academic and contact details
- **Project Submission**: Upload project files and reports with status tracking
- **Certificate Generation**: Automated certificate generation with QR codes
- **Document Management**: Upload NOC and referral documents
- **Responsive Dashboard**: Mobile-friendly interface with intuitive navigation

### Admin Features
- **Application Management**: Review and approve/reject student applications
- **Student Oversight**: View all registered students and their details
- **Project Monitoring**: Track project submissions and completion status
- **Certificate Control**: Generate and manage student certificates
- **Document Verification**: Review uploaded documents (NOC, referrals, projects)

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Styling**: Tailwind CSS
- **Icons**: Font Awesome
- **Server**: Apache (XAMPP recommended for local development)

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB 5.7+
- Apache Web Server
- XAMPP (recommended for local development)

## ⚡ Quick Setup

### 1. Clone the Repository
```bash
git clone <repository-url>
cd iffco.portal
```

### 2. Database Setup
1. Start XAMPP and ensure Apache & MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database named `if0_38954512_user`
4. Import the SQL file: `if0_38954512_user.sql`

### 3. Configuration
Update database credentials in `db_connect.php`:
```php
$host = 'localhost';
$username = 'root';
$password = ''; // XAMPP default
$database = 'if0_38954512_user';
```

### 4. File Permissions
Ensure the `uploads/` directory has write permissions:
```bash
chmod -R 755 uploads/
```

### 5. Access the Application
- **Main Portal**: http://localhost/iffco.portal/
- **Admin Login**: admin@example.com / password
- **Student Registration**: Click "Apply Now" on login page

## 📁 Project Structure

```
iffco.portal/
├── admin/                  # Admin panel files
│   ├── dashboard.php      # Admin dashboard
│   ├── applications.php   # Manage applications
│   ├── students.php       # Student management
│   └── view_docs.php      # Document viewer
├── user/                  # Student portal files
│   ├── dashboard.php      # Student dashboard
│   ├── profile.php        # Profile management
│   ├── projects.php       # Project submission
│   ├── certificates.php   # Certificate download
│   └── register.php       # Student registration
├── uploads/               # File storage
│   ├── certificates/      # Generated certificates
│   ├── projects/          # Project files
│   └── qr_codes/         # QR codes for certificates
├── assets/               # Static assets
│   └── CSS/
├── index.php             # Main login page
├── db_connect.php        # Database configuration
└── logout.php            # Logout handler
```

## 🗄️ Database Schema

### Users Table
- **id**: Primary key
- **name**: Full name
- **email**: Unique email address
- **password**: Hashed password
- **roll_no**: Student roll number
- **department**: Academic department
- **batch**: Academic batch/year
- **college**: Institution name
- **program**: Degree program
- **semester**: Current semester
- **duration**: Training duration
- **role**: user/admin
- **status**: pending/approved/rejected

### Projects Table
- **id**: Primary key
- **user_id**: Foreign key to users
- **project_name**: Project title
- **file_path**: Project file location
- **report_path**: Report file location
- **status**: Not started/Completed
- **submission_date**: Timestamp

### Certificates Table
- **id**: Primary key
- **user_id**: Foreign key to users (unique)
- **certificate_path**: Certificate file location
- **qr_code_path**: QR code image location
- **issue_date**: Certificate issue date

## 🔐 Security Features

- **Password Hashing**: Uses PHP's password_hash() with bcrypt
- **SQL Injection Prevention**: Prepared statements throughout
- **Session Management**: Secure session handling
- **Role-based Access Control**: Admin/user role separation
- **File Upload Validation**: Secure file handling
- **XSS Protection**: Input sanitization with htmlspecialchars()

## 📱 Responsive Design

- Mobile-first approach with Tailwind CSS
- Collapsible sidebar navigation for mobile devices
- Touch-friendly interface elements
- Optimized for tablets and smartphones

## 🚀 Deployment

### Local Development (XAMPP)
1. Place project in `htdocs/` directory
2. Configure database connection
3. Set proper file permissions
4. Access via localhost

### Production Deployment
1. Upload files to web server
2. Update database credentials
3. Configure SSL certificate
4. Set production-level security headers
5. Configure backup procedures

## 🔧 Configuration Options

### File Upload Limits
Modify in `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

### Database Connection
Update `db_connect.php` for production:
```php
$host = 'your-production-host';
$username = 'your-db-username';
$password = 'your-secure-password';
$database = 'your-database-name';
```

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Verify MySQL service is running
- Check database credentials
- Ensure database exists

**File Upload Errors**
- Check directory permissions (755 for uploads/)
- Verify PHP upload limits
- Ensure sufficient disk space

**Session Issues**
- Clear browser cookies
- Check PHP session configuration
- Verify session directory permissions

## 📄 License

This project is proprietary software developed for IFFCO's internal use.

## 👥 Support

For technical support or feature requests, contact the development team.

---

**Note**: This portal is designed specifically for IFFCO's vocational training programs and contains organization-specific workflows and branding.