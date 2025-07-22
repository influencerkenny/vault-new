<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare('SELECT first_name, last_name, email, avatar, username FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$displayName = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : 'Investor';
$email = $user ? $user['email'] : '';
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';

// Optional: Filtering
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$where = ['t.user_id = ?'];
$params = [$user_id];
if ($type) { $where[] = 't.type = ?'; $params[] = $type; }
if ($status) { $where[] = 't.status = ?'; $params[] = $status; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Enhanced query: join for investment (stake) and reward details
$stmt = $pdo->prepare(<<<SQL
SELECT t.*, 
  CASE WHEN t.type = 'investment' THEN s.plan_id ELSE NULL END AS plan_id,
  CASE WHEN t.type = 'investment' THEN s.amount ELSE NULL END AS stake_amount,
  CASE WHEN t.type = 'investment' THEN s.interest_earned ELSE NULL END AS interest_earned,
  CASE WHEN t.type = 'investment' THEN s.status ELSE NULL END AS stake_status,
  CASE WHEN t.type = 'investment' THEN s.started_at ELSE NULL END AS stake_started_at,
  CASE WHEN t.type = 'investment' THEN s.ended_at ELSE NULL END AS stake_ended_at,
  CASE WHEN t.type = 'investment' THEN p.name ELSE NULL END AS plan_name,
  CASE WHEN t.type = 'reward' THEN r.description ELSE NULL END AS reward_description
FROM transactions t
  LEFT JOIN user_stakes s ON t.type = 'investment' AND s.user_id = t.user_id AND s.amount = t.amount AND ABS(TIMESTAMPDIFF(SECOND, s.started_at, t.created_at)) < 86400
  LEFT JOIN plans p ON t.type = 'investment' AND p.id = s.plan_id
  LEFT JOIN user_rewards r ON t.type = 'reward' AND r.user_id = t.user_id AND r.amount = t.amount AND ABS(TIMESTAMPDIFF(SECOND, r.created_at, t.created_at)) < 86400
$whereSql
ORDER BY t.created_at DESC
SQL);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all user stakes that do not have a corresponding investment transaction
$stakes_stmt = $pdo->prepare('SELECT us.*, p.name AS plan_name FROM user_stakes us LEFT JOIN transactions t ON t.type = "investment" AND t.user_id = us.user_id AND t.amount = us.amount AND ABS(TIMESTAMPDIFF(SECOND, us.started_at, t.created_at)) < 86400 LEFT JOIN plans p ON us.plan_id = p.id WHERE us.user_id = ? AND t.id IS NULL');
$stakes_stmt->execute([$user_id]);
$orphan_stakes = $stakes_stmt->fetchAll(PDO::FETCH_ASSOC);

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
  <title>Transactions | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; }
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
    }
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
    .dashboard-content-wrapper { max-width: 900px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.93rem; }
    .table-responsive { overflow-x: auto; }
    .table { min-width: 600px; font-size: 0.92rem; }
    .table th, .table td { padding: 0.35rem 0.5rem; }
    @media (max-width: 991px) {
      .sidebar { left: -260px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; font-size: 0.91rem; }
    }
    @media (max-width: 767px) {
      .dashboard-content-wrapper { padding: 0 0.1rem; font-size: 0.89rem; }
    }
    @media (max-width: 575px) {
      .dashboard-content-wrapper { padding: 0 0.05rem; font-size: 0.87rem; }
      .table { font-size: 0.87rem; min-width: 480px; }
      .table th, .table td { padding: 0.28rem 0.35rem; }
    }
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
    .dashboard-footer {
      border-top: 1px solid #1e293b;
      padding: 2rem;
      background: rgba(17,24,39,0.85);
      color: #a1a1aa;
      text-align: center;
      margin-top: auto;
    }
    .sol-value { font-size: 0.95em; color: #38bdf8; font-weight: 600; }
    .usdt-convert { display: block; font-size: 0.6em; color: #94a3b8; margin-top: 0.1em; transition: color 0.3s ease; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div id="sidebar" class="sidebar">
    <div class="logo mb-4">
      <img src="/vault-logo-new.png" alt="Vault Logo" height="48" loading="lazy">
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
  <!-- Mobile Sidebar Overlay (after sidebar) -->
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'user/header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="dashboard-content-wrapper mx-auto">
        <h2 class="mb-4 text-info fw-bold">Transaction History</h2>
        <!-- Optional: Filter Form -->
        <form class="row g-3 mb-4" method="get">
          <div class="col-md-3">
            <select name="type" class="form-select" onchange="this.form.submit()">
              <option value="">All Types</option>
              <option value="deposit" <?=$type==='deposit'?'selected':''?>>Deposit</option>
              <option value="withdrawal" <?=$type==='withdrawal'?'selected':''?>>Withdrawal</option>
              <option value="investment" <?=$type==='investment'?'selected':''?>>Stake</option>
              <option value="reward" <?=$type==='reward'?'selected':''?>>Reward</option>
            </select>
          </div>
          <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
              <option value="">All Statuses</option>
              <option value="completed" <?=$status==='completed'?'selected':''?>>Completed</option>
              <option value="pending" <?=$status==='pending'?'selected':''?>>Pending</option>
              <option value="failed" <?=$status==='failed'?'selected':''?>>Failed</option>
            </select>
          </div>
        </form>
        <div class="table-responsive mb-5" style="border-radius: 1rem; overflow: hidden; background: #111827cc;">
          <table class="table table-dark table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $tx): ?>
              <tr>
                <td><?=date('M d, Y H:i', strtotime($tx['created_at']))?></td>
                <td>
                  <?php
                    if ($tx['type'] === 'investment') {
                      echo 'Stake';
                      if (!empty($tx['plan_name'])) echo ' - ' . htmlspecialchars($tx['plan_name']);
                    } elseif ($tx['type'] === 'reward') {
                      echo 'Reward';
                    } else {
                      echo ucfirst($tx['type']);
                    }
                  ?>
                </td>
                <td><?=sol_display($tx['amount'])?><?=usdt_placeholder($tx['amount'])?></td>
                <td>
                  <?php if ($tx['status'] === 'completed'): ?>
                    <span class="badge bg-success text-uppercase">Completed</span>
                  <?php elseif ($tx['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-uppercase">Pending</span>
                  <?php else: ?>
                    <span class="badge bg-secondary text-uppercase"><?=ucfirst($tx['status'])?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                    if ($tx['type'] === 'investment') {
                      echo 'Staked in plan';
                      if (!empty($tx['plan_name'])) echo ' ' . htmlspecialchars($tx['plan_name']);
                      // Remove interest display
                      // if (!empty($tx['interest_earned'])) echo ' | Interest: ' . sol_display($tx['interest_earned']);
                    } elseif ($tx['type'] === 'reward') {
                      echo !empty($tx['reward_description']) ? htmlspecialchars($tx['reward_description']) : 'Reward payout';
                    } else {
                      echo htmlspecialchars($tx['description']);
                    }
                  ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php foreach ($orphan_stakes as $stake): ?>
              <tr>
                <td><?=date('M d, Y H:i', strtotime($stake['started_at']))?></td>
                <td>Stake<?=!empty($stake['plan_name']) ? ' - ' . htmlspecialchars($stake['plan_name']) : ''?></td>
                <td><?=sol_display($stake['amount'])?><?=usdt_placeholder($stake['amount'])?></td>
                <td>
                  <?php if ($stake['status'] === 'completed'): ?>
                    <span class="badge bg-success text-uppercase">Completed</span>
                  <?php elseif ($stake['status'] === 'active'): ?>
                    <span class="badge bg-warning text-uppercase">Pending</span>
                  <?php else: ?>
                    <span class="badge bg-secondary text-uppercase"><?=ucfirst($stake['status'])?></span>
                  <?php endif; ?>
                </td>
                <td>
                  Staked in plan <?=!empty($stake['plan_name']) ? htmlspecialchars($stake['plan_name']) : ''?>
                  <!-- Interest display removed -->
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!count($transactions) && !count($orphan_stakes)): ?><tr><td colspan="5" class="text-center text-muted">No transactions found.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
    <footer class="dashboard-footer">
      <img src="/vault-logo-new.png" alt="Vault Logo" height="32" class="mb-2">
      <div class="mb-2">
        <a href="plans.php" class="text-info me-3">Staking Plans</a>
        <a href="roadmap.php" class="text-info">Roadmap</a>
      </div>
      <div>&copy; <?=date('Y')?> Vault. All rights reserved.</div>
    </footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle/overlay (copied from plans.php)
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
</body>
</html> 