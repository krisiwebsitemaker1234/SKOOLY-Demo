<?php
require_once '../config/functions.php';
require_role(['student']);

$user = get_user_details(get_user_id());
$student = get_student_details(get_user_id());

// Get newsletter ID
$newsletter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$newsletter_id) {
    header("Location: newsletters.php");
    exit;
}

// Get newsletter details
$stmt = $conn->prepare("
    SELECT n.*, u.username as author_name 
    FROM newsletters n
    INNER JOIN users u ON n.author_id = u.id
    WHERE n.id = ? AND n.status = 'published'
");
$stmt->bind_param("i", $newsletter_id);
$stmt->execute();
$newsletter = $stmt->get_result()->fetch_assoc();

if (!$newsletter) {
    header("Location: newsletters.php");
    exit;
}

// Increment views count
$conn->query("UPDATE newsletters SET views_count = views_count + 1 WHERE id = {$newsletter_id}");

// Get related newsletters (same category, exclude current)
$stmt = $conn->prepare("
    SELECT * FROM newsletters 
    WHERE category = ? AND id != ? AND status = 'published'
    ORDER BY published_date DESC
    LIMIT 3
");
$stmt->bind_param("si", $newsletter['category'], $newsletter_id);
$stmt->execute();
$related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($newsletter['title']); ?> - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    .newsletter-header-image {
      width: 100%;
      height: 400px;
      object-fit: cover;
      border-radius: 12px;
      margin-bottom: 30px;
    }
    .newsletter-content {
      font-size: 16px;
      line-height: 1.8;
      color: #333;
    }
    .newsletter-content h2 {
      margin-top: 30px;
      margin-bottom: 15px;
      color: #5D87FF;
    }
    .newsletter-content h3 {
      margin-top: 25px;
      margin-bottom: 12px;
      color: #49BEFF;
    }
    .newsletter-content p {
      margin-bottom: 20px;
    }
    .related-card {
      transition: transform 0.2s;
      cursor: pointer;
    }
    .related-card:hover {
      transform: translateY(-3px);
    }
    .related-thumbnail {
      width: 100%;
      height: 150px;
      object-fit: cover;
      border-radius: 8px;
    }
    .share-buttons .btn {
      width: 40px;
      height: 40px;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
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
          
          <!-- Back Button -->
          <div class="row mb-3">
            <div class="col-12">
              <a href="newsletters.php" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left"></i> Back to Newsletters
              </a>
            </div>
          </div>
          
          <!-- Newsletter Content -->
          <div class="row">
            <div class="col-lg-8">
              <div class="card">
                <div class="card-body">
                  <!-- Header Info -->
                  <div class="d-flex gap-2 mb-3">
                    <span class="badge bg-<?php echo get_category_color($newsletter['category']); ?>">
                      <?php echo ucfirst($newsletter['category']); ?>
                    </span>
                    <?php if ($newsletter['is_featured']): ?>
                    <span class="badge bg-warning text-dark">
                      <i class="ti ti-star"></i> Featured
                    </span>
                    <?php endif; ?>
                  </div>
                  
                  <!-- Title -->
                  <h1 class="mb-3"><?php echo htmlspecialchars($newsletter['title']); ?></h1>
                  
                  <!-- Meta Info -->
                  <div class="d-flex gap-4 mb-4 text-muted">
                    <small>
                      <i class="ti ti-calendar"></i> 
                      <?php echo date('F d, Y', strtotime($newsletter['published_date'])); ?>
                    </small>
                    <small>
                      <i class="ti ti-user"></i> 
                      By <?php echo htmlspecialchars($newsletter['author_name']); ?>
                    </small>
                    <small>
                      <i class="ti ti-eye"></i> 
                      <?php echo $newsletter['views_count']; ?> views
                    </small>
                  </div>
                  
                  <hr class="mb-4">
                  
                  <!-- Cover Image -->
                  <?php if ($newsletter['cover_image']): ?>
                  <img src="../uploads/newsletters/<?php echo htmlspecialchars($newsletter['cover_image']); ?>" class="newsletter-header-image" alt="<?php echo htmlspecialchars($newsletter['title']); ?>">
                  <?php endif; ?>
                  
                  <!-- Excerpt -->
                  <?php if ($newsletter['excerpt']): ?>
                  <div class="alert alert-info mb-4">
                    <strong>Summary:</strong> <?php echo htmlspecialchars($newsletter['excerpt']); ?>
                  </div>
                  <?php endif; ?>
                  
                  <!-- Main Content -->
                  <div class="newsletter-content">
                    <?php echo $newsletter['content']; ?>
                  </div>
                  
                  <hr class="my-4">
                  
                  <!-- Share Buttons -->
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <h6 class="mb-0">Share this article:</h6>
                    </div>
                    <div class="share-buttons d-flex gap-2">
                      <button class="btn btn-primary" onclick="shareNewsletter('facebook')" title="Share on Facebook">
                        <i class="ti ti-brand-facebook"></i>
                      </button>
                      <button class="btn btn-info" onclick="shareNewsletter('twitter')" title="Share on Twitter">
                        <i class="ti ti-brand-twitter"></i>
                      </button>
                      <button class="btn btn-success" onclick="shareNewsletter('whatsapp')" title="Share on WhatsApp">
                        <i class="ti ti-brand-whatsapp"></i>
                      </button>
                      <button class="btn btn-secondary" onclick="copyLink()" title="Copy Link">
                        <i class="ti ti-link"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
              <!-- Related Newsletters -->
              <?php if (!empty($related)): ?>
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title mb-4">Related Articles</h5>
                  
                  <?php foreach ($related as $rel): ?>
                  <div class="related-card mb-3 pb-3 border-bottom" onclick="window.location.href='newsletter-view.php?id=<?php echo $rel['id']; ?>'">
                    <div class="row">
                      <div class="col-4">
                        <?php if ($rel['cover_image']): ?>
                        <img src="../uploads/newsletters/<?php echo htmlspecialchars($rel['cover_image']); ?>" class="related-thumbnail" alt="<?php echo htmlspecialchars($rel['title']); ?>">
                        <?php else: ?>
                        <div class="related-thumbnail bg-primary-subtle d-flex align-items-center justify-content-center">
                          <i class="ti ti-news fs-4 text-primary"></i>
                        </div>
                        <?php endif; ?>
                      </div>
                      <div class="col-8">
                        <h6 class="mb-1" style="font-size: 14px;">
                          <?php echo htmlspecialchars(substr($rel['title'], 0, 60)); ?>
                          <?php echo strlen($rel['title']) > 60 ? '...' : ''; ?>
                        </h6>
                        <small class="text-muted">
                          <i class="ti ti-calendar"></i> 
                          <?php echo date('M d, Y', strtotime($rel['published_date'])); ?>
                        </small>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>
              
              <!-- Categories -->
              <div class="card mt-3">
                <div class="card-body">
                  <h5 class="card-title mb-3">Categories</h5>
                  <div class="d-flex flex-column gap-2">
                    <a href="newsletters.php?category=academic" class="btn btn-outline-primary btn-sm">Academic</a>
                    <a href="newsletters.php?category=events" class="btn btn-outline-success btn-sm">Events</a>
                    <a href="newsletters.php?category=sports" class="btn btn-outline-warning btn-sm">Sports</a>
                    <a href="newsletters.php?category=achievements" class="btn btn-outline-info btn-sm">Achievements</a>
                    <a href="newsletters.php?category=general" class="btn btn-outline-secondary btn-sm">General</a>
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

  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  
  <script>
    function shareNewsletter(platform) {
      const url = window.location.href;
      const title = <?php echo json_encode($newsletter['title']); ?>;
      
      let shareUrl = '';
      switch(platform) {
        case 'facebook':
          shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
          break;
        case 'twitter':
          shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`;
          break;
        case 'whatsapp':
          shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
          break;
      }
      
      if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
      }
    }
    
    function copyLink() {
      navigator.clipboard.writeText(window.location.href).then(() => {
        alert('Link copied to clipboard!');
      });
    }
  </script>
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