<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$user = get_user_details(get_user_id());

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Add new newsletter
        if ($_POST['action'] === 'add') {
            $title = trim($_POST['title']);
            $content = $_POST['content']; // HTML content from editor
            $excerpt = trim($_POST['excerpt']);
            $category = trim($_POST['category']);
            $published_date = trim($_POST['published_date']);
            $status = trim($_POST['status']);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $author_id = get_user_id();
            
            // Handle cover image upload
            $cover_image = null;
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
                $cover_image = upload_newsletter_image($_FILES['cover_image']);
            }
            
            $stmt = $conn->prepare("INSERT INTO newsletters (title, content, excerpt, cover_image, author_id, published_date, category, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssissis", $title, $content, $excerpt, $cover_image, $author_id, $published_date, $category, $is_featured, $status);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Newsletter created successfully!";
            } else {
                $_SESSION['error_message'] = "Error creating newsletter: " . $conn->error;
            }
            header("Location: manage-newsletters.php");
            exit;
        }
        
        // Edit newsletter
        if ($_POST['action'] === 'edit') {
            $newsletter_id = intval($_POST['newsletter_id']);
            $title = trim($_POST['title']);
            $content = $_POST['content'];
            $excerpt = trim($_POST['excerpt']);
            $category = trim($_POST['category']);
            $published_date = trim($_POST['published_date']);
            $status = trim($_POST['status']);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            
            // Get existing cover image
            $stmt = $conn->prepare("SELECT cover_image FROM newsletters WHERE id = ?");
            $stmt->bind_param("i", $newsletter_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $cover_image = $existing['cover_image'];
            
            // Handle new cover image upload
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
                // Delete old image
                if (!empty($cover_image)) {
                    @unlink("../uploads/newsletters/" . $cover_image);
                }
                $cover_image = upload_newsletter_image($_FILES['cover_image']);
            } elseif (isset($_POST['remove_cover_image'])) {
                // Remove image if checkbox is checked
                if (!empty($cover_image)) {
                    @unlink("../uploads/newsletters/" . $cover_image);
                }
                $cover_image = null;
            }
            
            $stmt = $conn->prepare("UPDATE newsletters SET title = ?, content = ?, excerpt = ?, cover_image = ?, published_date = ?, category = ?, is_featured = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssssiis", $title, $content, $excerpt, $cover_image, $published_date, $category, $is_featured, $status, $newsletter_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Newsletter updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating newsletter: " . $conn->error;
            }
            header("Location: manage-newsletters.php");
            exit;
        }
        
        // Delete newsletter
        if ($_POST['action'] === 'delete') {
            $newsletter_id = intval($_POST['newsletter_id']);
            
            // Get cover image to delete
            $stmt = $conn->prepare("SELECT cover_image FROM newsletters WHERE id = ?");
            $stmt->bind_param("i", $newsletter_id);
            $stmt->execute();
            $image = $stmt->get_result()->fetch_assoc();
            
            // Delete newsletter
            $stmt = $conn->prepare("DELETE FROM newsletters WHERE id = ?");
            $stmt->bind_param("i", $newsletter_id);
            
            if ($stmt->execute()) {
                // Delete cover image
                if (!empty($image['cover_image'])) {
                    @unlink("../uploads/newsletters/" . $image['cover_image']);
                }
                $_SESSION['success_message'] = "Newsletter deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Error deleting newsletter: " . $conn->error;
            }
            header("Location: manage-newsletters.php");
            exit;
        }
    }
}

// Function to upload newsletter cover image
function upload_newsletter_image($file) {
    $upload_dir = "../uploads/newsletters/";
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG, and GIF allowed.";
        return null;
    }
    
    if ($file['size'] > $max_size) {
        $_SESSION['error_message'] = "File too large. Max 5MB.";
        return null;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'newsletter_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return null;
}

