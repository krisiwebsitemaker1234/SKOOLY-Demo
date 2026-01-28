<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$current_year = get_current_academic_year();

// Get current month and year from URL or use current
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validate month and year
$month = max(1, min(12, $month));

// Get all classes for dropdown
$classes = $conn->query("SELECT * FROM classes WHERE academic_year_id = {$current_year['id']} ORDER BY grade_level, section")->fetch_all(MYSQLI_ASSOC);

// Calculate calendar data
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$day_of_week = date('w', $first_day); // 0 (Sunday) to 6 (Saturday)
$month_name = date('F', $first_day);

// Adjust to start week on Monday
$day_of_week = ($day_of_week == 0) ? 6 : $day_of_week - 1;

// Get school calendar data for this month
$start_date = date('Y-m-01', $first_day);
$end_date = date('Y-m-t', $first_day);

$calendar_data = $conn->query("
    SELECT * FROM school_calendar 
    WHERE date BETWEEN '$start_date' AND '$end_date' 
    AND academic_year_id = {$current_year['id']}
")->fetch_all(MYSQLI_ASSOC);

// Organize calendar data by date
$calendar_by_date = [];
foreach ($calendar_data as $cal) {
    $calendar_by_date[$cal['date']] = $cal;
}

// Get teacher availability for this month
$availability_data = $conn->query("
    SELECT ta.*, t.first_name, t.last_name
    FROM teacher_availability ta
    INNER JOIN teachers t ON ta.teacher_id = t.id
    WHERE ta.date BETWEEN '$start_date' AND '$end_date'
")->fetch_all(MYSQLI_ASSOC);

// Organize availability by date
$availability_by_date = [];
foreach ($availability_data as $avail) {
    if (!isset($availability_by_date[$avail['date']])) {
        $availability_by_date[$avail['date']] = [];
    }
    $availability_by_date[$avail['date']][] = $avail;
}

// Get count of daily overrides for each day
$overrides_data = $conn->query("
    SELECT date, COUNT(*) as override_count
    FROM daily_schedule_overrides
    WHERE date BETWEEN '$start_date' AND '$end_date'
    GROUP BY date
")->fetch_all(MYSQLI_ASSOC);

$overrides_by_date = [];
foreach ($overrides_data as $override) {
    $overrides_by_date[$override['date']] = $override['override_count'];
}

// Previous and next month links
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Schedule Calendar - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 1px;
      background-color: #dee2e6;
      border: 1px solid #dee2e6;
    }
    .calendar-day-header {
      background-color: #5D87FF;
      color: white;
      padding: 10px;
      text-align: center;
      font-weight: 600;
    }
    .calendar-day {
      background-color: white;
      min-height: 100px;
      padding: 8px;
      cursor: pointer;
      transition: all 0.2s;
      position: relative;
    }
    .calendar-day:hover {
      background-color: #f8f9fa;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .calendar-day.other-month {
      background-color: #f8f9fa;
      color: #adb5bd;
    }
    .calendar-day.weekend {
      background-color: #fff8e1;
    }
    .calendar-day.school-day {
      background-color: white;
    }
    .calendar-day.holiday {
      background-color: #ffebee;
    }
    .calendar-day.today {
      border: 2px solid #5D87FF;
      box-shadow: 0 0 10px rgba(93, 135, 255, 0.3);
    }
    .day-number {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 5px;
    }
    .day-indicators {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-top: 5px;
    }
    .indicator-badge {
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 3px;
    }
    .holiday-title {
      font-size: 11px;
      color: #d32f2f;
      font-weight: 600;
      margin-top: 4px;
    }
    .absent-count {
      font-size: 11px;
      color: #f57c00;
    }
    .override-count {
      font-size: 11px;
      color: #1976d2;
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
                      <h4 class="mb-0">Schedule Calendar</h4>
                      <p class="mb-0 text-muted">Manage daily schedules, teacher absences, and holidays</p>
                    </div>
                    <div>
                      <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                        <i class="ti ti-calendar-off"></i> Mark Holiday
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Calendar Navigation -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                      <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-outline-primary">
                        <i class="ti ti-chevron-left"></i> Previous
                      </a>
                    </div>
                    <h3 class="mb-0"><?php echo $month_name . ' ' . $year; ?></h3>
                    <div>
                      <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-outline-primary">
                        Next <i class="ti ti-chevron-right"></i>
                      </a>
                    </div>
                  </div>
                  
                  <!-- Legend -->
                  <div class="d-flex gap-3 mb-3 flex-wrap">
                    <span class="badge bg-light text-dark"><i class="ti ti-square-filled" style="color: white;"></i> School Day</span>
                    <span class="badge bg-light text-dark"><i class="ti ti-square-filled" style="color: #fff8e1;"></i> Weekend</span>
                    <span class="badge bg-light text-dark"><i class="ti ti-square-filled" style="color: #ffebee;"></i> Holiday</span>
                    <span class="badge bg-warning"><i class="ti ti-user-off"></i> Teacher Absent</span>
                    <span class="badge bg-info"><i class="ti ti-edit"></i> Has Overrides</span>
                  </div>
                  
                  <!-- Calendar Grid -->
                  <div class="calendar-grid">
                    <!-- Day Headers -->
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    <div class="calendar-day-header">Sun</div>
                    
                    <!-- Empty cells before first day -->
                    <?php for ($i = 0; $i < $day_of_week; $i++): ?>
                    <div class="calendar-day other-month"></div>
                    <?php endfor; ?>
                    
                    <!-- Days of month -->
                    <?php for ($day = 1; $day <= $days_in_month; $day++): 
                      $current_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
                      $timestamp = mktime(0, 0, 0, $month, $day, $year);
                      $day_name = date('l', $timestamp);
                      
                      $is_weekend = ($day_name == 'Saturday' || $day_name == 'Sunday');
                      $is_today = ($current_date == date('Y-m-d'));
                      
                      $calendar_info = isset($calendar_by_date[$current_date]) ? $calendar_by_date[$current_date] : null;
                      $is_holiday = $calendar_info && !$calendar_info['is_school_day'];
                      
                      $absent_teachers = isset($availability_by_date[$current_date]) ? $availability_by_date[$current_date] : [];
                      $absent_count = count($absent_teachers);
                      
                      $override_count = isset($overrides_by_date[$current_date]) ? $overrides_by_date[$current_date] : 0;
                      
                      $day_class = 'calendar-day';
                      if ($is_today) $day_class .= ' today';
                      if ($is_holiday) {
                        $day_class .= ' holiday';
                      } elseif ($is_weekend) {
                        $day_class .= ' weekend';
                      } else {
                        $day_class .= ' school-day';
                      }
                    ?>
                    <div class="<?php echo $day_class; ?>" onclick="openDayView('<?php echo $current_date; ?>')">
                      <div class="day-number"><?php echo $day; ?></div>
                      
                      <?php if ($calendar_info && $calendar_info['title']): ?>
                      <div class="holiday-title">
                        <i class="ti ti-flag"></i> <?php echo htmlspecialchars($calendar_info['title']); ?>
                      </div>
                      <?php endif; ?>
                      
                      <div class="day-indicators">
                        <?php if ($absent_count > 0): ?>
                        <span class="indicator-badge bg-warning-subtle text-warning">
                          <i class="ti ti-user-off"></i> <?php echo $absent_count; ?> absent
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($override_count > 0): ?>
                        <span class="indicator-badge bg-info-subtle text-info">
                          <i class="ti ti-edit"></i> <?php echo $override_count; ?> changes
                        </span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php endfor; ?>
                    
                    <!-- Empty cells after last day -->
                    <?php 
                    $remaining_cells = (7 - (($days_in_month + $day_of_week) % 7)) % 7;
                    for ($i = 0; $i < $remaining_cells; $i++): 
                    ?>
                    <div class="calendar-day other-month"></div>
                    <?php endfor; ?>
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

  <!-- Add Holiday Modal -->
  <div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="manage-holidays.php">
          <input type="hidden" name="action" value="add_holiday">
          
          <div class="modal-header">
            <h5 class="modal-title">Mark Holiday/Day Off</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="date" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Holiday Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" placeholder="e.g., National Holiday" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Mark as Holiday</button>
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
    function openDayView(date) {
      window.location.href = 'daily-schedule-view.php?date=' + date;
    }
  </script>
</body>
</html>