<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit;
}
$showCongrats = false;
if (isset($_SESSION['admin_just_logged_in']) && $_SESSION['admin_just_logged_in']) {
  $showCongrats = true;
  unset($_SESSION['admin_just_logged_in']);
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
// Total users
$totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
// Total completed deposits
$totalDeposits = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type='deposit' AND status='completed'")->fetchColumn();
// Total completed withdrawals
$totalWithdrawals = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type='withdrawal' AND status='completed'")->fetchColumn();
// Pending transactions
$pendingTx = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='pending'")->fetchColumn();

// Pending deposits count for notification
$pendingDeposits = $pdo->query("SELECT COUNT(*) FROM transactions WHERE type='deposit' AND status='pending'")->fetchColumn();

// Total staked by users
$totalStaked = $pdo->query("SELECT SUM(amount) FROM user_stakes WHERE status='active'")->fetchColumn();

// Fetch deposits over last 14 days
$depositLabels = [];
$depositData = [];
$stmt = $pdo->query("SELECT DATE(created_at) as day, SUM(amount) as total FROM transactions WHERE type='deposit' AND status='completed' GROUP BY day ORDER BY day DESC LIMIT 14");
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach (array_reverse($deposits) as $row) {
  $depositLabels[] = date('M j', strtotime($row['day']));
  $depositData[] = (float)$row['total'];
}
// Fetch withdrawals over last 14 days
$withdrawLabels = [];
$withdrawData = [];
$stmt = $pdo->query("SELECT DATE(created_at) as day, SUM(amount) as total FROM transactions WHERE type='withdrawal' AND status='completed' GROUP BY day ORDER BY day DESC LIMIT 14");
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach (array_reverse($withdrawals) as $row) {
  $withdrawLabels[] = date('M j', strtotime($row['day']));
  $withdrawData[] = (float)$row['total'];
}
// Fetch transaction type counts (pie/bar)
$txTypes = [];
$txCounts = [];
$stmt = $pdo->query("SELECT type, COUNT(*) as count FROM transactions GROUP BY type");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
  $txTypes[] = ucfirst($row['type']);
  $txCounts[] = (int)$row['count'];
}

// Load email config if exists
$email_config_file = __DIR__ . '/email_config.php';
$email_config = [
  'host' => '',
  'port' => '',
  'username' => '',
  'password' => '',
  'from' => '',
  'encryption' => ''
];
if (file_exists($email_config_file)) {
  $email_config = include $email_config_file;
}
$email_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email_config'])) {
  $email_config = [
    'host' => trim($_POST['host'] ?? ''),
    'port' => trim($_POST['port'] ?? ''),
    'username' => trim($_POST['username'] ?? ''),
    'password' => trim($_POST['password'] ?? ''),
    'from' => trim($_POST['from'] ?? ''),
    'encryption' => trim($_POST['encryption'] ?? '')
  ];
  file_put_contents($email_config_file, '<?php return ' . var_export($email_config, true) . ';');
  $email_success = true;
}

