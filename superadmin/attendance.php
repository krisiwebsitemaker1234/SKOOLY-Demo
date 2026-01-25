<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$current_year = get_current_academic_year();

// Get filter parameters
$filter_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Build query
$query = "
    SELECT a.*, 
           s.first_name as student_fname, s.last_name as student_lname, s.student_id,
           c.class_name
    FROM attendance a
    INNER JOIN students s ON a.student_id = s.id
    INNER JOIN classes c ON a.class_id = c.id
    WHERE a.attendance_date = '$filter_date'
";

if ($filter_class) {
    $query .= " AND a.class_id = $filter_class";
}

$query .= " ORDER BY c.class_name, s.first_name, s.last_name";

$attendance_records = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get classes for filter
$classes = $conn->query("SELECT * FROM classes WHERE academic_year_id = {$current_year['id']} ORDER BY grade_level, section")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance Overview - <?php echo SITE_NAME; ?></title>
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
              <h4 class="card-title mb-4">Attendance Overview</h4>
              
              <!-- Filters -->
              <form method="GET" class="mb-4">
                <div class="row">
                  <div class="col-md-6 mb-3">
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
                  
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Filter by Date</label>
                    <input type="date" class="form-select" name="date" value="<?php echo $filter_date; ?>" onchange="this.form.submit()">
                  </div>
                </div>
                
                <?php if ($filter_class): ?>
                <a href="attendance.php?date=<?php echo $filter_date; ?>" class="btn btn-sm btn-light">Clear Class Filter</a>
                <?php endif; ?>
              </form>
              
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Student</th>
                      <th>Class</th>
                      <th>P1</th>
                      <th>P2</th>
                      <th>P3</th>
                      <th>P4</th>
                      <th>P5</th>
                      <th>P6</th>
                      <th>P7</th>
                      <th>P8</th>
                      <th>Total Absent</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($attendance_records)): ?>
                    <tr>
                      <td colspan="11" class="text-center py-4">
                        <i class="ti ti-calendar-x fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No attendance records for this date</p>
                      </td>
                    </tr>
                    <?php else: ?>
                      <?php foreach ($attendance_records as $record): ?>
                      <tr>
                        <td>
                          <div>
                            <h6 class="mb-0"><?php echo $record['student_fname'] . ' ' . $record['student_lname']; ?></h6>
                            <small class="text-muted"><?php echo $record['student_id']; ?></small>
                          </div>
                        </td>
                        <td><?php echo $record['class_name']; ?></td>
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
