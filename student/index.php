<?php
require_once '../config/functions.php';
require_role(['student']);

$user = get_user_details(get_user_id());
$student = get_student_details(get_user_id());
$current_year = get_current_academic_year();

// Get student's class
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->bind_param("i", $student['class_id']);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

// Get grades
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

// Calculate average grade
$average_grade = calculate_average_grade($student['id'], $current_year['id']);

// Get attendance stats
$attendance_stats = get_attendance_stats($student['id'], $current_year['id']);

// Get recent attendance for calendar
$stmt = $conn->prepare("
    SELECT * FROM attendance 
    WHERE student_id = ? 
    ORDER BY attendance_date DESC 
    LIMIT 30
");
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$recent_attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get upcoming assignments
$stmt = $conn->prepare("
    SELECT a.*, s.subject_name 
    FROM assignments a
    INNER JOIN subjects s ON a.subject_id = s.id
    WHERE a.class_id = ? AND a.due_date >= CURDATE() AND a.status = 'active'
    ORDER BY a.due_date ASC
    LIMIT 5
");
$stmt->bind_param("i", $student['class_id']);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard - <?php echo SITE_NAME; ?></title>
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
          
          <!-- Welcome Section -->
          <div class="row">
            <div class="col-12">
              <div class="card bg-primary-subtle">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-primary d-flex align-items-center justify-content-center">
                      <i class="ti ti-user fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0">Welcome back, <?php echo $student['first_name']; ?>!</h4>
                      <p class="mb-0 text-muted">Class: <?php echo $class['class_name']; ?> | Student ID: <?php echo $student['student_id']; ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Statistics -->
          <div class="row">
            <div class="col-lg-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-success d-flex align-items-center justify-content-center">
                      <i class="ti ti-certificate fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $average_grade ? $average_grade : 'N/A'; ?></h4>
                      <span class="text-muted">Average Grade</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-warning d-flex align-items-center justify-content-center">
                      <i class="ti ti-calendar-x fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $attendance_stats['total_absent_hours'] ?? 0; ?></h4>
                      <span class="text-muted">Absent Hours</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-info d-flex align-items-center justify-content-center">
                      <i class="ti ti-clipboard-list fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo count($assignments); ?></h4>
                      <span class="text-muted">Pending Assignments</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Recent Grades and Attendance Calendar -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Recent Grades</h4>
                  
                  <?php if (empty($grades)): ?>
                  <div class="text-center py-4">
                    <i class="ti ti-certificate fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No grades available yet</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>Subject</th>
                          <th>Type</th>
                          <th>Grade</th>
                          <th>Date</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach (array_slice($grades, 0, 5) as $grade): ?>
                        <tr>
                          <td><?php echo $grade['subject_name']; ?></td>
                          <td><span class="badge bg-info-subtle text-info"><?php echo ucfirst($grade['grade_type']); ?></span></td>
                          <td>
                            <span class="badge bg-<?php echo get_grade_color($grade['grade']); ?>">
                              <?php echo $grade['grade']; ?>/10
                            </span>
                          </td>
                          <td><?php echo format_date($grade['exam_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <a href="grades.php" class="btn btn-sm btn-primary mt-2">View All Grades</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Attendance Calendar (Last 30 Days)</h4>
                  
                  <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th class="text-center">Periods Absent</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach (array_slice($recent_attendance, 0, 10) as $att): ?>
                        <tr>
                          <td><?php echo format_date($att['attendance_date']); ?></td>
                          <td class="text-center">
                            <span class="badge bg-<?php echo $att['total_absent_hours'] > 0 ? 'danger' : 'success'; ?>">
                              <?php echo $att['total_absent_hours']; ?>/8
                            </span>
                          </td>
                          <td>
                            <?php if ($att['total_absent_hours'] == 0): ?>
                            <span class="badge bg-success">Perfect</span>
                            <?php elseif ($att['total_absent_hours'] <= 2): ?>
                            <span class="badge bg-warning">Partial</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Absent</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <a href="attendance.php" class="btn btn-sm btn-primary mt-2">View Full Attendance</a>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Upcoming Assignments -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Upcoming Assignments</h4>
                  
                  <?php if (empty($assignments)): ?>
                  <div class="text-center py-4">
                    <i class="ti ti-clipboard-check fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No upcoming assignments</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>Assignment</th>
                          <th>Subject</th>
                          <th>Due Date</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                          <td><?php echo $assignment['subject_name']; ?></td>
                          <td><?php echo format_date($assignment['due_date']); ?></td>
                          <td>
                            <?php
                            $days_left = (strtotime($assignment['due_date']) - time()) / (60 * 60 * 24);
                            if ($days_left < 1) {
                                echo '<span class="badge bg-danger">Due Today</span>';
                            } elseif ($days_left <= 3) {
                                echo '<span class="badge bg-warning">Due Soon</span>';
                            } else {
                                echo '<span class="badge bg-success">Upcoming</span>';
                            }
                            ?>
                          </td>
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
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>
</html>
