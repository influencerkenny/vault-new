<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
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
// Fetch user info for sidebar/header
$stmt = $pdo->prepare('SELECT first_name, last_name, email, avatar, username FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
$displayName = ($user && (trim($user['first_name']) || trim($user['last_name']))) ? trim($user['first_name'] . ' ' . $user['last_name']) : ($user['email'] ?? 'Investor');
// Sidebar links
$sidebarLinks = [
  ['href' => 'user-dashboard.php', 'label' => 'Dashboard', 'icon' => 'bi-house'],
  ['href' => 'plans.php', 'label' => 'Plans', 'icon' => 'bi-layers'],
  ['href' => 'deposits.php', 'label' => 'Deposits', 'icon' => 'bi-download'],
  ['href' => 'withdrawals.php', 'label' => 'Withdrawals', 'icon' => 'bi-upload'],
  ['href' => 'transactions.php', 'label' => 'Transactions', 'icon' => 'bi-list'],
  ['href' => 'referral.php', 'label' => 'Referral', 'icon' => 'bi-people'],
  ['href' => 'account-settings.php', 'label' => 'Settings', 'icon' => 'bi-gear'],
  ['href' => 'profile.php', 'label' => 'Profile', 'icon' => 'bi-person'],
  ['href' => 'support.php', 'label' => 'Support', 'icon' => 'bi-question-circle'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; font-size: 0.93rem; }
    .sidebar {
      background: rgba(10,16,30,0.95);
      border-right: 1px solid #1e293b;
      min-height: 100vh;
      width: 260px;
      position: fixed;
      top: 0; left: 0;
      z-index: 2001;
      padding: 2rem 1.5rem 1.5rem 1.5rem;
      display: flex;
      flex-direction: column;
      transition: left 0.3s;
      font-size: 0.95em;
    }
    .sidebar .logo { margin-bottom: 2rem; text-align: center; font-size: 0.95em; }
    .sidebar .nav-link { color: #cbd5e1; font-weight: 500; border-radius: 0.75rem; padding: 0.75rem 1rem; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.75rem; transition: background 0.2s, color 0.2s; position: relative; font-size: 0.95em; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: linear-gradient(90deg, #2563eb22 0%, #0ea5e922 100%); color: #38bdf8; box-shadow: 0 2px 8px 0 rgba(59,130,246,0.08); }
    .sidebar .logout-btn { color: #f87171; font-weight: 500; border-radius: 0.75rem; padding: 0.75rem 1rem; margin-top: auto; background: none; border: none; display: flex; align-items: center; gap: 0.75rem; transition: background 0.2s, color 0.2s; font-size: 0.95em; }
    .sidebar .logout-btn:hover { background: #7f1d1d22; color: #f87171; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; font-size: 0.93rem; }
    .dashboard-content-wrapper { max-width: 900px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.91rem; display: flex; align-items: flex-start; justify-content: center; min-height: 100vh; }
    .settings-table {
      background: linear-gradient(135deg, #2563eb22 0%, #0ea5e922 100%);
      border: 1px solid #2563eb33;
      border-radius: 1.25rem;
      box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10);
      color: #e5e7eb;
      margin: 0 auto 1.5rem auto;
      overflow: hidden;
      font-size: 1em;
      width: 100%;
      max-width: 700px;
    }
    .settings-table th, .settings-table td { vertical-align: middle; }
    .settings-table th { background: #181f2a; color: #38bdf8; font-weight: 700; }
    .settings-table tr { border-bottom: 1px solid #2563eb33; }
    .settings-table td { background: transparent; }
    .settings-table .action-btn { min-width: 120px; }
    .modal-content { background: #111827cc; color: #e5e7eb; border-radius: 1.25rem; }
    .modal-header { border-bottom: 1px solid #2563eb33; }
    .modal-footer { border-top: 1px solid #2563eb33; }
    .alert-success { background: #22c55e22; color: #22c55e; border: none; }
    .alert-danger { background: #ef444422; color: #ef4444; border: none; }
    @media (max-width: 991px) { .main-content { margin-left: 0; } .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 1rem 0.3rem; font-size: 0.89rem; flex-direction: column; align-items: center; } }
    @media (max-width: 767px) { .dashboard-content-wrapper { padding: 0.5rem 0.1rem; font-size: 0.87rem; } .settings-table { font-size: 0.95em; } }
    @media (max-width: 575px) { .dashboard-content-wrapper { padding: 0.2rem 0.05rem; font-size: 0.85rem; } .settings-table { font-size: 0.93em; } }
    .sidebar-mobile-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 2000;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.2s;
    }
    .sidebar-mobile-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }
    /* Ensure Bootstrap modal is always above sidebar overlay and is interactive */
    .modal, .modal-backdrop {
      z-index: 3000 !important;
      pointer-events: auto !important;
    }
    /* Make modal overlay non-blocking */
    .modal-backdrop {
      pointer-events: none !important;
    }
  </style>
</head>
<body>
  <div id="sidebar" class="sidebar">
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
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'user/header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="dashboard-content-wrapper mx-auto" style="max-width: 900px; width: 100%; padding: 0 1rem; display: flex; align-items: flex-start; justify-content: center; min-height: 100vh;">
        <table class="table settings-table align-middle mb-0">
          <thead>
            <tr>
              <th>Setting</th>
              <th>Description</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Password</td>
              <td>Change your account password for security.</td>
              <td class="text-center action-btn">
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#passwordModal"><i class="bi bi-key"></i> Change</button>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- Password Change Modal Only -->
        <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="passwordModalLabel">Change Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
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
                  <button type="submit" class="btn btn-warning w-100" name="password_save">Change Password</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    <footer class="dashboard-footer">
      &copy; <?=date('Y')?> Vault. All rights reserved.
    </footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script defer>
    // Mobile sidebar toggle/overlay
    var sidebar = document.getElementById('sidebar');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    var sidebarToggle = document.getElementById('sidebarToggle');
    function openSidebar() {
      sidebar.classList.add('active');
      sidebarOverlay.classList.add('active');
    }
    function closeSidebar() {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
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
    // AJAX password form (optional, for smooth UX)
    var passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
      passwordForm.onsubmit = function(e) {
        if (!window.fetch) return;
        e.preventDefault();
        const form = this;
        const data = new FormData(form);
        fetch('change-password.php', {
          method: 'POST',
          body: data
        })
        .then(res => res.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newCard = doc.querySelector('#passwordModal .modal-content');
          if (newCard) {
            document.querySelector('#passwordModal .modal-content').replaceWith(newCard);
          }
        });
      };
    }
  </script>
  <script src="public/sidebar-toggle.js" defer></script>
</body>
</html> 