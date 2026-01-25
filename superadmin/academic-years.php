<?php
require_once '../config/functions.php';
require_role(['superadmin']);
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $year_name = sanitize_input($_POST['year_name']);
        $start_date = sanitize_input($_POST['start_date']);
        $end_date = sanitize_input($_POST['end_date']);
        $duration_years = intval($_POST['duration_years']);
        $stmt = $conn->prepare("INSERT INTO academic_years (year_name, start_date, end_date, duration_years) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $year_name, $start_date, $end_date, $duration_years);
        if ($stmt->execute()) {
            $message = "Academic year created successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
    } elseif ($_POST['action'] === 'set_current') {
        $id = intval($_POST['id']);
        $conn->query("UPDATE academic_years SET is_current = 0");
        $stmt = $conn->prepare("UPDATE academic_years SET is_current = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Current academic year updated!";
        }
    }
}
$years = $conn->query("SELECT * FROM academic_years ORDER BY start_date DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Academic Years - <?php echo SITE_NAME; ?></title>
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
          <?php if ($message): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
          <?php endif; ?>
          <div class="card">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="card-title mb-0">Academic Years</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addYearModal"><i class="ti ti-plus me-1"></i>Add Academic Year</button>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead><tr><th>Year Name</th><th>Duration</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th></tr></thead>
                  <tbody>
                    <?php foreach ($years as $year): ?>
                    <tr>
                      <td><strong><?php echo $year['year_name']; ?></strong></td>
                      <td><?php echo $year['duration_years']; ?> years</td>
                      <td><?php echo format_date($year['start_date']); ?></td>
                      <td><?php echo format_date($year['end_date']); ?></td>
                      <td><?php echo $year['is_current'] ? '<span class="badge bg-success">Current</span>' : '<span class="badge bg-secondary">Past</span>'; ?></td>
                      <td>
                        <?php if (!$year['is_current']): ?>
                        <form method="POST" class="d-inline">
                          <input type="hidden" name="action" value="set_current">
                          <input type="hidden" name="id" value="<?php echo $year['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-primary">Set as Current</button>
                        </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="py-6 px-6 text-center"><p class="mb-0 fs-4">Designed and Developed by <a class="pe-1 text-primary text-decoration-none">QUOLYTECH</a></p></div>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="addYearModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add Academic Year</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="create">
            <div class="mb-3"><label class="form-label">Year Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="year_name" placeholder="e.g., 2024-2025" required></div>
            <div class="mb-3"><label class="form-label">Start Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="start_date" required></div>
            <div class="mb-3"><label class="form-label">End Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="end_date" required></div>
            <div class="mb-3"><label class="form-label">Program Duration <span class="text-danger">*</span></label><select class="form-select" name="duration_years" required><option value="3">3 Years</option><option value="4" selected>4 Years</option></select></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create Year</button></div>
        </form>
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
