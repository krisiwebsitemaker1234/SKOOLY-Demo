<?php
require_once '../config/functions.php';
require_role(['parent']);
$user = get_user_details(get_user_id());
$parent = get_parent_details(get_user_id());
$children = get_parent_children($parent['id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
    <?php include 'includes/sidebar.php'; ?>
    <div class="body-wrapper">
      <?php include 'includes/header.php'; ?>
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          <div class="row">
            <div class="col-lg-4">
              <div class="card">
                <div class="card-body text-center">
                  <div class="round-110 rounded-circle mx-auto text-bg-primary d-flex align-items-center justify-content-center mb-3">
                    <i class="ti ti-heart-handshake fs-1 text-white"></i>
                  </div>
                  <h4 class="mb-0"><?php echo $parent['first_name'] . ' ' . $parent['last_name']; ?></h4>
                  <p class="text-muted mb-3">Parent/Guardian</p>
                  <span class="badge bg-info-subtle text-info mb-3"><?php echo ucfirst($parent['relationship']); ?></span>
                </div>
              </div>
            </div>
            <div class="col-lg-8">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">Personal Information</h4>
                  <div class="row mb-3">
                    <div class="col-md-6"><label class="form-label text-muted">First Name</label><p class="fw-bold"><?php echo $parent['first_name']; ?></p></div>
                    <div class="col-md-6"><label class="form-label text-muted">Last Name</label><p class="fw-bold"><?php echo $parent['last_name']; ?></p></div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-6"><label class="form-label text-muted">Email</label><p class="fw-bold"><?php echo $user['email']; ?></p></div>
                    <div class="col-md-6"><label class="form-label text-muted">Relationship</label><p class="fw-bold"><?php echo ucfirst($parent['relationship']); ?></p></div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-6"><label class="form-label text-muted">Phone</label><p class="fw-bold"><?php echo $parent['phone'] ?? 'N/A'; ?></p></div>
                    <div class="col-md-6"><label class="form-label text-muted">Occupation</label><p class="fw-bold"><?php echo $parent['occupation'] ?? 'N/A'; ?></p></div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-12"><label class="form-label text-muted">Address</label><p class="fw-bold"><?php echo $parent['address'] ?? 'N/A'; ?></p></div>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title mb-3">My Children</h5>
                  <div class="row">
                    <?php foreach ($children as $child): ?>
                    <div class="col-md-6 mb-3">
                      <div class="p-3 border rounded">
                        <h6 class="mb-1"><?php echo $child['first_name'] . ' ' . $child['last_name']; ?></h6>
                        <p class="mb-0 text-muted small"><?php echo $child['class_name']; ?></p>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="py-6 px-6 text-center"><p class="mb-0 fs-4">Designed and Developed by <a class="pe-1 text-primary text-decoration-none">QUOLYTECH</a></p></div>
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
