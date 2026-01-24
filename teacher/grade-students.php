<?php
require_once '../config/functions.php';
require_role(['teacher']);

$teacher = get_teacher_details(get_user_id());
$current_year = get_current_academic_year();
$message = '';
$error = '';

// Get teacher's assigned subjects and classes
$stmt = $conn->prepare("
    SELECT DISTINCT ts.*, s.subject_name, c.class_name, c.id as class_id, s.id as subject_id
    FROM teacher_subjects ts
    INNER JOIN subjects s ON ts.subject_id = s.id
    INNER JOIN classes c ON ts.class_id = c.id
    WHERE ts.teacher_id = ? AND ts.academic_year_id = ?
");
$stmt->bind_param("ii", $teacher['id'], $current_year['id']);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$selected_assignment = null;
$students = [];

if (isset($_POST['assignment_id'])) {
    $assignment_id = intval($_POST['assignment_id']);
    foreach ($assignments as $assignment) {
        if ($assignment['id'] == $assignment_id) {
            $selected_assignment = $assignment;
            break;
        }
    }
}

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grades'])) {
    $subject_id = intval($_POST['subject_id']);
    $class_id = intval($_POST['class_id']);
    $grade_type = sanitize_input($_POST['grade_type']);
    $exam_date = sanitize_input($_POST['exam_date']);
    $student_grades = $_POST['grades'] ?? [];
    
    foreach ($student_grades as $student_id => $grade_data) {
        if (!empty($grade_data['grade'])) {
            $grade = floatval($grade_data['grade']);
            $remarks = sanitize_input($grade_data['remarks'] ?? '');
            
            // Validate grade range
            if ($grade >= 4.0 && $grade <= 10.0) {
                $stmt = $conn->prepare("
                    INSERT INTO grades (student_id, subject_id, teacher_id, class_id, academic_year_id, grade, grade_type, exam_date, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiiiidsss", $student_id, $subject_id, $teacher['id'], $class_id, $current_year['id'], $grade, $grade_type, $exam_date, $remarks);
                $stmt->execute();
            }
        }
    }
    
    $message = "Grades submitted successfully!";
}

// Get students if class/subject selected
if ($selected_assignment) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY first_name, last_name");
    $stmt->bind_param("i", $selected_assignment['class_id']);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Grade Students - <?php echo SITE_NAME; ?></title>
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
            <i class="ti ti-check me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <div class="card">
            <div class="card-body">
              <h4 class="card-title mb-4">Grade Students</h4>
              
              <!-- Subject and Class Selection -->
              <form method="POST" class="mb-4">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Select Subject & Class</label>
                    <select class="form-select" name="assignment_id" required onchange="this.form.submit()">
                      <option value="">Choose subject and class...</option>
                      <?php foreach ($assignments as $assignment): ?>
                      <option value="<?php echo $assignment['id']; ?>" <?php echo ($selected_assignment && $selected_assignment['id'] == $assignment['id']) ? 'selected' : ''; ?>>
                        <?php echo $assignment['subject_name'] . ' - ' . $assignment['class_name']; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </form>
              
              <?php if ($selected_assignment && !empty($students)): ?>
              <!-- Grading Form -->
              <form method="POST">
                <input type="hidden" name="subject_id" value="<?php echo $selected_assignment['subject_id']; ?>">
                <input type="hidden" name="class_id" value="<?php echo $selected_assignment['class_id']; ?>">
                
                <div class="row mb-4">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Grade Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="grade_type" required>
                      <option value="">Select type...</option>
                      <option value="midterm">Midterm Exam</option>
                      <option value="final">Final Exam</option>
                      <option value="quiz">Quiz</option>
                      <option value="assignment">Assignment</option>
                      <option value="project">Project</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Exam/Assignment Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="exam_date" value="<?php echo date('Y-m-d'); ?>" required>
                  </div>
                </div>
                
                <div class="alert alert-info">
                  <i class="ti ti-info-circle me-2"></i><strong>Grading Scale:</strong> 4.0 (lowest) to 10.0 (highest)
                </div>
                
                <div class="table-responsive">
                  <table class="table table-bordered align-middle">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 35%;">Student Name</th>
                        <th style="width: 20%;">Student ID</th>
                        <th style="width: 15%;">Grade (4-10)</th>
                        <th style="width: 30%;">Remarks</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($students as $student): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="round-40 rounded-circle text-bg-light d-flex align-items-center justify-content-center me-2">
                              <i class="ti ti-user"></i>
                            </div>
                            <h6 class="mb-0"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                          </div>
                        </td>
                        <td><span class="badge bg-primary-subtle text-primary"><?php echo $student['student_id']; ?></span></td>
                        <td>
                          <input type="number" 
                                 class="form-control" 
                                 name="grades[<?php echo $student['id']; ?>][grade]" 
                                 step="0.1" 
                                 min="4.0" 
                                 max="10.0" 
                                 placeholder="e.g., 8.5">
                        </td>
                        <td>
                          <input type="text" 
                                 class="form-control" 
                                 name="grades[<?php echo $student['id']; ?>][remarks]" 
                                 placeholder="Optional remarks">
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                
                <div class="mt-4">
                  <button type="submit" name="submit_grades" class="btn btn-primary">
                    <i class="ti ti-check me-2"></i>Submit Grades
                  </button>
                </div>
              </form>
              <?php elseif ($selected_assignment): ?>
              <div class="alert alert-info">
                <i class="ti ti-info-circle me-2"></i>No students found in this class.
              </div>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Grading Guidelines -->
          <div class="card mt-3">
            <div class="card-body">
              <h5 class="card-title">Grading Guidelines</h5>
              <div class="row">
                <div class="col-md-3">
                  <span class="badge bg-success mb-2">9.0 - 10.0</span>
                  <p class="mb-0 small">Excellent Performance</p>
                </div>
                <div class="col-md-3">
                  <span class="badge bg-primary mb-2">7.0 - 8.9</span>
                  <p class="mb-0 small">Good Performance</p>
                </div>
                <div class="col-md-3">
                  <span class="badge bg-warning mb-2">6.0 - 6.9</span>
                  <p class="mb-0 small">Average Performance</p>
                </div>
                <div class="col-md-3">
                  <span class="badge bg-danger mb-2">4.0 - 5.9</span>
                  <p class="mb-0 small">Needs Improvement</p>
                </div>
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

  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>
</html>
