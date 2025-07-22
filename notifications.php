<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
// Pagination logic
$perPage = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;
// Fetch total count for pagination
$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
$stmt->execute([$user_id]);
$totalNotifications = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalNotifications / $perPage));
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
// Fetch notifications for current page
$stmt = $pdo->prepare('SELECT title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications | Vault</title>
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
    .notifications-table {
      background: linear-gradient(135deg, #2563eb22 0%, #0ea5e922 100%);
      border: 1px solid #2563eb33;
      border-radius: 1.25rem;
      box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10);
      color: #e5e7eb;
      margin: 0 auto 1.5rem auto;
      overflow: hidden;
      font-size: 1em;
      width: 100%;
      max-width: 900px;
    }
    .notifications-table th, .notifications-table td { vertical-align: middle; }
    .notifications-table th { background: #181f2a; color: #38bdf8; font-weight: 700; }
    .notifications-table tr { border-bottom: 1px solid #2563eb33; }
    .notifications-table td { background: transparent; }
    .fw-bold { font-weight: 700 !important; }
    .badge { font-size: 0.85em; }
    .notification-unread {
      background: linear-gradient(90deg, #2563eb33 0%, #0ea5e955 100%);
      border-left: 5px solid #38bdf8;
      color: #fff;
    }
    .notification-read {
      background: #181f2a;
      color: #a1a1aa;
    }
    .notification-message {
      font-size: 1.05em;
      font-weight: 600;
      color: #fbbf24;
      letter-spacing: 0.01em;
    }
    .notification-unread .notification-message {
      color: #38bdf8;
      text-shadow: 0 1px 8px #0ea5e9cc;
    }
    .notification-read .notification-message {
      color: #a1a1aa;
    }
    .notification-date {
      display: inline-block;
      min-width: 120px;
      padding: 0.35em 1em;
      border-radius: 999px;
      font-weight: 600;
      font-size: 0.98em;
      background: #181f2a;
      color: #fff;
      margin-right: 0.5em;
      letter-spacing: 0.01em;
    }
    .notification-unread .notification-date {
      background: #0f172a;
      color: #fff;
    }
    .notification-status-badge {
      font-weight: 700;
      letter-spacing: 0.03em;
      border-radius: 0.5rem;
      padding: 0.4em 0.9em;
      font-size: 0.92em;
    }
    .notification-unread .notification-status-badge {
      background: linear-gradient(90deg, #38bdf8 0%, #0ea5e9 100%);
      color: #fff;
      box-shadow: 0 2px 8px 0 #38bdf855;
    }
    .notification-read .notification-status-badge {
      background: #334155;
      color: #a1a1aa;
    }
    .pagination {
      margin: 1.5rem 0 0 0;
      justify-content: center;
    }
    .pagination .page-link {
      background: #181f2a;
      color: #38bdf8;
      border: 1px solid #2563eb33;
      border-radius: 0.5rem;
      margin: 0 0.15rem;
      font-weight: 600;
      transition: background 0.2s, color 0.2s;
    }
    .pagination .page-link.active, .pagination .page-link:focus, .pagination .page-link:hover {
      background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%);
      color: #fff;
      border-color: #38bdf8;
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
        <div class="w-100">
          <h3 class="mb-4 text-info fw-bold">Notifications</h3>
          <div class="table-responsive">
            <table class="table notifications-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Message</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($notifications as $n): ?>
                <tr class="<?=!$n['is_read'] ? 'notification-unread' : 'notification-read'?>">
                  <td class="notification-date"><?=date('M d, Y H:i', strtotime($n['created_at']))?></td>
                  <td>
                    <span class="notification-status-badge">
                      <?=!$n['is_read'] ? 'Unread' : 'Read'?>
                    </span>
                  </td>
                  <td class="notification-message"><?=htmlspecialchars($n['message'])?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <!-- Pagination Controls -->
          <?php if ($totalPages > 1): ?>
          <nav>
            <ul class="pagination">
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item<?=($i === $page ? ' active' : '')?>">
                  <a class="page-link<?=($i === $page ? ' active' : '')?>" href="?page=<?=$i?>"><?=$i?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
          <?php endif; ?>
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
  </script>
  <script src="public/sidebar-toggle.js" defer></script>
</body>
</html> 