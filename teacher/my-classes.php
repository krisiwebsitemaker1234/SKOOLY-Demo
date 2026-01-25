<?php
require_once '../config/functions.php';
require_role(['teacher']);
$teacher = get_teacher_details(get_user_id());
$current_year = get_current_academic_year();
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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Classes - <?php echo SITE_NAME; ?></title>
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
          <div class="card">
            <div class="card-body">
              <h4 class="card-title mb-4">My Classes</h4>
              <div class="row">
                <?php foreach ($classes as $class): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                  <div class="card border">
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-3">
                        <div class="round-48 rounded-circle text-bg-primary d-flex align-items-center justify-content-center me-2">
                          <i class="ti ti-school text-white"></i>
                        </div>
                        <div>
                          <h5 class="mb-0"><?php echo $class['class_name']; ?></h5>
                          <small class="text-muted"><?php echo $class['student_count']; ?> students</small>
                        </div>
                      </div>
                      <div class="d-grid gap-2">
                        <a href="mark-attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-primary btn-sm">Mark Attendance</a>
                        <a href="grade-students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-primary btn-sm">Grade Students</a>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
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
