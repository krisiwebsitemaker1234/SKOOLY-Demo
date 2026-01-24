# School Management System

A comprehensive PHP and MySQL-based school management system built with Bootstrap 5, featuring role-based dashboards for Super Admins, Teachers, Parents, and Students.

## 🎯 Features

### Super Admin Dashboard
- **Complete System Control**: Manage all users, classes, subjects, and academic years
- **Student Management**: Create, edit, and delete student accounts with detailed profiles
- **Teacher Management**: Assign teachers to subjects and classes, set guardian teachers
- **Parent Management**: Create parent accounts and link them to students
- **Class Management**: Create classes with grade levels and sections
- **Subject Management**: Add and manage custom subjects
- **Grade Management**: View and manage all student grades
- **Attendance Oversight**: Monitor attendance across all classes
- **Analytics Dashboard**: View enrollment trends and grade distributions with ApexCharts
- **Academic Year Management**: Set up 3-year or 4-year programs

### Teacher Dashboard
- **Class Overview**: View all assigned classes and students
- **Daily Schedule**: See today's teaching schedule with period-wise breakdown
- **Mark Attendance**: Hour-by-hour attendance tracking (8 periods per day)
  - Quick mark all absent/present buttons
  - Individual period checkboxes
  - View and edit previous attendance
- **Grade Students**: Assign grades on a 4-10 scale
  - Multiple grade types (midterm, final, quiz, assignment, project)
- **My Schedule**: View weekly timetable
- **Assignments**: Create and manage assignments for classes

### Student Dashboard
- **Academic Overview**: View average grades and attendance statistics
- **Grades**: View all grades by subject with performance indicators
- **Attendance Calendar**: Visual representation of absent hours per day
  - Last 30 days view
  - Color-coded status (Perfect, Partial, Absent)
- **Schedule**: View weekly class timetable
- **Assignments**: View upcoming assignments with due dates
  - Color-coded urgency (Due Today, Due Soon, Upcoming)
- **Profile**: View and update personal information

### Parent Dashboard
- **Multi-Child Support**: View information for all linked children
- **Child Selection**: Easy switching between children's records
- **Grades Monitoring**: View each child's recent grades and average
- **Attendance Tracking**: Monitor absent hours and attendance patterns
- **Academic Performance**: View comprehensive academic statistics

## 📋 System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser (Chrome, Firefox, Safari, Edge)

## 🚀 Installation

### 1. Database Setup

1. Create a new MySQL database named `school_management`
2. Import the database schema:
   ```bash
   mysql -u root -p school_management < database.sql
   ```

### 2. Configure Database Connection

