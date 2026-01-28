<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../config/functions.php';
} catch (Exception $e) {
    die("Error loading functions.php: " . $e->getMessage());
}

try {
    require_role(['superadmin']);
} catch (Exception $e) {
    die("Error checking role: " . $e->getMessage());
}

$current_year = get_current_academic_year();
$message = '';
$error = '';

// Handle Create/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'create') {
            // Validate all required fields
            if (empty($_POST['class_id']) || empty($_POST['subject_id']) || empty($_POST['teacher_id']) || 
                empty($_POST['day_of_week']) || empty($_POST['period_number']) || 
                empty($_POST['start_time']) || empty($_POST['end_time'])) {
                $error = "All required fields must be filled!";
            } else {
                try {
                    $class_id = intval($_POST['class_id']);
                    $subject_id = intval($_POST['subject_id']);
                    $teacher_id = intval($_POST['teacher_id']);
                    $day_of_week = sanitize_input($_POST['day_of_week']);
                    $period_number = intval($_POST['period_number']);
                    $start_time = sanitize_input($_POST['start_time']);
                    $end_time = sanitize_input($_POST['end_time']);
                    $room_number = isset($_POST['room_number']) ? sanitize_input($_POST['room_number']) : '';
                    
                    // Check for conflicts
                    $check_stmt = $conn->prepare("SELECT id FROM timetable WHERE class_id = ? AND day_of_week = ? AND period_number = ? AND academic_year_id = ?");
                    if (!$check_stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $check_stmt->bind_param("isii", $class_id, $day_of_week, $period_number, $current_year['id']);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $error = "This class already has a subject scheduled for this day and period!";
                    } else {
                        // Check teacher availability
                        $teacher_check = $conn->prepare("SELECT id FROM timetable WHERE teacher_id = ? AND day_of_week = ? AND period_number = ? AND academic_year_id = ?");
                        if (!$teacher_check) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        $teacher_check->bind_param("isii", $teacher_id, $day_of_week, $period_number, $current_year['id']);
                        $teacher_check->execute();
                        $teacher_result = $teacher_check->get_result();
                        
                        if ($teacher_result->num_rows > 0) {
                            $error = "This teacher is already assigned to another class at this time!";
                        } else {
                            // Insert the timetable entry
                            $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, period_number, start_time, end_time, room_number, academic_year_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            if (!$stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }
                            $stmt->bind_param("iiisissi", $class_id, $subject_id, $teacher_id, $day_of_week, $period_number, $start_time, $end_time, $room_number, $current_year['id']);
                            
                            if ($stmt->execute()) {
                                $message = "Timetable entry created successfully!";
                                // Redirect to clear POST data
                                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                                exit();
                            } else {
                                $error = "Error creating entry: " . $stmt->error;
                            }
                            $stmt->close();
                        }
                        $teacher_check->close();
                    }
                    $check_stmt->close();
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            try {
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("DELETE FROM timetable WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = "Timetable entry deleted successfully!";
                    header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
                    exit();
                } else {
                    $error = "Error deleting entry: " . $stmt->error;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle success messages from redirects
if (isset($_GET['success'])) {
    $message = "Timetable entry created successfully!";
}
if (isset($_GET['deleted'])) {
    $message = "Timetable entry deleted successfully!";
}

// Get timetable entries
try {
    $timetable_query = "
        SELECT t.*, c.class_name, s.subject_name, te.first_name as teacher_fname, te.last_name as teacher_lname
        FROM timetable t
        INNER JOIN classes c ON t.class_id = c.id
        INNER JOIN subjects s ON t.subject_id = s.id
        INNER JOIN teachers te ON t.teacher_id = te.id
        WHERE t.academic_year_id = {$current_year['id']}
        ORDER BY c.class_name, FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), t.period_number
    ";
    $timetable_result = $conn->query($timetable_query);
    if (!$timetable_result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    $timetable = $timetable_result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error loading timetable: " . $e->getMessage();
    $timetable = [];
}

// Get options for dropdowns
try {
    $classes_result = $conn->query("SELECT * FROM classes WHERE academic_year_id = {$current_year['id']} ORDER BY grade_level, section");
    $classes = $classes_result ? $classes_result->fetch_all(MYSQLI_ASSOC) : [];
    
    $subjects_result = $conn->query("SELECT * FROM subjects ORDER BY subject_name");
    $subjects = $subjects_result ? $subjects_result->fetch_all(MYSQLI_ASSOC) : [];
    
    $teachers_result = $conn->query("SELECT u.id, t.first_name, t.last_name FROM users u INNER JOIN teachers t ON u.id = t.user_id ORDER BY t.first_name");
    $teachers = $teachers_result ? $teachers_result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    $error = "Error loading dropdown data: " . $e->getMessage();
    $classes = [];
    $subjects = [];
    $teachers = [];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Timetable Management - </title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    
    <?php 
    if (file_exists('includes/sidebar.php')) {
        include 'includes/sidebar.php';
    }
    ?>
    
    <div class="body-wrapper">
      <?php 
      if (file_exists('includes/header.php')) {
          include 'includes/header.php';
      }
      ?>
      
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          
          <?php if ($message): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <div class="card">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="card-title mb-0">Timetable Management</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTimetableModal">
                  <i class="ti ti-plus me-1"></i>Add Schedule Entry
                </button>
              </div>
              
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Class</th>
                      <th>Day</th>
                      <th>Period</th>
                      <th>Time</th>
                      <th>Subject</th>
                      <th>Teacher</th>
                      <th>Room</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($timetable)): ?>
                    <tr>
                      <td colspan="8" class="text-center py-4">
                        <p class="text-muted mb-0">No timetable entries found. Click "Add Schedule Entry" to create one.</p>
                      </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($timetable as $entry): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($entry['class_name']); ?></td>
                      <td><?php echo htmlspecialchars($entry['day_of_week']); ?></td>
                      <td><span class="badge bg-primary"><?php echo $entry['period_number']; ?></span></td>
                      <td><?php echo date('H:i', strtotime($entry['start_time'])) . ' - ' . date('H:i', strtotime($entry['end_time'])); ?></td>
                      <td><?php echo htmlspecialchars($entry['subject_name']); ?></td>
                      <td><?php echo htmlspecialchars($entry['teacher_fname'] . ' ' . $entry['teacher_lname']); ?></td>
                      <td><?php echo htmlspecialchars($entry['room_number'] ? $entry['room_number'] : 'N/A'); ?></td>
                      <td>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-light text-danger">
                            <i class="ti ti-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
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

  <!-- Add Timetable Modal -->
  <div class="modal fade" id="addTimetableModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Schedule Entry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" id="timetableForm">
          <div class="modal-body">
            <input type="hidden" name="action" value="create">
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Class <span class="text-danger">*</span></label>
                <select class="form-select" name="class_id" id="class_id" required>
                  <option value="">Select class...</option>
                  <?php foreach ($classes as $class): ?>
                  <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Subject <span class="text-danger">*</span></label>
                <select class="form-select" name="subject_id" id="subject_id" required>
                  <option value="">Select subject...</option>
                  <?php foreach ($subjects as $subject): ?>
                  <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Teacher <span class="text-danger">*</span></label>
                <select class="form-select" name="teacher_id" id="teacher_id" required>
                  <option value="">Select teacher...</option>
                  <?php foreach ($teachers as $teacher): ?>
                  <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Day <span class="text-danger">*</span></label>
                <select class="form-select" name="day_of_week" id="day_of_week" required>
                  <option value="">Select day...</option>
                  <option value="Monday">Monday</option>
                  <option value="Tuesday">Tuesday</option>
                  <option value="Wednesday">Wednesday</option>
                  <option value="Thursday">Thursday</option>
                  <option value="Friday">Friday</option>
                </select>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Period <span class="text-danger">*</span></label>
                <select class="form-select" name="period_number" id="period_number" required>
                  <option value="">Select...</option>
                  <?php for ($i = 1; $i <= 8; $i++): ?>
                  <option value="<?php echo $i; ?>">Period <?php echo $i; ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                <input type="time" class="form-control" name="start_time" id="start_time" required>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">End Time <span class="text-danger">*</span></label>
                <input type="time" class="form-control" name="end_time" id="end_time" required>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Room Number</label>
              <input type="text" class="form-control" name="room_number" id="room_number" placeholder="e.g., 101">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <span class="btn-text">Create Entry</span>
              <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
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
  
  <script>
    // Form validation and submission handling
    document.getElementById('timetableForm').addEventListener('submit', function(e) {
      const submitBtn = document.getElementById('submitBtn');
      const btnText = submitBtn.querySelector('.btn-text');
      const spinner = submitBtn.querySelector('.spinner-border');
      
      // Show loading state
      btnText.classList.add('d-none');
      spinner.classList.remove('d-none');
      submitBtn.disabled = true;
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      });
    }, 5000);
    
    // Reset form when modal is closed
    document.getElementById('addTimetableModal').addEventListener('hidden.bs.modal', function() {
      document.getElementById('timetableForm').reset();
    });
    
    // Validate end time is after start time
    document.getElementById('end_time').addEventListener('change', function() {
      const startTime = document.getElementById('start_time').value;
      const endTime = this.value;
      
      if (startTime && endTime && endTime <= startTime) {
        alert('End time must be after start time');
        this.value = '';
      }
    });
  </script>
</body>
</html>