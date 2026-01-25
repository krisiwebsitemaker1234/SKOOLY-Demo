<?php
require_once '../config/functions.php';
require_role(['student']);

$user = get_user_details(get_user_id());
$student = get_student_details(get_user_id());

// Get all attendance records
$stmt = $conn->prepare("
    SELECT * FROM attendance 
    WHERE student_id = ? 
    ORDER BY attendance_date DESC
");
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$attendance_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get attendance stats
$attendance_stats = get_attendance_stats($student['id'], null);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Attendance - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    .attendance-calendar {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 10px;
    }
    .calendar-day {
      border: 1px solid #ddd;
      padding: 10px;
      border-radius: 4px;
      text-align: center;
      min-height: 80px;
    }
    .calendar-day.perfect { background-color: #d4edda; border-color: #28a745; }
    .calendar-day.partial { background-color: #fff3cd; border-color: #ffc107; }
    .calendar-day.absent { background-color: #f8d7da; border-color: #dc3545; }
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
          
          <!-- Stats -->
          <div class="row">
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-success d-flex align-items-center justify-content-center">
                      <i class="ti ti-calendar-check fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $attendance_stats['total_days'] ?? 0; ?></h4>
                      <span class="text-muted">Days Tracked</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-danger d-flex align-items-center justify-content-center">
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
            
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-info d-flex align-items-center justify-content-center">
                      <i class="ti ti-percentage fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <?php 
                      $total_possible = ($attendance_stats['total_days'] ?? 0) * 8;
                      $attendance_rate = $total_possible > 0 ? (($total_possible - ($attendance_stats['total_absent_hours'] ?? 0)) / $total_possible * 100) : 100;
                      ?>
                      <h4 class="mb-0 fw-bold"><?php echo round($attendance_rate, 1); ?>%</h4>
                      <span class="text-muted">Attendance Rate</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Attendance Records Table -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Attendance Records</h4>
                  
                  <?php if (empty($attendance_records)): ?>
                  <div class="text-center py-5">
                    <i class="ti ti-calendar-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No attendance records available</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Period 1</th>
                          <th>Period 2</th>
                          <th>Period 3</th>
                          <th>Period 4</th>
                          <th>Period 5</th>
                          <th>Period 6</th>
                          <th>Period 7</th>
                          <th>Period 8</th>
                          <th>Total Absent</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                        <tr>
                          <td><strong><?php echo format_date($record['attendance_date']); ?></strong></td>
                          <?php for ($i = 1; $i <= 8; $i++): 
                            $status = $record["period_$i"];
                            $icon = $status === 'present' ? 'ti-check text-success' : 'ti-x text-danger';
                          ?>
                          <td><i class="ti <?php echo $icon; ?>"></i></td>
                          <?php endfor; ?>
                          <td>
                            <span class="badge bg-<?php echo $record['total_absent_hours'] > 0 ? 'danger' : 'success'; ?>">
                              <?php echo $record['total_absent_hours']; ?>/8
                            </span>
                          </td>
                          <td>
                            <?php if ($record['total_absent_hours'] == 0): ?>
                            <span class="badge bg-success">Perfect</span>
                            <?php elseif ($record['total_absent_hours'] <= 2): ?>
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
