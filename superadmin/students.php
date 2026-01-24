<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$message = '';
$error = '';

// Handle Create/Update/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'create') {
            $student_id = sanitize_input($_POST['student_id']);
            $first_name = sanitize_input($_POST['first_name']);
            $last_name = sanitize_input($_POST['last_name']);
            $email = sanitize_input($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $dob = sanitize_input($_POST['dob']);
            $gender = sanitize_input($_POST['gender']);
            $phone = sanitize_input($_POST['phone']);
            $address = sanitize_input($_POST['address']);
            $class_id = sanitize_input($_POST['class_id']);
            $enrollment_date = sanitize_input($_POST['enrollment_date']);
            
            // Create user account
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'student')");
            $stmt->bind_param("ss", $email, $password);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Create student profile
                $stmt2 = $conn->prepare("INSERT INTO students (user_id, student_id, first_name, last_name, date_of_birth, gender, phone, address, class_id, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("isssssssss", $user_id, $student_id, $first_name, $last_name, $dob, $gender, $phone, $address, $class_id, $enrollment_date);
                
                if ($stmt2->execute()) {
                    $message = "Student created successfully!";
                } else {
                    $error = "Error creating student profile: " . $stmt2->error;
                }
            } else {
                $error = "Error creating user account: " . $stmt->error;
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Student deleted successfully!";
            } else {
                $error = "Error deleting student: " . $stmt->error;
            }
        }
    }
}

// Get all students
$students = $conn->query("
    SELECT s.*, c.class_name, u.email 
    FROM students s 
    INNER JOIN classes c ON s.class_id = c.id 
    INNER JOIN users u ON s.user_id = u.id 
    ORDER BY s.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get all classes for dropdown
$classes = $conn->query("SELECT * FROM classes ORDER BY grade_level, section")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Students - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="body-wrapper">
      <?php include 'includes/header.php'; ?>
      
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          
          <?php if ($message): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <div class="card">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="card-title mb-0">Students Management</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                  <i class="ti ti-plus me-1"></i>Add New Student
                </button>
              </div>
              
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Student ID</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Class</th>
                      <th>Gender</th>
                      <th>Enrollment Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                      <td><span class="badge bg-primary-subtle text-primary"><?php echo $student['student_id']; ?></span></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="round-40 rounded-circle text-bg-light d-flex align-items-center justify-content-center me-2">
                            <i class="ti ti-user"></i>
                          </div>
                          <h6 class="mb-0"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                        </div>
                      </td>
                      <td><?php echo $student['email']; ?></td>
                      <td><?php echo $student['class_name']; ?></td>
                      <td><span class="badge bg-info-subtle text-info"><?php echo ucfirst($student['gender']); ?></span></td>
                      <td><?php echo format_date($student['enrollment_date']); ?></td>
                      <td>
                        <a href="view-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-light" title="View">
                          <i class="ti ti-eye"></i>
                        </a>
                        <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-light" title="Edit">
                          <i class="ti ti-edit"></i>
                        </a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete">
                            <i class="ti ti-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          
          <div class="py-6 px-6 text-center">
            <p class="mb-0 fs-4">Designed and Developed by <a class="pe-1 text-primary text-decoration-none">QUOLYTECH</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Student Modal -->
  <div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="create">
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Student ID <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="student_id" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" required>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="first_name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="last_name" required>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="password" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="dob" required>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Gender <span class="text-danger">*</span></label>
                <select class="form-select" name="gender" required>
                  <option value="">Select Gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone">
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Class <span class="text-danger">*</span></label>
                <select class="form-select" name="class_id" required>
                  <option value="">Select Class</option>
                  <?php foreach ($classes as $class): ?>
                  <option value="<?php echo $class['id']; ?>"><?php echo $class['class_name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Enrollment Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="enrollment_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea class="form-control" name="address" rows="2"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Student</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>
</html>
