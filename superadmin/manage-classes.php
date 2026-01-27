<?php
require_once '../config/functions.php';
require_role(['superadmin']);

$user = get_user_details(get_user_id());
$current_year = get_current_academic_year();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Add new class
        if ($_POST['action'] === 'add') {
            $class_name = trim($_POST['class_name']);
            $grade_level = intval($_POST['grade_level']);
            $section = trim($_POST['section']);
            $max_students = intval($_POST['max_students']);
            $guardian_teacher_id = !empty($_POST['guardian_teacher_id']) ? intval($_POST['guardian_teacher_id']) : NULL;
            $location = trim($_POST['location']);
            $description = trim($_POST['description']);
            
            // Handle image uploads
            $image_paths = [];
            for ($i = 1; $i <= 3; $i++) {
                if (isset($_FILES["image{$i}"]) && $_FILES["image{$i}"]['error'] === 0) {
                    $image_paths[$i] = upload_class_image($_FILES["image{$i}"], $i);
                } else {
                    $image_paths[$i] = null;
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO classes (class_name, grade_level, section, academic_year_id, guardian_teacher_id, max_students, location, description, image1, image2, image3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisisissss", $class_name, $grade_level, $section, $current_year['id'], $guardian_teacher_id, $max_students, $location, $description, $image_paths[1], $image_paths[2], $image_paths[3]);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Class added successfully!";
            } else {
                $_SESSION['error_message'] = "Error adding class: " . $conn->error;
            }
            header("Location: manage-classes.php");
            exit;
        }
        
        // Edit class
        if ($_POST['action'] === 'edit') {
            $class_id = intval($_POST['class_id']);
            $class_name = trim($_POST['class_name']);
            $grade_level = intval($_POST['grade_level']);
            $section = trim($_POST['section']);
            $max_students = intval($_POST['max_students']);
            $guardian_teacher_id = !empty($_POST['guardian_teacher_id']) ? intval($_POST['guardian_teacher_id']) : NULL;
            $location = trim($_POST['location']);
            $description = trim($_POST['description']);
            
            // Get existing images
            $stmt = $conn->prepare("SELECT image1, image2, image3 FROM classes WHERE id = ?");
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            // Handle image uploads
            $image_paths = [
                1 => $existing['image1'],
                2 => $existing['image2'],
                3 => $existing['image3']
            ];
            
            for ($i = 1; $i <= 3; $i++) {
                if (isset($_FILES["image{$i}"]) && $_FILES["image{$i}"]['error'] === 0) {
                    // Delete old image if exists
                    if (!empty($image_paths[$i])) {
                        @unlink("../uploads/classes/" . $image_paths[$i]);
                    }
                    $image_paths[$i] = upload_class_image($_FILES["image{$i}"], $i);
                } elseif (isset($_POST["remove_image{$i}"])) {
                    // Remove image if checkbox is checked
                    if (!empty($image_paths[$i])) {
                        @unlink("../uploads/classes/" . $image_paths[$i]);
                    }
                    $image_paths[$i] = null;
                }
            }
            
            $stmt = $conn->prepare("UPDATE classes SET class_name = ?, grade_level = ?, section = ?, guardian_teacher_id = ?, max_students = ?, location = ?, description = ?, image1 = ?, image2 = ?, image3 = ? WHERE id = ?");
            $stmt->bind_param("sisiissssi", $class_name, $grade_level, $section, $guardian_teacher_id, $max_students, $location, $description, $image_paths[1], $image_paths[2], $image_paths[3], $class_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Class updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating class: " . $conn->error;
            }
            header("Location: manage-classes.php");
            exit;
        }
        
        // Delete class
        if ($_POST['action'] === 'delete') {
            $class_id = intval($_POST['class_id']);
            
            // Get images to delete
            $stmt = $conn->prepare("SELECT image1, image2, image3 FROM classes WHERE id = ?");
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            $images = $stmt->get_result()->fetch_assoc();
            
            // Delete class
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->bind_param("i", $class_id);
            
            if ($stmt->execute()) {
                // Delete images
                foreach ($images as $img) {
                    if (!empty($img)) {
                        @unlink("../uploads/classes/" . $img);
                    }
                }
                $_SESSION['success_message'] = "Class deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Error deleting class: " . $conn->error;
            }
            header("Location: manage-classes.php");
            exit;
        }
    }
}

// Function to upload class images
function upload_class_image($file, $number) {
    $upload_dir = "../uploads/classes/";
    
    // Create directory if it doesn't exist
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
    $filename = 'class_' . time() . '_' . $number . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return null;
}

