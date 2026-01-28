<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$current_year = get_current_academic_year();

// Get date from URL
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$day_name = date('l', strtotime($date));
$formatted_date = date('F j, Y', strtotime($date));

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Mark teacher availability
        if ($_POST['action'] === 'mark_teacher_availability') {
            $teacher_id = intval($_POST['teacher_id']);
            $status = $_POST['status'];
            $reason = trim($_POST['reason']);
            $notes = trim($_POST['notes']);
            $user_id = get_user_id();
            
            // Check if exists
            $check = $conn->prepare("SELECT id FROM teacher_availability WHERE teacher_id = ? AND date = ?");
            $check->bind_param("is", $teacher_id, $date);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            
            if ($existing) {
                $stmt = $conn->prepare("UPDATE teacher_availability SET status = ?, reason = ?, notes = ?, marked_by = ? WHERE id = ?");
                $stmt->bind_param("sssii", $status, $reason, $notes, $user_id, $existing['id']);
            } else {
                $stmt = $conn->prepare("INSERT INTO teacher_availability (teacher_id, date, status, reason, notes, marked_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssi", $teacher_id, $date, $status, $reason, $notes, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Teacher availability updated!";
            } else {
                $_SESSION['error_message'] = "Error updating availability: " . $conn->error;
            }
            header("Location: daily-schedule-view.php?date=" . $date);
            exit;
        }
        
        // Save schedule override
        if ($_POST['action'] === 'save_override') {
            $class_id = intval($_POST['class_id']);
            $period_id = intval($_POST['period_id']);
            $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : NULL;
            $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : NULL;
            $room_number = trim($_POST['room_number']);
            $status = $_POST['status'];
            $substitute_teacher_id = !empty($_POST['substitute_teacher_id']) ? intval($_POST['substitute_teacher_id']) : NULL;
            $original_teacher_id = !empty($_POST['original_teacher_id']) ? intval($_POST['original_teacher_id']) : NULL;
            $notes = trim($_POST['notes']);
            $user_id = get_user_id();
            
            // Check if override exists
            $check = $conn->prepare("SELECT id FROM daily_schedule_overrides WHERE date = ? AND class_id = ? AND period_id = ?");
            $check->bind_param("sii", $date, $class_id, $period_id);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            
            if ($existing) {
                $stmt = $conn->prepare("UPDATE daily_schedule_overrides SET teacher_id = ?, subject_id = ?, room_number = ?, status = ?, substitute_teacher_id = ?, original_teacher_id = ?, notes = ?, created_by = ? WHERE id = ?");
                $stmt->bind_param("iissiisii", $teacher_id, $subject_id, $room_number, $status, $substitute_teacher_id, $original_teacher_id, $notes, $user_id, $existing['id']);
            } else {
                $stmt = $conn->prepare("INSERT INTO daily_schedule_overrides (date, class_id, period_id, teacher_id, subject_id, room_number, status, substitute_teacher_id, original_teacher_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siiiissiiis", $date, $class_id, $period_id, $teacher_id, $subject_id, $room_number, $status, $substitute_teacher_id, $original_teacher_id, $notes, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Schedule override saved!";
            } else {
                $_SESSION['error_message'] = "Error saving override: " . $conn->error;
            }
            header("Location: daily-schedule-view.php?date=" . $date);
            exit;
        }
        
        // Delete override
        if ($_POST['action'] === 'delete_override') {
            $override_id = intval($_POST['override_id']);
            
            $stmt = $conn->prepare("DELETE FROM daily_schedule_overrides WHERE id = ?");
            $stmt->bind_param("i", $override_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Override deleted!";
            } else {
                $_SESSION['error_message'] = "Error deleting override: " . $conn->error;
            }
            header("Location: daily-schedule-view.php?date=" . $date);
            exit;
        }
        
        // Mark day as holiday
        if ($_POST['action'] === 'mark_holiday') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            
            $stmt = $conn->prepare("INSERT INTO school_calendar (date, is_school_day, day_type, title, description, academic_year_id) VALUES (?, 0, 'holiday', ?, ?, ?) ON DUPLICATE KEY UPDATE is_school_day = 0, day_type = 'holiday', title = ?, description = ?");
            $stmt->bind_param("sssiss", $date, $title, $description, $current_year['id'], $title, $description);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Day marked as holiday!";
            } else {
                $_SESSION['error_message'] = "Error marking holiday: " . $conn->error;
            }
            header("Location: schedule-calendar.php");
            exit;
        }
    }
}

