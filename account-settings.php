<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
$success = $error = '';
// Handle classic POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings_save'])) {
  $email = trim($_POST['email'] ?? '');
  $notify_email = isset($_POST['notify_email']) ? 1 : 0;
  if (!$email) {
    $error = 'Email is required.';
  } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email address.';
  } else {
    $stmt = $pdo->prepare('UPDATE users SET email=?, notify_email=? WHERE id=?');
    if ($stmt->execute([$email, $notify_email, $user_id])) {
      $success = 'Account settings updated successfully!';
    } else {
      $error = 'Failed to update settings.';
    }
  }
}
// Handle password change
$password_success = $password_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_save'])) {
  $current = $_POST['current_password'] ?? '';
  $new = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';
  if (!$current || !$new || !$confirm) {
    $password_error = 'All password fields are required.';
  } else if ($new !== $confirm) {
    $password_error = 'New passwords do not match.';
  } else {
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id=?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($current, $row['password'])) {
      $password_error = 'Current password is incorrect.';
    } else {
      $hash = password_hash($new, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare('UPDATE users SET password=? WHERE id=?');
      if ($stmt->execute([$hash, $user_id])) {
        $password_success = 'Password updated successfully!';
      } else {
        $password_error = 'Failed to update password.';
      }
    }
  }
}
// Handle 2FA toggle
$twofa_success = $twofa_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['twofa_save'])) {
  $twofa_enabled = isset($_POST['twofa_enabled']) ? 1 : 0;
  $stmt = $pdo->prepare('UPDATE users SET twofa_enabled=? WHERE id=?');
  if ($stmt->execute([$twofa_enabled, $user_id])) {
    $twofa_success = $twofa_enabled ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.';
  } else {
    $twofa_error = 'Failed to update 2FA setting.';
  }
}
// Fetch user info
$stmt = $pdo->prepare('SELECT first_name, last_name, email, notify_email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { die('User not found.'); }
$displayName = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
$sidebarLinks = [
  ['href' => 'user-dashboard.php', 'label' => 'Dashboard', 'icon' => 'bi-house'],
  ['href' => 'plans.php', 'label' => 'Plans', 'icon' => 'bi-layers'],
  ['href' => 'deposits.php', 'label' => 'Deposits', 'icon' => 'bi-download'],
  ['href' => 'withdrawals.php', 'label' => 'Withdrawals', 'icon' => 'bi-upload'],
  ['href' => 'transactions.php', 'label' => 'Transactions', 'icon' => 'bi-list'],
  ['href' => 'referral.php', 'label' => 'Referral', 'icon' => 'bi-people'],
  ['href' => 'settings.php', 'label' => 'Settings', 'icon' => 'bi-gear'],
  ['href' => 'profile.php', 'label' => 'Profile', 'icon' => 'bi-person'],
  ['href' => 'support.php', 'label' => 'Support', 'icon' => 'bi-question-circle'],
];
// Fetch 2FA status
$stmt = $pdo->prepare('SELECT twofa_enabled FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_twofa = $stmt->fetch(PDO::FETCH_ASSOC);
$twofa_enabled = $user_twofa ? (int)$user_twofa['twofa_enabled'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Settings | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; }
    .sidebar { background: rgba(10,16,30,0.95); border-right: 1px solid #1e293b; min-height: 100vh; width: 260px; position: fixed; top: 0; left: 0; z-index: 100; padding: 2rem 1.5rem 1.5rem 1.5rem; display: flex; flex-direction: column; transition: left 0.3s; }
    .sidebar .logo { margin-bottom: 2rem; text-align: center; }
    .sidebar .nav-link { color: #cbd5e1; font-weight: 500; border-radius: 0.75rem; padding: 0.75rem 1rem; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.75rem; transition: background 0.2s, color 0.2s; position: relative; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: linear-gradient(90deg, #2563eb22 0%, #0ea5e922 100%); color: #38bdf8; box-shadow: 0 2px 8px 0 rgba(59,130,246,0.08); }
    .sidebar .logout-btn { color: #f87171; font-weight: 500; border-radius: 0.75rem; padding: 0.75rem 1rem; margin-top: auto; background: none; border: none; display: flex; align-items: center; gap: 0.75rem; transition: background 0.2s, color 0.2s; }
    .sidebar .logout-btn:hover { background: #7f1d1d22; color: #f87171; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; }
    .dashboard-header { border-bottom: 1px solid #1e293b; padding: 1.5rem 2rem 1rem 2rem; background: rgba(17,24,39,0.85); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10; }
    .dashboard-header .logo { height: 48px; }
    .dashboard-header .back-link { color: #38bdf8; font-weight: 500; text-decoration: none; margin-left: 1.5rem; transition: color 0.2s; }
    .dashboard-header .back-link:hover { color: #0ea5e9; text-decoration: underline; }
    .settings-card { background: #111827cc; border-radius: 1.5rem; box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10); padding: 2rem; max-width: 520px; margin: 2rem auto; }
    .form-label { color: #38bdf8; font-weight: 500; }
    .btn-primary { background: linear-gradient(90deg, #2563eb 0%, #38bdf8 100%); border: none; }
    .btn-primary:hover { background: linear-gradient(90deg, #38bdf8 0%, #2563eb 100%); }
    .alert-success { background: #22c55e22; color: #22c55e; border: none; }
    .alert-danger { background: #ef444422; color: #ef4444; border: none; }
    .dashboard-footer { border-top: 1px solid #1e293b; padding: 2rem; background: rgba(17,24,39,0.85); color: #a1a1aa; text-align: center; margin-top: auto; }
    @media (max-width: 991px) { .sidebar { left: -260px; } .sidebar.active { left: 0; } .main-content { margin-left: 0; } }
    .sidebar-mobile-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 2000; display: none; }
    .sidebar-mobile-overlay.active { display: block; }
  </style>
</head>
<body>
  <!-- Mobile Sidebar Overlay -->
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <!-- Sidebar -->
  <div id="sidebar" class="sidebar d-none d-lg-flex flex-column">
    <div class="logo mb-4">
      <img src="/vault-logo-new.png" alt="Vault Logo" height="48">
    </div>
    <?php foreach ($sidebarLinks as $link): ?>
      <a href="<?=$link['href']?>" class="nav-link<?=basename($_SERVER['PHP_SELF']) === basename($link['href']) ? ' active' : ''?>">
        <i class="bi <?=$link['icon']?>"></i> <?=$link['label']?>
      </a>
    <?php endforeach; ?>
    <form method="get" class="mt-auto">
      <button type="submit" name="logout" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</button>
    </form>
  </div>
  <div class="main-content">
    <header class="dashboard-header d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <!-- Hamburger for mobile -->
        <button class="btn btn-outline-info d-lg-none me-3" id="sidebarToggle" aria-label="Open sidebar">
          <i class="bi bi-list" style="font-size:1.7rem;"></i>
        </button>
        <img src="/vault-logo-new.png" alt="Vault Logo" class="logo me-3">
      </div>
      <div><!-- Wallet connection placeholder -->
        <div class="dropdown">
          <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?=$avatar?>" alt="Profile" width="40" height="40" class="rounded-circle me-2" style="object-fit:cover;">
            <span class="d-none d-md-inline text-white fw-semibold">Profile</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow profile-dropdown-menu" aria-labelledby="profileDropdown">
            <li class="px-3 py-2 border-bottom mb-1" style="min-width:220px;">
              <div class="fw-semibold text-dark mb-0" style="font-size:1.05rem;"><?=$displayName?></div>
              <div class="text-muted" style="font-size:0.95rem;word-break:break-all;">
                <?=htmlspecialchars($user['email'])?>
              </div>
            </li>
            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="account-settings.php"><i class="bi bi-gear me-2"></i>Account Settings</a></li>
            <li><a class="dropdown-item" href="change-password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
            <li><a class="dropdown-item" href="my-activity.php"><i class="bi bi-activity me-2"></i>My Activity</a></li>
            <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="notifications.php"><span><i class="bi bi-bell me-2"></i>Notifications</span></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="support.php"><i class="bi bi-question-circle me-2"></i>Support</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </header>
    <main class="flex-grow-1 p-4">
      <div class="row g-4 justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
          <div class="settings-card h-100">
            <h2 class="mb-4 text-center">Account Settings</h2>
                <?php if ($success): ?><div class="alert alert-success" id="settingsSuccess"><?=$success?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger" id="settingsError"><?=$error?></div><?php endif; ?>
                <form id="settingsForm" method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required>
                  </div>
                  <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notify_email" name="notify_email" value="1" <?=($user['notify_email']?'checked':'')?> >
                    <label class="form-check-label" for="notify_email">Email me about important account activity</label>
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="settings_save">Save Settings</button>
                </form>
              </div>
            </div>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="settings-card h-100">
            <h2 class="mb-4 text-center">Change Password</h2>
                <?php if ($password_success): ?><div class="alert alert-success" id="passwordSuccess"><?=$password_success?></div><?php endif; ?>
                <?php if ($password_error): ?><div class="alert alert-danger" id="passwordError"><?=$password_error?></div><?php endif; ?>
                <form id="passwordForm" method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                  </div>
                  <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                  </div>
                  <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                  </div>
              <button type="submit" class="btn btn-primary w-100" name="password_save">Change Password</button>
                </form>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="settings-card h-100">
            <h2 class="mb-4 text-center">Two-Factor Authentication (2FA)</h2>
                <?php if ($twofa_success): ?><div class="alert alert-success" id="twofaSuccess"><?=$twofa_success?></div><?php endif; ?>
                <?php if ($twofa_error): ?><div class="alert alert-danger" id="twofaError"><?=$twofa_error?></div><?php endif; ?>
                <form id="twofaForm" method="post" autocomplete="off">
                  <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="twofa_enabled" name="twofa_enabled" value="1" <?=$twofa_enabled?'checked':''?> >
                    <label class="form-check-label" for="twofa_enabled">Enable two-factor authentication for extra security</label>
                  </div>
              <button type="submit" class="btn btn-primary w-100" name="twofa_save">Update 2FA Setting</button>
                </form>
          </div>
        </div>
      </div>
    </main>
    <footer class="dashboard-footer">
      &copy; <?=date('Y')?> Vault. All rights reserved.
    </footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Mobile sidebar toggle/overlay (copied from dashboard)
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarToggle = document.getElementById('sidebarToggle');
    function openSidebar() {
      sidebar.classList.add('active','d-flex');
      sidebar.classList.remove('d-none');
      sidebarOverlay.classList.add('active');
    }
    function closeSidebar() {
      sidebar.classList.remove('active','d-flex');
      sidebarOverlay.classList.remove('active');
      if (window.innerWidth < 992) sidebar.classList.add('d-none');
    }
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', openSidebar);
    }
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', closeSidebar);
    }
    document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
      link.addEventListener('click', function() {
        if (window.innerWidth < 992) closeSidebar();
      });
    });
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 992) {
        sidebar.classList.remove('d-none');
        sidebar.classList.add('d-flex');
        sidebarOverlay.classList.remove('active');
      } else {
        sidebar.classList.remove('d-flex');
        sidebar.classList.add('d-none');
      }
    });
    // Progressive enhancement: AJAX save
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
      if (!window.fetch) return; // fallback to classic
          e.preventDefault();
          const form = this;
          const data = new FormData(form);
          fetch('account-settings.php', {
            method: 'POST',
            body: data
          })
          .then(res => res.text())
          .then(html => {
        // Replace the settings card with the new HTML (partial reload)
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
        const newCard = doc.querySelector('.settings-card');
            if (newCard) {
          document.querySelector('.settings-card').replaceWith(newCard);
        }
      });
    });
    // Add AJAX for password and 2FA forms
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
          if (!window.fetch) return;
          e.preventDefault();
          const form = this;
          const data = new FormData(form);
          fetch('account-settings.php', {
            method: 'POST',
            body: data
          })
          .then(res => res.text())
          .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
        const newCard = doc.querySelectorAll('.settings-card')[1];
            if (newCard) {
          document.querySelectorAll('.settings-card')[1].replaceWith(newCard);
        }
      });
    });
    document.getElementById('twofaForm').addEventListener('submit', function(e) {
          if (!window.fetch) return;
          e.preventDefault();
          const form = this;
          const data = new FormData(form);
          fetch('account-settings.php', {
            method: 'POST',
            body: data
          })
          .then(res => res.text())
          .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
        const newCard = doc.querySelectorAll('.settings-card')[2];
            if (newCard) {
          document.querySelectorAll('.settings-card')[2].replaceWith(newCard);
        }
      });
    });
  </script>
</body>
</html> 