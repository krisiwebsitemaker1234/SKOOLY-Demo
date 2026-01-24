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
            $subject_name = sanitize_input($_POST['subject_name']);
            $subject_code = sanitize_input($_POST['subject_code']);
            $description = sanitize_input($_POST['description']);
            
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $subject_name, $subject_code, $description);
            
            if ($stmt->execute()) {
                $message = "Subject created successfully!";
            } else {
                $error = "Error creating subject: " . $stmt->error;
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Subject deleted successfully!";
            } else {
                $error = "Error deleting subject: " . $stmt->error;
            }
        }
    }
}

// Get all subjects
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Subjects - <?php echo SITE_NAME; ?></title>
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
                <h4 class="card-title mb-0">Subjects Management</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                  <i class="ti ti-plus me-1"></i>Add New Subject
                </button>
              </div>
              
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Subject Code</th>
                      <th>Subject Name</th>
                      <th>Description</th>
                      <th>Created</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($subjects as $subject): ?>
                    <tr>
                      <td><span class="badge bg-primary-subtle text-primary"><?php echo $subject['subject_code']; ?></span></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="round-40 rounded-circle text-bg-light d-flex align-items-center justify-content-center me-2">
                            <i class="ti ti-book"></i>
                          </div>
                          <h6 class="mb-0"><?php echo $subject['subject_name']; ?></h6>
                        </div>
                      </td>
                      <td><?php echo $subject['description'] ? substr($subject['description'], 0, 50) . '...' : 'N/A'; ?></td>
                      <td><?php echo format_date($subject['created_at']); ?></td>
                      <td>
                        <a href="edit-subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-light" title="Edit">
                          <i class="ti ti-edit"></i>
                        </a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
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

  <!-- Add Subject Modal -->
  <div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="create">
            
            <div class="mb-3">
              <label class="form-label">Subject Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="subject_name" placeholder="e.g., Advanced Mathematics" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Subject Code <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="subject_code" placeholder="e.g., MATH201" required>
              <small class="text-muted">Must be unique</small>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="3" placeholder="Brief description of the subject"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Subject</button>
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
