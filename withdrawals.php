<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
require_once 'api/settings_helper.php';
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
$success = $error = '';
// Fetch balances
$stmt = $pdo->prepare("SELECT available_balance FROM user_balances WHERE user_id = ?");
$stmt->execute([$user_id]);
$availableBalance = (float)($stmt->fetchColumn() ?: 0);
$stmt = $pdo->prepare("SELECT withdrawable_balance FROM user_balances WHERE user_id = ?");
$stmt->execute([$user_id]);
$withdrawableBalance = (float)($stmt->fetchColumn() ?: 0);
// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_amount'])) {
  $amount = (float)$_POST['withdraw_amount'];
  $wallet = trim($_POST['wallet_address'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  if ($amount <= 0 || $amount > $withdrawableBalance) {
    $error = 'Invalid withdrawal amount.';
  } elseif (empty($wallet)) {
    $error = 'Wallet address is required.';
  } else {
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, description, created_at) VALUES (?, 'withdrawal', ?, 'pending', ?, NOW())");
    $desc = 'Withdrawal to: ' . $wallet . ($notes ? ' | Notes: ' . $notes : '');
    if ($stmt->execute([$user_id, $amount, $desc])) {
      $stmt = $pdo->prepare("UPDATE user_balances SET available_balance = available_balance - ? WHERE user_id = ?");
      $stmt->execute([$amount, $user_id]);
      $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)')->execute([
        $user_id,
        'Withdrawal Submitted',
        'Your withdrawal request of ' . $amount . ' SOL is pending admin approval.'
      ]);
      header("Location: withdrawals.php?success=1");
      exit;
    } else {
      $error = 'Failed to submit withdrawal.';
    }
  }
}
if (isset($_GET['success']) && $_GET['success'] == '1') {
  $success = 'Withdrawal request submitted!';
}
$stmt = $pdo->prepare("SELECT amount, status, description, created_at FROM transactions WHERE user_id = ? AND type = 'withdrawal' ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare('SELECT first_name, last_name, email, avatar, username FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
$displayName = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : 'Investor';
$email = $user ? $user['email'] : '';
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
  <title>User Withdrawals | Vault</title>
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
    .dashboard-content-wrapper { max-width: 900px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.93rem; }
    .table-responsive { overflow-x: auto; }
    .table { min-width: 600px; font-size: 0.92rem; }
    .table th, .table td { padding: 0.35rem 0.5rem; }
    @media (max-width: 991px) {
      .sidebar { left: -260px; }
      .sidebar.open { left: 0; }
      .main-content { margin-left: 0; }
      .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; font-size: 0.91rem; }
      .sidebar-close-btn { display: block !important; }
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
    #withdrawForm .form-label { color: #fff !important; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar" aria-label="Sidebar navigation">
    <button type="button" class="sidebar-close-btn" aria-label="Close sidebar" onclick="closeSidebar()" style="position:absolute;top:14px;right:14px;display:none;font-size:2rem;background:none;border:none;color:#fff;z-index:2100;line-height:1;cursor:pointer;">&times;</button>
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
      ['href' => 'account-settings.php', 'label' => 'Settings', 'icon' => 'bi-gear'],
      ['href' => 'profile.php', 'label' => 'Profile', 'icon' => 'bi-person'],
      ['href' => 'support.php', 'label' => 'Support', 'icon' => 'bi-question-circle'],
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
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'user/header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="dashboard-content-wrapper mx-auto">
        <h2 class="mb-4 text-info fw-bold">Withdraw Funds</h2>
        <div class="row mb-4">
          <div class="col-md-6 mb-2">
            <div class="p-3 bg-dark rounded-3 border border-info">
              <div class="text-muted">Available Balance</div>
              <div class="fw-bold" style="font-size:1.2rem;color:#fff !important;">
                <?=sol_display($availableBalance)?></div>
            </div>
          </div>
          <div class="col-md-6 mb-2">
            <div class="p-3 bg-dark rounded-3 border border-success">
              <div class="text-muted">Withdrawable Balance <span class="text-warning small">(You can only withdraw from this)</span></div>
              <div class="fw-bold" style="font-size:1.2rem;color:#fff !important;">
                <?=sol_display($withdrawableBalance)?></div>
            </div>
          </div>
        </div>
        <?php if ($success): ?><div class="alert alert-success" id="withdrawSuccessAlert"><?=$success?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
        <div class="mb-4">
          <button class="btn btn-primary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#withdrawFormCollapse" aria-expanded="false" aria-controls="withdrawFormCollapse">
            <i class="bi bi-plus-circle me-1"></i> New Withdrawal
          </button>
          <div class="collapse" id="withdrawFormCollapse">
            <div class="card shadow-lg border-0" style="background:#181f2a;border-radius:1.25rem;max-width:700px;margin:0 auto 2.5rem auto;box-shadow:0 4px 32px #0003;">
              <div class="card-body p-4">
        <form id="withdrawForm" method="post" autocomplete="off">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="withdraw_amount" class="form-label">Amount</label>
              <input type="number" min="1" max="<?=number_format($withdrawableBalance,2,'.','')?>" step="0.01" class="form-control mb-2" id="withdraw_amount" name="withdraw_amount" placeholder="Enter amount" required>
              <div class="form-text">Withdrawable: <?=number_format($withdrawableBalance,2)?> SOL</div>
            </div>
            <div class="col-md-6">
              <label for="wallet_address" class="form-label">Wallet Address</label>
              <input type="text" class="form-control mb-2" id="wallet_address" name="wallet_address" placeholder="Enter your wallet address" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="notes" class="form-label">Notes (optional)</label>
            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
          </div>
          <div class="row g-3 mb-4">
            <div class="col-12">
              <button type="submit" class="btn btn-info w-100">Submit Withdrawal</button>
            </div>
          </div>
        </form>
              </div>
            </div>
          </div>
        </div>
        <h4 class="mt-5 mb-3 text-info fw-bold">Withdrawal History</h4>
        <div class="table-responsive mb-5" style="border-radius: 1rem; overflow: hidden; background: #111827cc;">
          <table class="table table-dark table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($withdrawals as $w): ?>
              <tr>
                <td><?=date('M d, Y H:i', strtotime($w['created_at']))?></td>
                <td><?=sol_display($w['amount'])?><?=usdt_placeholder($w['amount'])?></td>
                <td>
                  <?php if ($w['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                  <?php elseif ($w['status'] === 'completed'): ?>
                    <span class="badge bg-success">Successful</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Failed</span>
                  <?php endif; ?>
                </td>
                <td><?=htmlspecialchars($w['description'])?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!count($withdrawals)): ?><tr><td colspan="4" class="text-center text-muted">No withdrawals yet.</td></tr><?php endif; ?>
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
    var sidebar = document.getElementById('sidebar');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarCloseBtn = document.querySelector('.sidebar-close-btn');
    function openSidebar() {
      sidebar.classList.add('open');
      sidebarOverlay.classList.add('active');
    }
    function closeSidebar() {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('active');
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
  <script>
// Confirmation dialog before submitting withdrawal form
document.addEventListener('DOMContentLoaded', function() {
  var withdrawForm = document.getElementById('withdrawForm');
  if (withdrawForm) {
    withdrawForm.addEventListener('submit', function(e) {
      if (!confirm('Are you sure you want to submit this withdrawal?')) {
        e.preventDefault();
        return false;
      }
    });
  }
});
</script>
</body>
</html> 