<?php
require_once '../config/functions.php';
require_role(['parent']);

$parent = get_parent_details(get_user_id());
$children = get_parent_children($parent['id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Children - <?php echo SITE_NAME; ?></title>
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
          
          <div class="card">
            <div class="card-body">
              <h4 class="card-title mb-4">My Children</h4>
              
              <div class="row">
                <?php foreach ($children as $child): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                  <div class="card border">
                    <div class="card-body text-center">
                      <div class="round-110 rounded-circle mx-auto text-bg-primary d-flex align-items-center justify-content-center mb-3">
                        <i class="ti ti-user fs-1 text-white"></i>
                      </div>
                      <h5 class="mb-1"><?php echo $child['first_name'] . ' ' . $child['last_name']; ?></h5>
                      <p class="text-muted mb-2"><?php echo $child['class_name']; ?></p>
                      <span class="badge bg-primary-subtle text-primary mb-3"><?php echo $child['student_id']; ?></span>
                      
                      <div class="d-grid gap-2">
                        <a href="index.php?child_id=<?php echo $child['id']; ?>" class="btn btn-primary btn-sm">View Dashboard</a>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
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
</body>
</html>
