<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$current_year = get_current_academic_year();

// Get class_id from URL
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if (!$class_id) {
    $_SESSION['error_message'] = "Invalid class selected.";
    header("Location: manage-classes.php");
    exit;
}

// Get class details
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? AND academic_year_id = ?");
$stmt->bind_param("ii", $class_id, $current_year['id']);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) {
    $_SESSION['error_message'] = "Class not found.";
    header("Location: manage-classes.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Add new period
        if ($_POST['action'] === 'add_period') {
            $period_number = intval($_POST['period_number']);
            $period_name = trim($_POST['period_name']);
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $is_break = isset($_POST['is_break']) ? 1 : 0;
            
            $stmt = $conn->prepare("INSERT INTO class_periods (class_id, period_number, period_name, start_time, end_time, is_break) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssi", $class_id, $period_number, $period_name, $start_time, $end_time, $is_break);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Period added successfully!";
            } else {
                $_SESSION['error_message'] = "Error adding period: " . $conn->error;
            }
            header("Location: manage-class-periods.php?class_id=" . $class_id);
            exit;
        }
        
        // Edit period
        if ($_POST['action'] === 'edit_period') {
            $period_id = intval($_POST['period_id']);
            $period_number = intval($_POST['period_number']);
            $period_name = trim($_POST['period_name']);
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $is_break = isset($_POST['is_break']) ? 1 : 0;
            
            $stmt = $conn->prepare("UPDATE class_periods SET period_number = ?, period_name = ?, start_time = ?, end_time = ?, is_break = ? WHERE id = ? AND class_id = ?");
            $stmt->bind_param("issssii", $period_number, $period_name, $start_time, $end_time, $is_break, $period_id, $class_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Period updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating period: " . $conn->error;
            }
            header("Location: manage-class-periods.php?class_id=" . $class_id);
            exit;
        }
        
        // Delete period
        if ($_POST['action'] === 'delete_period') {
            $period_id = intval($_POST['period_id']);
            
            $stmt = $conn->prepare("DELETE FROM class_periods WHERE id = ? AND class_id = ?");
            $stmt->bind_param("ii", $period_id, $class_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Period deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Error deleting period: " . $conn->error;
            }
            header("Location: manage-class-periods.php?class_id=" . $class_id);
            exit;
        }
        
        // Quick setup - create default periods
        if ($_POST['action'] === 'quick_setup') {
            $num_periods = intval($_POST['num_periods']);
            $start_time = $_POST['quick_start_time'];
            $period_duration = intval($_POST['period_duration']); // in minutes
            $break_after_period = intval($_POST['break_after_period']);
            $break_duration = intval($_POST['break_duration']); // in minutes
            
            // Delete existing periods
            $conn->query("DELETE FROM class_periods WHERE class_id = $class_id");
            
            $current_time = strtotime($start_time);
            $period_num = 1;
            
            for ($i = 1; $i <= $num_periods; $i++) {
                $period_start = date('H:i:s', $current_time);
                $current_time += ($period_duration * 60);
                $period_end = date('H:i:s', $current_time);
                
                // Insert period
                $stmt = $conn->prepare("INSERT INTO class_periods (class_id, period_number, period_name, start_time, end_time, is_break) VALUES (?, ?, ?, ?, ?, 0)");
                $period_name = "Period " . $period_num;
                $stmt->bind_param("iisss", $class_id, $period_num, $period_name, $period_start, $period_end);
                $stmt->execute();
                
                // Add break if needed
                if ($i == $break_after_period && $i < $num_periods) {
                    $break_start = date('H:i:s', $current_time);
                    $current_time += ($break_duration * 60);
                    $break_end = date('H:i:s', $current_time);
                    
                    $period_num++;
                    $stmt = $conn->prepare("INSERT INTO class_periods (class_id, period_number, period_name, start_time, end_time, is_break) VALUES (?, ?, 'Break', ?, ?, 1)");
                    $stmt->bind_param("iiss", $class_id, $period_num, $break_start, $break_end);
                    $stmt->execute();
                }
                
                $period_num++;
            }
            
            $_SESSION['success_message'] = "Quick setup completed! $num_periods periods created.";
            header("Location: manage-class-periods.php?class_id=" . $class_id);
            exit;
        }
    }
}