// Get all classes
$stmt = $conn->prepare("
    SELECT c.*, 
           COUNT(DISTINCT s.id) as student_count,
           t.first_name as guardian_fname, 
           t.last_name as guardian_lname
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id
    LEFT JOIN teachers t ON c.guardian_teacher_id = t.id
    WHERE c.academic_year_id = ?
    GROUP BY c.id
    ORDER BY c.grade_level ASC, c.section ASC
");
$stmt->bind_param("i", $current_year['id']);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all teachers for dropdown
$teachers = $conn->query("SELECT id, first_name, last_name FROM teachers ORDER BY first_name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Classes - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    .image-preview-container {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }
    .image-preview {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 8px;
      border: 2px solid #ddd;
    }
    .image-upload-box {
      width: 100px;
      height: 100px;
      border: 2px dashed #ddd;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    .image-upload-box:hover {
      border-color: #5D87FF;
      background-color: #f8f9fa;
    }
    .class-image-thumb {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 6px;
      margin-right: 5px;
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
                      <h4 class="mb-0">Manage Classes</h4>
                      <p class="mb-0 text-muted">Add, edit, or delete classes for the academic year</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                      <i class="ti ti-plus"></i> Add New Class
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Statistics -->
          <div class="row">
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-primary d-flex align-items-center justify-content-center">
                      <i class="ti ti-school fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo count($classes); ?></h4>
                      <span class="text-muted">Total Classes</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-success d-flex align-items-center justify-content-center">
                      <i class="ti ti-users fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo array_sum(array_column($classes, 'student_count')); ?></h4>
                      <span class="text-muted">Total Students</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <div class="round-48 rounded-circle text-bg-warning d-flex align-items-center justify-content-center">
                      <i class="ti ti-calendar fs-6 text-white"></i>
                    </div>
                    <div class="ms-3">
                      <h4 class="mb-0 fw-bold"><?php echo $current_year['year_name']; ?></h4>
                      <span class="text-muted">Academic Year</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Classes Table -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">All Classes</h4>
                  
                  <?php if (empty($classes)): ?>
                  <div class="text-center py-5">
                    <i class="ti ti-folder-off fs-1 text-muted"></i>
                    <p class="text-muted mt-3 mb-0">No classes found. Add your first class!</p>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead>
                        <tr>
                          <th>Class Name</th>
                          <th>Grade/Section</th>
                          <th>Location</th>
                          <th>Students</th>
                          <th>Guardian Teacher</th>
                          <th>Images</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($classes as $class): ?>
                        <tr>
                          <td>
                            <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>
                            <?php if ($class['description']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($class['description'], 0, 50)); ?>...</small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="badge bg-primary-subtle text-primary">
                              Grade <?php echo $class['grade_level']; ?> - <?php echo $class['section']; ?>
                            </span>
                          </td>
                          <td>
                            <?php if ($class['location']): ?>
                            <small><i class="ti ti-map-pin text-danger"></i> <?php echo htmlspecialchars($class['location']); ?></small>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="badge bg-info">
                              <?php echo $class['student_count']; ?>/<?php echo $class['max_students']; ?>
                            </span>
                          </td>
                          <td>
                            <?php if ($class['guardian_fname']): ?>
                            <small><?php echo $class['guardian_fname'] . ' ' . $class['guardian_lname']; ?></small>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="d-flex">
                              <?php if ($class['image1']): ?>
                              <img src="../uploads/classes/<?php echo $class['image1']; ?>" class="class-image-thumb">
                              <?php endif; ?>
                              <?php if ($class['image2']): ?>
                              <img src="../uploads/classes/<?php echo $class['image2']; ?>" class="class-image-thumb">
                              <?php endif; ?>
                              <?php if ($class['image3']): ?>
                              <img src="../uploads/classes/<?php echo $class['image3']; ?>" class="class-image-thumb">
                              <?php endif; ?>
                              <?php if (!$class['image1'] && !$class['image2'] && !$class['image3']): ?>
                              <span class="text-muted">No images</span>
                              <?php endif; ?>
                            </div>
                          </td>
                          <td>
                            <div class="btn-group">
                              <button class="btn btn-sm btn-outline-primary" onclick="editClass(<?php echo htmlspecialchars(json_encode($class)); ?>)">
                                <i class="ti ti-edit"></i>
                              </button>
                              <button class="btn btn-sm btn-outline-danger" onclick="deleteClass(<?php echo $class['id']; ?>, '<?php echo htmlspecialchars($class['class_name']); ?>')">
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

  <!-- Add Class Modal -->
  <div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add">
          
          <div class="modal-header">
            <h5 class="modal-title">Add New Class</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12 mb-3">
                <label class="form-label">Class Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="class_name" placeholder="e.g., Grade 10-A" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="grade_level" placeholder="e.g., 10" min="1" max="12" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Section <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="section" placeholder="e.g., A" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Max Students <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="max_students" value="40" min="1" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Guardian Teacher</label>
                <select class="form-select" name="guardian_teacher_id">
                  <option value="">Select Teacher (Optional)</option>
                  <?php foreach ($teachers as $teacher): ?>
                  <option value="<?php echo $teacher['id']; ?>">
                    <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" name="location" placeholder="e.g., Floor 2, Building A, Room 201">
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Brief description about the class..."></textarea>
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Class Images (Optional - Max 3)</label>
                <div class="d-flex gap-2">
                  <div>
                    <label class="image-upload-box" for="image1_add">
                      <i class="ti ti-camera fs-4 text-muted"></i>
                    </label>
                    <input type="file" id="image1_add" name="image1" accept="image/*" style="display: none;" onchange="previewImage(this, 'preview1_add')">
                    <img id="preview1_add" class="image-preview mt-2" style="display: none;">
                  </div>
                  
                  <div>
                    <label class="image-upload-box" for="image2_add">
                      <i class="ti ti-camera fs-4 text-muted"></i>
                    </label>
                    <input type="file" id="image2_add" name="image2" accept="image/*" style="display: none;" onchange="previewImage(this, 'preview2_add')">
                    <img id="preview2_add" class="image-preview mt-2" style="display: none;">
                  </div>
                  
                  <div>
                    <label class="image-upload-box" for="image3_add">
                      <i class="ti ti-camera fs-4 text-muted"></i>
                    </label>
                    <input type="file" id="image3_add" name="image3" accept="image/*" style="display: none;" onchange="previewImage(this, 'preview3_add')">
                    <img id="preview3_add" class="image-preview mt-2" style="display: none;">
                  </div>
                </div>
                <small class="text-muted">Accepted formats: JPG, PNG, GIF (Max 5MB each)</small>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Class</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Class Modal -->
  <div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="class_id" id="edit_class_id">
          
          <div class="modal-header">
            <h5 class="modal-title">Edit Class</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12 mb-3">
                <label class="form-label">Class Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="class_name" id="edit_class_name" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="grade_level" id="edit_grade_level" min="1" max="12" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Section <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="section" id="edit_section" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Max Students <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="max_students" id="edit_max_students" min="1" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Guardian Teacher</label>
                <select class="form-select" name="guardian_teacher_id" id="edit_guardian_teacher_id">
                  <option value="">Select Teacher (Optional)</option>
                  <?php foreach ($teachers as $teacher): ?>
                  <option value="<?php echo $teacher['id']; ?>">
                    <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" name="location" id="edit_location">
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
              </div>
              
              <div class="col-md-12 mb-3">
                <label class="form-label">Class Images</label>
                <div id="edit_images_container"></div>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Class</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteClassModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="class_id" id="delete_class_id">
          
          <div class="modal-header">
            <h5 class="modal-title">Confirm Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          
          <div class="modal-body">
            <p>Are you sure you want to delete class <strong id="delete_class_name"></strong>?</p>
            <p class="text-danger"><i class="ti ti-alert-triangle"></i> This action cannot be undone. All class images will also be deleted.</p>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Delete Class</button>
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
    function previewImage(input, previewId) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById(previewId).src = e.target.result;
          document.getElementById(previewId).style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
      }
    }
    
    function editClass(classData) {
      document.getElementById('edit_class_id').value = classData.id;
      document.getElementById('edit_class_name').value = classData.class_name;
      document.getElementById('edit_grade_level').value = classData.grade_level;
      document.getElementById('edit_section').value = classData.section;
      document.getElementById('edit_max_students').value = classData.max_students;
      document.getElementById('edit_guardian_teacher_id').value = classData.guardian_teacher_id || '';
      document.getElementById('edit_location').value = classData.location || '';
      document.getElementById('edit_description').value = classData.description || '';
      
      // Handle existing images
      let imagesHtml = '<div class="d-flex gap-2">';
      for (let i = 1; i <= 3; i++) {
        const imageKey = 'image' + i;
        imagesHtml += '<div>';
        if (classData[imageKey]) {
          imagesHtml += `
            <img src="../uploads/classes/${classData[imageKey]}" class="image-preview mb-2" id="edit_preview${i}">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remove_image${i}" id="remove_image${i}">
              <label class="form-check-label" for="remove_image${i}">Remove</label>
            </div>
          `;
        } else {
          imagesHtml += `<div class="image-upload-box" onclick="document.getElementById('edit_image${i}').click()">
            <i class="ti ti-camera fs-4 text-muted"></i>
          </div>`;
        }
        imagesHtml += `
          <input type="file" id="edit_image${i}" name="image${i}" accept="image/*" style="display: none;" onchange="previewImage(this, 'edit_preview${i}')">
        </div>`;
      }
      imagesHtml += '</div><small class="text-muted">Upload new images to replace existing ones</small>';
      
      document.getElementById('edit_images_container').innerHTML = imagesHtml;
      
      var editModal = new bootstrap.Modal(document.getElementById('editClassModal'));
      editModal.show();
    }
    
    function deleteClass(classId, className) {
      document.getElementById('delete_class_id').value = classId;
      document.getElementById('delete_class_name').textContent = className;
      
      var deleteModal = new bootstrap.Modal(document.getElementById('deleteClassModal'));
      deleteModal.show();
    }
  </script>
</body>
</html>