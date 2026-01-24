<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$current_year = get_current_academic_year();

// Get filter parameters
$filter_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
$filter_subject = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;
$filter_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;

// Build query based on filters
$query = "
    SELECT g.*, 
           s.first_name as student_fname, s.last_name as student_lname, s.student_id,
           sub.subject_name,
           c.class_name,
           t.first_name as teacher_fname, t.last_name as teacher_lname
    FROM grades g
    INNER JOIN students s ON g.student_id = s.id
    INNER JOIN subjects sub ON g.subject_id = sub.id
    INNER JOIN classes c ON g.class_id = c.id
    INNER JOIN teachers t ON g.teacher_id = t.id
    WHERE g.academic_year_id = {$current_year['id']}
";

if ($filter_class) {
    $query .= " AND g.class_id = $filter_class";
}
if ($filter_subject) {
    $query .= " AND g.subject_id = $filter_subject";
}
if ($filter_student) {
    $query .= " AND g.student_id = $filter_student";
}

$query .= " ORDER BY g.exam_date DESC LIMIT 100";

$grades = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get filter options
$classes = $conn->query("SELECT * FROM classes WHERE academic_year_id = {$current_year['id']} ORDER BY grade_level, section")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
$students = $conn->query("SELECT * FROM students ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Grades Overview - <?php echo SITE_NAME; ?></title>
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
          
          <div class="card">
            <div class="card-body">
              <h4 class="card-title mb-4">Grades Overview</h4>
              
              <!-- Filters -->
              <form method="GET" class="mb-4">
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Filter by Class</label>
                    <select class="form-select" name="class_id" onchange="this.form.submit()">
                      <option value="">All Classes</option>
                      <?php foreach ($classes as $class): ?>
                      <option value="<?php echo $class['id']; ?>" <?php echo $filter_class == $class['id'] ? 'selected' : ''; ?>>
                        <?php echo $class['class_name']; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Filter by Subject</label>
                    <select class="form-select" name="subject_id" onchange="this.form.submit()">
                      <option value="">All Subjects</option>
                      <?php foreach ($subjects as $subject): ?>
                      <option value="<?php echo $subject['id']; ?>" <?php echo $filter_subject == $subject['id'] ? 'selected' : ''; ?>>
                        <?php echo $subject['subject_name']; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Filter by Student</label>
                    <select class="form-select" name="student_id" onchange="this.form.submit()">
                      <option value="">All Students</option>
                      <?php foreach ($students as $student): ?>
                      <option value="<?php echo $student['id']; ?>" <?php echo $filter_student == $student['id'] ? 'selected' : ''; ?>>
                        <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                
                <?php if ($filter_class || $filter_subject || $filter_student): ?>
                <a href="grades.php" class="btn btn-sm btn-light">Clear Filters</a>
                <?php endif; ?>
              </form>
              
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Student</th>
                      <th>Class</th>
                      <th>Subject</th>
                      <th>Grade Type</th>
                      <th>Grade</th>
                      <th>Date</th>
                      <th>Teacher</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($grades)): ?>
                    <tr>
                      <td colspan="7" class="text-center py-4">
                        <i class="ti ti-certificate fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No grades found</p>
                      </td>
                    </tr>
                    <?php else: ?>
                      <?php foreach ($grades as $grade): ?>
                      <tr>
                        <td>
                          <div>
                            <h6 class="mb-0"><?php echo $grade['student_fname'] . ' ' . $grade['student_lname']; ?></h6>
                            <small class="text-muted"><?php echo $grade['student_id']; ?></small>
                          </div>
                        </td>
                        <td><?php echo $grade['class_name']; ?></td>
                        <td><?php echo $grade['subject_name']; ?></td>
                        <td><span class="badge bg-info-subtle text-info"><?php echo ucfirst($grade['grade_type']); ?></span></td>
                        <td>
                          <span class="badge bg-<?php echo get_grade_color($grade['grade']); ?> fs-4">
                            <?php echo $grade['grade']; ?>/10
                          </span>
                        </td>
                        <td><?php echo format_date($grade['exam_date']); ?></td>
                        <td><?php echo $grade['teacher_fname'] . ' ' . $grade['teacher_lname']; ?></td>
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

  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>
</html>
