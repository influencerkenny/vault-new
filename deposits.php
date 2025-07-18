<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
$success = $error = '';

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
    
    // Refresh gateways after adding default
    $gateways = $pdo->query('SELECT * FROM payment_gateways WHERE status="enabled" ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    // If insertion fails, continue with empty gateways
    error_log('Failed to create default gateway: ' . $e->getMessage());
  }
}
// Fetch user info for sidebar/header
$stmt = $pdo->prepare('SELECT first_name, last_name, email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$displayName = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : 'Investor';
$email = $user ? $user['email'] : '';
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
          $success = 'Deposit request submitted! Awaiting admin approval.';
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
  <link rel="stylesheet" href="styles/globals.css">
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
    .deposit-container { max-width: 1050px; margin: 40px auto; background: #181f2a; border-radius: 18px; box-shadow: 0 4px 32px #0003; padding: 32px 32px 24px 32px; }
    .gateway-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; background: #232b3b; }
    .step-section { display: none; }
    .step-section.active { display: block; }
    .form-label { color: #60a5fa; font-weight: 500; }
    .form-control, .form-select { background: #232b3b; color: #e5e7eb; border: 1px solid #2563eb33; }
    .form-control:focus, .form-select:focus { border-color: #38bdf8; box-shadow: 0 0 0 2px #38bdf855; }
    .btn-info { background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%); border: none; }
    .btn-info:hover { background: linear-gradient(90deg, #0ea5e9 0%, #2563eb 100%); }
    .alert { font-size: 0.98rem; }
    .history-table { margin-top: 2.5rem; background: #151a23; border-radius: 1rem; box-shadow: 0 2px 12px #0003; overflow: hidden; }
    .history-table th, .history-table td { color: #f3f4f6; vertical-align: middle; font-size: 1.07rem; padding: 1.1rem 1.2rem; }
    .history-table th { background: #232b3b; color: #38bdf8; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; border-bottom: 2px solid #2563eb33; }
    .history-table td { font-weight: 600; color: #e0e7ef; background: #181f2a; border-bottom: 1px solid #232b3b; }
    .history-table tr:nth-child(even) td { background: #1e2330; }
    .history-table tr:hover td { background: #232b3b; color: #fff; transition: background 0.18s, color 0.18s; }
    .history-table tr:last-child td { border-bottom: none; }
    .user-profile-header { display: flex; align-items: center; gap: 1rem; }
    .user-profile-header img { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #38bdf8; }
    .user-profile-header .profile-info { line-height: 1.2; }
    .user-profile-header .profile-name { font-size: 1.15rem; font-weight: 700; color: #38bdf8; }
    .user-profile-header .profile-email { font-size: 0.98rem; color: #a1a1aa; }
    .badge.bg-success { background: #22c55e !important; color: #fff !important; font-weight: 700; }
    .badge.bg-danger { background: #ef4444 !important; color: #fff !important; font-weight: 700; }
    .badge.bg-warning { background: #fbbf24 !important; color: #181f2a !important; font-weight: 700; }
    .badge.bg-secondary { background: #64748b !important; color: #fff !important; font-weight: 700; }
    .proof-link { color: #60a5fa; text-decoration: underline; font-weight: 600; }
    .proof-link:hover { color: #38bdf8; }
    @media (max-width: 991px) { 
      .main-content { margin-left: 0; } 
      .sidebar { left: -260px; } 
      .sidebar.active { left: 0; } 
      .deposit-container { margin: 8px 2px; padding: 16px; } 
    }
    @media (max-width: 700px) { 
      .deposit-container { padding: 12px; margin: 4px; } 
      .dashboard-header { padding: 1rem; }
      .user-profile-header { flex-direction: column; gap: 0.5rem; text-align: center; }
      .user-profile-header img { width: 40px; height: 40px; }
      .user-profile-header .profile-name { font-size: 1rem; }
      .user-profile-header .profile-email { font-size: 0.9rem; }
    }
    @media (max-width: 575px) { 
      .deposit-container { padding: 8px; margin: 2px; } 
      .dashboard-header { padding: 0.75rem; }
      .dashboard-header .logo { height: 36px; }
      .back-link { font-size: 0.9rem; }
      .history-table { font-size: 0.9rem; }
      .history-table th, .history-table td { padding: 0.5rem 0.25rem; }
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
    <header class="dashboard-header d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <!-- Hamburger for mobile -->
        <button class="btn btn-outline-info d-lg-none me-3" id="sidebarToggle" aria-label="Open sidebar">
          <i class="bi bi-list" style="font-size:1.7rem;"></i>
        </button>
        <img src="/vault-logo-new.png" alt="Vault Logo" class="logo me-3">
        <a href="user-dashboard.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
      </div>
      <div class="user-profile-header">
        <img src="<?=$avatar?>" alt="Profile">
        <div class="profile-info">
          <div class="profile-name"><?=htmlspecialchars($displayName)?></div>
          <div class="profile-email"><?=htmlspecialchars($email)?></div>
        </div>
      </div>
    </header>
    <main class="flex-grow-1 p-4">
      <div class="deposit-container mx-auto">
        <h2 class="mb-4 text-info fw-bold text-center">Deposit Funds</h2>
        <?php if ($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
        <?php if (empty($gateways)): ?>
          <div class="alert alert-warning text-center">No payment methods are currently available. Please contact support.</div>
        <?php endif; ?>
        <form id="depositForm" method="post" enctype="multipart/form-data" autocomplete="off"<?=empty($gateways)?' style="pointer-events:none;opacity:0.6;"':''?>>
          <!-- Step 1: Select Gateway -->
          <div class="step-section" id="step1">
            <label for="gateway_id" class="form-label">Select Payment Method</label>
            <select class="form-select mb-3" id="gateway_id" name="gateway_id" required>
              <option value="">Choose a gateway</option>
              <?php foreach ($gateways as $gw): ?>
                <option value="<?=$gw['id']?>" data-currency="<?=$gw['currency']?>" data-rate="<?=$gw['rate_to_usd']?>" data-min="<?=$gw['min_amount']?>" data-max="<?=$gw['max_amount']?>" data-instructions="<?=htmlspecialchars($gw['instructions'])?>" data-userlabel="<?=htmlspecialchars($gw['user_data_label'])?>" data-thumb="<?=$gw['thumbnail']?>">
                  <?=$gw['name']?> (<?=$gw['currency']?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div id="gatewayDetails" class="form-text mb-3"></div>
            <button type="button" class="btn btn-info w-100" id="toStep2">Continue</button>
          </div>
          <!-- Step 2: Amount -->
          <div class="step-section" id="step2">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" class="form-control mb-2" id="amount" name="amount" min="0" step="0.01" required>
            <div id="amountRange" class="form-text mb-2"></div>
            <button type="button" class="btn btn-secondary w-100 mb-2" id="backTo1">Back</button>
            <button type="button" class="btn btn-info w-100" id="toStep3">Continue</button>
          </div>
          <!-- Step 3: Preview & Proof -->
          <div class="step-section" id="step3">
            <div class="mb-3" id="previewDetails"></div>
            <div class="mb-3">
              <label for="proof" class="form-label">Upload Proof of Payment</label>
              <input type="file" class="form-control" id="proof" name="proof" accept="image/*,application/pdf" required>
            </div>
            <button type="button" class="btn btn-secondary w-100 mb-2" id="backTo2">Back</button>
            <button type="submit" class="btn btn-info w-100">Pay Now</button>
          </div>
        </form>
        <!-- Deposit History Table -->
        <h4 class="mt-5 mb-3 text-info fw-bold text-center">Deposit History</h4>
        <div class="table-responsive">
          <table class="table history-table table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Date</th>
                <th>Proof</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($deposits as $dep): ?>
              <tr>
                <td><?=number_format($dep['amount'],2)?></td>
                <td><?=htmlspecialchars($dep['description'])?></td>
                <td>
                  <?php if ($dep['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                  <?php elseif ($dep['status'] === 'completed'): ?>
                    <span class="badge bg-success">Successful</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Rejected</span>
                  <?php endif; ?>
                </td>
                <td><?=date('Y-m-d H:i', strtotime($dep['created_at']))?></td>
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
      </div>
    </main>
    <footer class="dashboard-footer mt-4 text-center">
      &copy; <?=date('Y')?> Vault. All rights reserved.
    </footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
  <script defer>
    // Mobile sidebar toggle/overlay
    var sidebar = document.getElementById('sidebar');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    var sidebarToggle = document.getElementById('sidebarToggle');
    function openSidebar() { sidebar.classList.add('active'); sidebarOverlay.classList.add('active'); }
    function closeSidebar() { sidebar.classList.remove('active'); sidebarOverlay.classList.remove('active'); }
    if (sidebarToggle) { sidebarToggle.addEventListener('click', openSidebar); }
    if (sidebarOverlay) { sidebarOverlay.addEventListener('click', closeSidebar); }
    document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
      link.addEventListener('click', function() { if (window.innerWidth < 992) closeSidebar(); });
    });

    // Deposit form functionality
    document.addEventListener('DOMContentLoaded', function() {
      // Show first step by default
      document.getElementById('step1').classList.add('active');
      
      // Gateway selection change
      document.getElementById('gateway_id').addEventListener('change', function() {
        var selected = this.options[this.selectedIndex];
        var details = document.getElementById('gatewayDetails');
        if (selected.value) {
          details.innerHTML = `
            <div class="alert alert-info">
              <strong>${selected.text}</strong><br>
              Min: ${selected.dataset.min} ${selected.dataset.currency}<br>
              Max: ${selected.dataset.max} ${selected.dataset.currency}<br>
              Rate: 1 USD = ${selected.dataset.rate} ${selected.dataset.currency}
            </div>
          `;
        } else {
          details.innerHTML = '';
        }
      });

      // Step navigation
      document.getElementById('toStep2').addEventListener('click', function() {
        if (document.getElementById('gateway_id').value) {
          document.getElementById('step1').classList.remove('active');
          document.getElementById('step2').classList.add('active');
          updateAmountRange();
        }
      });

      document.getElementById('backTo1').addEventListener('click', function() {
        document.getElementById('step2').classList.remove('active');
        document.getElementById('step1').classList.add('active');
      });

      document.getElementById('toStep3').addEventListener('click', function() {
        if (document.getElementById('amount').value) {
          document.getElementById('step2').classList.remove('active');
          document.getElementById('step3').classList.add('active');
          updatePreview();
        }
      });

      document.getElementById('backTo2').addEventListener('click', function() {
        document.getElementById('step3').classList.remove('active');
        document.getElementById('step2').classList.add('active');
      });

      // Amount input change
      document.getElementById('amount').addEventListener('input', updateAmountRange);

      function updateAmountRange() {
        var selected = document.getElementById('gateway_id').options[document.getElementById('gateway_id').selectedIndex];
        var amount = document.getElementById('amount').value;
        var range = document.getElementById('amountRange');
        
        if (selected.value && amount) {
          var min = parseFloat(selected.dataset.min);
          var max = parseFloat(selected.dataset.max);
          var current = parseFloat(amount);
          
          if (current < min || current > max) {
            range.innerHTML = `<div class="alert alert-danger">Amount must be between ${min} and ${max} ${selected.dataset.currency}</div>`;
          } else {
            range.innerHTML = `<div class="alert alert-success">Amount is within valid range</div>`;
          }
        }
      }

      function updatePreview() {
        var selected = document.getElementById('gateway_id').options[document.getElementById('gateway_id').selectedIndex];
        var amount = document.getElementById('amount').value;
        var preview = document.getElementById('previewDetails');
        
        if (selected.value && amount) {
          preview.innerHTML = `
            <div class="alert alert-info">
              <h6>Deposit Summary</h6>
              <strong>Method:</strong> ${selected.text}<br>
              <strong>Amount:</strong> ${amount} ${selected.dataset.currency}<br>
              <strong>Instructions:</strong> ${selected.dataset.instructions}
            </div>
          `;
        }
      }
    });
  </script>
</body>
</html> 