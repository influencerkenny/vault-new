<?php
// user/header.php
require_once __DIR__ . '/../api/settings_helper.php';
require_once __DIR__ . '/../api/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$logo = get_setting('logo_path') ?: '/vault-logo-new.png';
$system_name = get_setting('system_name') ?: 'Vault';

// Ensure $user, $avatar, $notifications are set
if (!isset($user) || !isset($avatar) || !isset($notifications)) {
    if (isset($_SESSION['user_id'])) {
        $pdo = $pdo ?? new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
        // Fetch recent notifications for dropdown
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $user = [];
        $avatar = 'public/placeholder-user.jpg';
        $notifications = [];
    }
}
// Fetch unread notifications count for the logged-in user
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$_SESSION['user_id']]);
    $unreadCount = (int)$stmt->fetchColumn();
}
?>
<div class="user-header" style="border-bottom: 1px solid #1e293b; padding: 1.2rem 2rem 1rem 2rem; background: rgba(17,24,39,0.85); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10;">
  <div class="logo d-flex align-items-center">
    <img src="<?=htmlspecialchars($logo)?>" alt="Logo" class="me-2" style="height:36px;">
    <span style="font-weight:700;font-size:1.2rem;color:#38bdf8;"> <?=htmlspecialchars($system_name)?> </span>
  </div>
  <!-- Add user profile or nav here if needed -->
</div>
<header class="dashboard-header d-flex align-items-center justify-content-between">
  <div class="d-flex align-items-center">
    <button class="btn btn-outline-info d-lg-none me-3" id="sidebarToggle" aria-label="Open sidebar">
      <i class="bi bi-list" style="font-size:1.7rem;"></i>
    </button>
    <!-- Logo and Back to Dashboard link removed -->
  </div>
  <div class="d-flex align-items-center gap-2">
    <!-- Notification Dropdown -->
    <div class="dropdown me-2 d-inline-block">
      <button class="btn btn-outline-info position-relative p-2" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="line-height:1;">
        <i class="bi bi-bell" style="font-size:1.15rem;"></i>
        <?php if($unreadCount > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;">
            <?=$unreadCount > 9 ? '9+' : $unreadCount?>
          </span>
        <?php endif; ?>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow notification-dropdown-menu" aria-labelledby="notificationDropdown" style="min-width:320px; max-width:400px;">
        <li class="px-3 py-2 border-bottom">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-dark fw-bold">Notifications</h6>
            <?php if($unreadCount > 0): ?>
              <a href="notifications.php" class="text-info text-decoration-none small">Mark all read</a>
            <?php endif; ?>
          </div>
        </li>
        <?php if(isset($notifications) && count($notifications) > 0): ?>
          <?php foreach($notifications as $notif): ?>
            <li>
              <a class="dropdown-item notification-item <?=$notif['is_read'] == 0 ? 'unread' : ''?>" href="#" data-notification-id="<?=$notif['id']?>">
                <div class="d-flex align-items-start">
                  <div class="flex-shrink-0">
                    <div class="notification-icon <?=$notif['is_read'] == 0 ? 'unread' : ''?>">
                      <i class="bi bi-info-circle"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-2">
                    <div class="fw-semibold text-dark mb-1" style="font-size:0.9rem;">
<?=isset($notif['title']) ? htmlspecialchars($notif['title']) : 'Notification'?>
                    </div>
                    <div class="text-muted small mb-1" style="font-size:0.8rem; line-height:1.3;"><?=htmlspecialchars($notif['message'])?></div>
                    <div class="text-muted" style="font-size:0.75rem;"><?=date('M j, g:i A', strtotime($notif['created_at']))?></div>
                  </div>
                </div>
              </a>
            </li>
          <?php endforeach; ?>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-center text-info" href="notifications.php"><i class="bi bi-arrow-right me-1"></i>View All Notifications</a></li>
        <?php else: ?>
          <li class="px-3 py-3 text-center text-muted">
            <i class="bi bi-bell-slash mb-2" style="font-size:1.5rem;"></i>
            <div>No notifications yet</div>
          </li>
        <?php endif; ?>
      </ul>
    </div>
    <!-- Profile Dropdown -->
    <div class="dropdown d-inline-block">
      <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <img src="<?=$avatar?>" alt="Profile" width="40" height="40" class="rounded-circle me-2" style="object-fit:cover;">
        <span class="d-none d-md-inline text-white fw-semibold">Profile</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow profile-dropdown-menu" aria-labelledby="profileDropdown">
        <li class="px-3 py-2 border-bottom mb-1" style="min-width:220px;">
          <div class="fw-semibold text-dark mb-0" style="font-size:1.05rem;"><?=$user['username'] ?? ''?></div>
          <div class="text-muted" style="font-size:0.95rem;word-break:break-all;">
            <?=htmlspecialchars($user['email'] ?? '')?>
          </div>
        </li>
        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
        <li><a class="dropdown-item" href="account-settings.php"><i class="bi bi-gear me-2"></i>Account Settings</a></li>
        <li><a class="dropdown-item" href="change-password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
        <li><a class="dropdown-item" href="my-activity.php"><i class="bi bi-activity me-2"></i>My Activity</a></li>
        <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="notifications.php"><span><i class="bi bi-bell me-2"></i>Notifications</span><?php if($unreadCount>0): ?><span class="badge bg-danger ms-2"><?=$unreadCount?></span><?php endif; ?></a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="support.php"><i class="bi bi-question-circle me-2"></i>Support</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</header>
<script>
// Mark all notifications as read via AJAX
document.addEventListener('DOMContentLoaded', function() {
  var markAllLink = document.querySelector('.notification-dropdown-menu .text-info.text-decoration-none.small');
  if (markAllLink) {
    markAllLink.addEventListener('click', function(e) {
      e.preventDefault();
      fetch('api/mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'all=1'
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Remove unread class from all notification items
          document.querySelectorAll('.notification-item').forEach(function(item) {
            item.classList.remove('unread');
            var icon = item.querySelector('.notification-icon');
            if (icon) icon.classList.remove('unread');
          });
          // Remove unread badge
          var badge = document.querySelector('#notificationDropdown .badge');
          if (badge) badge.remove();
        }
      });
    });
  }
});
</script> 