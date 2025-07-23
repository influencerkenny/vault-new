<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
require_once __DIR__ . '/api/settings_helper.php';
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
$success = $error = '';
// Fetch user info
$stmt = $pdo->prepare('SELECT first_name, last_name, email, avatar, username FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
// Fetch user available balance for modal display
$stmt = $pdo->prepare('SELECT available_balance FROM user_balances WHERE user_id = ?');
$stmt->execute([$user_id]);
$user_balance = $stmt->fetchColumn();
if ($user_balance === false) $user_balance = 0.00;
// Handle staking (classic POST or AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stake_plan_id'])) {
  $plan_id = (int)$_POST['stake_plan_id'];
  $amount = $_POST['stake_amount'];
  if (!is_numeric($amount) || $amount <= 0) {
    $error = 'Invalid amount. Please enter a valid number greater than zero.';
    $response['error'] = $error;
  } else {
    $amount = (float)$amount;
    $response = ['success' => false, 'error' => '', 'new_balance' => null];
    // Fetch plan
    $stmt = $pdo->prepare('SELECT * FROM plans WHERE id=? AND status="active"');
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$plan) {
      $error = 'Invalid plan selected.';
      $response['error'] = $error;
    } else if ($amount < $plan['min_investment'] || $amount > $plan['max_investment']) {
      $error = 'Amount must be between the plan minimum and maximum.';
      $response['error'] = $error;
    } else {
      // Check user available balance
      $stmt = $pdo->prepare('SELECT available_balance FROM user_balances WHERE user_id = ?');
      $stmt->execute([$user_id]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $user_balance = $row ? (float)$row['available_balance'] : 0.00;
      if ($amount > $user_balance) {
        $error = 'Insufficient available balance.';
        $response['error'] = $error;
      } else {
        $start = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO user_stakes (user_id, plan_id, amount, status, started_at) VALUES (?, ?, ?, "active", ?)');
        if ($stmt->execute([$user_id, $plan_id, $amount, $start])) {
          // Insert into transactions table for investment
          $desc = 'Staked in plan: ' . ($plan['name'] ?? ('Plan #' . $plan_id));
          $stmt2 = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, status, description, created_at) VALUES (?, "investment", ?, "completed", ?, ?)');
          $stmt2->execute([$user_id, $amount, $desc, $start]);
          // Deduct from user balance
          $stmt = $pdo->prepare('UPDATE user_balances SET available_balance = available_balance - ? WHERE user_id = ?');
          $stmt->execute([$amount, $user_id]);
          // Get new balance
          $stmt = $pdo->prepare('SELECT available_balance FROM user_balances WHERE user_id = ?');
          $stmt->execute([$user_id]);
          $new_balance = (float)($stmt->fetch(PDO::FETCH_ASSOC)['available_balance'] ?? 0);
          $success = 'Staking successful!';
          $response['success'] = true;
          $response['new_balance'] = $new_balance;
          // Send staking confirmation email
          $template = get_setting('email_template_staking');
          $replacements = [
              '{USER_NAME}' => $user['first_name'] . ' ' . $user['last_name'],
              '{AMOUNT}' => $amount,
              '{PLAN_NAME}' => $plan['name'],
              '{DATE}' => $start,
          ];
          $body = strtr($template, $replacements);
          $subject = 'Staking Confirmation';
          $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\n";
          mail($user['email'], $subject, $body, $headers);
        } else {
          $pdoError = $stmt->errorInfo();
          error_log('Failed to stake in plan: ' . print_r($pdoError, true));
          $error = 'Failed to stake in plan.';
          $response['error'] = $error;
        }
      }
    }
  }
  // AJAX response
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }
}
// Helper function to display ROI label and value
function plan_roi_label($plan) {
  $type = isset($plan['roi_type']) ? ucfirst($plan['roi_type']) : 'Daily';
  $mode = isset($plan['roi_mode']) && $plan['roi_mode'] === 'fixed' ? '$' : '%';
  $value = isset($plan['roi_value']) ? $plan['roi_value'] : 0;
  return $type . ' ROI: ' . ($mode === '$' ? '$' . number_format($value,2) : number_format($value,2) . '%');
}
// Fetch user info
$stmt = $pdo->prepare('SELECT first_name, last_name, email, avatar, username FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
// Fetch unread notifications/messages count from database
$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$user_id]);
$unreadCount = (int)$stmt->fetchColumn();
// Fetch recent notifications for dropdown
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
// Fetch user available balance
// Fetch all active plans
$plans = $pdo->query('SELECT id, name, description, min_investment, max_investment, roi_type, roi_mode, roi_value, lock_in_duration FROM plans WHERE status="active" ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
// Fetch user plan history with pagination (now using user_stakes)
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$stmt = $pdo->prepare('SELECT us.id, us.plan_id, us.amount, us.status, us.started_at, us.ended_at, us.interest_earned, p.name AS plan_name FROM user_stakes us JOIN plans p ON us.plan_id = p.id WHERE us.user_id = ? ORDER BY us.started_at DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$plan_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Count total for pagination
$stmt = $pdo->prepare('SELECT COUNT(*) FROM user_stakes WHERE user_id = ?');
$stmt->execute([$user_id]);
$total_history = $stmt->fetchColumn();
$total_pages = ceil($total_history / $limit);
?>
<?php include 'user/sidebar.php'; ?>
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
    .dashboard-header { border-bottom: 1px solid #1e293b; padding: 1.5rem 2rem 1rem 2rem; background: rgba(17,24,39,0.85); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10; font-size: 0.95em; }
    .dashboard-header .logo { height: 48px; font-size: 0.95em; }
    .dashboard-header .back-link { color: #38bdf8; font-weight: 500; text-decoration: none; margin-left: 1.5rem; transition: color 0.2s; font-size: 0.95em; }
    .dashboard-header .back-link:hover { color: #0ea5e9; text-decoration: underline; }
    .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; }
    .plan-card { background: linear-gradient(135deg, #2563eb22 0%, #0ea5e922 100%); border: 1px solid #2563eb33; border-radius: 1.25rem; padding: 2rem 1.5rem 1.5rem 1.5rem; box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10); color: #e5e7eb; position: relative; min-height: 260px; display: flex; flex-direction: column; justify-content: space-between; transition: box-shadow 0.2s, border 0.2s, background 0.2s; overflow: hidden; margin-bottom: 1.2rem; font-size: 0.91em; }
    .plan-card .plan-title { font-size: 1.05rem; color: #38bdf8; font-weight: 700; margin-bottom: 0.5rem; letter-spacing: 0.01em; }
    .plan-card .plan-desc { font-size: 0.97rem; color: #a1a1aa; margin-bottom: 1rem; }
    .plan-card .plan-meta { font-size: 0.95rem; color: #e5e7eb; margin-bottom: 0.5rem; }
    .plan-card .plan-footer { margin-top: auto; }
    .plan-card .btn { font-size: 0.97rem; border-radius: 0.75rem; padding: 0.5rem 1.1rem; }
    .modal-content { background: #111827cc; color: #e5e7eb; border-radius: 1.25rem; font-size: 0.95em; }
    .modal-header { border-bottom: 1px solid #2563eb33; font-size: 0.95em; }
    .modal-footer { border-top: 1px solid #2563eb33; font-size: 0.95em; }
    .dashboard-content-wrapper { max-width: 900px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.91rem; }
    @media (max-width: 991px) {
      .sidebar { left: -260px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; font-size: 0.89rem; }
    }
    @media (max-width: 767px) {
      .dashboard-content-wrapper { padding: 0 0.1rem; font-size: 0.87rem; }
      .plans-grid { grid-template-columns: 1fr; gap: 0.7rem; }
      .plan-card { padding: 1rem 0.5rem 0.8rem 0.5rem; min-height: 150px; font-size: 0.89em; }
      .plan-card .plan-title { font-size: 0.97rem; }
      .plan-card .plan-desc { font-size: 0.89rem; }
      .plan-card .plan-meta { font-size: 0.89rem; }
      .plan-card-mobile, .history-card-mobile { width: 100%; box-sizing: border-box; font-size: 0.89em; }
    }
    @media (max-width: 575px) {
      .dashboard-content-wrapper { padding: 0 0.05rem; font-size: 0.85rem; }
      .plan-card { padding: 0.5rem 0.1rem 0.5rem 0.1rem; min-height: 100px; font-size: 0.87em; }
      .plan-card .plan-title { font-size: 0.89rem; }
      .plan-card .plan-desc { font-size: 0.87rem; }
      .plan-card .plan-meta { font-size: 0.87rem; }
      .plan-card-mobile, .history-card-mobile { padding: 0.7rem 0.1rem 0.7rem 0.1rem; font-size: 0.87em; width: 100%; }
    }
    .table-responsive { overflow-x: auto; }
    .table { min-width: 600px; font-size: 0.89rem; }
    .table th, .table td { padding: 0.28rem 0.35rem; }
    @media (max-width: 575px) {
      .table { font-size: 0.87rem; min-width: 480px; }
      .table th, .table td { padding: 0.22rem 0.28rem; }
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
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'user/header.php'; ?>
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
              </div>
              <div class="plan-desc mb-2" style="color:#a1a1aa; font-size:1.01rem;"> <?=htmlspecialchars($plan['description'])?> </div>
              <div class="plan-attr-row"><span class="plan-attr-label">Minimum Stake:</span><span class="plan-attr-value">SOL <?=number_format($plan['min_investment'],2)?></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Maximum Stake:</span><span class="plan-attr-value">SOL <?=number_format($plan['max_investment'],2)?></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label"><?=plan_roi_label($plan)?></span><span class="plan-attr-value"></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Lock-in:</span><span class="plan-attr-value"><?=$plan['lock_in_duration']?> days</span></div>
              <div class="plan-footer mt-3">
                <button class="btn btn-info w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#stakeModal" data-plan-id="<?=$plan['id']?>" data-plan-name="<?=htmlspecialchars($plan['name'])?>" data-min="<?=$plan['min_investment']?>" data-max="<?=$plan['max_investment']?>" data-lockin="<?=$plan['lock_in_duration']?>">Stake Now</button>
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
              </div>
              <div class="plan-card-desc mb-1" style="color:#a1a1aa; font-size:0.97rem;"> <?= htmlspecialchars($plan['description']) ?> </div>
              <div class="plan-attr-row"><span class="plan-attr-label">Minimum Stake:</span><span class="plan-attr-value">SOL <?=number_format($plan['min_investment'],2)?></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Maximum Stake:</span><span class="plan-attr-value">SOL <?=number_format($plan['max_investment'],2)?></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label"><?=plan_roi_label($plan)?></span><span class="plan-attr-value"></span></div>
              <div class="plan-attr-row"><span class="plan-attr-label">Lock-in:</span><span class="plan-attr-value"><?=$plan['lock_in_duration']?> days</span></div>
              <div class="plan-card-actions mt-2">
                <button class="btn btn-info w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#stakeModal" data-plan-id="<?=$plan['id']?>" data-plan-name="<?=htmlspecialchars($plan['name'])?>" data-min="<?=$plan['min_investment']?>" data-max="<?=$plan['max_investment']?>" data-lockin="<?=$plan['lock_in_duration']?>">Stake Now</button>
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
                <th>End Day</th>
                <th>Interest Earned</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (
                $plan_history as $inv): ?>
                <tr>
                  <td><?=htmlspecialchars($inv['plan_name'])?></td>
                  <td>SOL <?=number_format($inv['amount'],2)?></td>
                  <td><span class="badge bg-<?=($inv['status']==='active'?'success':($inv['status']==='completed'?'secondary':'danger'))?> text-uppercase"><?=$inv['status']?></span></td>
                  <td><?=date('M d, Y', strtotime($inv['started_at']))?></td>
                  <td><?=$inv['ended_at'] ? date('M d, Y (l)', strtotime($inv['ended_at'])) : '-'?></td>
                  <td>SOL <?=number_format($inv['interest_earned'],2)?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!count($plan_history)): ?><tr><td colspan="6" class="text-center text-muted">No staking history yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
          <?php if ($total_pages > $page): ?>
            <div class="text-center mt-3">
              <button class="btn btn-outline-info load-more-history" data-next-page="<?=($page+1)?>">Load More</button>
            </div>
          <?php endif; ?>
        </div>
        <!-- Mobile History List -->
        <div class="history-mobile-list d-md-none mb-5">
          <?php foreach ($plan_history as $inv): ?>
            <div class="history-card-mobile mb-3">
              <div class="history-card-header">
                <span class="history-card-title"><?= htmlspecialchars($inv['plan_name']) ?></span>
                <span class="badge bg-<?=($inv['status']==='active'?'success':($inv['status']==='completed'?'secondary':'danger'))?> text-uppercase ms-2" style="font-size:0.85em;"> <?=$inv['status']?> </span>
              </div>
              <div class="history-card-row"><b>Amount:</b> SOL <?= number_format($inv['amount'], 2) ?></div>
              <div class="history-card-row"><b>Start:</b> <?= date('M d, Y', strtotime($inv['started_at'])) ?></div>
              <div class="history-card-row"><b>End:</b> <?= $inv['ended_at'] ? date('M d, Y', strtotime($inv['ended_at'])) : '-' ?></div>
              <div class="history-card-row"><b>End Day:</b> <?= $inv['ended_at'] ? date('M d, Y (l)', strtotime($inv['ended_at'])) : '-' ?></div>
              <div class="history-card-row"><b>Interest Earned:</b> SOL <?= number_format($inv['interest_earned'], 2) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (!count($plan_history)): ?><div class="text-center text-muted">No staking history yet.</div><?php endif; ?>
          <?php if ($total_pages > $page): ?>
            <div class="text-center mt-3">
              <button class="btn btn-outline-info load-more-history" data-next-page="<?=($page+1)?>">Load More</button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
    <footer class="dashboard-footer">
      &copy; <?=date('Y')?> Vault. All rights reserved.
    </footer>
  </div>
<!-- Stake Modal (Redesigned, with plan select) -->
<div class="modal fade" id="stakeModal" tabindex="-1" aria-labelledby="stakeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem; background: linear-gradient(135deg, #181f2a 80%, #232b3b 100%);">
      <form id="stakeForm" method="post" autocomplete="off">
        <div class="modal-header border-0 pb-0" style="background: none;">
          <h4 class="modal-title fw-bold text-info" id="stakeModalLabel">Stake in Plan</h4>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-2">
          <div class="mb-3">
            <label for="stake_plan_id" class="form-label fw-semibold">Select Plan</label>
            <select class="form-select bg-dark text-info border-0 rounded-3" id="stake_plan_id" name="stake_plan_id" required>
              <option value="" disabled selected>Select a plan</option>
              <?php foreach ($plans as $plan): ?>
                <option value="<?=$plan['id']?>" data-min="<?=$plan['min_investment']?>" data-max="<?=$plan['max_investment']?>" data-lockin="<?=$plan['lock_in_duration']?>">
                  <?=htmlspecialchars($plan['name'])?> (Min: <?=number_format($plan['min_investment'],2)?>, Max: <?=number_format($plan['max_investment'],2)?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="stake_amount" class="form-label fw-semibold">Amount to Stake</label>
            <div class="input-group">
              <input type="text" class="form-control bg-dark text-white border-0 rounded-start-3" id="stake_amount" name="stake_amount" required placeholder="Enter amount">
              <span class="input-group-text bg-dark text-info border-0 rounded-end-3">SOL</span>
            </div>
            <div class="form-text text-secondary" id="stakeRange"></div>
            <div class="alert alert-info d-flex align-items-center py-2 px-3 mb-2" style="font-size:1.15rem; font-weight:600; border-radius:0.75rem;">
              <i class="bi bi-wallet2 me-2"></i>Available Balance: <span id="modalAvailableBalance" class="ms-1 text-primary">SOL <?=number_format($user_balance,2)?></span>
              <button type="button" id="refreshBalanceBtn" class="btn btn-sm btn-outline-primary ms-auto" title="Refresh Balance"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
            <div class="form-text text-info" id="expectedReturns"></div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 d-flex flex-column gap-2">
          <button type="submit" class="btn btn-info w-100 fw-bold py-2" style="font-size:1.15rem; border-radius:0.75rem;">Stake Now</button>
          <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Scripts: Bootstrap first, then custom -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script defer>
    // Mobile sidebar toggle/overlay (copied from dashboard)
    var sidebar = document.getElementById('sidebar');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    var sidebarToggle = document.getElementById('sidebarToggle');
    function openSidebar() {
      sidebar.classList.add('active');
      sidebarOverlay.classList.add('active');
      if (sidebarToggle) sidebarToggle.classList.add('active');
    }
    function closeSidebar() {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
      if (sidebarToggle) sidebarToggle.classList.remove('active');
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
    // Enhanced stake modal logic for plan selection
    const planSelect = document.getElementById('stake_plan_id');
    const amountInput = document.getElementById('stake_amount');
    const stakeRange = document.getElementById('stakeRange');
    const expectedReturns = document.getElementById('expectedReturns');
    let selectedPlan = null;
    if (planSelect) {
      planSelect.addEventListener('change', function() {
        const selected = planSelect.options[planSelect.selectedIndex];
        if (!selected.value) return;
        const min = parseFloat(selected.getAttribute('data-min'));
        const max = parseFloat(selected.getAttribute('data-max'));
        const lockin = parseInt(selected.getAttribute('data-lockin'));
        amountInput.min = min;
        amountInput.max = max;
        amountInput.value = '';
        stakeRange.textContent = `Min: SOL ${min.toFixed(2)} | Max: SOL ${max.toFixed(2)}`;
        expectedReturns.textContent = '';
        selectedPlan = { min, max, lockin };
      });
      amountInput.addEventListener('input', function() {
        if (!selectedPlan) return;
        let val = parseFloat(this.value);
        if (isNaN(val) || val <= 0) {
          expectedReturns.textContent = '';
          return;
        }
        if (val && selectedPlan.lockin) {
          const total = val * (0.01) * selectedPlan.lockin; // Assuming daily ROI is 1% for simplicity in JS
          expectedReturns.textContent = `Expected Earnings: SOL ${total.toFixed(2)} (${selectedPlan.lockin} days)`;
        } else {
          expectedReturns.textContent = '';
        }
      });
    }
    // Progressive enhancement: AJAX stake
    const stakeForm = document.getElementById('stakeForm');
    if (stakeForm) {
      stakeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = stakeForm.querySelector('button[type=submit]');
        // Prevent double submission
        if (btn.disabled) return;
        btn.disabled = true;
        const originalBtnHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
        // Remove previous alerts
        if (document.getElementById('stakeSuccess')) document.getElementById('stakeSuccess').remove();
        if (document.getElementById('stakeError')) document.getElementById('stakeError').remove();
        const formData = new FormData(stakeForm);
        fetch(window.location.href, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success';
            alert.id = 'stakeSuccess';
            alert.textContent = 'Staking successful!';
            stakeForm.querySelector('.modal-body').prepend(alert);
            // Update available balance in modal
            document.querySelectorAll('.text-warning').forEach(el => {
              el.textContent = 'Available Balance: SOL ' + (data.new_balance !== null ? data.new_balance.toFixed(2) : '0.00');
            });
            setTimeout(() => {
              const modal = bootstrap.Modal.getInstance(document.getElementById('stakeModal'));
              modal.hide();
              stakeForm.reset();
              alert.remove();
              window.location.reload(); // Reload to update plan history and widgets
            }, 1200);
          } else {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger';
            alert.id = 'stakeError';
            alert.textContent = data.error || 'Failed to stake.';
            stakeForm.querySelector('.modal-body').prepend(alert);
          }
        })
        .catch(() => {
          const alert = document.createElement('div');
          alert.className = 'alert alert-danger';
          alert.id = 'stakeError';
          alert.textContent = 'Failed to stake.';
          stakeForm.querySelector('.modal-body').prepend(alert);
        })
        .finally(() => {
          btn.disabled = false;
          btn.innerHTML = originalBtnHtml;
        });
      });
    }
    // Pagination for staking history
    document.querySelectorAll('.load-more-history').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var nextPage = parseInt(this.dataset.nextPage);
        var url = new URL(window.location.href);
        url.searchParams.set('page', nextPage);
        window.location.href = url.toString();
      });
    });
    // Fallback JS trigger for stake modal
    window.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#stakeModal"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          // If Bootstrap modal doesn't open, open it manually
          setTimeout(function() {
            var modalElem = document.getElementById('stakeModal');
            if (modalElem && !modalElem.classList.contains('show')) {
              try {
                var modal = bootstrap.Modal.getOrCreateInstance(modalElem);
                modal.show();
              } catch (err) {
                console.error('Modal fallback error:', err);
              }
            }
          }, 200);
        });
      });
    });
    // Debug: log JS errors
    window.addEventListener('error', function(e) {
      console.error('JS Error:', e.message, e);
    });
  </script>
  <script>
document.getElementById('refreshBalanceBtn').addEventListener('click', function() {
  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  fetch('api/update_user_balance.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'get_balance' })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success && data.balance) {
      document.getElementById('modalAvailableBalance').textContent = 'SOL ' + parseFloat(data.balance.available_balance).toFixed(2);
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
  });
});
</script>
  <script src="public/sidebar-toggle.js" defer></script>
</body>
</html> 