// Get all newsletters
$newsletters = $conn->query("
    SELECT n.*, u.username as author_name 
    FROM newsletters n
    INNER JOIN users u ON n.author_id = u.id
    ORDER BY n.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'total' => count($newsletters),
    'published' => count(array_filter($newsletters, fn($n) => $n['status'] === 'published')),
    'draft' => count(array_filter($newsletters, fn($n) => $n['status'] === 'draft')),
    'featured' => count(array_filter($newsletters, fn($n) => $n['is_featured'] == 1))
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Newsletters - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <!-- TinyMCE Editor -->
  <script src="https://cdn.tiny.cloud/1/wy4a39bl3x8z5x1vw33wh8ip5s51m29evs7obqf5uvrc52w1/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <style>
    .newsletter-thumbnail {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
    }
    .status-badge {
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
                      <h4 class="mb-0">Manage Newsletters</h4>
                      <p class="mb-0 text-muted">Create and manage school newsletters</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewsletterModal">
                      <i class="ti ti-plus"></i> Create Newsletter
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Statistics -->
          <div class="row">
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-primary d-flex align-items-center justify-content-center">
                      <i class="ti ti-news fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $stats['total']; ?></h4>
                      <span class="text-muted">Total Newsletters</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-success d-flex align-items-center justify-content-center">
                      <i class="ti ti-check fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $stats['published']; ?></h4>
                      <span class="text-muted">Published</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-warning d-flex align-items-center justify-content-center">
                      <i class="ti ti-clock fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $stats['draft']; ?></h4>
                      <span class="text-muted">Drafts</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-info d-flex align-items-center justify-content-center">
                      <i class="ti ti-star fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $stats['featured']; ?></h4>
                      <span class="text-muted">Featured</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Newsletters Table -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">All Newsletters</h4>
                  
                  <?php if (empty($newsletters)): ?>
                  <div class="text-center py-5">
                    <i class="ti ti-news-off fs-1 text-muted"></i>
                    <p class="text-muted mt-3 mb-0">No newsletters found. Create your first one!</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>Cover</th>
                          <th>Title</th>
                          <th>Category</th>
                          <th>Published Date</th>
                          <th>Status</th>
                          <th>Views</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($newsletters as $newsletter): ?>
                        <tr>
                          <td>
                            <?php if ($newsletter['cover_image']): ?>
                            <img src="../uploads/newsletters/<?php echo $newsletter['cover_image']; ?>" class="newsletter-thumbnail">
                            <?php else: ?>
                            <div class="newsletter-thumbnail bg-primary-subtle d-flex align-items-center justify-content-center">
                              <i class="ti ti-news text-primary"></i>
                            </div>
                            <?php endif; ?>
                          </td>
                          <td>
                            <strong><?php echo htmlspecialchars($newsletter['title']); ?></strong>
                            <?php if ($newsletter['is_featured']): ?>
                            <br><span class="badge bg-warning text-dark status-badge"><i class="ti ti-star"></i> Featured</span>
                            <?php endif; ?>
                          </td>
                          <td><span class="badge bg-primary-subtle text-primary"><?php echo ucfirst($newsletter['category']); ?></span></td>
                          <td><?php echo date('M d, Y', strtotime($newsletter['published_date'])); ?></td>
                          <td>
                            <?php if ($newsletter['status'] === 'published'): ?>
                            <span class="badge bg-success">Published</span>
                            <?php elseif ($newsletter['status'] === 'draft'): ?>
                            <span class="badge bg-warning">Draft</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Archived</span>
                            <?php endif; ?>
                          </td>
                          <td><span class="badge bg-info"><?php echo $newsletter['views_count']; ?></span></td>
                          <td>
                            <div class="btn-group">
                              <button class="btn btn-sm btn-outline-primary" onclick="editNewsletter(<?php echo htmlspecialchars(json_encode($newsletter)); ?>)">
                                <i class="ti ti-edit"></i>
                              </button>
                              <a href="../student/newsletter-view.php?id=<?php echo $newsletter['id']; ?>" class="btn btn-sm btn-outline-info" target="_blank" title="Preview">
                                <i class="ti ti-eye"></i>
                              </a>
                              <button class="btn btn-sm btn-outline-danger" onclick="deleteNewsletter(<?php echo $newsletter['id']; ?>, '<?php echo htmlspecialchars($newsletter['title']); ?>')">
                                <i class="ti ti-trash"></i>
                              </button>
                            </div>
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

  <!-- Add Newsletter Modal -->
  <div class="modal fade" id="addNewsletterModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add">
          
          <div class="modal-header">
            <h5 class="modal-title">Create New Newsletter</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="row">
              <div class="col-md-8 mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title" required>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Published Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="published_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Excerpt (Short Summary)</label>
                <textarea class="form-control" name="excerpt" rows="2" placeholder="Brief description shown in the newsletter list..."></textarea>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select class="form-select" name="category" required>
                  <option value="general">General</option>
                  <option value="academic">Academic</option>
                  <option value="events">Events</option>
                  <option value="sports">Sports</option>
                  <option value="achievements">Achievements</option>
                </select>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select class="form-select" name="status" required>
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                  <option value="archived">Archived</option>
                </select>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Options</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured_add">
                  <label class="form-check-label" for="is_featured_add">
                    Mark as Featured
                  </label>
                </div>
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Cover Image (Optional)</label>
                <input type="file" class="form-control" name="cover_image" accept="image/*">
                <small class="text-muted">Recommended size: 1200x600px (Max 5MB)</small>
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Content <span class="text-danger">*</span></label>
                <textarea id="content_add" name="content" class="form-control"></textarea>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Newsletter</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Newsletter Modal -->
  <div class="modal fade" id="editNewsletterModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="newsletter_id" id="edit_newsletter_id">
          
          <div class="modal-header">
            <h5 class="modal-title">Edit Newsletter</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="row">
              <div class="col-md-8 mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title" id="edit_title" required>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Published Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="published_date" id="edit_published_date" required>
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Excerpt</label>
                <textarea class="form-control" name="excerpt" id="edit_excerpt" rows="2"></textarea>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select class="form-select" name="category" id="edit_category" required>
                  <option value="general">General</option>
                  <option value="academic">Academic</option>
                  <option value="events">Events</option>
                  <option value="sports">Sports</option>
                  <option value="achievements">Achievements</option>
                </select>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select class="form-select" name="status" id="edit_status" required>
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                  <option value="archived">Archived</option>
                </select>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Options</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_featured" id="edit_is_featured">
                  <label class="form-check-label" for="edit_is_featured">
                    Mark as Featured
                  </label>
                </div>
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Cover Image</label>
                <div id="edit_cover_image_container"></div>
                <input type="file" class="form-control mt-2" name="cover_image" accept="image/*">
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Content <span class="text-danger">*</span></label>
                <textarea id="content_edit" name="content" class="form-control"></textarea>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Newsletter</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteNewsletterModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="newsletter_id" id="delete_newsletter_id">
          
          <div class="modal-header">
            <h5 class="modal-title">Confirm Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <p>Are you sure you want to delete newsletter <strong id="delete_newsletter_title"></strong>?</p>
            <p class="text-danger"><i class="ti ti-alert-triangle"></i> This action cannot be undone.</p>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Delete Newsletter</button>
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
    // Initialize TinyMCE for add modal
    tinymce.init({
      selector: '#content_add',
      height: 400,
      menubar: false,
      plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
      ],
      toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image | preview code',
      content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }'
    });
    
    function editNewsletter(newsletter) {
      document.getElementById('edit_newsletter_id').value = newsletter.id;
      document.getElementById('edit_title').value = newsletter.title;
      document.getElementById('edit_excerpt').value = newsletter.excerpt || '';
      document.getElementById('edit_category').value = newsletter.category;
      document.getElementById('edit_status').value = newsletter.status;
      document.getElementById('edit_published_date').value = newsletter.published_date;
      document.getElementById('edit_is_featured').checked = newsletter.is_featured == 1;
      
      // Handle cover image
      let imageHtml = '';
      if (newsletter.cover_image) {
        imageHtml = `
          <img src="../uploads/newsletters/${newsletter.cover_image}" class="img-thumbnail mb-2" style="max-width: 200px;">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remove_cover_image" id="remove_cover_image">
            <label class="form-check-label" for="remove_cover_image">Remove current image</label>
          </div>
        `;
      } else {
        imageHtml = '<p class="text-muted">No cover image</p>';
      }
      document.getElementById('edit_cover_image_container').innerHTML = imageHtml;
      
      // Initialize TinyMCE for edit modal
      if (tinymce.get('content_edit')) {
        tinymce.get('content_edit').remove();
      }
      
      setTimeout(() => {
        tinymce.init({
          selector: '#content_edit',
          height: 400,
          menubar: false,
          plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
          ],
          toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image | preview code',
          content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }',
          setup: function(editor) {
            editor.on('init', function() {
              editor.setContent(newsletter.content || '');
            });
          }
        });
      }, 100);
      
      var editModal = new bootstrap.Modal(document.getElementById('editNewsletterModal'));
      editModal.show();
    }
    
    function deleteNewsletter(id, title) {
      document.getElementById('delete_newsletter_id').value = id;
      document.getElementById('delete_newsletter_title').textContent = title;
      
      var deleteModal = new bootstrap.Modal(document.getElementById('deleteNewsletterModal'));
      deleteModal.show();
    }
  </script>
</body>
</html>