<?php
require_once '../config/functions.php';
require_role(['teacher']);

$teacher = get_teacher_details(get_user_id());
$current_year = get_current_academic_year();
$message = '';
$error = '';

// Get teacher's classes
$stmt = $conn->prepare("
    SELECT DISTINCT c.* 
    FROM classes c
    INNER JOIN teacher_subjects ts ON c.id = ts.class_id
    WHERE ts.teacher_id = ? AND ts.academic_year_id = ?
");
$stmt->bind_param("ii", $teacher['id'], $current_year['id']);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$selected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : (isset($_POST['class_id']) ? intval($_POST['class_id']) : null);
$selected_date = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $class_id = intval($_POST['class_id']);
    $attendance_date = $_POST['attendance_date'];
    $students = $_POST['students'] ?? [];
    
    foreach ($students as $student_id => $periods) {
        // Calculate total absent hours
        $total_absent = 0;
        for ($i = 1; $i <= 8; $i++) {
            if (isset($periods["period_$i"]) && $periods["period_$i"] === 'absent') {
                $total_absent++;
            }
        }
        
        // Check if attendance already exists
        $check = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ?");
        $check->bind_param("is", $student_id, $attendance_date);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing
            $stmt = $conn->prepare("
                UPDATE attendance SET 
                period_1 = ?, period_2 = ?, period_3 = ?, period_4 = ?,
                period_5 = ?, period_6 = ?, period_7 = ?, period_8 = ?,
                total_absent_hours = ?, marked_by = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssssssssii",
                $periods['period_1'], $periods['period_2'], $periods['period_3'], $periods['period_4'],
                $periods['period_5'], $periods['period_6'], $periods['period_7'], $periods['period_8'],
                $total_absent, $teacher['id'], $existing['id']
            );
        } else {
            // Insert new
            $stmt = $conn->prepare("
                INSERT INTO attendance 
                (student_id, class_id, attendance_date, period_1, period_2, period_3, period_4, period_5, period_6, period_7, period_8, total_absent_hours, marked_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisssssssssii",
                $student_id, $class_id, $attendance_date,
                $periods['period_1'], $periods['period_2'], $periods['period_3'], $periods['period_4'],
                $periods['period_5'], $periods['period_6'], $periods['period_7'], $periods['period_8'],
                $total_absent, $teacher['id']
            );
        }
        
        $stmt->execute();
    }
    
    $message = "Attendance marked successfully!";
}

// Get students if class is selected
$students = [];
if ($selected_class_id) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY first_name, last_name");
    $stmt->bind_param("i", $selected_class_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get existing attendance for the date
    foreach ($students as &$student) {
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? AND attendance_date = ?");
        $stmt->bind_param("is", $student['id'], $selected_date);
        $stmt->execute();
        $student['attendance'] = $stmt->get_result()->fetch_assoc();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mark Attendance - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    .period-checkbox {
      width: 20px;
      height: 20px;
    }
    .student-row:hover {
      background-color: #f8f9fa;
    }
  </style>
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
            <i class="ti ti-check me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <div class="card">
            <div class="card-body">
              <h4 class="card-title mb-4">Mark Attendance (Hour-by-Hour)</h4>
              
              <!-- Class and Date Selection -->
              <form method="POST" class="mb-4">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Select Class</label>
                    <select class="form-select" name="class_id" required onchange="this.form.submit()">
                      <option value="">Choose a class...</option>
                      <?php foreach ($classes as $class): ?>
                      <option value="<?php echo $class['id']; ?>" <?php echo $selected_class_id == $class['id'] ? 'selected' : ''; ?>>
                        <?php echo $class['class_name']; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Select Date</label>
                    <input type="date" class="form-control" name="attendance_date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                  </div>
                </div>
              </form>
              
              <?php if ($selected_class_id && !empty($students)): ?>
              <!-- Attendance Form -->
              <form method="POST">
                <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                
                <div class="table-responsive">
                  <table class="table table-bordered align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>Student Name</th>
                        <th class="text-center">Period 1</th>
                        <th class="text-center">Period 2</th>
                        <th class="text-center">Period 3</th>
                        <th class="text-center">Period 4</th>
                        <th class="text-center">Period 5</th>
                        <th class="text-center">Period 6</th>
                        <th class="text-center">Period 7</th>
                        <th class="text-center">Period 8</th>
                        <th class="text-center">Quick Mark</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($students as $student): ?>
                      <tr class="student-row">
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="round-40 rounded-circle text-bg-light d-flex align-items-center justify-content-center me-2">
                              <i class="ti ti-user"></i>
                            </div>
                            <div>
                              <h6 class="mb-0"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                              <small class="text-muted"><?php echo $student['student_id']; ?></small>
                            </div>
                          </div>
                        </td>
                        <?php for ($period = 1; $period <= 8; $period++): 
                          $period_name = "period_$period";
                          $current_status = $student['attendance'][$period_name] ?? 'present';
                        ?>
                        <td class="text-center">
                          <input type="checkbox" 
                                 class="form-check-input period-checkbox" 
                                 name="students[<?php echo $student['id']; ?>][<?php echo $period_name; ?>]" 
                                 value="absent"
                                 <?php echo $current_status === 'absent' ? 'checked' : ''; ?>>
                          <input type="hidden" name="students[<?php echo $student['id']; ?>][<?php echo $period_name; ?>]" value="present">
                        </td>
                        <?php endfor; ?>
                        <td class="text-center">
                          <button type="button" class="btn btn-sm btn-danger mark-all-absent" data-student="<?php echo $student['id']; ?>">
                            All Absent
                          </button>
                          <button type="button" class="btn btn-sm btn-success mark-all-present" data-student="<?php echo $student['id']; ?>">
                            All Present
                          </button>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                
                <div class="mt-4">
                  <button type="submit" name="submit_attendance" class="btn btn-primary">
                    <i class="ti ti-check me-2"></i>Save Attendance
                  </button>
                </div>
              </form>
              <?php elseif ($selected_class_id): ?>
              <div class="alert alert-info">
                <i class="ti ti-info-circle me-2"></i>No students found in this class.
              </div>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Legend -->
          <div class="card mt-3">
            <div class="card-body">
              <h5 class="card-title">Legend</h5>
              <p class="mb-2"><i class="ti ti-checkbox text-danger me-2"></i><strong>Checked:</strong> Student is absent for that period</p>
              <p class="mb-0"><i class="ti ti-square text-success me-2"></i><strong>Unchecked:</strong> Student is present for that period</p>
            </div>
          </div>
          
          <div class="py-6 px-6 text-center">
            <p class="mb-0 fs-4">Designed and Developed by <a class="pe-1 text-primary text-decoration-none">QUOLYTECH</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  
  <script>
    $(document).ready(function() {
      // Mark all absent
      $('.mark-all-absent').click(function() {
        const studentId = $(this).data('student');
        $(`input[name^="students[${studentId}][period_"]`).prop('checked', true);
      });
      
      // Mark all present
      $('.mark-all-present').click(function() {
        const studentId = $(this).data('student');
        $(`input[name^="students[${studentId}][period_"]`).prop('checked', false);
      });
    });
  </script>
</body>
</html>
