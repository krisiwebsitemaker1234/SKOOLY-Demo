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

// Get timetable for the student's class
$stmt = $conn->prepare("
    SELECT t.*, s.subject_name, te.first_name as teacher_fname, te.last_name as teacher_lname
    FROM timetable t
    INNER JOIN subjects s ON t.subject_id = s.id
    INNER JOIN teachers te ON t.teacher_id = te.id
    WHERE t.class_id = ? AND t.academic_year_id = ?
    ORDER BY 
        FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
        t.period_number
");
$stmt->bind_param("ii", $student['class_id'], $current_year['id']);
$stmt->execute();
$timetable = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Organize by day
$schedule_by_day = [];
foreach ($timetable as $entry) {
    $schedule_by_day[$entry['day_of_week']][$entry['period_number']] = $entry;
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Schedule - <?php echo SITE_NAME; ?></title>
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
          
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-1">My Weekly Schedule</h4>
                  <p class="text-muted mb-4">Class: <?php echo $class['class_name']; ?> | Academic Year: <?php echo $current_year['year_name']; ?></p>
                  
                  <?php if (empty($timetable)): ?>
                  <div class="text-center py-5">
                    <i class="ti ti-clock fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No schedule available yet</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead class="table-light">
                        <tr>
                          <th>Period</th>
                          <?php foreach ($days as $day): ?>
                          <th><?php echo $day; ?></th>
                          <?php endforeach; ?>
                        </tr>
                      </thead>
                      <tbody>
                        <?php for ($period = 1; $period <= 8; $period++): ?>
                        <tr>
                          <td class="fw-bold">Period <?php echo $period; ?></td>
                          <?php foreach ($days as $day): ?>
                          <td>
                            <?php if (isset($schedule_by_day[$day][$period])): 
                              $entry = $schedule_by_day[$day][$period];
                            ?>
                            <div class="p-2 bg-primary-subtle rounded">
                              <strong class="d-block"><?php echo $entry['subject_name']; ?></strong>
                              <small class="text-muted"><?php echo $entry['teacher_fname'] . ' ' . $entry['teacher_lname']; ?></small>
                              <small class="d-block text-muted">
                                <i class="ti ti-clock"></i> <?php echo date('H:i', strtotime($entry['start_time'])); ?> - <?php echo date('H:i', strtotime($entry['end_time'])); ?>
                              </small>
                              <?php if ($entry['room_number']): ?>
                              <small class="d-block text-muted">
                                <i class="ti ti-door"></i> Room <?php echo $entry['room_number']; ?>
                              </small>
                              <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-muted p-2">-</div>
                            <?php endif; ?>
                          </td>
                          <?php endforeach; ?>
                        </tr>
                        <?php endfor; ?>
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
