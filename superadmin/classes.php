<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$message = '';
$error = '';
$current_year = get_current_academic_year();

// Handle Create/Update/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'create') {
            $grade_level = intval($_POST['grade_level']);
            $section = sanitize_input($_POST['section']);
            $class_name = "Grade $grade_level-$section";
            $academic_year_id = intval($_POST['academic_year_id']);
            $guardian_teacher_id = !empty($_POST['guardian_teacher_id']) ? intval($_POST['guardian_teacher_id']) : null;
            $max_students = intval($_POST['max_students']);
            
            $stmt = $conn->prepare("INSERT INTO classes (class_name, grade_level, section, academic_year_id, guardian_teacher_id, max_students) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisiii", $class_name, $grade_level, $section, $academic_year_id, $guardian_teacher_id, $max_students);
            
            if ($stmt->execute()) {
                $message = "Class created successfully!";
            } else {
                $error = "Error creating class: " . $stmt->error;
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Class deleted successfully!";
            } else {
                $error = "Error deleting class: " . $stmt->error;
            }
        }
    }
}

// Get all classes
$classes = $conn->query("
    SELECT c.*, 
           ay.year_name,
           t.first_name as teacher_fname, 
           t.last_name as teacher_lname,
           COUNT(s.id) as student_count
    FROM classes c 
    INNER JOIN academic_years ay ON c.academic_year_id = ay.id
    LEFT JOIN users u ON c.guardian_teacher_id = u.id
    LEFT JOIN teachers t ON u.id = t.user_id
    LEFT JOIN students s ON c.id = s.class_id
    GROUP BY c.id
    ORDER BY c.grade_level, c.section
")->fetch_all(MYSQLI_ASSOC);

// Get teachers for guardian assignment
$teachers = $conn->query("
    SELECT u.id, t.first_name, t.last_name 
    FROM users u 
    INNER JOIN teachers t ON u.id = t.user_id 
    ORDER BY t.first_name, t.last_name
")->fetch_all(MYSQLI_ASSOC);

// Get academic years
$academic_years = $conn->query("SELECT * FROM academic_years ORDER BY start_date DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Classes - <?php echo SITE_NAME; ?></title>
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
                <h4 class="card-title mb-0">Classes Management</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                  <i class="ti ti-plus me-1"></i>Add New Class
                </button>
              </div>
              
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Class Name</th>
                      <th>Academic Year</th>
                      <th>Guardian Teacher</th>
                      <th>Students</th>
                      <th>Max Capacity</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($classes as $class): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="round-40 rounded-circle text-bg-primary d-flex align-items-center justify-content-center me-2">
                            <i class="ti ti-school"></i>
                          </div>
                          <h6 class="mb-0"><?php echo $class['class_name']; ?></h6>
                        </div>
                      </td>
                      <td><?php echo $class['year_name']; ?></td>
                      <td>
                        <?php if ($class['teacher_fname']): ?>
                          <?php echo $class['teacher_fname'] . ' ' . $class['teacher_lname']; ?>
                        <?php else: ?>
                          <span class="text-muted">Not assigned</span>
                        <?php endif; ?>
                      </td>
                      <td><span class="badge bg-info"><?php echo $class['student_count']; ?> students</span></td>
                      <td><?php echo $class['max_students']; ?></td>
                      <td>
                        <a href="view-class.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-light" title="View">
                          <i class="ti ti-eye"></i>
                        </a>
                        <a href="edit-class.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-light" title="Edit">
                          <i class="ti ti-edit"></i>
                        </a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this class?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo $class['id']; ?>">
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

  <!-- Add Class Modal -->
  <div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="create">
            
            <div class="mb-3">
              <label class="form-label">Grade Level <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="grade_level" min="1" max="13" placeholder="e.g., 10" required>
              <small class="text-muted">Enter grade number (e.g., 10, 11, 12, 13)</small>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Section <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="section" maxlength="10" placeholder="e.g., A, B, C" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Academic Year <span class="text-danger">*</span></label>
              <select class="form-select" name="academic_year_id" required>
                <?php foreach ($academic_years as $year): ?>
                <option value="<?php echo $year['id']; ?>" <?php echo $year['is_current'] ? 'selected' : ''; ?>>
                  <?php echo $year['year_name']; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Guardian Teacher (Optional)</label>
              <select class="form-select" name="guardian_teacher_id">
                <option value="">Select teacher...</option>
                <?php foreach ($teachers as $teacher): ?>
                <option value="<?php echo $teacher['id']; ?>">
                  <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Max Students</label>
              <input type="number" class="form-control" name="max_students" value="40" min="1">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Class</button>
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