function sol_display($amount) {
  return '<span class="sol-value">' . number_format($amount, 2) . ' SOL</span>';
}
function usdt_placeholder($amount) {
  // Placeholder span for JS to fill in
  return '<span class="usdt-convert" data-sol="' . htmlspecialchars($amount, ENT_QUOTES) . '">≈ $--.-- USDT</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Vault</title>
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
    .sidebar .nav-link .badge {
      font-size: 0.65rem !important;
      padding: 0.25rem 0.4rem;
      border-radius: 0.5rem;
      font-weight: 600;
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }
    .sidebar .nav-link:hover .badge {
      animation: none;
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
    .dashboard-header .profile-dropdown {
      position: relative;
      display: inline-block;
    }
    .dashboard-header .profile-btn {
      background: none;
      border: none;
      color: #e5e7eb;
      font-size: 2rem;
      border-radius: 50%;
      width: 48px;
      height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s;
    }
    .dashboard-header .profile-btn:hover, .dashboard-header .profile-btn:focus { background: #1e293b; color: #38bdf8; }
    .dashboard-header .profile-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 120%;
      background: #1e293b;
      border-radius: 0.75rem;
      box-shadow: 0 8px 32px 0 rgba(31,41,55,0.18);
      min-width: 180px;
      z-index: 2000;
      padding: 0.5rem 0;
    }
    .dashboard-header .profile-dropdown.open .profile-menu { display: block; }
    .dashboard-header .profile-menu a {
      display: block;
      color: #e5e7eb;
      padding: 0.75rem 1.25rem;
      text-decoration: none;
      font-size: 1rem;
      transition: background 0.2s, color 0.2s;
    }
    .dashboard-header .profile-menu a:hover, .dashboard-header .profile-menu a:focus { background: #2563eb; color: #fff; }
    .dashboard-widgets {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 1.5rem;
      margin: 2.5rem 0 2rem 0;
      padding: 0 2rem;
    }
    .dashboard-widget {
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
    .dashboard-widget .widget-title {
      font-size: 1.08rem;
      color: #a1a1aa;
      font-weight: 600;
      margin-bottom: 0.5rem;
      letter-spacing: 0.01em;
    }
    .dashboard-widget .widget-value {
      font-size: 2.3rem;
      font-weight: 800;
      color: #fff;
      margin-bottom: 0.5rem;
      letter-spacing: 0.01em;
    }
    .dashboard-widget .widget-icon {
      font-size: 2.6rem;
      margin-bottom: 0.5rem;
      border-radius: 0.75rem;
      padding: 0.5rem;
      box-shadow: 0 2px 8px 0 rgba(59,130,246,0.10);
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .dashboard-widget.users .widget-icon { background: linear-gradient(90deg, #2563eb 0%, #38bdf8 100%); color: #fff; }
    .dashboard-widget.deposits .widget-icon { background: linear-gradient(90deg, #22c55e 0%, #38bdf8 100%); color: #fff; }
    .dashboard-widget.withdrawals .widget-icon { background: linear-gradient(90deg, #ef4444 0%, #fbbf24 100%); color: #fff; }
    .dashboard-widget.pending .widget-icon { background: linear-gradient(90deg, #fbbf24 0%, #f59e42 100%); color: #fff; }
    .dashboard-widget.staked .widget-icon { background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%); color: #fff; }
    .footer {
      background: rgba(17,24,39,0.95);
      color: #64748b;
      text-align: center;
      padding: 1rem 0 0.5rem 0;
      font-size: 0.97rem;
      border-top: 1px solid #2563eb33;
      margin-top: auto;
    }
    @media (max-width: 991px) {
      .sidebar { left: -260px; min-width: 220px; max-width: 220px; }
      .sidebar.open { left: 0; }
      .main-content { margin-left: 0; }
      .dashboard-widgets { padding: 0 0.5rem; }
    }
    @media (max-width: 575px) {
      .dashboard-header { padding: 1rem 0.5rem; }
      .dashboard-widgets { padding: 0 0.25rem; }
      .dashboard-widget {
        padding: 1rem 0.75rem 0.75rem 0.75rem;
        min-height: 100px;
      }
      .dashboard-widget .widget-title {
        font-size: 0.98rem;
      }
      .dashboard-widget .widget-value {
        font-size: 1.3rem;
      }
      .dashboard-widget .widget-icon {
        font-size: 1.5rem;
        padding: 0.35rem;
      }
    }
    .chart-card {
      box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10);
      border-radius: 1.25rem;
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      border: 1.5px solid #2563eb33;
    }
    .chart-card h6 {
      font-size: 1.1rem;
      letter-spacing: 0.01em;
    }
    .usdt-convert {
      display: block;
      font-size: 0.6em;
      color: #94a3b8;
      margin-top: 0.1em;
    }
    td .usdt-convert, td .sol-value {
      font-size: 0.6em;
    }
    .sol-value {
      font-size: 0.65em;
      color: #38bdf8;
      font-weight: 600;
    }
  </style>
</head>
<body>
<div class="sidebar" id="sidebar" aria-label="Admin sidebar navigation">
  <div class="logo mb-4">
    <img src="/vault-logo-new.png" alt="Vault Logo" style="height:40px;">
    <div style="font-weight:700;font-size:1.3rem;color:#38bdf8;">Vault Admin</div>
  </div>
  <a href="dashboard.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='dashboard.php'?' active':''?>"><i class="bi bi-house"></i> Dashboard</a>
  <a href="users.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='users.php'?' active':''?>"><i class="bi bi-people"></i> Users</a>
  <a href="plans.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='plans.php'?' active':''?>"><i class="bi bi-layers"></i> Plans</a>
  <a href="deposits.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='deposits.php'?' active':''?> position-relative">
    <i class="bi bi-download"></i> Deposits
    <?php if($pendingDeposits > 0): ?>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.7rem; margin-left:0.5rem;">
        <?=$pendingDeposits > 9 ? '9+' : $pendingDeposits?>
      </span>
    <?php endif; ?>
  </a>
  <a href="withdrawals.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='withdrawals.php'?' active':''?>"><i class="bi bi-upload"></i> Withdrawals</a>
  <a href="transactions.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='transactions.php'?' active':''?>"><i class="bi bi-list"></i> Transactions</a>
  <a href="referrals.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='referrals.php'?' active':''?>"><i class="bi bi-people"></i> Referrals</a>
  <a href="payment-gateway.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='payment-gateway.php'?' active':''?>"><i class="bi bi-credit-card"></i> Payment Gateway</a>
  <a href="settings.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='settings.php'?' active':''?>"><i class="bi bi-gear"></i> Settings</a>
  <a href="support.php" class="nav-link<?=basename($_SERVER['PHP_SELF'])==='support.php'?' active':''?>"><i class="bi bi-question-circle"></i> Support & Tickets</a>
  <form method="get" action="dashboard.php"><button type="submit" name="logout" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</button></form>
</div>
<div class="main-content">
  <div class="dashboard-header">
    <div class="logo d-flex align-items-center">
      <img src="/vault-logo-new.png" alt="Vault Logo" class="me-2" style="height:36px;">
      <span style="font-weight:700;font-size:1.2rem;color:#38bdf8;">Vault Admin</span>
    </div>
    <div class="profile-dropdown" id="profileDropdown">
      <button class="profile-btn" id="profileBtn" aria-haspopup="true" aria-expanded="false" aria-label="Admin profile menu">
        <i class="bi bi-person-circle"></i>
      </button>
      <div class="profile-menu" id="profileMenu" role="menu">
        <a href="#" tabindex="0">Profile</a>
        <a href="#" tabindex="0">Settings</a>
        <a href="logout.php" tabindex="0" class="text-danger">Logout</a>
      </div>
    </div>
  </div>
  <div class="dashboard-widgets">
    <div class="dashboard-widget users">
      <div class="widget-icon"><i class="bi bi-people"></i></div>
      <div class="widget-title">Total Users</div>
      <div class="widget-value"><?=$totalUsers?></div>
    </div>
    <div class="dashboard-widget deposits">
      <div class="widget-icon"><i class="bi bi-download"></i></div>
      <div class="widget-title">Total Deposits</div>
      <div class="widget-value"><?=sol_display($totalDeposits)?><?=usdt_placeholder($totalDeposits)?></div>
    </div>
    <div class="dashboard-widget withdrawals">
      <div class="widget-icon"><i class="bi bi-upload"></i></div>
      <div class="widget-title">Total Withdrawals</div>
      <div class="widget-value"><?=sol_display($totalWithdrawals)?><?=usdt_placeholder($totalWithdrawals)?></div>
    </div>
    <div class="dashboard-widget staked">
      <div class="widget-icon"><i class="bi bi-piggy-bank"></i></div>
      <div class="widget-title">Total Staked</div>
      <div class="widget-value"><?=sol_display($totalStaked)?><?=usdt_placeholder($totalStaked)?></div>
    </div>
    <div class="dashboard-widget pending">
      <div class="widget-icon"><i class="bi bi-clock-history"></i></div>
      <div class="widget-title">Pending Transactions</div>
      <div class="widget-value"><?=$pendingTx?></div>
    </div>
  </div>
  <div class="dashboard-charts row g-4 px-3 pb-4">
    <div class="col-12 col-lg-6">
      <div class="card p-3 glassy-card h-100 chart-card">
        <h6 class="mb-3 text-info fw-bold">Deposits Over Time</h6>
        <canvas id="depositsChart" height="120"></canvas>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card p-3 glassy-card h-100 chart-card">
        <h6 class="mb-3 text-warning fw-bold">Withdrawals Over Time</h6>
        <canvas id="withdrawalsChart" height="120"></canvas>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card p-3 glassy-card h-100 chart-card">
        <h6 class="mb-3 text-success fw-bold">Transactions by Type</h6>
        <canvas id="transactionsChart" height="120"></canvas>
      </div>
    </div>
  </div>
  <footer class="footer">
    &copy; <?=date('Y')?> Vault Platform. All rights reserved.
  </footer>
</div>
<script>
// Profile dropdown
const profileBtn = document.getElementById('profileBtn');
const profileDropdown = document.getElementById('profileDropdown');
const profileMenu = document.getElementById('profileMenu');
if (profileBtn) {
  profileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    profileDropdown.classList.toggle('open');
    profileBtn.setAttribute('aria-expanded', profileDropdown.classList.contains('open'));
  });
  document.addEventListener('click', function(e) {
    if (!profileDropdown.contains(e.target)) {
      profileDropdown.classList.remove('open');
      profileBtn.setAttribute('aria-expanded', 'false');
    }
  });
}

// Auto-refresh pending deposits notification
function updatePendingDepositsCount() {
  fetch('get_pending_deposits_count.php')
    .then(response => response.json())
    .then(data => {
      const depositsLink = document.querySelector('a[href="deposits.php"]');
      const existingBadge = depositsLink.querySelector('.badge');
      
      if (data.pending_count > 0) {
        const badgeText = data.pending_count > 9 ? '9+' : data.pending_count;
        
        if (existingBadge) {
          existingBadge.textContent = badgeText;
        } else {
          const newBadge = document.createElement('span');
          newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
          newBadge.style.fontSize = '0.7rem';
          newBadge.style.marginLeft = '0.5rem';
          newBadge.textContent = badgeText;
          depositsLink.appendChild(newBadge);
        }
      } else if (existingBadge) {
        existingBadge.remove();
      }
    })
    .catch(error => console.error('Error updating pending deposits count:', error));
}

// Update count every 30 seconds
setInterval(updatePendingDepositsCount, 30000);
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const depositLabels = <?=json_encode($depositLabels)?>;
const depositData = <?=json_encode($depositData)?>;
const withdrawLabels = <?=json_encode($withdrawLabels)?>;
const withdrawData = <?=json_encode($withdrawData)?>;
const txTypes = <?=json_encode($txTypes)?>;
const txCounts = <?=json_encode($txCounts)?>;
// Professional Chart.js options
const baseFont = {
  family: 'Inter, sans-serif',
  size: 14,
  weight: '500',
  lineHeight: 1.4
};
// Deposits chart
new Chart(document.getElementById('depositsChart'), {
  type: 'line',
  data: {
    labels: depositLabels,
    datasets: [{
      label: 'Deposits',
      data: depositData,
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
// Withdrawals chart
new Chart(document.getElementById('withdrawalsChart'), {
  type: 'line',
  data: {
    labels: withdrawLabels,
    datasets: [{
      label: 'Withdrawals',
      data: withdrawData,
      borderColor: '#fbbf24',
      backgroundColor: (ctx) => {
        const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, 'rgba(251,191,36,0.22)');
        gradient.addColorStop(1, 'rgba(251,191,36,0.03)');
        return gradient;
      },
      tension: 0.45,
      fill: true,
      pointRadius: 4,
      pointBackgroundColor: '#fbbf24',
      borderWidth: 3,
      pointHoverRadius: 6,
      pointBorderWidth: 2,
      pointBorderColor: '#fff',
      shadowOffsetX: 0,
      shadowOffsetY: 2,
      shadowBlur: 8,
      shadowColor: 'rgba(251,191,36,0.15)'
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1e293b',
        titleColor: '#fbbf24',
        bodyColor: '#e5e7eb',
        borderColor: '#fbbf24',
        borderWidth: 1,
        padding: 12,
        titleFont: baseFont,
        bodyFont: baseFont
      }
    },
    scales: {
      x: {
        grid: { color: 'rgba(251,191,36,0.07)', borderColor: '#fbbf24', drawBorder: false },
        ticks: { color: '#a1a1aa', font: baseFont }
      },
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(251,191,36,0.07)', borderColor: '#fbbf24', drawBorder: false },
        ticks: { color: '#a1a1aa', font: baseFont }
      }
    }
  }
});
// Transactions by type chart
new Chart(document.getElementById('transactionsChart'), {
  type: 'doughnut',
  data: {
    labels: txTypes,
    datasets: [{
      data: txCounts,
      backgroundColor: ['#38bdf8','#fbbf24','#22c55e','#ef4444','#a78bfa','#f472b6','#f59e42'],
      borderWidth: 2,
      borderColor: '#0f172a',
      hoverOffset: 8
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        position: 'bottom',
        labels: { color: '#a1a1aa', font: baseFont, boxWidth: 18, padding: 18 }
      },
      tooltip: {
        backgroundColor: '#1e293b',
        titleColor: '#22c55e',
        bodyColor: '#e5e7eb',
        borderColor: '#22c55e',
        borderWidth: 1,
        padding: 12,
        titleFont: baseFont,
        bodyFont: baseFont
      }
    },
    cutout: '68%',
    borderRadius: 12,
    animation: { animateRotate: true, animateScale: true }
  }
});

// Fetch SOL price from CoinGecko and update all .usdt-convert fields
function updateSolToUsdt() {
  fetch('https://api.coingecko.com/api/v3/simple/price?ids=solana&vs_currencies=usdt')
    .then(res => res.json())
    .then(data => {
      const rate = data.solana.usdt;
      document.querySelectorAll('.usdt-convert').forEach(function(span) {
        const sol = parseFloat(span.getAttribute('data-sol'));
        if (!isNaN(sol)) {
          const usdt = sol * rate;
          span.textContent = `≈ $${usdt.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} USDT`;
        }
      });
    });
}
updateSolToUsdt();
setInterval(updateSolToUsdt, 300000);
</script>
</body>
</html> 