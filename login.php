<?php
require_once 'config/functions.php';

if (is_logged_in()) {
    $role = get_user_role();
    header("Location: $role/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = sanitize_input($_POST['role']);
    
    // Get credentials based on role
    if ($role === 'superadmin') {
        $username = sanitize_input($_POST['sa_username']);
        $email = sanitize_input($_POST['sa_email']);
        $password = $_POST['sa_password'];
        $child_name = null;
        $student_id = null;
    } elseif ($role === 'teacher') {
        $email = sanitize_input($_POST['t_email']);
        $password = $_POST['t_password'];
        $username = null;
        $child_name = null;
        $student_id = null;
    } elseif ($role === 'parent') {
        $email = sanitize_input($_POST['p_email']);
        $password = $_POST['p_password'];
        $child_name = sanitize_input($_POST['p_child_name']);
        $username = null;
        $student_id = null;
    } elseif ($role === 'student') {
        $child_name = sanitize_input($_POST['s_fullname']);
        $student_id = sanitize_input($_POST['s_student_id']);
        $email = 'student@temp.com';
        $password = 'student';
        $username = null;
    }
    
    if (login_user($email, $password, $role, $username, $child_name, $student_id)) {
        header("Location: $role/index.php");
        exit();
    } else {
        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="./assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="./assets/css/styles.min.css" />
  <style>
    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f8f9fa;
    }
    .login-card {
      max-width: 450px;
      width: 100%;
    }
    .role-field {
      display: none;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="card shadow-lg">
        <div class="card-body p-5">
          <div class="text-center mb-4">
            <img src="images/skooly_logo.png" alt="Logo" style="max-width: 180px;">
            <h3 class="mt-4 mb-2">Welcome Back</h3>
            <p class="text-muted">Sign in to continue to your dashboard</p>
          </div>
          
          <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <form method="POST" action="">
            <div class="mb-3">
              <label class="form-label">Select Role</label>
              <select class="form-select" name="role" id="roleSelect" required>
                <option value="">Choose your role...</option>
                <option value="superadmin">Super Admin</option>
                <option value="teacher">Teacher</option>
                <option value="parent">Parent</option>
                <option value="student">Student</option>
              </select>
            </div>
            
            <!-- Super Admin Fields -->
            <div class="role-field" id="superadmin-fields">
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="sa_username" placeholder="Enter username">
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="sa_email" placeholder="Enter email">
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="sa_password" placeholder="Enter password">
              </div>
            </div>
            
            <!-- Teacher Fields -->
            <div class="role-field" id="teacher-fields">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="t_email" placeholder="Enter email">
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="t_password" placeholder="Enter password">
              </div>
            </div>
            
            <!-- Parent Fields -->
            <div class="role-field" id="parent-fields">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="p_email" placeholder="Enter email">
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="p_password" placeholder="Enter password">
              </div>
              <div class="mb-3">
                <label class="form-label">Child's Full Name</label>
                <input type="text" class="form-control" name="p_child_name" placeholder="Enter child's full name">
              </div>
            </div>
            
            <!-- Student Fields -->
            <div class="role-field" id="student-fields">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="s_fullname" placeholder="Enter your full name">
              </div>
              <div class="mb-3">
                <label class="form-label">Student ID</label>
                <input type="text" class="form-control" name="s_student_id" placeholder="Enter student ID">
              </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
              <i class="ti ti-login me-2"></i>Sign In
            </button>
          </form>
          
          <div class="text-center mt-4">
            <p class="text-muted small mb-0">
              <i class="ti ti-lock me-1"></i>Your data is secure and encrypted
            </p>
          </div>
        </div>
      </div>
      
      <div class="text-center mt-3">
        <p class="text-white small">
          Default Login: admin@school.com / superadmin / password
        </p>
      </div>
    </div>
  </div>

  <script src="./assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="./assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  
  <script>
    $(document).ready(function() {
      $('#roleSelect').change(function() {
        const role = $(this).val();
        
        // Hide all role fields
        $('.role-field').hide();
        $('.role-field input').prop('required', false);
        
        // Show selected role fields
        if (role) {
          $(`#${role}-fields`).show();
          $(`#${role}-fields input`).prop('required', true);
        }
      });
    });
  </script>
</body>
</html>