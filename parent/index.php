<?php
require_once '../config/functions.php';
require_role(['parent']);

$user = get_user_details(get_user_id());
$parent = get_parent_details(get_user_id());
$current_year = get_current_academic_year();

// Get parent's children
$children = get_parent_children($parent['id']);

// Get selected child (default to first child)
$selected_child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : ($children[0]['id'] ?? null);

$selected_child = null;
$child_grades = [];
$child_attendance = [];
$average_grade = 0;
$attendance_stats = [];

if ($selected_child_id) {
    foreach ($children as $child) {
        if ($child['id'] == $selected_child_id) {
            $selected_child = $child;
            break;
        }
    }
    
    if ($selected_child) {
        // Get child's grades
        $stmt = $conn->prepare("
            SELECT g.*, s.subject_name, t.first_name as teacher_fname, t.last_name as teacher_lname
            FROM grades g
            INNER JOIN subjects s ON g.subject_id = s.id
            INNER JOIN teachers t ON g.teacher_id = t.id
            WHERE g.student_id = ? AND g.academic_year_id = ?
            ORDER BY g.exam_date DESC
        ");
        $stmt->bind_param("ii", $selected_child_id, $current_year['id']);
        $stmt->execute();
        $child_grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get average grade
        $average_grade = calculate_average_grade($selected_child_id, $current_year['id']);
        
        // Get attendance stats
        $attendance_stats = get_attendance_stats($selected_child_id, $current_year['id']);
        
        // Get recent attendance
        $stmt = $conn->prepare("
            SELECT * FROM attendance 
            WHERE student_id = ? 
            ORDER BY attendance_date DESC 
            LIMIT 20
        ");
        $stmt->bind_param("i", $selected_child_id);
        $stmt->execute();
        $child_attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Parent Dashboard - <?php echo SITE_NAME; ?></title>
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
                      <i class="ti ti-heart-handshake fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0">Welcome, <?php echo $parent['first_name'] . ' ' . $parent['last_name']; ?>!</h4>
                      <p class="mb-0 text-muted">Monitor your children's academic progress</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Children Selection -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title mb-3">Select Child</h5>
                  <div class="row">
                    <?php foreach ($children as $child): ?>
                    <div class="col-md-4 mb-3">
                      <a href="?child_id=<?php echo $child['id']; ?>" 
                         class="card text-decoration-none <?php echo $selected_child_id == $child['id'] ? 'border-primary' : ''; ?>">
                        <div class="card-body">
                          <div class="d-flex align-items-center">
                            <div class="round-40 rounded-circle text-bg-light d-flex align-items-center justify-content-center me-2">
                              <i class="ti ti-user"></i>
                            </div>
                            <div>
                              <h6 class="mb-0"><?php echo $child['first_name'] . ' ' . $child['last_name']; ?></h6>
                              <small class="text-muted"><?php echo $child['class_name']; ?></small>
                            </div>
                          </div>
                        </div>
                      </a>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <?php if ($selected_child): ?>
          <!-- Statistics for Selected Child -->
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
                      <span class="text-muted">Total Absent Hours</span>
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
                      <i class="ti ti-calendar-stats fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $attendance_stats['total_days'] ?? 0; ?></h4>
                      <span class="text-muted">Days Tracked</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Grades and Attendance -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Recent Grades</h4>
                  
                  <?php if (empty($child_grades)): ?>
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
                        <?php foreach (array_slice($child_grades, 0, 8) as $grade): ?>
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
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Recent Attendance</h4>
                  
                  <?php if (empty($child_attendance)): ?>
                  <div class="text-center py-4">
                    <i class="ti ti-calendar-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No attendance records available</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover table-sm">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th class="text-center">Absent Hours</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($child_attendance as $att): ?>
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
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php else: ?>
          <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>Please select a child to view their academic information.
          </div>
          <?php endif; ?>
          
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
