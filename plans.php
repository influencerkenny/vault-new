<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
$success = $error = '';
// Handle staking (classic POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stake_plan_id'])) {
  $plan_id = (int)$_POST['stake_plan_id'];
  $amount = (float)$_POST['stake_amount'];
  // Fetch plan
  $stmt = $pdo->prepare('SELECT * FROM plans WHERE id=? AND status="active"');
  $stmt->execute([$plan_id]);
  $plan = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$plan) {
    $error = 'Invalid plan selected.';
  } else if ($amount < $plan['min_investment'] || $amount > $plan['max_investment']) {
    $error = 'Amount must be between the plan minimum and maximum.';
  } else {
    $start = date('Y-m-d H:i:s');
    $end = date('Y-m-d H:i:s', strtotime("+{$plan['lock_in_duration']} days"));
    $stmt = $pdo->prepare('INSERT INTO investments (user_id, plan_id, amount, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, "active")');
    if ($stmt->execute([$user_id, $plan_id, $amount, $start, $end])) {
      $success = 'Staking successful!';
    } else {
      $error = 'Failed to stake in plan.';
    }
  }
}
// Fetch user info
$stmt = $pdo->prepare('SELECT first_name, last_name, email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$displayName = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : 'Investor';
if (!$displayName) $displayName = $user['email'] ?? 'Investor';
$avatar = 'public/placeholder-user.jpg';
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
// Fetch user available balance
// Fetch all active plans
$plans = $pdo->query('SELECT * FROM plans WHERE status="active" ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
// Fetch user plan history
$stmt = $pdo->prepare('SELECT i.*, p.name AS plan_name FROM investments i JOIN plans p ON i.plan_id = p.id WHERE i.user_id = ? ORDER BY i.start_date DESC');
$stmt->execute([$user_id]);
$plan_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staking Plans | Vault</title>
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
    .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; }
    .plan-card { background: linear-gradient(135deg, #2563eb22 0%, #0ea5e922 100%); border: 1px solid #2563eb33; border-radius: 1.25rem; padding: 2rem 1.5rem 1.5rem 1.5rem; box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10); color: #e5e7eb; position: relative; min-height: 260px; display: flex; flex-direction: column; justify-content: space-between; transition: box-shadow 0.2s, border 0.2s, background 0.2s; overflow: hidden; margin-bottom: 1.2rem; }
    .plan-card .plan-title { font-size: 1.25rem; color: #38bdf8; font-weight: 700; margin-bottom: 0.5rem; letter-spacing: 0.01em; }
    .plan-card .plan-desc { font-size: 1.05rem; color: #a1a1aa; margin-bottom: 1rem; }
    .plan-card .plan-meta { font-size: 1.01rem; color: #e5e7eb; margin-bottom: 0.5rem; }
    .plan-card .plan-footer { margin-top: auto; }
    .plan-card .btn { font-size: 1.05rem; border-radius: 0.75rem; padding: 0.6rem 1.5rem; }
    .modal-content { background: #111827cc; color: #e5e7eb; border-radius: 1.25rem; }
    .modal-header { border-bottom: 1px solid #2563eb33; }
    .modal-footer { border-top: 1px solid #2563eb33; }
    .dashboard-content-wrapper { max-width: 900px; width: 100%; margin: 0 auto; padding: 0 1rem; }
    @media (max-width: 991px) {
      .sidebar { left: -260px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; }
    }
    @media (max-width: 767px) {
      .dashboard-content-wrapper { padding: 0 0.1rem; }
      .plans-grid { grid-template-columns: 1fr; gap: 0.7rem; }
      .plan-card { padding: 1rem 0.5rem 0.8rem 0.5rem; min-height: 150px; font-size: 0.97rem; }
      .plan-card .plan-title { font-size: 1.08rem; }
      .plan-card .plan-desc { font-size: 0.98rem; }
      .plan-card .plan-meta { font-size: 0.97rem; }
      .plan-card-mobile, .history-card-mobile { width: 100%; box-sizing: border-box; }
    }
    @media (max-width: 575px) {
      .dashboard-content-wrapper { padding: 0 0.05rem; }
      .plan-card { padding: 0.5rem 0.1rem 0.5rem 0.1rem; min-height: 100px; font-size: 0.91rem; }
      .plan-card .plan-title { font-size: 0.98rem; }
      .plan-card .plan-desc { font-size: 0.91rem; }
      .plan-card .plan-meta { font-size: 0.91rem; }
      .plan-card-mobile, .history-card-mobile { padding: 0.7rem 0.1rem 0.7rem 0.1rem; font-size: 0.91rem; width: 100%; }
    }
    .table-responsive { overflow-x: auto; }
    .table { min-width: 600px; }
    @media (max-width: 575px) {
      .table { font-size: 0.93rem; min-width: 480px; }
      .table th, .table td { padding: 0.4rem 0.5rem; }
    }
    /* Mobile plans card/list view */
    .plans-mobile-list { display: none; }
    .plan-card-mobile {
      background: linear-gradient(135deg, #2563eb22 0%, #0ea5e922 100%);
      border: 1px solid #2563eb33;
      border-radius: 1.25rem;
      box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10);
      color: #e5e7eb;
      padding: 1.1rem 1rem 1rem 1rem;
      margin-bottom: 1.2rem;
      font-size: 1.01rem;
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
    }
    .plan-card-header { font-size: 1.13rem; font-weight: 700; color: #38bdf8; margin-bottom: 2px; }
    .plan-card-title { font-size: 1.08rem; }
    .plan-card-desc { font-size: 0.97rem; color: #a1a1aa; margin-bottom: 2px; }
    .plan-card-row { font-size: 0.97rem; color: #e5e7eb; margin-bottom: 2px; }
    .plan-card-actions { margin-top: 8px; }
    /* Mobile history card/list view */
    .history-mobile-list { display: none; }
    .history-card-mobile {
      background: #181f2a;
      border: 1px solid #2563eb33;
      border-radius: 1.1rem;
      box-shadow: 0 2px 12px #0003;
      color: #e5e7eb;
      padding: 1rem 0.8rem 0.8rem 0.8rem;
      margin-bottom: 1.1rem;
      font-size: 0.99rem;
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
    }
    .history-card-header { font-size: 1.05rem; font-weight: 700; color: #38bdf8; display: flex; align-items: center; margin-bottom: 2px; }
    .history-card-title { font-size: 1.01rem; }
    .history-card-row { font-size: 0.97rem; color: #e5e7eb; margin-bottom: 2px; }
    /* Responsive: show mobile list, hide grid/table on small screens */
    @media (max-width: 767px) {
      .plans-grid { display: none !important; }
      .plans-mobile-list { display: block !important; }
      .table-responsive { display: none !important; }
      .history-mobile-list { display: block !important; }
    }
    @media (max-width: 575px) {
      .plan-card-mobile, .history-card-mobile { padding: 0.7rem 0.3rem 0.7rem 0.3rem; font-size: 0.93rem; }
      .plan-card-header, .history-card-header { font-size: 0.98rem; }
      .plan-card-title, .history-card-title { font-size: 0.95rem; }
      .plan-card-row, .history-card-row { font-size: 0.93rem; }
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
    /* Dotted leader for plan attributes */
    .plan-attr-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 1.01rem;
      margin-bottom: 0.3rem;
      color: #e5e7eb;
    }
    .plan-attr-label {
      flex: 1 1 auto;
      position: relative;
      padding-right: 0.5em;
      white-space: nowrap;
    }
    .plan-attr-label:after {
      content: "";
      display: inline-block;
      border-bottom: 1px dotted #64748b;
      width: 100%;
      position: absolute;
      left: 100%;
      top: 50%;
      transform: translateY(-50%);
      z-index: 0;
    }
    .plan-attr-value {
      flex: 0 0 auto;
      font-weight: 600;
      color: #38bdf8;
      margin-left: 0.5em;
      white-space: nowrap;
      z-index: 1;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div id="sidebar" class="sidebar">
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
  <!-- Mobile Sidebar Overlay (after sidebar) -->
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
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
              <div class="fw-semibold text-dark mb-0" style="font-size:1.05rem;">{{$displayName}}</div>
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
      <div class="dashboard-content-wrapper mx-auto" style="max-width: 900px; width: 100%; padding: 0 1rem;">
        <h2 class="mb-4 text-info fw-bold">Staking Plans</h2>
        <?php if ($success): ?><div class="alert alert-success" id="stakeSuccess"><?=$success?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger" id="stakeError"><?=$error?></div><?php endif; ?>
        <!-- Desktop Plans Grid -->
        <div class="plans-grid mb-5 d-none d-md-grid">
          <?php foreach ($plans as $plan): ?>
            <div class="plan-card d-flex flex-column mb-3" style="box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10); border-radius: 1.25rem; background: linear-gradient(135deg, #232b3b 80%, #202736 100%); border: 1.5px solid #2563eb33;">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="plan-title mb-0" style="font-size:1.18rem; color:#38bdf8; font-weight:700; letter-spacing:0.01em;"> <?=htmlspecialchars($plan['name'])?> </div>
                <?php if ($plan['bonus'] > 0): ?><span class="badge bg-success ms-2">Bonus</span><?php endif; ?>
              </div>
              <div class="plan-desc mb-2" style="color:#a1a1aa; font-size:1.01rem;"> <?=htmlspecialchars($plan['description'])?> </div>
              <div class="plan-attr-row"><span class="plan-attr-label">Minimum Stake:</span><span class="plan-attr-value">$<?=number_format($plan['min_investment'],2)?></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Maximum Stake:</span><span class="plan-attr-value">$<?=number_format($plan['max_investment'],2)?></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Daily ROI:</span><span class="plan-attr-value"><?=number_format($plan['daily_roi'],2)?>%</span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Monthly ROI:</span><span class="plan-attr-value"><?=number_format($plan['monthly_roi'],2)?>%</span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Lock-in:</span><span class="plan-attr-value"><?=$plan['lock_in_duration']?> days</span></div>
              <?php if ($plan['bonus'] > 0): ?><div class="plan-attr-row"><span class="plan-attr-label">Bonus:</span><span class="plan-attr-value">$<?=number_format($plan['bonus'],2)?></span></div><?php endif; ?>
              <?php if ($plan['referral_reward'] > 0): ?><div class="plan-attr-row"><span class="plan-attr-label">Referral Reward:</span><span class="plan-attr-value">$<?=number_format($plan['referral_reward'],2)?></span></div><?php endif; ?>
              <div class="plan-footer mt-3">
                <button class="btn btn-info w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#stakeModal" data-plan-id="<?=$plan['id']?>" data-plan-name="<?=htmlspecialchars($plan['name'])?>" data-min="<?=$plan['min_investment']?>" data-max="<?=$plan['max_investment']?>" data-daily-roi="<?=$plan['daily_roi']?>" data-lockin="<?=$plan['lock_in_duration']?>">Stake Now</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <!-- Mobile Plans List -->
        <div class="plans-mobile-list d-md-none mb-5">
          <?php foreach ($plans as $plan): ?>
            <div class="plan-card-mobile mb-3" style="box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10); border-radius: 1.25rem; background: linear-gradient(135deg, #232b3b 80%, #202736 100%); border: 1.5px solid #2563eb33;">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="plan-card-title" style="font-size:1.08rem; color:#38bdf8; font-weight:700;"> <?= htmlspecialchars($plan['name']) ?> </span>
                <?php if ($plan['bonus'] > 0): ?><span class="badge bg-success ms-2">Bonus</span><?php endif; ?>
              </div>
              <div class="plan-card-desc mb-1" style="color:#a1a1aa; font-size:0.97rem;"> <?= htmlspecialchars($plan['description']) ?> </div>
              <div class="plan-attr-row"><span class="plan-attr-label">Minimum Stake:</span><span class="plan-attr-value">$<?=number_format($plan['min_investment'],2)?></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Maximum Stake:</span><span class="plan-attr-value">$<?=number_format($plan['max_investment'],2)?></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Daily ROI:</span><span class="plan-attr-value"><?=number_format($plan['daily_roi'],2)?>%</span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Monthly ROI:</span><span class="plan-attr-value"><?=number_format($plan['monthly_roi'],2)?>%</span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Lock-in:</span><span class="plan-attr-value"><?=$plan['lock_in_duration']?> days</span></div>
              <?php if ($plan['bonus'] > 0): ?><div class="plan-attr-row"><span class="plan-attr-label">Bonus:</span><span class="plan-attr-value">$<?=number_format($plan['bonus'],2)?></span></div><?php endif; ?>
              <?php if ($plan['referral_reward'] > 0): ?><div class="plan-attr-row"><span class="plan-attr-label">Referral Reward:</span><span class="plan-attr-value">$<?=number_format($plan['referral_reward'],2)?></span></div><?php endif; ?>
              <div class="plan-card-actions mt-2">
                <button class="btn btn-info w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#stakeModal" data-plan-id="<?=$plan['id']?>" data-plan-name="<?=htmlspecialchars($plan['name'])?>" data-min="<?=$plan['min_investment']?>" data-max="<?=$plan['max_investment']?>" data-daily-roi="<?=$plan['daily_roi']?>" data-lockin="<?=$plan['lock_in_duration']?>">Stake Now</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <!-- Plan History -->
        <h3 class="mb-3 text-info fw-bold">My Staking History</h3>
        <!-- Desktop Table -->
        <div class="table-responsive mb-5 d-none d-md-block" style="border-radius: 1rem; overflow: hidden; background: #111827cc;">
          <table class="table table-dark table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>Plan</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Start</th>
                <th>End</th>
                <th>Total Earned</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($plan_history as $inv): ?>
                <tr>
                  <td><?=htmlspecialchars($inv['plan_name'])?></td>
                  <td>$<?=number_format($inv['amount'],2)?></td>
                  <td><span class="badge bg-<?=($inv['status']==='active'?'success':($inv['status']==='completed'?'secondary':'danger'))?> text-uppercase"><?=$inv['status']?></span></td>
                  <td><?=date('M d, Y', strtotime($inv['start_date']))?></td>
                  <td><?=date('M d, Y', strtotime($inv['end_date']))?></td>
                  <td>$<?=number_format($inv['total_earned'],2)?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!count($plan_history)): ?><tr><td colspan="6" class="text-center text-muted">No staking history yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <!-- Mobile History List -->
        <div class="history-mobile-list d-md-none mb-5">
          <?php foreach ($plan_history as $inv): ?>
            <div class="history-card-mobile mb-3">
              <div class="history-card-header">
                <span class="history-card-title"><?= htmlspecialchars($inv['plan_name']) ?></span>
                <span class="badge bg-<?=($inv['status']==='active'?'success':($inv['status']==='completed'?'secondary':'danger'))?> text-uppercase ms-2" style="font-size:0.85em;"> <?=$inv['status']?> </span>
              </div>
              <div class="history-card-row"><b>Amount:</b> $<?= number_format($inv['amount'], 2) ?></div>
              <div class="history-card-row"><b>Start:</b> <?= date('M d, Y', strtotime($inv['start_date'])) ?></div>
              <div class="history-card-row"><b>End:</b> <?= date('M d, Y', strtotime($inv['end_date'])) ?></div>
              <div class="history-card-row"><b>Total Earned:</b> $<?= number_format($inv['total_earned'], 2) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (!count($plan_history)): ?><div class="text-center text-muted">No staking history yet.</div><?php endif; ?>
        </div>
      </div>
      <!-- Stake Modal -->
      <div class="modal fade" id="stakeModal" tabindex="-1" aria-labelledby="stakeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="stakeModalLabel">Stake in Plan</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="stakeForm" method="post" autocomplete="off">
              <div class="modal-body">
                <input type="hidden" name="stake_plan_id" id="stake_plan_id">
                <div class="mb-3">
                  <label for="stake_plan_name" class="form-label">Plan</label>
                  <input type="text" class="form-control" id="stake_plan_name" readonly>
                </div>
                <div class="mb-3">
                  <label for="stake_amount" class="form-label">Amount</label>
                  <input type="number" class="form-control" id="stake_amount" name="stake_amount" min="0" step="0.01" required>
                  <div class="form-text" id="stakeRange"></div>
                  <div class="form-text text-warning">Available Balance: $<?=number_format($user_balance,2)?></div>
                  <div class="form-text text-info" id="expectedReturns"></div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Stake</button>
              </div>
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
    // Stake modal logic
    var stakeModal = document.getElementById('stakeModal');
    stakeModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var planId = button.getAttribute('data-plan-id');
      var planName = button.getAttribute('data-plan-name');
      var min = button.getAttribute('data-min');
      var max = button.getAttribute('data-max');
      var dailyRoi = button.getAttribute('data-daily-roi');
      var lockin = button.getAttribute('data-lockin');
      document.getElementById('stake_plan_id').value = planId;
      document.getElementById('stake_plan_name').value = planName;
      document.getElementById('stake_amount').min = min;
      document.getElementById('stake_amount').max = max;
      document.getElementById('stakeRange').textContent = `Min: $${parseFloat(min).toFixed(2)} | Max: $${parseFloat(max).toFixed(2)}`;
      document.getElementById('stake_amount').value = '';
      document.getElementById('expectedReturns').textContent = '';
      // Advanced logic: prevent over-staking
      document.getElementById('stake_amount').addEventListener('input', function() {
        var val = parseFloat(this.value);
        var avail = <?=json_encode($user_balance)?>;
        if (val > avail) {
          this.value = avail;
        }
        // Show expected returns
        if (val && dailyRoi && lockin) {
          var total = val * (parseFloat(dailyRoi)/100) * parseInt(lockin);
          document.getElementById('expectedReturns').textContent = `Expected Earnings: $${total.toFixed(2)} (${lockin} days)`;
        } else {
          document.getElementById('expectedReturns').textContent = '';
        }
      });
    });
    // Progressive enhancement: AJAX stake
    document.getElementById('stakeForm').addEventListener('submit', function(e) {
      if (!window.fetch) return; // fallback to classic
      e.preventDefault();
      const form = this;
      const data = new FormData(form);
      fetch('plans.php', {
        method: 'POST',
        body: data
      })
      .then(res => res.text())
      .then(html => {
        // Replace the plans grid and alerts with the new HTML (partial reload)
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newGrid = doc.querySelector('.plans-grid');
        const newSuccess = doc.getElementById('stakeSuccess');
        const newError = doc.getElementById('stakeError');
        if (newGrid) document.querySelector('.plans-grid').replaceWith(newGrid);
        if (document.getElementById('stakeSuccess')) document.getElementById('stakeSuccess').remove();
        if (document.getElementById('stakeError')) document.getElementById('stakeError').remove();
        if (newSuccess) document.querySelector('.container-fluid').insertBefore(newSuccess, document.querySelector('.plans-grid'));
        if (newError) document.querySelector('.container-fluid').insertBefore(newError, document.querySelector('.plans-grid'));
        var modal = bootstrap.Modal.getInstance(document.getElementById('stakeModal'));
        modal.hide();
      });
    });
  </script>
  <script src="public/sidebar-toggle.js"></script>
</body>
</html> 