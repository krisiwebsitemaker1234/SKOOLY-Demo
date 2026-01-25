<?php
require_once '../config/functions.php';
require_role(['parent']);
$parent = get_parent_details(get_user_id());
$children = get_parent_children($parent['id']);
$selected_child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : ($children[0]['id'] ?? null);
$selected_child = null;
$child_grades = [];
if ($selected_child_id) {
    foreach ($children as $child) { if ($child['id'] == $selected_child_id) { $selected_child = $child; break; } }
    if ($selected_child) {
        $stmt = $conn->prepare("SELECT * FROM grades WHERE student_id = ? ORDER BY grades_date DESC LIMIT 50");
        $stmt->bind_param("i", $selected_child_id);
        $stmt->execute();
        $child_grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Grades Records - <?php echo SITE_NAME; ?></title>
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
          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title mb-3">Select Child</h5>
              <div class="btn-group" role="group">
                <?php foreach ($children as $child): ?>
                <a href="?child_id=<?php echo $child['id']; ?>" class="btn <?php echo $selected_child_id == $child['id'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                  <?php echo $child['first_name'] . ' ' . $child['last_name']; ?>
                </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php if ($selected_child): ?>
          <div class="card">
            <div class="card-body">
              <h4 class="card-title mb-4"><?php echo $selected_child['first_name']; ?>'s Grades Records</h4>
              <?php if (empty($child_grades)): ?>
              <div class="text-center py-5"><i class="ti ti-calendar-x fs-1 text-muted"></i><p class="text-muted mt-2">No grades records</p></div>
              <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead><tr><th>Date</th><th>P1</th><th>P2</th><th>P3</th><th>P4</th><th>P5</th><th>P6</th><th>P7</th><th>P8</th><th>Total Absent</th><th>Status</th></tr></thead>
                  <tbody>
                    <?php foreach ($child_grades as $r): ?>
                    <tr>
                      <td><strong><?php echo format_date($r['grades_date']); ?></strong></td>
                      <?php for ($i = 1; $i <= 8; $i++): $icon = $r["period_$i"] === 'present' ? 'ti-check text-success' : 'ti-x text-danger'; ?>
                      <td><i class="ti <?php echo $icon; ?>"></i></td>
                      <?php endfor; ?>
                      <td><span class="badge bg-<?php echo $r['total_absent_hours'] > 0 ? 'danger' : 'success'; ?>"><?php echo $r['total_absent_hours']; ?>/8</span></td>
                      <td><?php if ($r['total_absent_hours'] == 0): ?><span class="badge bg-success">Perfect</span><?php elseif ($r['total_absent_hours'] <= 2): ?><span class="badge bg-warning">Partial</span><?php else: ?><span class="badge bg-danger">Absent</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
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