Edit `config/database.php` and update your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'school_management');
```

### 3. Copy Files

Place all files in your web server's document root (e.g., `htdocs`, `www`, or `public_html`)

### 4. Set Permissions

Ensure proper permissions for the config directory:
```bash
chmod 755 config/
chmod 644 config/*.php
```

## 🔑 Default Login Credentials

### Super Admin
- **Username**: superadmin
- **Email**: admin@school.com
- **Password**: password

### Login Instructions by Role

1. **Super Admin**: Enter username + email + password
2. **Teacher**: Enter email + password
3. **Parent**: Enter email + password + child's full name
4. **Student**: Enter full name + student ID

## 📁 Project Structure

```
school_management_system/
│
├── config/
│   ├── database.php          # Database configuration
│   └── functions.php          # Helper functions and authentication
│
├── superadmin/
│   ├── index.php             # Admin dashboard
│   ├── students.php          # Student management
│   ├── teachers.php          # Teacher management
│   ├── parents.php           # Parent management
│   ├── classes.php           # Class management
│   ├── subjects.php          # Subject management
│   ├── grades.php            # Grade management
│   ├── attendance.php        # Attendance overview
│   └── includes/
│       ├── sidebar.php       # Admin sidebar navigation
│       └── header.php        # Admin header
│
├── teacher/
│   ├── index.php             # Teacher dashboard
│   ├── mark-attendance.php   # Hour-by-hour attendance marking
│   ├── grade-students.php    # Student grading
│   ├── my-classes.php        # View assigned classes
│   ├── my-schedule.php       # Weekly schedule
│   └── includes/
│       ├── sidebar.php       # Teacher sidebar
│       └── header.php        # Teacher header
│
├── student/
│   ├── index.php             # Student dashboard
│   ├── grades.php            # View all grades
│   ├── attendance.php        # Attendance calendar
│   ├── schedule.php          # Class schedule
│   └── includes/
│       ├── sidebar.php       # Student sidebar
│       └── header.php        # Student header
│
├── parent/
│   ├── index.php             # Parent dashboard
│   ├── children.php          # View all children
│   ├── grades.php            # Children's grades
│   ├── attendance.php        # Children's attendance
│   └── includes/
│       ├── sidebar.php       # Parent sidebar
│       └── header.php        # Parent header
│
├── assets/
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript files
│   ├── images/               # Images and logos
│   └── libs/                 # Bootstrap, jQuery, ApexCharts
│
├── login.php                 # Role-based login page
├── logout.php                # Logout handler
└── database.sql              # Database schema
```

## 🎨 Features in Detail

### Attendance System
- **8 Periods Per Day**: Track each hour individually
- **Hour-by-Hour Tracking**: Mark students absent for specific periods
- **Automatic Calculation**: System calculates total absent hours per day
- **Calendar View**: Visual representation of attendance history
- **Quick Actions**: Mark all students present/absent with one click

### Grading System
- **Scale**: 4.0 to 10.0 (4 = lowest, 10 = highest)
- **Grade Types**: Midterm, Final, Quiz, Assignment, Project
- **Color Coding**:
  - Green (9-10): Excellent
  - Blue (7-8): Good
  - Yellow (6): Average
  - Red (<6): Needs Improvement
- **Average Calculation**: Automatic calculation of student averages

### User Management
- **Password Security**: All passwords are hashed using bcrypt
- **Role-Based Access**: Strict role separation with authorization checks
- **Session Management**: Secure session handling
- **Profile Management**: Users can update their information

### Class Structure
- **Grade Levels**: Support for multiple grade levels (10, 11, 12, 13)
- **Sections**: Multiple sections per grade (A, B, C, etc.)
- **Guardian Teacher**: Each class has an assigned guardian teacher
- **Subject Assignment**: Teachers assigned to specific subjects per class

## 🔧 Customization

### Adding New Subjects
1. Log in as Super Admin
2. Navigate to Subjects
3. Click "Add New Subject"
4. Enter subject details (name, code, description)

### Creating Classes
1. Log in as Super Admin
2. Navigate to Classes
3. Click "Add New Class"
4. Select grade level, section, and guardian teacher
5. Choose academic year

### Adding Students
1. Log in as Super Admin
2. Navigate to Students
3. Click "Add New Student"
4. Fill in all required information
5. **Important**: Manually enter a unique Student ID
6. Assign to a class

### Linking Parents to Students
1. Create a parent account
2. Navigate to Parent-Student Management
3. Select parent and student(s) to link
4. Multiple parents can be linked to one student

## 📊 Database Schema Highlights

### Key Tables
- **users**: Authentication and role management
- **students**: Student profiles and information
- **teachers**: Teacher profiles and qualifications
- **parents**: Parent/guardian information
- **parent_student**: Links parents to their children
- **classes**: Class definitions with sections
- **subjects**: Subject catalog
- **teacher_subjects**: Teacher-subject-class assignments
- **grades**: Student grades with 4-10 scale
- **attendance**: Hour-by-hour attendance tracking (8 periods)
- **timetable**: Weekly schedule management
- **assignments**: Homework and assignment tracking
- **academic_years**: Academic year definitions (3-4 year programs)

## 🛡️ Security Features

- Password hashing with bcrypt
- SQL injection prevention using prepared statements
- Role-based access control
- Session management
- Input sanitization
- XSS protection

## 🎯 Future Enhancements (Potential Features)

- Email notifications for parents
- SMS alerts for absences
- Report card generation (PDF)
- Exam schedule management
- Fee management system
- Library management
- Student behavior tracking
- Parent-teacher messaging
- Mobile app integration
- Bulk data import/export

## 📝 License

This project is created for educational purposes and demonstration.

## 👥 Support

For questions or issues:
1. Check the database connection in `config/database.php`
2. Ensure all files have proper permissions
3. Verify MySQL service is running
4. Check PHP error logs for debugging

## 🏆 Credits

**Design**: Based on Quolytech Admin Dashboard Template
**Developed by**: QUOLYTECH
**Framework**: Bootstrap 5
**Charts**: ApexCharts
**Icons**: Tabler Icons

---

**Note**: This is a demonstration system. For production use, additional security measures, testing, and optimization are recommended.
