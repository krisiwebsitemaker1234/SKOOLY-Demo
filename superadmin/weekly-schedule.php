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

// Check if class has periods configured
$periods = $conn->query("SELECT * FROM class_periods WHERE class_id = $class_id ORDER BY period_number ASC")->fetch_all(MYSQLI_ASSOC);

if (empty($periods)) {
    $_SESSION['error_message'] = "Please configure periods for this class first.";
    header("Location: manage-class-periods.php?class_id=" . $class_id);
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Save schedule entry
        if ($_POST['action'] === 'save_schedule') {
            $period_id = intval($_POST['period_id']);
            $day_of_week = $_POST['day_of_week'];
            $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : NULL;
            $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : NULL;
            $room_number = trim($_POST['room_number']);
            $notes = trim($_POST['notes']);
            
            // Check if this slot already exists
            $check = $conn->prepare("SELECT id FROM weekly_schedule_template WHERE class_id = ? AND period_id = ? AND day_of_week = ?");
            $check->bind_param("iis", $class_id, $period_id, $day_of_week);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            
            if ($existing) {
                // Update existing
                if ($teacher_id && $subject_id) {
                    $stmt = $conn->prepare("UPDATE weekly_schedule_template SET teacher_id = ?, subject_id = ?, room_number = ?, notes = ? WHERE id = ?");
                    $stmt->bind_param("iissi", $teacher_id, $subject_id, $room_number, $notes, $existing['id']);
                } else {
                    // Delete if both teacher and subject are empty
                    $stmt = $conn->prepare("DELETE FROM weekly_schedule_template WHERE id = ?");
                    $stmt->bind_param("i", $existing['id']);
                }
            } else {
                // Insert new
                if ($teacher_id && $subject_id) {
                    $stmt = $conn->prepare("INSERT INTO weekly_schedule_template (class_id, period_id, day_of_week, teacher_id, subject_id, academic_year_id, room_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisiisss", $class_id, $period_id, $day_of_week, $teacher_id, $subject_id, $current_year['id'], $room_number, $notes);
                }
            }
            
            if (isset($stmt) && $stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating schedule']);
            }
            exit;
        }
        
        // Delete schedule entry
        if ($_POST['action'] === 'delete_schedule') {
            $schedule_id = intval($_POST['schedule_id']);
            
            $stmt = $conn->prepare("DELETE FROM weekly_schedule_template WHERE id = ? AND class_id = ?");
            $stmt->bind_param("ii", $schedule_id, $class_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting schedule']);
            }
            exit;
        }
    }
}

// Get all teachers
$teachers = $conn->query("SELECT id, first_name, last_name FROM teachers ORDER BY first_name ASC")->fetch_all(MYSQLI_ASSOC);

// Get all subjects
$subjects = $conn->query("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name ASC")->fetch_all(MYSQLI_ASSOC);

