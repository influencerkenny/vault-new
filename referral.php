<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
// Fetch user info
$stmt = $pdo->prepare('SELECT username, email, avatar, first_name, last_name FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
// Fetch unread notifications/messages count from database
$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$_SESSION['user_id']]);
$unreadCount = (int)$stmt->fetchColumn();
// Fetch recent notifications for dropdown
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$referralCode = $user['username'];
$referralLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/signup.php?ref=' . urlencode($referralCode);
// Fetch referred users
$stmt = $pdo->prepare('SELECT username, email, created_at, status FROM users WHERE referred_by = ?');
$stmt->execute([$referralCode]);
$referredUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalReferred = count($referredUsers);
// Fetch total referral rewards
$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total_rewards FROM user_rewards WHERE user_id = ? AND type = "referral"');
$stmt->execute([$_SESSION['user_id']]);
$totalRewards = $stmt->fetchColumn();
require_once __DIR__ . '/api/settings_helper.php';
$referral_commission = get_setting('referral_commission');
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
function sol_display($amount) {
  return '<span class="sol-value">' . number_format($amount, 2) . ' SOL</span>';
}
function usdt_placeholder($amount) {
    $rate = get_setting('sol_usdt_rate');
    if (!$rate || !is_numeric($rate)) $rate = 203.36;
    $usdtAmount = $amount * $rate;
    return '<span class="usdt-convert" data-sol="' . htmlspecialchars($amount, ENT_QUOTES) . '">≈ $' . number_format($usdtAmount, 2) . ' USDT</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Referral | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; }
    .sidebar { background: rgba(10,16,30,0.95); border-right: 1px solid #1e293b; min-height: 100vh; width: 260px; position: fixed; top: 0; left: 0; z-index: 2001; padding: 2rem 1.5rem 1.5rem 1.5rem; display: flex; flex-direction: column; transition: left 0.3s; }
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
    .referral-section { max-width: 700px; margin: 2.5rem auto 2rem auto; background: #181f2a; border-radius: 1.25rem; box-shadow: 0 4px 32px #0003; padding: 2.5rem 2rem 2rem 2rem; }
    .referral-label { color: #a1a1aa; font-weight: 600; font-size: 1.05rem; }
    .referral-value { font-size: 1.25rem; font-weight: 700; color: #38bdf8; margin-bottom: 0.5rem; }
    .copy-btn { font-size: 0.95em; border-radius: 0.5rem; padding: 0.3rem 0.9rem; margin-left: 0.5rem; }
    .referral-stats { display: flex; gap: 2rem; margin: 2rem 0 1.5rem 0; }
    .referral-stat { background: linear-gradient(135deg, #2563eb22 0%, #0ea5e922 100%); border: 1px solid #2563eb33; border-radius: 1rem; padding: 1.2rem 1.5rem; flex: 1; text-align: center; }
    .referral-stat .stat-label { color: #a1a1aa; font-size: 1.01rem; font-weight: 600; }
    .referral-stat .stat-value { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 0.2rem; }
    .referral-stat .stat-usdt { font-size: 0.9rem; color: #94a3b8; }
    .table-responsive { border-radius: 1rem; overflow: hidden; }
    .referrals-table { background: #151a23; border-radius: 1rem; overflow: hidden; font-size: 0.97rem; }
    .referrals-table th, .referrals-table td { color: #f3f4f6; vertical-align: middle; padding: 0.7rem 0.8rem; }
    .referrals-table th { background: #232b3b; color: #38bdf8; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; border-bottom: 2px solid #2563eb33; }
    .referrals-table td { font-weight: 600; color: #e0e7ef; background: #181f2a; border-bottom: 1px solid #232b3b; }
    .referrals-table tr:nth-child(even) td { background: #1e2330; }
    .referrals-table tr:hover td { background: #232b3b; color: #fff; transition: background 0.18s, color 0.18s; }
    .referrals-table tr:last-child td { border-bottom: none; }
    @media (max-width: 991px) { .sidebar { left: -260px; } .sidebar.active { left: 0; } .main-content { margin-left: 0; } .referral-section { padding: 1.2rem 0.5rem; } }
    @media (max-width: 767px) { .referral-section { padding: 0.7rem 0.2rem; } .referral-stats { flex-direction: column; gap: 1rem; } }
    @media (max-width: 575px) { .referral-section { padding: 0.5rem 0.1rem; } .referral-stats { flex-direction: column; gap: 0.7rem; } .referrals-table { font-size: 0.93rem; } .referrals-table th, .referrals-table td { padding: 0.4rem 0.5rem; } }
    .sol-value { font-size: 0.95em; color: #38bdf8; font-weight: 600; }
    .usdt-convert { display: block; font-size: 0.6em; color: #94a3b8; margin-top: 0.1em; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div id="sidebar" class="sidebar">
    <div class="logo mb-4">
      <img src="/vault-logo-new.png" alt="Vault Logo" height="48" loading="lazy">
    </div>
    <?php
    $sidebarLinks = [
      ['href' => 'user-dashboard.php', 'label' => 'Dashboard', 'icon' => 'bi-house'],
      ['href' => 'plans.php', 'label' => 'Plans', 'icon' => 'bi-layers'],
      ['href' => 'deposits.php', 'label' => 'Deposits', 'icon' => 'bi-download'],
      ['href' => 'withdrawals.php', 'label' => 'Withdrawals', 'icon' => 'bi-upload'],
      ['href' => 'transactions.php', 'label' => 'Transactions', 'icon' => 'bi-list'],
      ['href' => 'referral.php', 'label' => 'Referral', 'icon' => 'bi-people'],
      ['href' => 'settings.php', 'label' => 'Settings', 'icon' => 'bi-gear'],
      ['href' => 'profile.php', 'label' => 'Profile', 'icon' => 'bi-person'],
      ['href' => 'support.php', 'icon' => 'bi-question-circle', 'label' => 'Support'],
    ];
    foreach ($sidebarLinks as $link): ?>
      <a href="<?=$link['href']?>" class="nav-link<?=basename($_SERVER['PHP_SELF']) === basename($link['href']) ? ' active' : ''?>">
        <i class="bi <?=$link['icon']?>"></i> <?=$link['label']?>
      </a>
    <?php endforeach; ?>
    <form method="get" class="mt-auto">
      <button type="submit" name="logout" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</button>
    </form>
  </div>
  <div class="main-content">
    <?php include 'user/header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="referral-section mx-auto">
        <div class="mb-4">
          <div class="referral-label">Your Referral Code</div>
          <div class="referral-value"><?=$referralCode?></div>
        </div>
        <div class="mb-4">
          <div class="referral-label">Your Referral Link</div>
          <div class="d-flex align-items-center">
            <input type="text" class="form-control form-control-sm" id="referralLink" value="<?=$referralLink?>" readonly style="max-width: 420px; margin-right: 0.5rem;">
            <a href="<?=$referralLink?>" target="_blank" class="btn btn-info btn-sm me-2">Open</a>
            <button class="btn btn-outline-info btn-sm copy-btn" type="button" onclick="copyReferralLink()"><i class="bi bi-clipboard"></i> Copy</button>
          </div>
        </div>
        <div class="referral-stats">
          <div class="referral-stat">
            <div class="stat-label">Total Referred Users</div>
            <div class="stat-value"><?=$totalReferred?></div>
          </div>
          <div class="referral-stat">
            <div class="stat-label">Total Referral Rewards</div>
            <div class="stat-value"><?=sol_display($totalRewards)?></div>
            <div class="stat-usdt"><?=usdt_placeholder($totalRewards)?></div>
          </div>
          <div class="referral-stat">
            <div class="stat-label">Current Referral Commission</div>
            <div class="stat-value"><?=is_numeric($referral_commission) ? floatval($referral_commission) : 0?>%</div>
          </div>
        </div>
        <h5 class="mb-3 text-info fw-bold">Your Referred Users</h5>
        <div class="table-responsive">
          <table class="table referrals-table table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Joined</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($referredUsers as $ref): ?>
              <tr>
                <td><?=htmlspecialchars($ref['username'])?></td>
                <td><?=htmlspecialchars($ref['email'])?></td>
                <td><?=date('Y-m-d', strtotime($ref['created_at']))?></td>
                <td><?=ucfirst($ref['status'])?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!count($referredUsers)): ?><tr><td colspan="4" class="text-center text-muted">No referred users yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
    <footer class="dashboard-footer text-center d-flex flex-column align-items-center justify-content-center" style="border-top: 1px solid #1e293b; padding: 2rem; background: rgba(17,24,39,0.85); color: #a1a1aa; margin-top: auto;">
      <img src="/vault-logo-new.png" alt="Vault Logo" height="32" class="mb-2 mx-auto">
      <div class="mb-2">
        <a href="plans.php" class="text-info me-3">Staking Plans</a>
        <a href="roadmap.php" class="text-info">Roadmap</a>
      </div>
      <div>&copy; <?=date('Y')?> Vault. All rights reserved.</div>
    </footer>
  </div>
  <script>
    function copyReferralLink() {
      var copyText = document.getElementById('referralLink');
      copyText.select();
      copyText.setSelectionRange(0, 99999);
      document.execCommand('copy');
      var btn = document.querySelector('.copy-btn');
      btn.innerHTML = '<i class="bi bi-clipboard-check"></i> Copied!';
      setTimeout(function() { btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy'; }, 1200);
    }
    // Sidebar toggle/overlay for mobile
    var sidebar = document.getElementById('sidebar');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarClose = document.getElementById('sidebarClose');
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
      link.addEventListener('click', function() { if (window.innerWidth < 992) closeSidebar(); });
    });
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 992) {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
      }
      handleSidebarCloseBtn(); // Update close button visibility on resize
    });
    document.addEventListener('DOMContentLoaded', handleSidebarCloseBtn); // Initial call for DOMContentLoaded
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 