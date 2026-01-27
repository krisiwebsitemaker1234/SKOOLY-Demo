<?php
require_once '../config/functions.php';
require_role(['superadmin']);

// Fake forms data
$forms = [
    ['id' => 1, 'title' => 'Student Satisfaction Survey 2025', 'description' => 'Help us improve your learning experience', 'recipients' => 'students', 'status' => 'active', 'created_date' => '2025-01-15', 'responses' => 245, 'total_sent' => 312],
    ['id' => 2, 'title' => 'Parent-Teacher Conference Availability', 'description' => 'Select your preferred meeting times', 'recipients' => 'parents', 'status' => 'active', 'created_date' => '2025-01-20', 'responses' => 89, 'total_sent' => 156],
    ['id' => 3, 'title' => 'Teacher Professional Development', 'description' => 'Training topics preferences', 'recipients' => 'teachers', 'status' => 'closed', 'created_date' => '2025-01-10', 'responses' => 42, 'total_sent' => 45],
    ['id' => 4, 'title' => 'School Facilities Feedback', 'description' => 'Rate facilities and suggest improvements', 'recipients' => 'all', 'status' => 'active', 'created_date' => '2025-01-22', 'responses' => 387, 'total_sent' => 513]
];

$selected_form_id = isset($_GET['view_form']) ? intval($_GET['view_form']) : null;
$form_responses = null;