// Get weekly schedule
$weekly_schedule = $conn->query("
    SELECT ws.*, 
           t.first_name as teacher_fname, t.last_name as teacher_lname,
           s.subject_name, s.subject_code,
           cp.period_number, cp.period_name, cp.start_time, cp.end_time, cp.is_break
    FROM weekly_schedule_template ws
    INNER JOIN teachers t ON ws.teacher_id = t.id
    INNER JOIN subjects s ON ws.subject_id = s.id
    INNER JOIN class_periods cp ON ws.period_id = cp.id
    WHERE ws.class_id = $class_id AND ws.academic_year_id = {$current_year['id']}
    ORDER BY cp.period_number ASC
")->fetch_all(MYSQLI_ASSOC);

// Organize schedule by day and period
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$schedule_grid = [];

foreach ($days as $day) {
    $schedule_grid[$day] = [];
    foreach ($periods as $period) {
        $schedule_grid[$day][$period['id']] = null;
    }
}

foreach ($weekly_schedule as $entry) {
    $schedule_grid[$entry['day_of_week']][$entry['period_id']] = $entry;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Weekly Schedule - <?php echo htmlspecialchars($class['class_name']); ?> - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    .schedule-grid {
      overflow-x: auto;
    }
    .schedule-table {
      min-width: 1000px;
    }
    .schedule-cell {
      min-height: 80px;
      border: 1px solid #dee2e6;
      padding: 8px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .schedule-cell:hover {
      background-color: #f8f9fa;
      border-color: #5D87FF;
    }
    .schedule-cell.empty {
      background-color: #f8f9fa;
    }
    .schedule-cell.filled {
      background-color: #e7f3ff;
      border-left: 3px solid #5D87FF;
    }
    .schedule-cell.break {
      background-color: #fff8e1;
      cursor: not-allowed;
    }
    .period-header {
      background-color: #5D87FF;
      color: white;
      font-weight: 600;
      padding: 10px;
      text-align: center;
    }
    .day-header {
      background-color: #49BEFF;
      color: white;
      font-weight: 600;
      padding: 10px;
      text-align: center;
      writing-mode: vertical-rl;
      text-orientation: mixed;
      min-width: 40px;
    }
    .schedule-info {
      font-size: 12px;
    }
    .teacher-name {
      font-weight: 600;
      color: #2a3547;
    }
    .subject-name {
      color: #5a6a85;
      font-size: 11px;
    }
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
                      <h4 class="mb-0"><?php echo htmlspecialchars($class['class_name']); ?> - Weekly Schedule Template</h4>
                      <p class="mb-0 text-muted">Click on any cell to assign teacher and subject</p>
                    </div>
                    <div>
                      <a href="manage-class-periods.php?class_id=<?php echo $class_id; ?>" class="btn btn-info">
                        <i class="ti ti-clock"></i> Manage Periods
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Schedule Grid -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Weekly Schedule Grid</h5>
                    <span class="badge bg-info"><?php echo count($weekly_schedule); ?> slots filled</span>
                  </div>
                  
                  <div class="schedule-grid">
                    <table class="table schedule-table mb-0">
                      <thead>
                        <tr>
                          <th class="period-header" style="width: 150px;">Period / Day</th>
                          <?php foreach ($days as $day): ?>
                          <th class="period-header"><?php echo $day; ?></th>
                          <?php endforeach; ?>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($periods as $period): ?>
                        <tr>
                          <td class="day-header">
                            <div>
                              <strong>P<?php echo $period['period_number']; ?>: <?php echo $period['period_name']; ?></strong><br>
                              <small><?php echo date('g:i A', strtotime($period['start_time'])); ?> - <?php echo date('g:i A', strtotime($period['end_time'])); ?></small>
                            </div>
                          </td>
                          <?php foreach ($days as $day): ?>
                          <td>
                            <?php if ($period['is_break']): ?>
                            <div class="schedule-cell break text-center">
                              <i class="ti ti-coffee"></i> Break
                            </div>
                            <?php else: ?>
                              <?php 
                              $slot = $schedule_grid[$day][$period['id']];
                              $cellClass = $slot ? 'filled' : 'empty';
                              ?>
                              <div class="schedule-cell <?php echo $cellClass; ?>" 
                                   onclick="openScheduleModal(<?php echo $period['id']; ?>, '<?php echo $day; ?>', <?php echo $slot ? $slot['id'] : 'null'; ?>, <?php echo $slot ? htmlspecialchars(json_encode($slot)) : 'null'; ?>)">
                                <?php if ($slot): ?>
                                  <div class="schedule-info">
                                    <div class="teacher-name">
                                      <i class="ti ti-user"></i> <?php echo $slot['teacher_fname'] . ' ' . $slot['teacher_lname']; ?>
                                    </div>
                                    <div class="subject-name">
                                      <i class="ti ti-book"></i> <?php echo $slot['subject_name']; ?>
                                    </div>
                                    <?php if ($slot['room_number']): ?>
                                    <div class="subject-name">
                                      <i class="ti ti-door"></i> Room <?php echo $slot['room_number']; ?>
                                    </div>
                                    <?php endif; ?>
                                  </div>
                                <?php else: ?>
                                  <div class="text-center text-muted">
                                    <i class="ti ti-plus"></i> Click to assign
                                  </div>
                                <?php endif; ?>
                              </div>
                            <?php endif; ?>
                          </td>
                          <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  
                  <div class="alert alert-info mt-3">
                    <i class="ti ti-info-circle"></i>
                    <strong>Note:</strong> This weekly template will repeat every week. Use the calendar view to make specific day changes/overrides.
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

  <!-- Schedule Modal -->
  <div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="scheduleForm">
          <input type="hidden" name="action" value="save_schedule">
          <input type="hidden" name="period_id" id="modal_period_id">
          <input type="hidden" name="day_of_week" id="modal_day_of_week">
          <input type="hidden" name="schedule_id" id="modal_schedule_id">
          
          <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Assign Schedule</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Teacher <span class="text-danger">*</span></label>
              <select class="form-select" name="teacher_id" id="modal_teacher_id" required>
                <option value="">Select Teacher</option>
                <?php foreach ($teachers as $teacher): ?>
                <option value="<?php echo $teacher['id']; ?>">
                  <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Subject <span class="text-danger">*</span></label>
              <select class="form-select" name="subject_id" id="modal_subject_id" required>
                <option value="">Select Subject</option>
                <?php foreach ($subjects as $subject): ?>
                <option value="<?php echo $subject['id']; ?>">
                  <?php echo $subject['subject_name']; ?> (<?php echo $subject['subject_code']; ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Room Number</label>
              <input type="text" class="form-control" name="room_number" id="modal_room_number" placeholder="e.g., 201">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" id="modal_notes" rows="2" placeholder="Optional notes..."></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="deleteBtn" onclick="deleteSchedule()" style="display: none;">
              <i class="ti ti-trash"></i> Delete
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
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
    let scheduleModal;
    let currentScheduleId = null;
    
    document.addEventListener('DOMContentLoaded', function() {
      scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
      
      // Handle form submission
      document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSchedule();
      });
    });
    
    function openScheduleModal(periodId, dayOfWeek, scheduleId, scheduleData) {
      currentScheduleId = scheduleId;
      
      document.getElementById('modal_period_id').value = periodId;
      document.getElementById('modal_day_of_week').value = dayOfWeek;
      document.getElementById('modal_schedule_id').value = scheduleId || '';
      
      document.getElementById('modalTitle').textContent = scheduleId ? 'Edit Schedule' : 'Assign Schedule';
      
      if (scheduleData) {
        document.getElementById('modal_teacher_id').value = scheduleData.teacher_id;
        document.getElementById('modal_subject_id').value = scheduleData.subject_id;
        document.getElementById('modal_room_number').value = scheduleData.room_number || '';
        document.getElementById('modal_notes').value = scheduleData.notes || '';
        document.getElementById('deleteBtn').style.display = 'inline-block';
      } else {
        document.getElementById('scheduleForm').reset();
        document.getElementById('modal_period_id').value = periodId;
        document.getElementById('modal_day_of_week').value = dayOfWeek;
        document.getElementById('deleteBtn').style.display = 'none';
      }
      
      scheduleModal.show();
    }
    
    function saveSchedule() {
      const formData = new FormData(document.getElementById('scheduleForm'));
      
      fetch('weekly-schedule.php?class_id=<?php echo $class_id; ?>', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Error: ' + (data.message || 'Failed to save schedule'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to save schedule');
      });
    }
    
    function deleteSchedule() {
      if (!currentScheduleId) return;
      
      if (!confirm('Are you sure you want to delete this schedule entry?')) return;
      
      const formData = new FormData();
      formData.append('action', 'delete_schedule');
      formData.append('schedule_id', currentScheduleId);
      
      fetch('weekly-schedule.php?class_id=<?php echo $class_id; ?>', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Error: ' + (data.message || 'Failed to delete schedule'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete schedule');
      });
    }
  </script>
</body>
</html>