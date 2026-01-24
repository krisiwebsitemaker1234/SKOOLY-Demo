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
            $relationship = sanitize_input($_POST['relationship']);
            $phone = sanitize_input($_POST['phone']);
            $address = sanitize_input($_POST['address']);
            $occupation = sanitize_input($_POST['occupation']);
            
            // Create user account
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'parent')");
            $stmt->bind_param("ss", $email, $password);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Create parent profile
                $stmt2 = $conn->prepare("INSERT INTO parents (user_id, first_name, last_name, relationship, phone, address, occupation) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("issssss", $user_id, $first_name, $last_name, $relationship, $phone, $address, $occupation);
                
                if ($stmt2->execute()) {
                    $parent_id = $conn->insert_id;
                    
                    // Link to students if selected
                    if (isset($_POST['students']) && is_array($_POST['students'])) {
                        foreach ($_POST['students'] as $student_id) {
                            $stmt3 = $conn->prepare("INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)");
                            $stmt3->bind_param("ii", $parent_id, $student_id);
                            $stmt3->execute();
                        }
                    }
                    
                    $message = "Parent created successfully!";
                } else {
                    $error = "Error creating parent profile: " . $stmt2->error;
                }
            } else {
                $error = "Error creating user account: " . $stmt->error;
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM parents WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Parent deleted successfully!";
            } else {
                $error = "Error deleting parent: " . $stmt->error;
            }
        }
    }
}

// Get all parents with their children
$parents = $conn->query("
    SELECT p.*, u.email,
           GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') as children
    FROM parents p 
    INNER JOIN users u ON p.user_id = u.id 
    LEFT JOIN parent_student ps ON p.id = ps.parent_id
    LEFT JOIN students s ON ps.student_id = s.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get all students for linking
$students = $conn->query("SELECT * FROM students ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Parents - <?php echo SITE_NAME; ?></title>
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
                <h4 class="card-title mb-0">Parents Management</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addParentModal">
                  <i class="ti ti-plus me-1"></i>Add New Parent
                </button>
              </div>
              
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Relationship</th>
                      <th>Email</th>
                      <th>Phone</th>
                      <th>Children</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($parents as $parent): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="round-40 rounded-circle text-bg-light d-flex align-items-center justify-content-center me-2">
                            <i class="ti ti-heart-handshake"></i>
                          </div>
                          <h6 class="mb-0"><?php echo $parent['first_name'] . ' ' . $parent['last_name']; ?></h6>
                        </div>
                      </td>
                      <td><span class="badge bg-info-subtle text-info"><?php echo ucfirst($parent['relationship']); ?></span></td>
                      <td><?php echo $parent['email']; ?></td>
                      <td><?php echo $parent['phone'] ?? 'N/A'; ?></td>
                      <td><?php echo $parent['children'] ?? '<span class="text-muted">No children linked</span>'; ?></td>
                      <td>
                        <a href="view-parent.php?id=<?php echo $parent['id']; ?>" class="btn btn-sm btn-light" title="View">
                          <i class="ti ti-eye"></i>
                        </a>
                        <a href="edit-parent.php?id=<?php echo $parent['id']; ?>" class="btn btn-sm btn-light" title="Edit">
                          <i class="ti ti-edit"></i>
                        </a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this parent?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo $parent['id']; ?>">
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

  <!-- Add Parent Modal -->
  <div class="modal fade" id="addParentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Parent</h5>
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
                <label class="form-label">Relationship <span class="text-danger">*</span></label>
                <select class="form-select" name="relationship" required>
                  <option value="">Select...</option>
                  <option value="mother">Mother</option>
                  <option value="father">Father</option>
                  <option value="guardian">Guardian</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone">
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Occupation</label>
              <input type="text" class="form-control" name="occupation">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea class="form-control" name="address" rows="2"></textarea>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Link to Children (Optional)</label>
              <select class="form-select" name="students[]" multiple size="5">
                <?php foreach ($students as $student): ?>
                <option value="<?php echo $student['id']; ?>">
                  <?php echo $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'; ?>
                </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Hold Ctrl/Cmd to select multiple students</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Parent</button>
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