if ($selected_form_id) {
    $form_responses = [
        'form_title' => 'Student Satisfaction Survey 2025',
        'total_responses' => 245,
        'response_rate' => 78.5,
        'questions' => [
            ['question' => 'How satisfied are you with teaching quality?', 'type' => 'rating', 'responses' => [
                ['option' => '5 - Very Satisfied', 'count' => 142, 'percentage' => 58],
                ['option' => '4 - Satisfied', 'count' => 73, 'percentage' => 30],
                ['option' => '3 - Neutral', 'count' => 20, 'percentage' => 8],
                ['option' => '2 - Dissatisfied', 'count' => 7, 'percentage' => 3],
                ['option' => '1 - Very Dissatisfied', 'count' => 3, 'percentage' => 1]
            ]],
            ['question' => 'Which subjects do you find most engaging?', 'type' => 'multiple_choice', 'responses' => [
                ['option' => 'Mathematics', 'count' => 98, 'percentage' => 40],
                ['option' => 'Science', 'count' => 156, 'percentage' => 64],
                ['option' => 'English', 'count' => 87, 'percentage' => 36],
                ['option' => 'History', 'count' => 45, 'percentage' => 18],
                ['option' => 'Physical Education', 'count' => 123, 'percentage' => 50]
            ]],
            ['question' => 'What improvements would you like to see?', 'type' => 'text', 'sample_responses' => [
                'More sports equipment and better gym facilities',
                'Longer lunch breaks and better food options',
                'More computer labs with updated software',
                'Better library resources and study spaces'
            ]]
        ]
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forms Management - <?php echo SITE_NAME; ?></title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>.progress { height: 25px; }</style>
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
    <?php include 'includes/sidebar.php'; ?>
    <div class="body-wrapper">
      <?php include 'includes/header.php'; ?>
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          <?php if (!$selected_form_id): ?>
          <div class="card">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="card-title mb-0">Forms Management</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFormModal"><i class="ti ti-plus me-1"></i>Create Form</button>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead><tr><th>Form Title</th><th>Recipients</th><th>Responses</th><th>Response Rate</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                  <tbody>
                    <?php foreach ($forms as $f): $rate = round(($f['responses']/$f['total_sent'])*100,1); ?>
                    <tr>
                      <td><strong><?php echo $f['title']; ?></strong><br><small class="text-muted"><?php echo $f['description']; ?></small></td>
                      <td><span class="badge bg-<?php echo $f['recipients']==='all'?'primary':'info'; ?>"><?php echo ucfirst($f['recipients']); ?></span></td>
                      <td><strong><?php echo $f['responses']; ?></strong> / <?php echo $f['total_sent']; ?></td>
                      <td><div class="progress"><div class="progress-bar bg-<?php echo $rate>=70?'success':($rate>=50?'warning':'danger'); ?>" style="width:<?php echo $rate; ?>%"><?php echo $rate; ?>%</div></div></td>
                      <td><span class="badge bg-<?php echo $f['status']==='active'?'success':'secondary'; ?>"><?php echo ucfirst($f['status']); ?></span></td>
                      <td><?php echo format_date($f['created_date']); ?></td>
                      <td><a href="?view_form=<?php echo $f['id']; ?>" class="btn btn-sm btn-primary"><i class="ti ti-eye me-1"></i>View</a></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <?php else: ?>
          <div class="mb-3"><a href="forms.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i>Back</a></div>
          <div class="card">
            <div class="card-body">
              <h3 class="card-title mb-1"><?php echo $form_responses['form_title']; ?></h3>
              <p class="text-muted mb-4">Survey Results & Analytics</p>
              <div class="row mb-4">
                <div class="col-md-4"><div class="p-3 bg-primary-subtle rounded"><h6 class="text-muted mb-1">Total Responses</h6><h2 class="mb-0"><?php echo $form_responses['total_responses']; ?></h2></div></div>
                <div class="col-md-4"><div class="p-3 bg-success-subtle rounded"><h6 class="text-muted mb-1">Response Rate</h6><h2 class="mb-0"><?php echo $form_responses['response_rate']; ?>%</h2></div></div>
                <div class="col-md-4"><div class="p-3 bg-info-subtle rounded"><h6 class="text-muted mb-1">Average Satisfaction</h6><h2 class="mb-0">4.2/5</h2></div></div>
              </div>
            </div>
          </div>
          <?php foreach ($form_responses['questions'] as $i => $q): ?>
          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title mb-3">Q<?php echo $i+1; ?>: <?php echo $q['question']; ?></h5>
              <?php if ($q['type'] !== 'text'): ?>
              <?php foreach ($q['responses'] as $r): ?>
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1"><span><?php echo $r['option']; ?></span><span class="fw-bold"><?php echo $r['count']; ?> (<?php echo $r['percentage']; ?>%)</span></div>
                <div class="progress" style="height:30px;"><div class="progress-bar" style="width:<?php echo $r['percentage']; ?>%"><?php echo $r['percentage']; ?>%</div></div>
              </div>
              <?php endforeach; ?>
              <?php else: ?>
              <div class="alert alert-light">
                <h6 class="mb-3">Sample Responses:</h6>
                <?php foreach ($q['sample_responses'] as $s): ?>
                <div class="p-2 mb-2 bg-white border-start border-primary border-3"><i class="ti ti-message-circle me-2"></i><?php echo $s; ?></div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title mb-3">Export Options</h5>
              <button class="btn btn-primary me-2"><i class="ti ti-file-spreadsheet me-1"></i>Export to Excel</button>
              <button class="btn btn-outline-primary me-2"><i class="ti ti-file-type-pdf me-1"></i>Export PDF</button>
              <button class="btn btn-outline-primary"><i class="ti ti-file-type-csv me-1"></i>Export CSV</button>
            </div>
          </div>
          <?php endif; ?>
          <div class="py-6 px-6 text-center"><p class="mb-0 fs-4">Designed and Developed by <a class="pe-1 text-primary text-decoration-none">QUOLYTECH</a></p></div>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="createFormModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Create New Form</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="createFormForm">
          <div class="modal-body">
            <div class="mb-3"><label class="form-label">Form Title *</label><input type="text" class="form-control" placeholder="e.g., Student Feedback Survey 2025" required></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" rows="2"></textarea></div>
            <div class="mb-3"><label class="form-label">Send to *</label>
              <div class="form-check"><input class="form-check-input" type="checkbox" value="students" id="s"><label class="form-check-label" for="s"><i class="ti ti-users me-1"></i>Students (312)</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" value="teachers" id="t"><label class="form-check-label" for="t"><i class="ti ti-user-check me-1"></i>Teachers (45)</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" value="parents" id="p"><label class="form-check-label" for="p"><i class="ti ti-heart-handshake me-1"></i>Parents (156)</label></div>
            </div>
            <div class="mb-3"><label class="form-label">Questions</label><div id="questionsContainer"><div class="border rounded p-3 mb-3"><div class="row"><div class="col-md-8 mb-2"><input type="text" class="form-control" placeholder="Question text"></div><div class="col-md-4 mb-2"><select class="form-select"><option>Text Answer</option><option>Multiple Choice</option><option>Rating (1-5)</option><option>Yes/No</option></select></div></div></div></div><button type="button" class="btn btn-sm btn-outline-primary" onclick="addQ()"><i class="ti ti-plus me-1"></i>Add Question</button></div>
            <div class="mb-3"><label class="form-label">Deadline (Optional)</label><input type="date" class="form-control"></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>Create & Send</button></div>
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
    function addQ() {
      document.getElementById('questionsContainer').insertAdjacentHTML('beforeend','<div class="border rounded p-3 mb-3"><div class="row"><div class="col-md-7 mb-2"><input type="text" class="form-control" placeholder="Question text"></div><div class="col-md-4 mb-2"><select class="form-select"><option>Text</option><option>Multiple Choice</option><option>Rating</option><option>Yes/No</option></select></div><div class="col-md-1"><button type="button" class="btn btn-light text-danger" onclick="this.parentElement.parentElement.parentElement.remove()"><i class="ti ti-trash"></i></button></div></div></div>');
    }
    document.getElementById('createFormForm').addEventListener('submit',function(e){e.preventDefault();const m=bootstrap.Modal.getInstance(document.getElementById('createFormModal'));m.hide();document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin','<div class="alert alert-success alert-dismissible fade show"><i class="ti ti-check me-2"></i>Form created and sent successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');this.reset();window.scrollTo({top:0,behavior:'smooth'});});
  </script>
</body>
</html>
