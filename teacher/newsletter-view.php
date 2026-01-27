<?php
require_once '../config/functions.php';
require_role(["teacher"]);

$user = get_user_details(get_user_id());
$student = get_student_details(get_user_id());

// Get filter parameters
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query for published newsletters only
$query = "SELECT n.*, u.username as author_name 
          FROM newsletters n
          INNER JOIN users u ON n.author_id = u.id
          WHERE n.status = 'published'";

$params = [];
$types = "";

// Add category filter
if (!empty($category_filter)) {
    $query .= " AND n.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

// Add search filter
if (!empty($search)) {
    $query .= " AND (n.title LIKE ? OR n.excerpt LIKE ? OR n.content LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY n.is_featured DESC, n.published_date DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $newsletters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $newsletters = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}

// Get featured newsletter
$featured = array_filter($newsletters, function($n) { return $n['is_featured'] == 1; });
$featured = !empty($featured) ? reset($featured) : null;

// Categories for filter
$categories = ['academic', 'events', 'sports', 'achievements', 'general'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Newsletters - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    .newsletter-card {
      transition: transform 0.2s, box-shadow 0.2s;
      height: 100%;
    }
    .newsletter-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .featured-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      z-index: 10;
    }
    .newsletter-cover {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 8px 8px 0 0;
    }
    .newsletter-cover-placeholder {
      width: 100%;
      height: 200px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px 8px 0 0;
    }
    .category-badge {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .featured-newsletter {
      background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
      border: 2px solid #667eea;
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
                      <h4 class="mb-0">School Newsletters</h4>
                      <p class="mb-0 text-muted">Stay updated with the latest news and announcements</p>
                    </div>
                    <div class="round-48 rounded-circle text-bg-primary d-flex align-items-center justify-content-center">
                      <i class="ti ti-news fs-6 text-white"></i>
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
                    <div class="col-md-7">
                      <label class="form-label">Search Newsletters</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search by title or content..." value="<?php echo htmlspecialchars($search); ?>">
                      </div>
                    </div>
                    
                    <div class="col-md-3">
                      <label class="form-label">Filter by Category</label>
                      <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $category_filter == $cat ? 'selected' : ''; ?>>
                          <?php echo ucfirst($cat); ?>
                        </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end gap-2">
                      <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-filter"></i> Filter
                      </button>
                      <?php if (!empty($search) || !empty($category_filter)): ?>
                      <a href="newsletters.php" class="btn btn-outline-secondary">
                        <i class="ti ti-x"></i>
                      </a>
                      <?php endif; ?>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Featured Newsletter -->
          <?php if ($featured && empty($search) && empty($category_filter)): ?>
          <div class="row">
            <div class="col-12">
              <div class="card featured-newsletter">
                <div class="card-body">
                  <div class="position-relative">
                    <span class="badge bg-warning text-dark featured-badge">
                      <i class="ti ti-star"></i> Featured
                    </span>
                    <div class="row">
                      <div class="col-md-4">
                        <?php if ($featured['cover_image']): ?>
                        <img src="../uploads/newsletters/<?php echo htmlspecialchars($featured['cover_image']); ?>" class="newsletter-cover">
                        <?php else: ?>
                        <div class="newsletter-cover-placeholder">
                          <i class="ti ti-news fs-1 text-white"></i>
                        </div>
                        <?php endif; ?>
                      </div>
                      <div class="col-md-8">
                        <div class="d-flex gap-2 mb-2">
                          <span class="badge bg-<?php echo get_category_color($featured['category']); ?>-subtle text-<?php echo get_category_color($featured['category']); ?> category-badge">
                            <?php echo ucfirst($featured['category']); ?>
                          </span>
                          <span class="badge bg-light text-dark">
                            <i class="ti ti-calendar"></i> <?php echo date('M d, Y', strtotime($featured['published_date'])); ?>
                          </span>
                        </div>
                        <h3 class="mb-3"><?php echo htmlspecialchars($featured['title']); ?></h3>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($featured['excerpt']); ?></p>
                        <a href="newsletter-view.php?id=<?php echo $featured['id']; ?>" class="btn btn-primary">
                          Read More <i class="ti ti-arrow-right"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
          
          <!-- Results Count -->
          <div class="row">
            <div class="col-12">
              <p class="text-muted mb-3">
                <i class="ti ti-list"></i> Found <?php echo count($newsletters); ?> newsletter<?php echo count($newsletters) != 1 ? 's' : ''; ?>
              </p>
            </div>
          </div>
          
          <!-- Newsletters Grid -->
          <div class="row">
            <?php if (empty($newsletters)): ?>
            <div class="col-12">
              <div class="card">
                <div class="card-body text-center py-5">
                  <i class="ti ti-inbox-off fs-1 text-muted"></i>
                  <p class="text-muted mt-3 mb-0">No newsletters found</p>
                  <?php if (!empty($search) || !empty($category_filter)): ?>
                  <a href="newsletters.php" class="btn btn-sm btn-primary mt-3">Clear Filters</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php else: ?>
            
            <?php foreach ($newsletters as $newsletter): ?>
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="card newsletter-card position-relative">
                <?php if ($newsletter['is_featured']): ?>
                <span class="badge bg-warning text-dark featured-badge">
                  <i class="ti ti-star"></i> Featured
                </span>
                <?php endif; ?>
                
                <?php if ($newsletter['cover_image']): ?>
                <img src="../uploads/newsletters/<?php echo htmlspecialchars($newsletter['cover_image']); ?>" class="newsletter-cover">
                <?php else: ?>
                <div class="newsletter-cover-placeholder">
                  <i class="ti ti-news fs-1 text-white"></i>
                </div>
                <?php endif; ?>
                
                <div class="card-body">
                  <div class="d-flex gap-2 mb-2">
                    <span class="badge bg-<?php echo get_category_color($newsletter['category']); ?>-subtle text-<?php echo get_category_color($newsletter['category']); ?> category-badge">
                      <?php echo ucfirst($newsletter['category']); ?>
                    </span>
                    <span class="badge bg-light text-dark">
                      <i class="ti ti-eye"></i> <?php echo $newsletter['views_count']; ?>
                    </span>
                  </div>
                  
                  <h5 class="card-title mb-2"><?php echo htmlspecialchars($newsletter['title']); ?></h5>
                  
                  <p class="text-muted small mb-3" style="font-size: 13px;">
                    <?php echo htmlspecialchars(substr($newsletter['excerpt'], 0, 120)); ?>
                    <?php echo strlen($newsletter['excerpt']) > 120 ? '...' : ''; ?>
                  </p>
                  
                  <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                      <i class="ti ti-calendar"></i> <?php echo date('M d, Y', strtotime($newsletter['published_date'])); ?>
                    </small>
                    <a href="newsletter-view.php?id=<?php echo $newsletter['id']; ?>" class="btn btn-sm btn-outline-primary">
                      Read More <i class="ti ti-arrow-right"></i>
                    </a>
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

  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>
</html>

<?php
function get_category_color($category) {
    $colors = [
        'academic' => 'primary',
        'events' => 'success',
        'sports' => 'warning',
        'achievements' => 'info',
        'general' => 'secondary'
    ];
    return $colors[$category] ?? 'secondary';
}
?>