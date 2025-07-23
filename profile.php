<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault', 'root', '');
$user_id = $_SESSION['user_id'];
$success = $error = '';
// Handle classic POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_save'])) {
  $first_name = trim($_POST['first_name'] ?? '');
  $last_name = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $avatar = trim($_POST['avatar_url'] ?? '');
  // Handle avatar upload
  if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
      $filename = 'avatar_' . uniqid() . '.' . $ext;
      $dest = '/public/avatars/' . $filename;
      if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $dest)) {
        $avatar = $dest;
      } else {
        $error = 'Failed to upload avatar.';
      }
    } else {
      $error = 'Invalid avatar file type.';
    }
  }
  if (!$first_name || !$last_name || !$email) {
    $error = 'All fields except avatar are required.';
  } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email address.';
  } else if (!$error) {
    $stmt = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=?, avatar=? WHERE id=?');
    if ($stmt->execute([$first_name, $last_name, $email, $avatar, $user_id])) {
      $success = 'Profile updated successfully!';
    } else {
      $error = 'Failed to update profile.';
    }
  }
}
// Fetch user info
$stmt = $pdo->prepare('SELECT first_name, last_name, email, avatar FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { die('User not found.'); }
$displayName = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
// Sidebar links
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
?>
<?php include 'user/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile | Vault</title>
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
    .profile-card { background: #111827cc; border-radius: 1.5rem; box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10); padding: 2rem; max-width: 480px; margin: 2rem auto; }
    .profile-avatar { width: 96px; height: 96px; border-radius: 50%; object-fit: cover; border: 3px solid #2563eb; margin-bottom: 1rem; }
    .form-label { color: #38bdf8; font-weight: 500; }
    .btn-primary { background: linear-gradient(90deg, #2563eb 0%, #38bdf8 100%); border: none; }
    .btn-primary:hover { background: linear-gradient(90deg, #38bdf8 0%, #2563eb 100%); }
    .alert-success { background: #22c55e22; color: #22c55e; border: none; }
    .alert-danger { background: #ef444422; color: #ef4444; border: none; }
    .dashboard-footer { border-top: 1px solid #1e293b; padding: 2rem; background: rgba(17,24,39,0.85); color: #a1a1aa; text-align: center; margin-top: auto; }
    @media (max-width: 991px) { .sidebar { left: -260px; } .sidebar.active { left: 0; } .main-content { margin-left: 0; } }
    .sidebar-mobile-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 2000; display: none; }
    .sidebar-mobile-overlay.active { display: block; }
    @media (max-width: 991px) {
      .sidebar { left: -260px !important; transition: left 0.3s; min-width: 220px !important; max-width: 220px !important; box-shadow: 2px 0 16px #0004; }
      .sidebar.open { left: 0 !important; z-index: 2000 !important; }
      .sidebar-close-btn { display: block !important; }
    }
  </style>
</head>
<body>
  <!-- Mobile Sidebar Overlay -->
  <div id="sidebarOverlay" class="sidebar-mobile-overlay" aria-label="Sidebar navigation" style="position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 2000; opacity: 0; pointer-events: none; transition: opacity 0.2s;"></div>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar" aria-label="Sidebar navigation" style="background: rgba(10,16,30,0.95); border-right: 1px solid #1e293b; min-height: 100vh; width: 260px; position: fixed; top: 0; left: 0; z-index: 100; padding: 2rem 1.5rem 1.5rem 1.5rem; display: flex; flex-direction: column; transition: left 0.3s;">
    <button type="button" class="sidebar-close-btn" aria-label="Close sidebar" onclick="closeSidebar()" style="position:absolute;top:14px;right:14px;display:none;font-size:2rem;background:none;border:none;color:#fff;z-index:2100;line-height:1;cursor:pointer;">&times;</button>
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
    <main class="flex-grow-1 p-4">
      <div class="profile-card">
        <h2 class="mb-4 text-center">My Profile</h2>
        <div class="text-center mb-3">
          <img src="<?=htmlspecialchars($avatar)?>" alt="Avatar" class="profile-avatar" id="profileAvatarImg">
        </div>
        <?php if ($success): ?><div class="alert alert-success" id="profileSuccess"><?=$success?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger" id="profileError"><?=$error?></div><?php endif; ?>
        <form id="profileForm" method="post" enctype="multipart/form-data" autocomplete="off">
          <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?=htmlspecialchars($user['first_name'])?>" required>
          </div>
          <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?=htmlspecialchars($user['last_name'])?>" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required>
          </div>
          <div class="mb-3">
            <label for="avatar_file" class="form-label">Avatar Upload</label>
            <input type="file" class="form-control" id="avatar_file" name="avatar_file" accept="image/*">
          </div>
          <button type="submit" class="btn btn-primary w-100" name="profile_save">Save Profile</button>
        </form>
      </div>
    </main>
    <footer class="dashboard-footer">
      &copy; <?=date('Y')?> Vault. All rights reserved.
    </footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    var sidebar = document.getElementById('sidebar');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarCloseBtn = document.querySelector('.sidebar-close-btn');
    function openSidebar() {
      sidebar.classList.add('open');
      sidebarOverlay.classList.add('active');
      sidebarOverlay.style.opacity = 1;
      sidebarOverlay.style.pointerEvents = 'auto';
    }
    function closeSidebar() {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('active');
      sidebarOverlay.style.opacity = 0;
      sidebarOverlay.style.pointerEvents = 'none';
    }
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', openSidebar);
    }
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', closeSidebar);
    }
    if (sidebarCloseBtn) {
      sidebarCloseBtn.addEventListener('click', closeSidebar);
    }
    document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
      link.addEventListener('click', function() { if (window.innerWidth < 992) closeSidebar(); });
    });
  </script>
</body>
</html> 