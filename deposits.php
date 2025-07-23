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
if (isset($_GET['success']) && $_GET['success'] == '1') {
  $success = 'Deposit request submitted! Awaiting admin approval.';
}
// Fetch enabled gateways
$gateways = $pdo->query('SELECT * FROM payment_gateways WHERE status="enabled" ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
// If no gateways exist, create a default one
if (empty($gateways)) {
  try {
    $defaultGateway = [
      'name' => 'Bank Transfer',
      'currency' => 'USD',
      'rate_to_usd' => 1.00,
      'min_amount' => 10.00,
      'max_amount' => 10000.00,
      'instructions' => 'Please transfer the amount to our bank account. Include your user ID as reference.',
      'user_data_label' => 'Bank Account Details',
      'thumbnail' => 'bank-transfer.png',
      'status' => 'enabled'
    ];
    $stmt = $pdo->prepare('INSERT INTO payment_gateways (name, currency, rate_to_usd, min_amount, max_amount, instructions, user_data_label, thumbnail, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
      $defaultGateway['name'],
      $defaultGateway['currency'],
      $defaultGateway['rate_to_usd'],
      $defaultGateway['min_amount'],
      $defaultGateway['max_amount'],
      $defaultGateway['instructions'],
      $defaultGateway['user_data_label'],
      $defaultGateway['thumbnail'],
      $defaultGateway['status']
    ]);
    $gateways = $pdo->query('SELECT * FROM payment_gateways WHERE status="enabled" ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    error_log('Failed to create default gateway: ' . $e->getMessage());
  }
}
// Fetch user info for sidebar/header
$stmt = $pdo->prepare('SELECT first_name, last_name, email, avatar, username FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
$displayName = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : 'Investor';
$email = $user ? $user['email'] : '';
// Handle deposit submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gateway_id'], $_POST['amount'])) {
  $gateway_id = (int)$_POST['gateway_id'];
  $amount = (float)$_POST['amount'];
  $proof = '';
  $gateway = null;
  foreach ($gateways as $gw) if ($gw['id'] == $gateway_id) $gateway = $gw;
  if (!$gateway) {
    $error = 'Invalid payment method.';
  } elseif ($amount < $gateway['min_amount'] || $amount > $gateway['max_amount']) {
    $error = 'Amount must be within the allowed range.';
  } elseif (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
    $error = 'Proof of payment is required.';
  } else {
    $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','pdf'];
    if (!in_array($ext, $allowed)) {
      $error = 'Invalid file type for proof.';
    } else {
      $fname = 'deposit_proof_' . time() . '_' . rand(1000,9999) . '.' . $ext;
      $dest = 'public/' . $fname;
      if (!move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) {
        $error = 'Failed to upload proof.';
      } else {
        $proof = $fname;
        $desc = "Deposit via {$gateway['name']} ({$gateway['currency']})";
        $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, status, description, created_at, proof) VALUES (?, "deposit", ?, "pending", ?, NOW(), ?)');
        if ($stmt->execute([$user_id, $amount, $desc, $proof])) {
          $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)')->execute([
            $user_id,
            'Deposit Submitted',
            'Your deposit request of ' . $amount . ' ' . $gateway['currency'] . ' is pending admin approval.'
          ]);
          header('Location: deposits.php?success=1');
          exit;
        } else {
          $error = 'Failed to submit deposit.';
        }
      }
    }
  }
}
$stmt = $pdo->prepare('SELECT * FROM transactions WHERE user_id = ? AND type = "deposit" ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
  <title>User Deposits | Vault</title>
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
    /* Make deposit form labels white */
    #depositForm .form-label {
      color: #fff !important;
    }
    /* Make payment description (gateway instructions) white */
    #gatewayDetails {
      color: #fff !important;
    }
    /* Mobile deposit history cards */
    @media (max-width: 600px) {
      .card.mb-3 {
        background: #181f2a;
        border-radius: 1rem;
        box-shadow: 0 2px 8px #0002;
        margin-bottom: 1.1rem;
        color: #fff;
      }
      .card-body.p-3 {
        padding: 1.1rem 1rem 1rem 1rem;
        color: #fff;
      }
      .card.mb-3 .fw-bold,
      .card.mb-3 .fw-semibold,
      .card.mb-3 .text-info,
      .card.mb-3 .mb-1,
      .card.mb-3 a.proof-link {
        color: #fff !important;
      }
      .card.mb-3 .text-secondary {
        color: #cbd5e1 !important;
      }
    }
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
        <h2 class="mb-4 text-info fw-bold">Deposit Funds</h2>
        <?php if ($success): ?><div class="alert alert-success" id="depositSuccessAlert"><?=$success?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
        <?php if (empty($gateways)): ?>
          <div class="alert alert-warning text-center">No payment methods are currently available. Please contact support.</div>
        <?php endif; ?>
        <div class="mb-4">
          <button class="btn btn-primary mb-2" type="button" data-bs-toggle="modal" data-bs-target="#depositModal">
            <i class="bi bi-plus-circle me-1"></i> New Deposit
          </button>
          <!-- Deposit Modal will be moved to just before </body> -->
        </div>
        <h4 class="mt-5 mb-3 text-info fw-bold">Deposit History</h4>
        <div class="mb-5">
          <!-- Desktop Table -->
          <div class="table-responsive d-none d-sm-block" style="border-radius: 1rem; overflow: hidden; background: #111827cc;">
            <table class="table table-dark table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Status</th>
                  <th>Proof</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($deposits as $dep): ?>
                <tr>
                  <td><?=date('M d, Y H:i', strtotime($dep['created_at']))?></td>
                  <td><?=number_format($dep['amount'],2)?></td>
                  <td><?=!empty($dep['description']) ? htmlspecialchars($dep['description']) : 'Deposit'?></td>
                  <td>
                    <?php if ($dep['status'] === 'pending'): ?>
                      <span class="badge bg-warning text-dark">Pending</span>
                    <?php elseif ($dep['status'] === 'completed'): ?>
                      <span class="badge bg-success">Successful</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Rejected</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($dep['proof'])): ?>
                      <a href="public/<?=htmlspecialchars($dep['proof'])?>" target="_blank" class="proof-link">View</a>
                    <?php else: ?>
                      <span class="text-secondary">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!count($deposits)): ?><tr><td colspan="5" class="text-center text-muted">No deposit history yet.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
          <!-- Mobile Cards -->
          <div class="d-block d-sm-none">
            <?php if (count($deposits)): ?>
              <?php foreach ($deposits as $dep): ?>
                <div class="card mb-3" style="background:#181f2a;border-radius:1rem;box-shadow:0 2px 8px #0002;">
                  <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="fw-bold text-info" style="font-size:1.05em;">Amount:</span>
                      <span class="fw-semibold" style="font-size:1.1em;"><?=number_format($dep['amount'],2)?></span>
                    </div>
                    <div class="mb-1"><span class="fw-bold">Date:</span> <?=date('M d, Y H:i', strtotime($dep['created_at']))?></div>
                    <div class="mb-1"><span class="fw-bold">Method:</span> <?=!empty($dep['description']) ? htmlspecialchars($dep['description']) : 'Deposit'?></div>
                    <div class="mb-1"><span class="fw-bold">Status:</span> 
                      <?php if ($dep['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                      <?php elseif ($dep['status'] === 'completed'): ?>
                        <span class="badge bg-success">Successful</span>
                      <?php else: ?>
                        <span class="badge bg-danger">Rejected</span>
                      <?php endif; ?>
                    </div>
                    <div class="mb-1"><span class="fw-bold">Proof:</span> 
                      <?php if (!empty($dep['proof'])): ?>
                        <a href="public/<?=htmlspecialchars($dep['proof'])?>" target="_blank" class="proof-link">View</a>
                      <?php else: ?>
                        <span class="text-secondary">-</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-center text-muted">No deposit history yet.</div>
            <?php endif; ?>
          </div>
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
// Confirmation dialog before submitting deposit form
document.addEventListener('DOMContentLoaded', function() {
  var depositForm = document.getElementById('depositForm');
  if (depositForm) {
    depositForm.addEventListener('submit', function(e) {
      if (!confirm('Are you sure you want to submit this deposit?')) {
        e.preventDefault();
        return false;
      }
    });
  }
});
// Show payment gateway instructions when a gateway is selected
  document.addEventListener('DOMContentLoaded', function() {
    var gatewaySelect = document.getElementById('gateway_id');
    var gatewayDetails = document.getElementById('gatewayDetails');
    if (gatewaySelect && gatewayDetails) {
      function updateGatewayDetails() {
        var selected = gatewaySelect.options[gatewaySelect.selectedIndex];
        var instructions = selected ? selected.getAttribute('data-instructions') : '';
        gatewayDetails.textContent = instructions || '';
      }
      gatewaySelect.addEventListener('change', updateGatewayDetails);
      // Show instructions for pre-selected value (if any)
      updateGatewayDetails();
    }
  });
</script>
<!-- Deposit Modal (moved here for best compatibility) -->
<div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content bg-dark text-light" style="border-radius:1.1rem;background:#181f2a;max-width:420px;margin:auto;">
      <form id="depositForm" method="post" enctype="multipart/form-data" autocomplete="off">
        <div class="modal-header bg-info text-white" style="border-top-left-radius:1.25rem;border-top-right-radius:1.25rem;">
          <h5 class="modal-title" id="depositModalLabel">New Deposit</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label for="gateway_id" class="form-label">Payment Method</label>
            <select class="form-select mb-2" id="gateway_id" name="gateway_id" required>
              <option value="">Choose a gateway</option>
              <?php foreach ($gateways as $gw): ?>
                <option value="<?=$gw['id']?>" data-currency="<?=$gw['currency']?>" data-rate="<?=$gw['rate_to_usd']?>" data-min="<?=$gw['min_amount']?>" data-max="<?=$gw['max_amount']?>" data-instructions="<?=htmlspecialchars($gw['instructions'])?>" data-userlabel="<?=htmlspecialchars($gw['user_data_label'])?>" data-thumb="<?=$gw['thumbnail']?>">
                  <?=$gw['name']?> (<?=$gw['currency']?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Payment Description</label>
            <div id="gatewayDetails" class="p-2 mb-2" style="background:#232b3b;border-radius:0.6rem;color:#fff;min-height:36px;font-size:0.97em;"></div>
          </div>
          <div class="mb-2">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" class="form-control mb-2" id="amount" name="amount" min="0" step="0.01" required>
            <div id="amountRange" class="form-text mb-2"></div>
          </div>
          <div class="mb-2">
            <label for="proof" class="form-label">Proof of Payment</label>
            <input type="file" class="form-control" id="proof" name="proof" accept="image/*,application/pdf" required>
          </div>
        </div>
        <div class="modal-footer" style="border-bottom-left-radius:1.25rem;border-bottom-right-radius:1.25rem;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info">Submit Deposit</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
// Fallback: If modal does not open, open it manually
document.addEventListener('DOMContentLoaded', function() {
  var btn = document.querySelector('[data-bs-target="#depositModal"]');
  if (btn) {
    btn.addEventListener('click', function(e) {
      var modalEl = document.getElementById('depositModal');
      if (modalEl && typeof bootstrap !== 'undefined') {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      }
    });
  }
});
</script>
</body>
</html> 