<?php
require_once '../config/functions.php';
require_role(['superadmin']);
$current_year = get_current_academic_year();
$assignments = $conn->query("
    SELECT a.*, s.subject_name, c.class_name, t.first_name as teacher_fname, t.last_name as teacher_lname
    FROM assignments a
    INNER JOIN subjects s ON a.subject_id = s.id
    INNER JOIN classes c ON a.class_id = c.id
    INNER JOIN teachers t ON a.teacher_id = t.id
    WHERE a.academic_year_id = {$current_year['id']}
    ORDER BY a.due_date DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Assignments Overview - <?php echo SITE_NAME; ?></title>
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
              <h4 class="card-title mb-4">All Assignments</h4>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr><th>Title</th><th>Subject</th><th>Class</th><th>Teacher</th><th>Due Date</th><th>Status</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($assignments as $a): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($a['title']); ?></strong></td>
                      <td><?php echo $a['subject_name']; ?></td>
                      <td><?php echo $a['class_name']; ?></td>
                      <td><?php echo $a['teacher_fname'] . ' ' . $a['teacher_lname']; ?></td>
                      <td><?php echo format_date($a['due_date']); ?></td>
                      <td><span class="badge bg-<?php echo $a['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
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
