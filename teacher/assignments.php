<?php
require_once '../config/functions.php';
require_role(['teacher']);

$user = get_user_details(get_user_id());
$student = get_student_details(get_user_id());
$current_year = get_current_academic_year();

// Get assignments for student's class
$stmt = $conn->prepare("
    SELECT a.*, s.subject_name, t.first_name as teacher_fname, t.last_name as teacher_lname,
           sub.submission_date, sub.marks_obtained, sub.status as submission_status
    FROM assignments a
    INNER JOIN subjects s ON a.subject_id = s.id
    INNER JOIN teachers t ON a.teacher_id = t.id
    LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
    WHERE a.class_id = ? AND a.academic_year_id = ?
    ORDER BY a.due_date ASC
");
$stmt->bind_param("iii", $student['id'], $student['class_id'], $current_year['id']);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Separate into pending and completed
$pending = array_filter($assignments, function($a) {
    return empty($a['submission_status']) && $a['status'] === 'active';
});
$completed = array_filter($assignments, function($a) {
    return !empty($a['submission_status']);
});
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Assignments - <?php echo SITE_NAME; ?></title>
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
          
          <!-- Stats -->
          <div class="row">
            <div class="col-md-6">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-warning d-flex align-items-center justify-content-center">
                      <i class="ti ti-clipboard-list fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo count($pending); ?></h4>
                      <span class="text-muted">Pending Assignments</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-success d-flex align-items-center justify-content-center">
                      <i class="ti ti-clipboard-check fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo count($completed); ?></h4>
                      <span class="text-muted">Completed Assignments</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Pending Assignments -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Pending Assignments</h4>
                  
                  <?php if (empty($pending)): ?>
                  <div class="text-center py-5">
                    <i class="ti ti-clipboard-check fs-1 text-success"></i>
                    <p class="text-muted mt-2">No pending assignments! Great job!</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>Assignment</th>
                          <th>Subject</th>
                          <th>Teacher</th>
                          <th>Due Date</th>
                          <th>Total Marks</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($pending as $assignment): 
                          $days_left = (strtotime($assignment['due_date']) - time()) / (60 * 60 * 24);
                        ?>
                        <tr>
                          <td>
                            <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                            <?php if ($assignment['description']): ?>
                            <br><small class="text-muted"><?php echo substr(htmlspecialchars($assignment['description']), 0, 100); ?>...</small>
                            <?php endif; ?>
                          </td>
                          <td><?php echo $assignment['subject_name']; ?></td>
                          <td><?php echo $assignment['teacher_fname'] . ' ' . $assignment['teacher_lname']; ?></td>
                          <td><?php echo format_date($assignment['due_date']); ?></td>
                          <td><span class="badge bg-info"><?php echo $assignment['total_marks']; ?> marks</span></td>
                          <td>
                            <?php if ($days_left < 0): ?>
                            <span class="badge bg-danger">Overdue</span>
                            <?php elseif ($days_left < 1): ?>
                            <span class="badge bg-danger">Due Today</span>
                            <?php elseif ($days_left <= 3): ?>
                            <span class="badge bg-warning">Due Soon</span>
                            <?php else: ?>
                            <span class="badge bg-success">Upcoming</span>
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
          
          <!-- Completed Assignments -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Completed Assignments</h4>
                  
                  <?php if (empty($completed)): ?>
                  <div class="text-center py-5">
                    <i class="ti ti-clipboard-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No completed assignments yet</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>Assignment</th>
                          <th>Subject</th>
                          <th>Submitted On</th>
                          <th>Marks Obtained</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($completed as $assignment): ?>
                        <tr>
                          <td><strong><?php echo htmlspecialchars($assignment['title']); ?></strong></td>
                          <td><?php echo $assignment['subject_name']; ?></td>
                          <td><?php echo $assignment['submission_date'] ? format_date($assignment['submission_date']) : 'N/A'; ?></td>
                          <td>
                            <?php if ($assignment['marks_obtained']): ?>
                            <span class="badge bg-primary">
                              <?php echo $assignment['marks_obtained']; ?>/<?php echo $assignment['total_marks']; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">Not graded</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($assignment['submission_status'] === 'graded'): ?>
                            <span class="badge bg-success">Graded</span>
                            <?php elseif ($assignment['submission_status'] === 'late'): ?>
                            <span class="badge bg-warning">Late Submission</span>
                            <?php else: ?>
                            <span class="badge bg-info">Submitted</span>
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
