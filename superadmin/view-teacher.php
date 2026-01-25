<?php
require_once '../config/functions.php';
require_role(['superadmin']);
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT s.*, c.class_name, u.email FROM students s INNER JOIN classes c ON s.class_id = c.id INNER JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) { header('Location: students.php'); exit(); }
$current_year = get_current_academic_year();
$average = calculate_average_grade($student_id, $current_year['id']);
$attendance_stats = get_attendance_stats($student_id, $current_year['id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>View Student - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
    <?php include 'includes/sidebar.php'; ?>
    <div class="body-wrapper">
      <?php include 'includes/header.php'; ?>
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="mb-0">Student Details</h4>
            <div>
              <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="btn btn-primary"><i class="ti ti-edit me-1"></i>Edit</a>
              <a href="students.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Back</a>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-4">
              <div class="card">
                <div class="card-body text-center">
                  <div class="round-110 rounded-circle mx-auto text-bg-primary d-flex align-items-center justify-content-center mb-3">
                    <i class="ti ti-user fs-1 text-white"></i>
                  </div>
                  <h4 class="mb-0"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h4>
                  <p class="text-muted mb-3"><?php echo $student['class_name']; ?></p>
                  <span class="badge bg-primary-subtle text-primary"><?php echo $student['student_id']; ?></span>
                </div>
              </div>
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title mb-3">Quick Stats</h5>
                  <div class="mb-3"><label class="text-muted">Average Grade</label><h4 class="mb-0"><?php echo $average ? $average : 'N/A'; ?>/10</h4></div>
                  <div><label class="text-muted">Total Absent Hours</label><h4 class="mb-0"><?php echo $attendance_stats['total_absent_hours'] ?? 0; ?></h4></div>
                </div>
              </div>
            </div>
            <div class="col-lg-8">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title mb-4">Personal Information</h5>
                  <div class="row mb-3">
                    <div class="col-md-6"><label class="form-label text-muted">Student ID</label><p class="fw-bold"><?php echo $student['student_id']; ?></p></div>
                    <div class="col-md-6"><label class="form-label text-muted">Email</label><p class="fw-bold"><?php echo $student['email']; ?></p></div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-6"><label class="form-label text-muted">Date of Birth</label><p class="fw-bold"><?php echo format_date($student['date_of_birth']); ?></p></div>
                    <div class="col-md-6"><label class="form-label text-muted">Gender</label><p class="fw-bold"><?php echo ucfirst($student['gender']); ?></p></div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-6"><label class="form-label text-muted">Class</label><p class="fw-bold"><?php echo $student['class_name']; ?></p></div>
                    <div class="col-md-6"><label class="form-label text-muted">Enrollment Date</label><p class="fw-bold"><?php echo format_date($student['enrollment_date']); ?></p></div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-6"><label class="form-label text-muted">Phone</label><p class="fw-bold"><?php echo $student['phone'] ?? 'N/A'; ?></p></div>
                    <div class="col-md-6"><label class="form-label text-muted">Emergency Contact</label><p class="fw-bold"><?php echo $student['emergency_contact'] ?? 'N/A'; ?></p></div>
                  </div>
                  <div class="row"><div class="col-12"><label class="form-label text-muted">Address</label><p class="fw-bold"><?php echo $student['address'] ?? 'N/A'; ?></p></div></div>
                </div>
              </div>
            </div>
          </div>
          <div class="py-6 px-6 text-center"><p class="mb-0 fs-4">Designed and Developed by <a class="pe-1 text-primary text-decoration-none">QUOLYTECH</a></p></div>
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
