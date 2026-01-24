<?php
require_once __DIR__ . '/database.php';

// Authentication Functions
function login_user($email, $password, $role, $username = null, $child_name = null, $student_id = null) {
    global $conn;
    
    // Different login logic based on role
    if ($role === 'superadmin') {
        // Super admin needs username, email, and password
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND username = ? AND role = 'superadmin' AND status = 'active'");
        $stmt->bind_param("ss", $email, $username);
    } elseif ($role === 'teacher') {
        // Teacher needs email and password
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'teacher' AND status = 'active'");
        $stmt->bind_param("s", $email);
    } elseif ($role === 'parent') {
        // Parent needs email, password, and child's full name
        $stmt = $conn->prepare("
            SELECT u.* FROM users u
            INNER JOIN parents p ON u.id = p.user_id
            INNER JOIN parent_student ps ON p.id = ps.parent_id
            INNER JOIN students s ON ps.student_id = s.id
            WHERE u.email = ? AND CONCAT(s.first_name, ' ', s.last_name) = ? 
            AND u.role = 'parent' AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->bind_param("ss", $email, $child_name);
    } elseif ($role === 'student') {
        // Student needs full name and student ID (hashed)
        $stmt = $conn->prepare("
            SELECT u.* FROM users u
            INNER JOIN students s ON u.id = s.user_id
            WHERE CONCAT(s.first_name, ' ', s.last_name) = ? AND s.student_id = ? 
            AND u.role = 'student' AND u.status = 'active'
        ");
        $stmt->bind_param("ss", $child_name, $student_id);
    } else {
        return false;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password (except for students who use student_id)
        if ($role !== 'student') {
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                return true;
            }
        } else {
            // For students, student_id acts as password
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            return true;
        }
    }
    
    return false;
}

function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function get_user_role() {
    return $_SESSION['role'] ?? null;
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function logout_user() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

function require_role($allowed_roles) {
    require_login();
    
    if (!in_array(get_user_role(), $allowed_roles)) {
        header('Location: unauthorized.php');
        exit();
    }
}

// Helper Functions
function get_user_details($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function get_teacher_details($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function get_student_details($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function get_parent_details($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM parents WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function get_parent_children($parent_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT s.*, c.class_name 
        FROM students s
        INNER JOIN parent_student ps ON s.id = ps.student_id
        INNER JOIN classes c ON s.class_id = c.id
        WHERE ps.parent_id = ?
    ");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_current_academic_year() {
    global $conn;
    
    $result = $conn->query("SELECT * FROM academic_years WHERE is_current = 1 LIMIT 1");
    return $result->fetch_assoc();
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_date($date) {
    return date('M d, Y', strtotime($date));
}

function get_grade_color($grade) {
    if ($grade >= 9) return 'success';
    if ($grade >= 7) return 'primary';
    if ($grade >= 6) return 'warning';
    return 'danger';
}

function get_attendance_stats($student_id, $academic_year_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(total_absent_hours) as total_absent_hours
        FROM attendance 
        WHERE student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function calculate_average_grade($student_id, $academic_year_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT AVG(grade) as average 
        FROM grades 
        WHERE student_id = ? AND academic_year_id = ?
    ");
    $stmt->bind_param("ii", $student_id, $academic_year_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    return round($data['average'], 1);
}

function get_notifications($user_id, $limit = 5) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function mark_notification_read($notification_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

function create_notification($user_id, $title, $message, $type = 'info') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $message, $type);
    return $stmt->execute();
}

function hash_student_id($student_id) {
    return password_hash($student_id, PASSWORD_DEFAULT);
}
?>
