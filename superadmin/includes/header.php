<?php
$notifications = get_notifications(get_user_id(), 5);
$unread_count = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
?>
<header class="app-header">
  <nav class="navbar navbar-expand-lg navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item d-block d-xl-none">
        <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
          <i class="ti ti-menu-2"></i>
        </a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link position-relative" href="javascript:void(0)" id="drop1" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="ti ti-bell"></i>
          <?php if ($unread_count > 0): ?>
          <div class="notification bg-primary rounded-circle"></div>
          <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-animate-up" aria-labelledby="drop1">
          <div class="message-body">
            <h5 class="dropdown-header">Notifications</h5>
            <?php if (empty($notifications)): ?>
            <a href="javascript:void(0)" class="dropdown-item text-muted">
              <small>No new notifications</small>
            </a>
            <?php else: ?>
              <?php foreach ($notifications as $notif): ?>
              <a href="javascript:void(0)" class="dropdown-item <?php echo !$notif['is_read'] ? 'bg-light' : ''; ?>">
                <div class="d-flex align-items-start">
                  <div class="flex-grow-1">
                    <h6 class="mb-1"><?php echo htmlspecialchars($notif['title']); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars(substr($notif['message'], 0, 50)) . '...'; ?></small>
                  </div>
                </div>
              </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </li>
    </ul>
    <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
      <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
        <li class="nav-item dropdown">
          <a class="nav-link" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="../assets/images/profile/user-1.jpg" alt="" width="35" height="35" class="rounded-circle">
          </a>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
            <div class="message-body">
              <a href="profile.php" class="d-flex align-items-center gap-2 dropdown-item">
                <i class="ti ti-user fs-6"></i>
                <p class="mb-0 fs-3">My Profile</p>
              </a>
              <a href="settings.php" class="d-flex align-items-center gap-2 dropdown-item">
                <i class="ti ti-settings fs-6"></i>
                <p class="mb-0 fs-3">Settings</p>
              </a>
              <a href="../logout.php" class="btn btn-outline-primary mx-3 mt-2 d-block">Logout</a>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </nav>
</header>
