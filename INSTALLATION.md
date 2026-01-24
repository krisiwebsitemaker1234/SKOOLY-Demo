# School Management System - Installation Guide

## Quick Start Guide

### Step 1: Database Setup

1. Open your MySQL client (phpMyAdmin, MySQL Workbench, or command line)

2. Create a new database:
```sql
CREATE DATABASE school_management;
```

3. Import the database schema:
   - If using phpMyAdmin:
     * Select the `school_management` database
     * Click "Import" tab
     * Choose `database.sql` file
     * Click "Go"
   
   - If using command line:
```bash
mysql -u root -p school_management < database.sql
```

### Step 2: Configure Database Connection

1. Open `config/database.php`

2. Update these lines with your database credentials:
```php
define('DB_HOST', 'localhost');     // Usually 'localhost'
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', '');              // Your MySQL password
define('DB_NAME', 'school_management');
```

### Step 3: Deploy Files

1. Copy the entire `school_management_system` folder to your web server:
   - **XAMPP**: `C:/xampp/htdocs/`
   - **WAMP**: `C:/wamp/www/`
   - **MAMP**: `/Applications/MAMP/htdocs/`
   - **Linux**: `/var/www/html/`

2. Ensure proper folder structure:
```
htdocs/
└── school_management_system/
    ├── config/
    ├── superadmin/
    ├── teacher/
    ├── student/
    ├── parent/
    ├── assets/
    ├── login.php
    ├── logout.php
    └── database.sql
```

### Step 4: Access the System

1. Start your web server (Apache) and MySQL

2. Open your browser and navigate to:
```
http://localhost/school_management_system/login.php
```

3. Login with default credentials:
   - **Email**: admin@school.com
   - **Username**: superadmin
   - **Password**: password

### Step 5: Initial Setup (As Super Admin)

1. **Create Academic Year**:
   - Go to Academic Years
   - Add current academic year (e.g., 2024-2025)
   - Mark as current

2. **Add Subjects**:
   - Navigate to Subjects
   - Sample subjects are already added
   - Add more as needed

3. **Create Classes**:
   - Go to Classes
   - Create classes (e.g., Grade 10-A, Grade 11-B)
   - Assign guardian teachers later

4. **Add Teachers**:
   - Navigate to Teachers
   - Create teacher accounts
   - Note their credentials

5. **Assign Teachers to Subjects**:
   - Go to Teacher Subjects
   - Assign each teacher to their subjects and classes

6. **Add Students**:
   - Navigate to Students
   - Create student accounts
   - **Important**: Enter unique Student IDs manually
   - Assign to classes

7. **Add Parents**:
   - Navigate to Parents
   - Create parent accounts
   - Link parents to their children

## Testing the System

### Test Super Admin Access
1. Login with: admin@school.com / superadmin / password
2. Verify you can see all management options
3. Create a test student, teacher, and parent

### Test Teacher Access
1. Create a teacher account via Super Admin
2. Logout and login as teacher with email and password
3. Mark attendance for a class
4. Grade some students

### Test Student Access
1. Create a student via Super Admin
2. Note the student's full name and ID
3. Logout and login with full name + student ID
4. Verify you can see grades and attendance

### Test Parent Access
1. Create a parent and link to a student
2. Note parent email and child's full name
3. Logout and login with: email + password + child's full name
4. Verify you can see child's grades and attendance

## Common Issues & Solutions

### Issue 1: "Connection failed" error
**Solution**: 
- Check if MySQL is running
- Verify database credentials in `config/database.php`
- Ensure database `school_management` exists

### Issue 2: "Table doesn't exist" error
**Solution**: 
- Re-import `database.sql`
- Check that all tables were created successfully

### Issue 3: Login not working
**Solution**: 
- Clear browser cache and cookies
- Check that user exists in database
- Verify role is correct
- For students: ensure full name matches exactly (case-sensitive)

### Issue 4: Cannot see images/icons
**Solution**: 
- Ensure `assets` folder is in the correct location
- Check file permissions (should be readable)
- Verify paths in HTML files

### Issue 5: Session errors
**Solution**: 
- Ensure PHP session is enabled
- Check folder permissions for session storage
- Clear session files

## File Permissions (Linux/Mac)

```bash
cd /path/to/school_management_system
chmod 755 config/
chmod 644 config/*.php
chmod 755 superadmin/ teacher/ student/ parent/
chmod 644 *.php
```

## Security Recommendations for Production

1. **Change Default Password**:
   - Login as super admin
   - Change password immediately
   - Use strong passwords (min 12 characters)

2. **Database Security**:
   - Create a dedicated MySQL user with limited privileges
   - Use strong database password
   - Don't use 'root' in production

3. **File Permissions**:
   - Restrict write permissions
   - Make config files read-only after setup

4. **SSL Certificate**:
   - Use HTTPS in production
   - Get a free SSL from Let's Encrypt

5. **PHP Configuration**:
   - Disable error display in production
   - Enable error logging
   - Set appropriate session timeout

## System Requirements Check

### Minimum Requirements:
- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+ or Nginx
- 512 MB RAM
- 100 MB disk space

### Recommended:
- PHP 8.0+
- MySQL 8.0+
- 2 GB RAM
- SSD storage

### Check PHP Version:
```bash
php -v
```

### Check MySQL Version:
```bash
mysql --version
```

## Backup Instructions

### Database Backup:
```bash
mysqldump -u root -p school_management > backup_$(date +%Y%m%d).sql
```

### File Backup:
```bash
tar -czf school_backup_$(date +%Y%m%d).tar.gz school_management_system/
```

## Support & Troubleshooting

### Enable PHP Error Reporting (for debugging):

Add to top of `login.php`:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
```

### Check Apache Error Logs:
- **XAMPP**: `xampp/apache/logs/error.log`
- **Linux**: `/var/log/apache2/error.log`

### Check MySQL Logs:
- **XAMPP**: `xampp/mysql/data/*.err`
- **Linux**: `/var/log/mysql/error.log`

## Next Steps

1. Customize the system for your school
2. Add real student and teacher data
3. Set up regular backups
4. Train staff on using the system
5. Gather feedback and request features

## Need Help?

If you encounter issues:
1. Check this guide first
2. Review error logs
3. Verify all installation steps
4. Check database connection
5. Ensure all files are uploaded correctly

---

**Remember**: This is a demonstration system. For production use, implement additional security measures and thoroughly test all functionality.
