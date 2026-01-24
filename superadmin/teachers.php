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
            $first_name = sanitize_input($_POST['first_name']);
            $last_name = sanitize_input($_POST['last_name']);
            $email = sanitize_input($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $phone = sanitize_input($_POST['phone']);
            $address = sanitize_input($_POST['address']);
            $dob = sanitize_input($_POST['dob']);
            $hire_date = sanitize_input($_POST['hire_date']);
            $qualification = sanitize_input($_POST['qualification']);
            
            // Create user account
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'teacher')");
            $stmt->bind_param("ss", $email, $password);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Create teacher profile
                $stmt2 = $conn->prepare("INSERT INTO teachers (user_id, first_name, last_name, phone, address, date_of_birth, hire_date, qualification) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("isssssss", $user_id, $first_name, $last_name, $phone, $address, $dob, $hire_date, $qualification);
                
                if ($stmt2->execute()) {
                    $message = "Teacher created successfully!";
                } else {
                    $error = "Error creating teacher profile: " . $stmt2->error;
                }
            } else {
                $error = "Error creating user account: " . $stmt->error;
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Teacher deleted successfully!";
            } else {
                $error = "Error deleting teacher: " . $stmt->error;
            }
        }
    }
}

// Get all teachers
$teachers = $conn->query("
    SELECT t.*, u.email 
    FROM teachers t 
    INNER JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Teachers - <?php echo SITE_NAME; ?></title>
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
                <h4 class="card-title mb-0">Teachers Management</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                  <i class="ti ti-plus me-1"></i>Add New Teacher
                </button>
              </div>
              
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Phone</th>
                      <th>Qualification</th>
                      <th>Hire Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="round-40 rounded-circle text-bg-light d-flex align-items-center justify-content-center me-2">
                            <i class="ti ti-user-check"></i>
                          </div>
                          <h6 class="mb-0"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></h6>
                        </div>
                      </td>
                      <td><?php echo $teacher['email']; ?></td>
                      <td><?php echo $teacher['phone'] ?? 'N/A'; ?></td>
                      <td><?php echo $teacher['qualification'] ?? 'N/A'; ?></td>
                      <td><?php echo format_date($teacher['hire_date']); ?></td>
                      <td>
                        <a href="view-teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-light" title="View">
                          <i class="ti ti-eye"></i>
                        </a>
                        <a href="edit-teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-light" title="Edit">
                          <i class="ti ti-edit"></i>
                        </a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this teacher?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
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

  <!-- Add Teacher Modal -->
  <div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Teacher</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="create">
            
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
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="password" required>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="form-control" name="dob">
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Hire Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="hire_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Qualification</label>
                <input type="text" class="form-control" name="qualification" placeholder="e.g., Master's in Mathematics">
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea class="form-control" name="address" rows="2"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Teacher</button>
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
