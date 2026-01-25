<?php
require_once '../config/functions.php';
require_role(['student']);

$user = get_user_details(get_user_id());
$student = get_student_details(get_user_id());
$current_year = get_current_academic_year();

// Get all grades for the student
$stmt = $conn->prepare("
    SELECT g.*, s.subject_name, t.first_name as teacher_fname, t.last_name as teacher_lname
    FROM grades g
    INNER JOIN subjects s ON g.subject_id = s.id
    INNER JOIN teachers t ON g.teacher_id = t.id
    WHERE g.student_id = ? AND g.academic_year_id = ?
    ORDER BY g.exam_date DESC
");
$stmt->bind_param("ii", $student['id'], $current_year['id']);
$stmt->execute();
$grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate average by subject
$stmt = $conn->prepare("
    SELECT s.subject_name, AVG(g.grade) as average
    FROM grades g
    INNER JOIN subjects s ON g.subject_id = s.id
    WHERE g.student_id = ? AND g.academic_year_id = ?
    GROUP BY s.id
");
$stmt->bind_param("ii", $student['id'], $current_year['id']);
$stmt->execute();
$subject_averages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$overall_average = calculate_average_grade($student['id'], $current_year['id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Grades - <?php echo SITE_NAME; ?></title>
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
          
          <!-- Overall Average -->
          <div class="row">
            <div class="col-12">
              <div class="card bg-primary-subtle">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <h4 class="mb-1">Overall Average Grade</h4>
                      <p class="mb-0 text-muted">Academic Year: <?php echo $current_year['year_name']; ?></p>
                    </div>
                    <div class="text-end">
                      <h1 class="mb-0 display-4 fw-bold"><?php echo $overall_average ? $overall_average : 'N/A'; ?></h1>
                      <small class="text-muted">out of 10.0</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Subject Averages -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Average by Subject</h4>
                  <div class="row">
                    <?php foreach ($subject_averages as $avg): ?>
                    <div class="col-md-4 mb-3">
                      <div class="p-3 border rounded">
                        <h6 class="mb-2"><?php echo $avg['subject_name']; ?></h6>
                        <h3 class="mb-0">
                          <span class="badge bg-<?php echo get_grade_color($avg['average']); ?> fs-5">
                            <?php echo round($avg['average'], 1); ?>/10
                          </span>
                        </h3>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- All Grades -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">All Grades</h4>
                  
                  <?php if (empty($grades)): ?>
                  <div class="text-center py-5">
                    <i class="ti ti-certificate fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No grades available yet</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>Subject</th>
                          <th>Grade Type</th>
                          <th>Grade</th>
                          <th>Date</th>
                          <th>Teacher</th>
                          <th>Remarks</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($grades as $grade): ?>
                        <tr>
                          <td><strong><?php echo $grade['subject_name']; ?></strong></td>
                          <td><span class="badge bg-info-subtle text-info"><?php echo ucfirst($grade['grade_type']); ?></span></td>
                          <td>
                            <span class="badge bg-<?php echo get_grade_color($grade['grade']); ?> fs-5">
                              <?php echo $grade['grade']; ?>/10
                            </span>
                          </td>
                          <td><?php echo format_date($grade['exam_date']); ?></td>
                          <td><?php echo $grade['teacher_fname'] . ' ' . $grade['teacher_lname']; ?></td>
                          <td><?php echo $grade['remarks'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <?php endif; ?>
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
  <script src="../assets/libs/apexcharts/dist/apexcharts.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>
</html>
