<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$user = get_user_details(get_user_id());
$current_year = get_current_academic_year();

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'];
$total_parents = $conn->query("SELECT COUNT(*) as count FROM parents")->fetch_assoc()['count'];
$total_classes = $conn->query("SELECT COUNT(*) as count FROM classes WHERE academic_year_id = {$current_year['id']}")->fetch_assoc()['count'];

// Get recent activities
$recent_students = $conn->query("SELECT s.*, c.class_name FROM students s INNER JOIN classes c ON s.class_id = c.id ORDER BY s.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$notifications = get_notifications(get_user_id(), 5);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    
    <!-- Sidebar Start -->
    <?php include 'includes/sidebar.php'; ?>
    <!-- Sidebar End -->
    
    <!-- Main wrapper -->
    <div class="body-wrapper">
      <!-- Header Start -->
      <?php include 'includes/header.php'; ?>
      <!-- Header End -->
      
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          
          <!-- Statistics Row -->
          <div class="row">
            <div class="col-lg-3 col-md-6">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-primary d-flex align-items-center justify-content-center">
                      <i class="ti ti-users fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $total_students; ?></h4>
                      <span class="text-muted">Total Students</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-warning d-flex align-items-center justify-content-center">
                      <i class="ti ti-user-check fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $total_teachers; ?></h4>
                      <span class="text-muted">Total Teachers</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-success d-flex align-items-center justify-content-center">
                      <i class="ti ti-heart-handshake fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $total_parents; ?></h4>
                      <span class="text-muted">Total Parents</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-info d-flex align-items-center justify-content-center">
                      <i class="ti ti-school fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $total_classes; ?></h4>
                      <span class="text-muted">Total Classes</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Charts Row -->
          <div class="row">
            <div class="col-lg-8">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Students Enrollment Overview</h4>
                  <p class="card-subtitle">Monthly enrollment statistics</p>
                  <div id="enrollment-chart" class="mt-4"></div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-4">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Grade Distribution</h4>
                  <div id="grade-chart"></div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Recent Students -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">Recently Added Students</h4>
                    <a href="students.php" class="btn btn-sm btn-primary">View All</a>
                  </div>
                  
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>Student ID</th>
                          <th>Name</th>
                          <th>Class</th>
                          <th>Enrollment Date</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($recent_students as $student): ?>
                        <tr>
                          <td><span class="badge bg-primary-subtle text-primary"><?php echo $student['student_id']; ?></span></td>
                          <td>
                            <div class="d-flex align-items-center">
                              <div class="round-40 rounded-circle text-bg-light d-flex align-items-center justify-content-center me-2">
                                <i class="ti ti-user"></i>
                              </div>
                              <div>
                                <h6 class="mb-0"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                              </div>
                            </div>
                          </td>
                          <td><?php echo $student['class_name']; ?></td>
                          <td><?php echo format_date($student['enrollment_date']); ?></td>
                          <td>
                            <a href="view-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-light">
                              <i class="ti ti-eye"></i>
                            </a>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
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
  <script src="../assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  
  <script>
    // Enrollment Chart
    var enrollmentOptions = {
      series: [{
        name: 'Students Enrolled',
        data: [45, 52, 38, 65, 72, 58, 90, 85, 95, 105, 98, 110]
      }],
      chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false }
      },
      colors: ['#5D87FF'],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      tooltip: { theme: 'dark' }
    };
    var enrollmentChart = new ApexCharts(document.querySelector("#enrollment-chart"), enrollmentOptions);
    enrollmentChart.render();
    
    // Grade Distribution Chart
    var gradeOptions = {
      series: [44, 55, 13, 33],
      chart: {
        type: 'donut',
        height: 300
      },
      labels: ['Grade 10', 'Grade 11', 'Grade 12', 'Grade 13'],
      colors: ['#5D87FF', '#49BEFF', '#13DEB9', '#FFAE1F'],
      legend: { position: 'bottom' }
    };
    var gradeChart = new ApexCharts(document.querySelector("#grade-chart"), gradeOptions);
    gradeChart.render();
  </script>
</body>
</html>
