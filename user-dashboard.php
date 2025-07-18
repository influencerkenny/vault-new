<?php
session_start();

// Redirect to signin if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}

// Database connection (adjust as needed)
$pdo = new PDO('mysql:host=localhost;dbname=vault', 'root', '');

// Fetch user info
$stmt = $pdo->prepare('SELECT first_name, last_name, email, avatar FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$displayName = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : 'Investor';
if (!$displayName) $displayName = $user['email'] ?? 'Investor';
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/avatars/avatar_6878d13ace8db.jpg';
// Fetch unread notifications/messages count from database
$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$_SESSION['user_id']]);
$unreadCount = (int)$stmt->fetchColumn();

// Fetch portfolio data (replace with real queries)
$availableBalance = 0.00;
$stakedAmount = 0.00;
$totalRewards = 0.00;

// Available Balance
$stmt = $pdo->prepare('SELECT available_balance FROM user_balances WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $availableBalance = (float)$row['available_balance'];
}
// Staked Amount
$stmt = $pdo->prepare("SELECT SUM(amount) AS staked FROM user_stakes WHERE user_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $stakedAmount = (float)$row['staked'] ?: 0.00;
}
// Total Rewards
$stmt = $pdo->prepare('SELECT SUM(amount) AS rewards FROM user_rewards WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $totalRewards = (float)$row['rewards'] ?: 0.00;
}
// Total Portfolio
$totalPortfolio = $availableBalance + $stakedAmount + $totalRewards;
// Portfolio Change & APY (placeholder)
$portfolioChange = 12.5;
$apy = 12.8;
$portfolio = [
  'totalBalance' => $totalPortfolio,
  'stakedAmount' => $stakedAmount,
  'availableBalance' => $availableBalance,
  'totalRewards' => $totalRewards,
  'portfolioChange' => $portfolioChange,
  'apy' => $apy,
];

