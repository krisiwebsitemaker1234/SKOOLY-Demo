<?php
require_once '../config/functions.php';
require_role(['teacher']);

$user = get_user_details(get_user_id());
$teacher = get_teacher_details(get_user_id());
$current_year = get_current_academic_year();

// Get teacher's assigned classes
$stmt = $conn->prepare("
    SELECT DISTINCT c.*, COUNT(DISTINCT s.id) as student_count
    FROM classes c
    LEFT JOIN teacher_subjects ts ON c.id = ts.class_id
    LEFT JOIN students s ON c.id = s.class_id
    WHERE ts.teacher_id = ? AND ts.academic_year_id = ?
    GROUP BY c.id
");
$stmt->bind_param("ii", $teacher['id'], $current_year['id']);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get teacher's subjects
$stmt = $conn->prepare("
    SELECT DISTINCT sub.* 
    FROM subjects sub
    INNER JOIN teacher_subjects ts ON sub.id = ts.subject_id
    WHERE ts.teacher_id = ? AND ts.academic_year_id = ?
");
$stmt->bind_param("ii", $teacher['id'], $current_year['id']);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get today's schedule
$today = date('l'); // Monday, Tuesday, etc.
$stmt = $conn->prepare("
    SELECT t.*, s.subject_name, c.class_name 
    FROM timetable t
    INNER JOIN subjects s ON t.subject_id = s.id
    INNER JOIN classes c ON t.class_id = c.id
    WHERE t.teacher_id = ? AND t.day_of_week = ? AND t.academic_year_id = ?
    ORDER BY t.period_number
");
$stmt->bind_param("isi", $teacher['id'], $today, $current_year['id']);
$stmt->execute();
$today_schedule = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Teacher Dashboard - <?php echo SITE_NAME; ?></title>
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
                      <i class="ti ti-user-check fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0">Welcome back, <?php echo $teacher['first_name']; ?>!</h4>
                      <p class="mb-0 text-muted">Here's what's happening with your classes today</p>
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
                    <div class="round-48 rounded-circle text-bg-primary d-flex align-items-center justify-content-center">
                      <i class="ti ti-school fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo count($classes); ?></h4>
                      <span class="text-muted">Classes Teaching</span>
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
                      <i class="ti ti-book fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo count($subjects); ?></h4>
                      <span class="text-muted">Subjects</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-success d-flex align-items-center justify-content-center">
                      <i class="ti ti-clock fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo count($today_schedule); ?></h4>
                      <span class="text-muted">Classes Today</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Today's Schedule -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Today's Schedule - <?php echo $today; ?></h4>
                  
                  <?php if (empty($today_schedule)): ?>
                  <div class="text-center py-4">
                    <i class="ti ti-calendar-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No classes scheduled for today</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>Period</th>
                          <th>Time</th>
                          <th>Subject</th>
                          <th>Class</th>
                          <th>Room</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($today_schedule as $schedule): ?>
                        <tr>
                          <td><span class="badge bg-primary-subtle text-primary"><?php echo $schedule['period_number']; ?></span></td>
                          <td><?php echo date('H:i', strtotime($schedule['start_time'])) . ' - ' . date('H:i', strtotime($schedule['end_time'])); ?></td>
                          <td><?php echo $schedule['subject_name']; ?></td>
                          <td><?php echo $schedule['class_name']; ?></td>
                          <td><?php echo $schedule['room_number'] ?? 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <!-- My Classes -->
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">My Classes</h4>
                  
                  <?php foreach ($classes as $class): ?>
                  <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                      <div class="round-40 rounded-circle text-bg-primary d-flex align-items-center justify-content-center me-2">
                        <i class="ti ti-school"></i>
                      </div>
                      <div>
                        <h6 class="mb-0"><?php echo $class['class_name']; ?></h6>
                        <small class="text-muted"><?php echo $class['student_count']; ?> students</small>
                      </div>
                    </div>
                    <a href="mark-attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-primary">
                      Mark Attendance
                    </a>
                  </div>
                  <?php endforeach; ?>
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