// Get all teachers with their availability for this date
$teachers = $conn->query("
    SELECT t.*, u.email,
           ta.status as availability_status, ta.reason as absence_reason, ta.notes as absence_notes
    FROM teachers t
    INNER JOIN users u ON t.user_id = u.id
    LEFT JOIN teacher_availability ta ON t.id = ta.teacher_id AND ta.date = '$date'
    ORDER BY t.first_name ASC
")->fetch_all(MYSQLI_ASSOC);

// Get all classes
$classes = $conn->query("SELECT * FROM classes WHERE academic_year_id = {$current_year['id']} ORDER BY grade_level, section")->fetch_all(MYSQLI_ASSOC);

// Get all subjects
$subjects = $conn->query("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name ASC")->fetch_all(MYSQLI_ASSOC);

// For each class, get their schedule for this day
$class_schedules = [];
foreach ($classes as $class) {
    $class_id = $class['id'];
    
    // Get class periods
    $periods = $conn->query("SELECT * FROM class_periods WHERE class_id = $class_id ORDER BY period_number ASC")->fetch_all(MYSQLI_ASSOC);
    
    // Get weekly template for this day
    $weekly_template = $conn->query("
        SELECT ws.*, 
               t.first_name as teacher_fname, t.last_name as teacher_lname,
               s.subject_name, s.subject_code,
               cp.period_number, cp.period_name, cp.start_time, cp.end_time, cp.is_break
        FROM weekly_schedule_template ws
        INNER JOIN teachers t ON ws.teacher_id = t.id
        INNER JOIN subjects s ON ws.subject_id = s.id
        INNER JOIN class_periods cp ON ws.period_id = cp.id
        WHERE ws.class_id = $class_id AND ws.day_of_week = '$day_name'
        ORDER BY cp.period_number ASC
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Get daily overrides for this date
    $overrides = $conn->query("
        SELECT dso.*, 
               t.first_name as teacher_fname, t.last_name as teacher_lname,
               st.first_name as sub_fname, st.last_name as sub_lname,
               ot.first_name as orig_fname, ot.last_name as orig_lname,
               s.subject_name, s.subject_code,
               cp.period_number, cp.period_name
        FROM daily_schedule_overrides dso
        LEFT JOIN teachers t ON dso.teacher_id = t.id
        LEFT JOIN teachers st ON dso.substitute_teacher_id = st.id
        LEFT JOIN teachers ot ON dso.original_teacher_id = ot.id
        LEFT JOIN subjects s ON dso.subject_id = s.id
        INNER JOIN class_periods cp ON dso.period_id = cp.id
        WHERE dso.date = '$date' AND dso.class_id = $class_id
        ORDER BY cp.period_number ASC
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Organize by period
    $schedule = [];
    foreach ($periods as $period) {
        $period_id = $period['id'];
        
        // Check for override first
        $override = null;
        foreach ($overrides as $o) {
            if ($o['period_id'] == $period_id) {
                $override = $o;
                break;
            }
        }
        
        // If no override, use weekly template
        $template = null;
        if (!$override) {
            foreach ($weekly_template as $wt) {
                if ($wt['period_id'] == $period_id) {
                    $template = $wt;
                    break;
                }
            }
        }
        
        $schedule[] = [
            'period' => $period,
            'template' => $template,
            'override' => $override,
            'display' => $override ?: $template
        ];
    }
    
    $class_schedules[$class_id] = [
        'class' => $class,
        'schedule' => $schedule
    ];
}

// Check if this day is marked as holiday
$holiday_info = $conn->query("SELECT * FROM school_calendar WHERE date = '$date' AND academic_year_id = {$current_year['id']}")->fetch_assoc();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daily Schedule - <?php echo $formatted_date; ?> - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    .teacher-card {
      transition: all 0.2s;
    }
    .teacher-card.absent {
      border-left: 4px solid #f57c00;
      background-color: #fff8e1;
    }
    .teacher-card.available {
      border-left: 4px solid #66bb6a;
    }
    .schedule-item {
      border: 1px solid #dee2e6;
      border-radius: 6px;
      padding: 10px;
      margin-bottom: 8px;
    }
    .schedule-item.has-override {
      border-left: 3px solid #1976d2;
      background-color: #e3f2fd;
    }
    .schedule-item.cancelled {
      opacity: 0.6;
      background-color: #ffebee;
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
                      <a href="schedule-calendar.php" class="btn btn-sm btn-light mb-2">
                        <i class="ti ti-arrow-left"></i> Back to Calendar
                      </a>
                      <h4 class="mb-0"><?php echo $formatted_date; ?> (<?php echo $day_name; ?>)</h4>
                      <p class="mb-0 text-muted">Manage daily schedule and teacher availability</p>
                    </div>
                    <div>
                      <?php if ($holiday_info && !$holiday_info['is_school_day']): ?>
                      <span class="badge bg-danger fs-4">
                        <i class="ti ti-calendar-off"></i> Holiday: <?php echo htmlspecialchars($holiday_info['title']); ?>
                      </span>
                      <?php else: ?>
                      <button class="btn btn-danger" onclick="markHoliday()">
                        <i class="ti ti-calendar-off"></i> Mark as Holiday
                      </button>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Teacher Availability Section -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title mb-3">
                    <i class="ti ti-users"></i> Teacher Availability (<?php echo count($teachers); ?>)
                  </h5>
                  
                  <div class="row">
                    <?php foreach ($teachers as $teacher): 
                      $is_absent = ($teacher['availability_status'] == 'absent' || $teacher['availability_status'] == 'partial');
                      $card_class = $is_absent ? 'absent' : 'available';
                    ?>
                    <div class="col-md-3 mb-3">
                      <div class="teacher-card <?php echo $card_class; ?> card h-100">
                        <div class="card-body p-3">
                          <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                              <h6 class="mb-0"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></h6>
                              <small class="text-muted"><?php echo $teacher['email']; ?></small>
                            </div>
                            <button class="btn btn-sm btn-light" onclick="markAvailability(<?php echo htmlspecialchars(json_encode($teacher)); ?>)">
                              <i class="ti ti-edit"></i>
                            </button>
                          </div>
                          
                          <?php if ($is_absent): ?>
                          <div class="mt-2">
                            <span class="badge bg-warning mb-1">
                              <?php echo ucfirst($teacher['availability_status']); ?>
                            </span>
                            <?php if ($teacher['absence_reason']): ?>
                            <div class="text-muted small">
                              <i class="ti ti-info-circle"></i> <?php echo htmlspecialchars($teacher['absence_reason']); ?>
                            </div>
                            <?php endif; ?>
                          </div>
                          <?php else: ?>
                          <span class="badge bg-success">Available</span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Class Schedules -->
          <?php foreach ($class_schedules as $class_schedule): 
            $class = $class_schedule['class'];
            $schedule = $class_schedule['schedule'];
          ?>
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title mb-3">
                    <?php echo htmlspecialchars($class['class_name']); ?> Schedule
                  </h5>
                  
                  <?php if (empty($schedule)): ?>
                  <div class="text-center py-4 text-muted">
                    <i class="ti ti-calendar-off fs-1"></i>
                    <p class="mt-2">No periods configured for this class</p>
                  </div>
                  <?php else: ?>
                  <div class="row">
                    <?php foreach ($schedule as $entry): 
                      $period = $entry['period'];
                      $display = $entry['display'];
                      $has_override = !empty($entry['override']);
                      
                      if ($period['is_break']) continue;
                      
                      $item_class = 'schedule-item';
                      if ($has_override) {
                        $item_class .= ' has-override';
                        if ($entry['override']['status'] == 'cancelled') {
                          $item_class .= ' cancelled';
                        }
                      }
                    ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                      <div class="<?php echo $item_class; ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <div>
                            <strong>Period <?php echo $period['period_number']; ?>: <?php echo $period['period_name']; ?></strong>
                            <div class="text-muted small">
                              <?php echo date('g:i A', strtotime($period['start_time'])); ?> - 
                              <?php echo date('g:i A', strtotime($period['end_time'])); ?>
                            </div>
                          </div>
                          <button class="btn btn-sm btn-light" onclick="editSchedule(<?php echo $class['id']; ?>, <?php echo $period['id']; ?>, <?php echo htmlspecialchars(json_encode($entry)); ?>)">
                            <i class="ti ti-edit"></i>
                          </button>
                        </div>
                        
                        <?php if ($display): ?>
                        <div class="mt-2">
                          <div><i class="ti ti-user"></i> <?php echo $display['teacher_fname'] . ' ' . $display['teacher_lname']; ?></div>
                          <div><i class="ti ti-book"></i> <?php echo $display['subject_name']; ?></div>
                          <?php if (!empty($display['room_number'])): ?>
                          <div><i class="ti ti-door"></i> Room <?php echo $display['room_number']; ?></div>
                          <?php endif; ?>
                          
                          <?php if ($has_override): ?>
                          <div class="mt-2">
                            <span class="badge bg-info">Modified</span>
                            <?php if ($entry['override']['status'] == 'substitute'): ?>
                            <span class="badge bg-warning">Substitute</span>
                            <?php elseif ($entry['override']['status'] == 'cancelled'): ?>
                            <span class="badge bg-danger">Cancelled</span>
                            <?php endif; ?>
                          </div>
                          <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-muted small text-center py-2">
                          <i class="ti ti-circle-dashed"></i> Not assigned
                        </div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          
          <div class="py-6 px-6 text-center">
            <p class="mb-0 fs-4">Designed and Developed by <a class="pe-1 text-primary text-decoration-none">QUOLYTECH</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Mark Teacher Availability Modal -->
  <div class="modal fade" id="availabilityModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="mark_teacher_availability">
          <input type="hidden" name="teacher_id" id="avail_teacher_id">
          
          <div class="modal-header">
            <h5 class="modal-title" id="avail_teacher_name">Mark Teacher Availability</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select class="form-select" name="status" id="avail_status" required>
                <option value="available">Available</option>
                <option value="absent">Absent (All Day)</option>
                <option value="partial">Partial (Some Periods)</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Reason</label>
              <input type="text" class="form-control" name="reason" id="avail_reason" placeholder="e.g., Sick, Personal, Training">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" id="avail_notes" rows="2"></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Schedule Override Modal -->
  <div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="save_override">
          <input type="hidden" name="class_id" id="sched_class_id">
          <input type="hidden" name="period_id" id="sched_period_id">
          <input type="hidden" name="original_teacher_id" id="sched_original_teacher_id">
          
          <div class="modal-header">
            <h5 class="modal-title" id="sched_title">Edit Schedule</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="alert alert-info">
              <i class="ti ti-info-circle"></i> Changes here only affect <strong><?php echo $formatted_date; ?></strong>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="sched_status">
                <option value="normal">Normal (Use this teacher/subject)</option>
                <option value="substitute">Substitute Teacher</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            
            <div class="mb-3" id="teacher_section">
              <label class="form-label">Teacher</label>
              <select class="form-select" name="teacher_id" id="sched_teacher_id">
                <option value="">Select Teacher</option>
                <?php foreach ($teachers as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo $t['first_name'] . ' ' . $t['last_name']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3" id="substitute_section" style="display: none;">
              <label class="form-label">Substitute Teacher</label>
              <select class="form-select" name="substitute_teacher_id" id="sched_substitute_teacher_id">
                <option value="">Select Substitute</option>
                <?php foreach ($teachers as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo $t['first_name'] . ' ' . $t['last_name']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Subject</label>
              <select class="form-select" name="subject_id" id="sched_subject_id">
                <option value="">Select Subject</option>
                <?php foreach ($subjects as $s): ?>
                <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Room Number</label>
              <input type="text" class="form-control" name="room_number" id="sched_room_number">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" id="sched_notes" rows="2"></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Override</button>
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
    let availabilityModal, scheduleModal;
    
    document.addEventListener('DOMContentLoaded', function() {
      availabilityModal = new bootstrap.Modal(document.getElementById('availabilityModal'));
      scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
      
      // Handle status change in schedule modal
      document.getElementById('sched_status').addEventListener('change', function() {
        const status = this.value;
        document.getElementById('teacher_section').style.display = (status === 'substitute') ? 'none' : 'block';
        document.getElementById('substitute_section').style.display = (status === 'substitute') ? 'block' : 'none';
      });
    });
    
    function markAvailability(teacher) {
      document.getElementById('avail_teacher_id').value = teacher.id;
      document.getElementById('avail_teacher_name').textContent = teacher.first_name + ' ' + teacher.last_name;
      document.getElementById('avail_status').value = teacher.availability_status || 'available';
      document.getElementById('avail_reason').value = teacher.absence_reason || '';
      document.getElementById('avail_notes').value = teacher.absence_notes || '';
      
      availabilityModal.show();
    }
    
    function editSchedule(classId, periodId, entry) {
      document.getElementById('sched_class_id').value = classId;
      document.getElementById('sched_period_id').value = periodId;
      
      const display = entry.override || entry.template;
      
      if (display) {
        document.getElementById('sched_teacher_id').value = display.teacher_id || '';
        document.getElementById('sched_subject_id').value = display.subject_id || '';
        document.getElementById('sched_room_number').value = display.room_number || '';
      }
      
      if (entry.override) {
        document.getElementById('sched_status').value = entry.override.status || 'normal';
        document.getElementById('sched_substitute_teacher_id').value = entry.override.substitute_teacher_id || '';
        document.getElementById('sched_original_teacher_id').value = entry.override.original_teacher_id || '';
        document.getElementById('sched_notes').value = entry.override.notes || '';
        
        const status = entry.override.status;
        document.getElementById('teacher_section').style.display = (status === 'substitute') ? 'none' : 'block';
        document.getElementById('substitute_section').style.display = (status === 'substitute') ? 'block' : 'none';
      } else {
        document.getElementById('sched_status').value = 'normal';
        document.getElementById('sched_notes').value = '';
        document.getElementById('teacher_section').style.display = 'block';
        document.getElementById('substitute_section').style.display = 'none';
        
        if (display) {
          document.getElementById('sched_original_teacher_id').value = display.teacher_id || '';
        }
      }
      
      scheduleModal.show();
    }
    
    function markHoliday() {
      const title = prompt('Enter holiday name:');
      if (!title) return;
      
      const form = document.createElement('form');
      form.method = 'POST';
      form.innerHTML = `
        <input type="hidden" name="action" value="mark_holiday">
        <input type="hidden" name="title" value="${title}">
        <input type="hidden" name="description" value="">
      `;
      document.body.appendChild(form);
      form.submit();
    }
  </script>
</body>
</html>