// Fetch portfolio chart data (last 14 days)
$chartLabels = [];
$chartData = [];
$stmt = $pdo->prepare('SELECT recorded_at, portfolio_value FROM portfolio_history WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 14');
$stmt->execute([$_SESSION['user_id']]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach (array_reverse($history) as $row) {
  $chartLabels[] = date('M j', strtotime($row['recorded_at']));
  $chartData[] = (float)$row['portfolio_value'];
}
// Fetch staking positions
$stmt = $pdo->prepare('SELECT amount, status, plan_id, started_at, ended_at FROM user_stakes WHERE user_id = ? ORDER BY started_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$stakes = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch transaction history
$stmt = $pdo->prepare('SELECT type, amount, status, description, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Handle logout
if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: signin.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Vault</title>
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
      z-index: 100;
      padding: 2rem 1.5rem 1.5rem 1.5rem;
      display: flex;
      flex-direction: column;
      transition: left 0.3s;
    }
    .sidebar .logo {
      margin-bottom: 2rem;
      text-align: center;
    }
    .sidebar .nav-link {
      color: #cbd5e1;
      font-weight: 500;
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
      margin-bottom: 0.25rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      transition: background 0.2s, color 0.2s;
      position: relative;
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover {
      background: linear-gradient(90deg, #2563eb22 0%, #0ea5e922 100%);
      color: #38bdf8;
      box-shadow: 0 2px 8px 0 rgba(59,130,246,0.08);
    }
    .sidebar .logout-btn {
      color: #f87171;
      font-weight: 500;
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
      margin-top: auto;
      background: none;
      border: none;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      transition: background 0.2s, color 0.2s;
    }
    .sidebar .logout-btn:hover {
      background: #7f1d1d22;
      color: #f87171;
    }
    .main-content {
      margin-left: 260px;
      min-height: 100vh;
      background: #0f172a;
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
    }
    .dashboard-header {
      border-bottom: 1px solid #1e293b;
      padding: 1.5rem 2rem 1rem 2rem;
      background: rgba(17,24,39,0.85);
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 10;
    }
    .dashboard-header .logo {
      height: 48px;
    }
    .dashboard-header .back-link {
      color: #38bdf8;
      font-weight: 500;
      text-decoration: none;
      margin-left: 1.5rem;
      transition: color 0.2s;
    }
    .dashboard-header .back-link:hover {
      color: #0ea5e9;
      text-decoration: underline;
    }
    .portfolio-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2.5rem;
    }
    .portfolio-card {
      background: linear-gradient(135deg, #2563eb22 0%, #0ea5e922 100%);
      border: 1px solid #2563eb33;
      border-radius: 1.25rem;
      padding: 2rem 1.5rem 1.5rem 1.5rem;
      box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10);
      color: #e5e7eb;
      position: relative;
      min-height: 160px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: box-shadow 0.2s, border 0.2s, background 0.2s;
      overflow: hidden;
    }
    .portfolio-card .card-title {
      font-size: 1.08rem;
      color: #a1a1aa;
      font-weight: 600;
      margin-bottom: 0.5rem;
      letter-spacing: 0.01em;
    }
    .portfolio-card .card-value {
      font-size: 2.3rem;
      font-weight: 800;
      color: #fff;
      margin-bottom: 0.5rem;
      letter-spacing: 0.01em;
    }
    .portfolio-card .card-icon {
      font-size: 2.6rem;
      margin-bottom: 0.5rem;
      border-radius: 0.75rem;
      padding: 0.5rem;
      box-shadow: 0 2px 8px 0 rgba(59,130,246,0.10);
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .portfolio-card.balance .card-icon { background: linear-gradient(90deg, #2563eb 0%, #38bdf8 100%); color: #fff; }
    .portfolio-card.staked .card-icon { background: linear-gradient(90deg, #22c55e 0%, #38bdf8 100%); color: #fff; }
    .portfolio-card.rewards .card-icon { background: linear-gradient(90deg, #ef4444 0%, #fbbf24 100%); color: #fff; }
    .portfolio-card.total .card-icon { background: linear-gradient(90deg, #fbbf24 0%, #f59e42 100%); color: #fff; }
    .portfolio-card .card-footer {
      font-size: 1rem;
      color: #38d39f;
      font-weight: 500;
    }
    .show-balance-btn {
      background: none;
      border: none;
      color: #a1a1aa;
      margin-left: 0.5rem;
      font-size: 1.2rem;
      cursor: pointer;
      transition: color 0.2s;
    }
    .show-balance-btn:hover {
      color: #fff;
    }
    .dashboard-footer {
      border-top: 1px solid #1e293b;
      padding: 2rem;
      background: rgba(17,24,39,0.85);
      color: #a1a1aa;
      text-align: center;
      margin-top: auto;
    }
    @media (max-width: 991px) {
      .sidebar { left: -260px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
    }
    .sidebar-mobile-overlay {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      background: rgba(0,0,0,0.5);
      z-index: 2000;
      display: none;
    }
    .sidebar-mobile-overlay.active {
      display: block;
    }
    .profile-dropdown-menu {
      border-radius: 1rem;
      box-shadow: 0 8px 32px 0 rgba(31,41,55,0.18);
      min-width: 220px;
      padding-top: 0.5rem;
      padding-bottom: 0.5rem;
    }
    .profile-dropdown-menu .dropdown-item {
      border-radius: 0.5rem;
      transition: background 0.18s, color 0.18s;
    }
    .profile-dropdown-menu .dropdown-item:hover, .profile-dropdown-menu .dropdown-item:focus {
      background: linear-gradient(90deg, #2563eb22 0%, #0ea5e922 100%);
      color: #2563eb;
    }
    .profile-dropdown-menu .dropdown-item.text-danger:hover, .profile-dropdown-menu .dropdown-item.text-danger:focus {
      background: #7f1d1d22;
      color: #f87171;
    }
    .chart-card {
      box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10);
      border-radius: 1.25rem;
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      border: 1.5px solid #2563eb33;
      padding: 1.25rem 1rem 1rem 1rem;
    }
    .chart-card h6 {
      font-size: 1.05rem;
      letter-spacing: 0.01em;
      margin-bottom: 0.75rem;
    }
    #portfolioChart {
      height: 80px !important;
      max-height: 100px;
    }
    .staking-widget, .transactions-widget, .quick-links-widget {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      border: 1.5px solid #2563eb33;
      border-radius: 1.25rem;
      box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10);
      padding: 1.25rem 1rem 1rem 1rem;
      margin-bottom: 1.5rem;
      min-height: 120px;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      overflow: hidden;
    }
    .staking-widget h5, .transactions-widget h5, .quick-links-widget h5 {
      font-size: 1.05rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      color: #38bdf8;
      letter-spacing: 0.01em;
    }
    .staking-widget table, .transactions-widget table {
      font-size: 0.97rem;
    }
    .staking-widget td, .transactions-widget td {
      padding: 0.35rem 0.5rem;
    }
    .quick-links-widget .btn {
      font-size: 0.97rem;
      padding: 0.5rem 1rem;
      border-radius: 0.75rem;
      margin-bottom: 0.5rem;
    }
    @media (max-width: 575px) {
      .staking-widget, .transactions-widget, .quick-links-widget {
        padding: 0.75rem 0.5rem 0.5rem 0.5rem;
        min-height: 80px;
      }
      .staking-widget h5, .transactions-widget h5, .quick-links-widget h5 {
        font-size: 0.98rem;
        margin-bottom: 0.5rem;
      }
      .quick-links-widget .btn {
        font-size: 0.93rem;
        padding: 0.4rem 0.7rem;
      }
    }
  </style>
</head>
<body>
  <!-- Mobile Sidebar Overlay -->
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <!-- Sidebar -->
  <div id="sidebar" class="sidebar d-none d-lg-flex flex-column">
    <div class="logo mb-4">
      <img src="public/vault-logo-new.png" alt="Vault Logo" height="48">
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
        <img src="public/vault-logo-new.png" alt="Vault Logo" class="logo me-3">
        <a href="/" class="back-link"><i class="bi bi-arrow-left"></i> Back to Home</a>
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
            <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="notifications.php"><span><i class="bi bi-bell me-2"></i>Notifications</span><?php if($unreadCount>0): ?><span class="badge bg-danger ms-2"><?=$unreadCount?></span><?php endif; ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="support.php"><i class="bi bi-question-circle me-2"></i>Support</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </header>
    <main class="flex-grow-1 p-4">
      <h2 class="mb-4">Welcome, <span class="text-info"><?=$displayName?></span>!</h2>
      <div class="d-flex align-items-center mb-3">
        <span class="me-2">Show Balances</span>
        <button id="toggleBalance" class="btn btn-sm btn-outline-info" type="button" aria-label="Toggle balance visibility">
          <i id="balanceIcon" class="bi bi-eye"></i>
        </button>
      </div>
      <!-- Portfolio cards row -->
      <div class="portfolio-cards mb-4">
        <div class="portfolio-card balance">
          <div class="card-title">Total Portfolio</div>
          <div class="card-value balance-value">$<?=number_format($portfolio['totalBalance'],2)?></div>
          <div class="card-footer">+<?=number_format($portfolio['portfolioChange'],1)?>% this month</div>
        </div>
        <div class="portfolio-card staked">
          <div class="card-title">Staked Amount</div>
          <div class="card-value balance-value">$<?=number_format($portfolio['stakedAmount'],2)?></div>
          <div class="card-footer">APY: <?=number_format($portfolio['apy'],1)?>%</div>
        </div>
        <div class="portfolio-card rewards">
          <div class="card-title">Total Rewards</div>
          <div class="card-value balance-value">$<?=number_format($portfolio['totalRewards'],2)?></div>
          <div class="card-footer">Daily earnings</div>
        </div>
        <div class="portfolio-card total">
          <div class="card-title">Available Balance</div>
          <div class="card-value balance-value">$<?=number_format($portfolio['availableBalance'],2)?></div>
          <div class="card-footer">Ready to stake</div>
        </div>
      </div>
      <!-- Chart and quick links row -->
      <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
          <div class="card p-3 glassy-card h-100 chart-card">
            <h6 class="mb-3 text-info fw-bold">Portfolio Value Over Time</h6>
            <canvas id="portfolioChart" height="80"></canvas>
          </div>
        </div>
        <div class="col-12 col-lg-4">
          <div class="quick-links-widget h-100 d-flex flex-column align-items-center justify-content-center">
            <h5>Quick Links</h5>
            <button type="button" class="btn btn-outline-info w-100 mb-2" data-bs-toggle="modal" data-bs-target="#depositModal"><i class="bi bi-download me-2"></i>Deposit</button>
            <button type="button" class="btn btn-outline-info w-100 mb-2" data-bs-toggle="modal" data-bs-target="#withdrawModal"><i class="bi bi-upload me-2"></i>Withdraw</button>
            <button type="button" class="btn btn-outline-info w-100" data-bs-toggle="modal" data-bs-target="#plansModal"><i class="bi bi-layers me-2"></i>View Plans</button>
          </div>
        </div>
      </div>
      <!-- Staking positions and transaction history row -->
      <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
          <div class="staking-widget">
            <h5>Staking Positions</h5>
            <div class="table-responsive">
              <table class="table table-dark table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Plan</th>
                    <th>Started</th>
                    <th>Ended</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($stakes)): foreach ($stakes as $s): ?>
                  <tr>
                    <td>$<?=number_format($s['amount'],2)?></td>
                    <td><span class="badge bg-<?=($s['status']==='active'?'success':($s['status']==='completed'?'secondary':'danger'))?> text-uppercase"><?=$s['status']?></span></td>
                    <td><?=$s['plan_id']??'-'?></td>
                    <td><?=date('M j, Y', strtotime($s['started_at']))?></td>
                    <td><?=$s['ended_at'] ? date('M j, Y', strtotime($s['ended_at'])) : '-'?></td>
                  </tr>
                  <?php endforeach; else: ?>
                  <tr><td colspan="5" class="text-center text-muted">No staking positions</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-6">
          <div class="transactions-widget">
            <h5>Transaction History</h5>
            <div class="table-responsive" style="max-height:220px;overflow:auto;">
              <table class="table table-dark table-sm align-middle mb-0">
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
                    <td><?=ucfirst($tx['type'])?></td>
                    <td>$<?=number_format($tx['amount'],2)?></td>
                    <td>
                      <?php if ($tx['status'] === 'completed'): ?>
                        <span class="badge bg-success text-uppercase">Completed</span>
                      <?php elseif ($tx['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-uppercase">Pending</span>
                      <?php else: ?>
                        <span class="badge bg-secondary text-uppercase"><?=ucfirst($tx['status'])?></span>
                      <?php endif; ?>
                    </td>
                    <td><?=htmlspecialchars($tx['description'])?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <!-- Deposit Modal -->
      <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content bg-dark text-white rounded-4">
            <div class="modal-header border-0">
              <h5 class="modal-title" id="depositModalLabel">Deposit Funds</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="depositForm">
              <div class="modal-body">
                <div class="mb-3">
                  <label for="depositAmount" class="form-label">Amount</label>
                  <input type="number" min="1" step="0.01" class="form-control" id="depositAmount" name="amount" placeholder="Enter amount" required>
                </div>
                <div class="mb-3">
                  <label for="depositWallet" class="form-label">Crypto Wallet</label>
                  <select class="form-select" id="depositWallet" name="wallet" required>
                    <option value="">Select Wallet</option>
                    <option value="Bitcoin">Bitcoin</option>
                    <option value="Ethereum">Ethereum</option>
                    <option value="USDT">USDT</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="depositAddress" class="form-label">Wallet Address</label>
                  <input type="text" class="form-control" id="depositAddress" name="address" placeholder="Enter your wallet address" required>
                </div>
                <div class="mb-3">
                  <label for="depositNotes" class="form-label">Notes (optional)</label>
                  <textarea class="form-control" id="depositNotes" name="notes" rows="2"></textarea>
                </div>
                <div id="depositSuccess" class="alert alert-success d-none">Deposit request submitted!</div>
                <div id="depositError" class="alert alert-danger d-none"></div>
              </div>
              <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Deposit</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!-- Withdraw Modal -->
      <div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content bg-dark text-white rounded-4">
            <div class="modal-header border-0">
              <h5 class="modal-title" id="withdrawModalLabel">Withdraw Funds</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="withdrawForm">
              <div class="modal-body">
                <div class="mb-3">
                  <label for="withdrawAmount" class="form-label">Amount</label>
                  <input type="number" min="1" step="0.01" class="form-control" id="withdrawAmount" name="amount" placeholder="Enter amount" required>
                </div>
                <div class="mb-3">
                  <label for="withdrawWallet" class="form-label">Crypto Wallet</label>
                  <select class="form-select" id="withdrawWallet" name="wallet" required>
                    <option value="">Select Wallet</option>
                    <option value="Bitcoin">Bitcoin</option>
                    <option value="Ethereum">Ethereum</option>
                    <option value="USDT">USDT</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="withdrawAddress" class="form-label">Destination Address</label>
                  <input type="text" class="form-control" id="withdrawAddress" name="address" placeholder="Enter destination address" required>
                </div>
                <div class="mb-3">
                  <label for="withdrawNotes" class="form-label">Notes (optional)</label>
                  <textarea class="form-control" id="withdrawNotes" name="notes" rows="2"></textarea>
                </div>
                <div id="withdrawSuccess" class="alert alert-success d-none">Withdrawal request submitted!</div>
                <div id="withdrawError" class="alert alert-danger d-none"></div>
              </div>
              <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info">Withdraw</button>
              </div>
            </form>
          </div>
        </div>
      </div>
            
    </main>
    <footer class="dashboard-footer">
      <img src="public/vault-logo-new.png" alt="Vault Logo" height="32" class="mb-2">
      <div class="mb-2">
        <a href="plans.php" class="text-info me-3">Staking Plans</a>
        <a href="roadmap.php" class="text-info">Roadmap</a>
      </div>
      <div>&copy; <?=date('Y')?> Vault. All rights reserved.</div>
    </footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
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
    // Close sidebar on nav link click (mobile)
    document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
      link.addEventListener('click', function() {
        if (window.innerWidth < 992) closeSidebar();
      });
    });
    // Responsive sidebar
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

    // Show/hide balance toggle
    let showBalance = true;
    const toggleBtn = document.getElementById('toggleBalance');
    const balanceIcon = document.getElementById('balanceIcon');
    const balanceValues = document.querySelectorAll('.balance-value');
    function updateBalanceDisplay() {
      balanceValues.forEach(function(el) {
        if (showBalance) {
          el.textContent = el.dataset.value;
        } else {
          el.textContent = '••••••';
        }
      });
      balanceIcon.className = showBalance ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
    // Store original values in data-value
    balanceValues.forEach(function(el) {
      el.dataset.value = el.textContent;
    });
    if (toggleBtn) {
      toggleBtn.addEventListener('click', function() {
        showBalance = !showBalance;
        updateBalanceDisplay();
      });
    }

    // Portfolio Chart
    const chartCanvas = document.getElementById('portfolioChart');
    if (chartCanvas) {
      const ctx = chartCanvas.getContext('2d');
      const baseFont = {
        family: 'Inter, sans-serif',
        size: 14,
        weight: '500',
        lineHeight: 1.4
      };
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: <?=json_encode($chartLabels)?>,
          datasets: [{
            label: 'Portfolio Value',
            data: <?=json_encode($chartData)?>,
            borderColor: '#38bdf8',
            backgroundColor: (ctx) => {
              const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
              gradient.addColorStop(0, 'rgba(56,189,248,0.25)');
              gradient.addColorStop(1, 'rgba(56,189,248,0.03)');
              return gradient;
            },
            tension: 0.45,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#38bdf8',
            borderWidth: 3,
            pointHoverRadius: 6,
            pointBorderWidth: 2,
            pointBorderColor: '#fff',
            shadowOffsetX: 0,
            shadowOffsetY: 2,
            shadowBlur: 8,
            shadowColor: 'rgba(56,189,248,0.15)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#1e293b',
              titleColor: '#38bdf8',
              bodyColor: '#e5e7eb',
              borderColor: '#38bdf8',
              borderWidth: 1,
              padding: 12,
              titleFont: baseFont,
              bodyFont: baseFont
            }
          },
          scales: {
            x: {
              grid: { color: 'rgba(56,189,248,0.07)', borderColor: '#2563eb', drawBorder: false },
              ticks: { color: '#a1a1aa', font: baseFont }
            },
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(56,189,248,0.07)', borderColor: '#2563eb', drawBorder: false },
              ticks: { color: '#a1a1aa', font: baseFont }
            }
          }
        }
      });
    }

    // Deposit Modal AJAX
    const depositForm = document.getElementById('depositForm');
    if (depositForm) {
      depositForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = depositForm.querySelector('button[type=submit]');
        btn.disabled = true;
        document.getElementById('depositSuccess').classList.add('d-none');
        document.getElementById('depositError').classList.add('d-none');
        const formData = new FormData(depositForm);
        fetch('api/deposit.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            document.getElementById('depositSuccess').classList.remove('d-none');
            setTimeout(() => {
              document.getElementById('depositSuccess').classList.add('d-none');
              const modal = bootstrap.Modal.getInstance(document.getElementById('depositModal'));
              modal.hide();
              depositForm.reset();
            }, 1500);
          } else {
            document.getElementById('depositError').textContent = data.error || 'Deposit failed.';
            document.getElementById('depositError').classList.remove('d-none');
          }
        })
        .catch(() => {
          document.getElementById('depositError').textContent = 'Deposit failed.';
          document.getElementById('depositError').classList.remove('d-none');
        })
        .finally(() => { btn.disabled = false; });
      });
    }
    // Withdraw Modal AJAX
    const withdrawForm = document.getElementById('withdrawForm');
    if (withdrawForm) {
      withdrawForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = withdrawForm.querySelector('button[type=submit]');
        btn.disabled = true;
        document.getElementById('withdrawSuccess').classList.add('d-none');
        document.getElementById('withdrawError').classList.add('d-none');
        const formData = new FormData(withdrawForm);
        fetch('api/withdraw.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            document.getElementById('withdrawSuccess').classList.remove('d-none');
            setTimeout(() => {
              document.getElementById('withdrawSuccess').classList.add('d-none');
              const modal = bootstrap.Modal.getInstance(document.getElementById('withdrawModal'));
              modal.hide();
              withdrawForm.reset();
            }, 1500);
          } else {
            document.getElementById('withdrawError').textContent = data.error || 'Withdrawal failed.';
            document.getElementById('withdrawError').classList.remove('d-none');
          }
        })
        .catch(() => {
          document.getElementById('withdrawError').textContent = 'Withdrawal failed.';
          document.getElementById('withdrawError').classList.remove('d-none');
        })
        .finally(() => { btn.disabled = false; });
      });
    }
  </script>
</body>
</html> 