// Get all periods for this class
$periods = $conn->query("SELECT * FROM class_periods WHERE class_id = $class_id ORDER BY period_number ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Periods - <?php echo htmlspecialchars($class['class_name']); ?> - <?php echo SITE_NAME; ?></title>
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
          
          <!-- Success/Error Messages -->
          <?php if (isset($_SESSION['success_message'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <?php if (isset($_SESSION['error_message'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <!-- Page Header -->
          <div class="row">
            <div class="col-12">
              <div class="card bg-primary-subtle">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <a href="manage-classes.php" class="btn btn-sm btn-light mb-2">
                        <i class="ti ti-arrow-left"></i> Back to Classes
                      </a>
                      <h4 class="mb-0"><?php echo htmlspecialchars($class['class_name']); ?> - Period Schedule</h4>
                      <p class="mb-0 text-muted">Set up the daily period structure for this class</p>
                    </div>
                    <div>
                      <button class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#quickSetupModal">
                        <i class="ti ti-bolt"></i> Quick Setup
                      </button>
                      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
                        <i class="ti ti-plus"></i> Add Period
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Periods Table -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Daily Periods (<?php echo count($periods); ?>)</h4>
                  
                  <?php if (empty($periods)): ?>
                  <div class="text-center py-5">
                    <i class="ti ti-clock fs-1 text-muted"></i>
                    <p class="text-muted mt-3 mb-2">No periods configured yet</p>
                    <p class="text-muted small">Use Quick Setup to create periods automatically or add them manually</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>Period #</th>
                          <th>Period Name</th>
                          <th>Start Time</th>
                          <th>End Time</th>
                          <th>Duration</th>
                          <th>Type</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($periods as $period): 
                          $start = strtotime($period['start_time']);
                          $end = strtotime($period['end_time']);
                          $duration = ($end - $start) / 60; // in minutes
                        ?>
                        <tr>
                          <td><strong><?php echo $period['period_number']; ?></strong></td>
                          <td>
                            <?php echo htmlspecialchars($period['period_name']); ?>
                            <?php if ($period['is_break']): ?>
                            <span class="badge bg-warning-subtle text-warning ms-2">
                              <i class="ti ti-coffee"></i> Break
                            </span>
                            <?php endif; ?>
                          </td>
                          <td><?php echo date('g:i A', $start); ?></td>
                          <td><?php echo date('g:i A', $end); ?></td>
                          <td><?php echo $duration; ?> min</td>
                          <td>
                            <?php if ($period['is_break']): ?>
                            <span class="badge bg-warning">Break</span>
                            <?php else: ?>
                            <span class="badge bg-success">Teaching</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="btn-group">
                              <button class="btn btn-sm btn-outline-primary" onclick='editPeriod(<?php echo json_encode($period); ?>)'>
                                <i class="ti ti-edit"></i>
                              </button>
                              <button class="btn btn-sm btn-outline-danger" onclick="deletePeriod(<?php echo $period['id']; ?>, '<?php echo htmlspecialchars($period['period_name']); ?>')">
                                <i class="ti ti-trash"></i>
                              </button>
                            </div>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  
                  <div class="alert alert-info mt-3">
                    <i class="ti ti-info-circle"></i>
                    <strong>Note:</strong> These periods will be used for the weekly schedule and daily calendar. You can have teaching periods and break periods.
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

  <!-- Quick Setup Modal -->
  <div class="modal fade" id="quickSetupModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="quick_setup">
          
          <div class="modal-header">
            <h5 class="modal-title"><i class="ti ti-bolt"></i> Quick Period Setup</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="ti ti-alert-triangle"></i> This will delete all existing periods and create new ones.
            </div>
            
            <div class="mb-3">
              <label class="form-label">Number of Periods <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="num_periods" value="6" min="1" max="12" required>
              <small class="text-muted">How many teaching periods per day?</small>
            </div>
            
            <div class="mb-3">
              <label class="form-label">School Start Time <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="quick_start_time" value="08:00" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Period Duration (minutes) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="period_duration" value="45" min="15" max="120" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Add Break After Period <span class="text-danger">*</span></label>
              <select class="form-select" name="break_after_period" required>
                <option value="0">No Break</option>
                <option value="2">After Period 2</option>
                <option value="3" selected>After Period 3</option>
                <option value="4">After Period 4</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Break Duration (minutes) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="break_duration" value="15" min="5" max="60" required>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning">Create Periods</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Period Modal -->
  <div class="modal fade" id="addPeriodModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="add_period">
          
          <div class="modal-header">
            <h5 class="modal-title">Add New Period</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Period Number <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="period_number" value="<?php echo count($periods) + 1; ?>" min="1" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Period Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="period_name" placeholder="e.g., Period 1, Break, Lunch" required>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                <input type="time" class="form-control" name="start_time" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">End Time <span class="text-danger">*</span></label>
                <input type="time" class="form-control" name="end_time" required>
              </div>
            </div>
            
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_break" id="is_break_add">
                <label class="form-check-label" for="is_break_add">
                  This is a break/lunch period
                </label>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Period</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Period Modal -->
  <div class="modal fade" id="editPeriodModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="edit_period">
          <input type="hidden" name="period_id" id="edit_period_id">
          
          <div class="modal-header">
            <h5 class="modal-title">Edit Period</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Period Number <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="period_number" id="edit_period_number" min="1" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Period Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="period_name" id="edit_period_name" required>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                <input type="time" class="form-control" name="start_time" id="edit_start_time" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">End Time <span class="text-danger">*</span></label>
                <input type="time" class="form-control" name="end_time" id="edit_end_time" required>
              </div>
            </div>
            
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_break" id="edit_is_break">
                <label class="form-check-label" for="edit_is_break">
                  This is a break/lunch period
                </label>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Period</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deletePeriodModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="delete_period">
          <input type="hidden" name="period_id" id="delete_period_id">
          
          <div class="modal-header">
            <h5 class="modal-title">Confirm Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <p>Are you sure you want to delete <strong id="delete_period_name"></strong>?</p>
            <p class="text-danger"><i class="ti ti-alert-triangle"></i> This will also delete any schedules assigned to this period.</p>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Delete Period</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  
  <script>
    function editPeriod(period) {
      document.getElementById('edit_period_id').value = period.id;
      document.getElementById('edit_period_number').value = period.period_number;
      document.getElementById('edit_period_name').value = period.period_name;
      document.getElementById('edit_start_time').value = period.start_time;
      document.getElementById('edit_end_time').value = period.end_time;
      document.getElementById('edit_is_break').checked = period.is_break == 1;
      
      var editModal = new bootstrap.Modal(document.getElementById('editPeriodModal'));
      editModal.show();
    }
    
    function deletePeriod(periodId, periodName) {
      document.getElementById('delete_period_id').value = periodId;
      document.getElementById('delete_period_name').textContent = periodName;
      
      var deleteModal = new bootstrap.Modal(document.getElementById('deletePeriodModal'));
      deleteModal.show();
    }
  </script>
</body>
</html>