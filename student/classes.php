<?php
require_once '../config/functions.php';
require_role(['student']);

$user = get_user_details(get_user_id());
$student = get_student_details(get_user_id());
$current_year = get_current_academic_year();

// Get filter and search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$grade_filter = isset($_GET['grade']) ? intval($_GET['grade']) : '';

// Build query
$query = "SELECT c.*, 
          COUNT(DISTINCT s.id) as student_count,
          t.first_name as guardian_fname, 
          t.last_name as guardian_lname
          FROM classes c
          LEFT JOIN students s ON c.id = s.class_id
          LEFT JOIN teachers t ON c.guardian_teacher_id = t.id
          WHERE c.academic_year_id = ?";

$params = [$current_year['id']];
$types = "i";

// Add search filter
if (!empty($search)) {
    $query .= " AND (c.class_name LIKE ? OR c.location LIKE ? OR c.description LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Add grade level filter
if (!empty($grade_filter)) {
    $query .= " AND c.grade_level = ?";
    $params[] = $grade_filter;
    $types .= "i";
}

$query .= " GROUP BY c.id ORDER BY c.grade_level ASC, c.section ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get available grade levels for filter
$grade_stmt = $conn->query("SELECT DISTINCT grade_level FROM classes ORDER BY grade_level ASC");
$available_grades = $grade_stmt->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Classes - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    .class-card {
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .class-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .class-image-gallery {
      display: flex;
      gap: 8px;
      margin-top: 12px;
    }
    .class-image-gallery img {
      width: 32%;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.2s;
    }
    .class-image-gallery img:hover {
      transform: scale(1.05);
    }
    .class-placeholder-img {
      width: 32%;
      height: 80px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 12px;
    }
    .location-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #f5f5f5;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 13px;
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
                      <h4 class="mb-0">All Classes</h4>
                      <p class="mb-0 text-muted">Browse and explore all classes in the school</p>
                    </div>
                    <div class="round-48 rounded-circle text-bg-primary d-flex align-items-center justify-content-center">
                      <i class="ti ti-school fs-6 text-white"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Search and Filter -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Search Classes</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search by class name, location, or description..." value="<?php echo htmlspecialchars($search); ?>">
                      </div>
                    </div>
                    
                    <div class="col-md-4">
                      <label class="form-label">Filter by Grade Level</label>
                      <select class="form-select" name="grade">
                        <option value="">All Grades</option>
                        <?php foreach ($available_grades as $grade): ?>
                        <option value="<?php echo $grade['grade_level']; ?>" <?php echo $grade_filter == $grade['grade_level'] ? 'selected' : ''; ?>>
                          Grade <?php echo $grade['grade_level']; ?>
                        </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end gap-2">
                      <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-filter"></i> Filter
                      </button>
                      <?php if (!empty($search) || !empty($grade_filter)): ?>
                      <a href="classes.php" class="btn btn-outline-secondary">
                        <i class="ti ti-x"></i>
                      </a>
                      <?php endif; ?>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Results Count -->
          <div class="row">
            <div class="col-12">
              <p class="text-muted mb-3">
                <i class="ti ti-list"></i> Found <?php echo count($classes); ?> class<?php echo count($classes) != 1 ? 'es' : ''; ?>
                <?php if (!empty($search)): ?>
                  matching "<?php echo htmlspecialchars($search); ?>"
                <?php endif; ?>
              </p>
            </div>
          </div>
          
          <!-- Classes Grid -->
          <div class="row">
            <?php if (empty($classes)): ?>
            <div class="col-12">
              <div class="card">
                <div class="card-body text-center py-5">
                  <i class="ti ti-folder-off fs-1 text-muted"></i>
                  <p class="text-muted mt-3 mb-0">No classes found matching your criteria</p>
                  <?php if (!empty($search) || !empty($grade_filter)): ?>
                  <a href="classes.php" class="btn btn-sm btn-primary mt-3">Clear Filters</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php else: ?>
            
            <?php foreach ($classes as $class): ?>
            <div class="col-lg-6 col-xl-4">
              <div class="card class-card">
                <div class="card-body">
                  <!-- Class Header -->
                  <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                      <h5 class="mb-1"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                      <span class="badge bg-primary-subtle text-primary">Grade <?php echo $class['grade_level']; ?> - Section <?php echo $class['section']; ?></span>
                    </div>
                    <?php if ($student['class_id'] == $class['id']): ?>
                    <span class="badge bg-success">Your Class</span>
                    <?php endif; ?>
                  </div>
                  
                  <!-- Location -->
                  <?php if (!empty($class['location'])): ?>
                  <div class="mb-3">
                    <span class="location-badge">
                      <i class="ti ti-map-pin text-danger"></i>
                      <?php echo htmlspecialchars($class['location']); ?>
                    </span>
                  </div>
                  <?php endif; ?>
                  
                  <!-- Description -->
                  <?php if (!empty($class['description'])): ?>
                  <p class="text-muted mb-3" style="font-size: 14px;">
                    <?php echo htmlspecialchars(substr($class['description'], 0, 100)); ?>
                    <?php echo strlen($class['description']) > 100 ? '...' : ''; ?>
                  </p>
                  <?php endif; ?>
                  
                  <!-- Class Info -->
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center">
                      <i class="ti ti-users text-primary me-1"></i>
                      <small class="text-muted"><?php echo $class['student_count']; ?>/<?php echo $class['max_students']; ?> Students</small>
                    </div>
                    
                    <?php if ($class['guardian_fname']): ?>
                    <div class="d-flex align-items-center">
                      <i class="ti ti-user-check text-success me-1"></i>
                      <small class="text-muted"><?php echo $class['guardian_fname'] . ' ' . $class['guardian_lname']; ?></small>
                    </div>
                    <?php endif; ?>
                  </div>
                  
                  <!-- Image Gallery -->
                  <div class="class-image-gallery">
                    <?php if (!empty($class['image1']) || !empty($class['image2']) || !empty($class['image3'])): ?>
                      <?php if (!empty($class['image1'])): ?>
                      <img src="../uploads/classes/<?php echo htmlspecialchars($class['image1']); ?>" alt="Class Image 1" onclick="viewImage(this.src)">
                      <?php else: ?>
                      <div class="class-placeholder-img">No Image</div>
                      <?php endif; ?>
                      
                      <?php if (!empty($class['image2'])): ?>
                      <img src="../uploads/classes/<?php echo htmlspecialchars($class['image2']); ?>" alt="Class Image 2" onclick="viewImage(this.src)">
                      <?php else: ?>
                      <div class="class-placeholder-img">No Image</div>
                      <?php endif; ?>
                      
                      <?php if (!empty($class['image3'])): ?>
                      <img src="../uploads/classes/<?php echo htmlspecialchars($class['image3']); ?>" alt="Class Image 3" onclick="viewImage(this.src)">
                      <?php else: ?>
                      <div class="class-placeholder-img">No Image</div>
                      <?php endif; ?>
                    <?php else: ?>
                      <div class="class-placeholder-img">
                        <i class="ti ti-photo fs-4"></i>
                      </div>
                      <div class="class-placeholder-img">
                        <i class="ti ti-photo fs-4"></i>
                      </div>
                      <div class="class-placeholder-img">
                        <i class="ti ti-photo fs-4"></i>
                      </div>
                    <?php endif; ?>
                  </div>
                  
                  <!-- Actions -->
                  <div class="mt-3">
                    <button class="btn btn-sm btn-outline-primary w-100" onclick="viewClassDetails(<?php echo $class['id']; ?>)">
                      <i class="ti ti-eye"></i> View Details
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            
            <?php endif; ?>
          </div>
          
          <div class="py-6 px-6 text-center">
            <p class="mb-0 fs-4">Designed and Developed by <a class="pe-1 text-primary text-decoration-none">QUOLYTECH</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Image Modal -->
  <div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Class Image</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <img id="modalImage" src="" style="max-width: 100%; height: auto; border-radius: 8px;">
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  
  <script>
    function viewImage(src) {
      document.getElementById('modalImage').src = src;
      var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
      imageModal.show();
    }
    
    function viewClassDetails(classId) {
      // You can implement this to show more details about the class
      // For now, it can redirect to a detail page or show a modal
      alert('Class details page coming soon! Class ID: ' + classId);
      // window.location.href = 'class-details.php?id=' + classId;
    }
  </script>
</body>
</html>