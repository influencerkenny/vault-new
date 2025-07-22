<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: ../signin.php');
  exit;
}
require_once __DIR__ . '/../api/settings_helper.php';
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
$success = $error = '';

// Fetch enabled gateways
$gateways = $pdo->query('SELECT * FROM payment_gateways WHERE status="enabled" ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

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
    // Handle proof upload
    $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','pdf'];
    if (!in_array($ext, $allowed)) {
      $error = 'Invalid file type for proof.';
    } else {
      $fname = 'deposit_proof_' . time() . '_' . rand(1000,9999) . '.' . $ext;
      $dest = '../public/' . $fname;
      if (!move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) {
        $error = 'Failed to upload proof.';
      } else {
        $proof = $fname;
        // Insert deposit as pending
        $desc = "Deposit via {$gateway['name']} ({$gateway['currency']})";
        $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, status, description, created_at) VALUES (?, "deposit", ?, "pending", ?, NOW())');
        if ($stmt->execute([$user_id, $amount, $desc])) {
          // Add notification
          $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)')->execute([
            $user_id,
            'Deposit Submitted',
            'Your deposit request of ' . $amount . ' ' . $gateway['currency'] . ' is pending admin approval.'
          ]);
          // Placeholder for email (implement real email in production)
          // mail($user_email, 'Deposit Submitted', 'Your deposit request is pending approval.');
          $success = 'Deposit request submitted! Awaiting admin approval.';

          // Credit referral commission if this is the user's first deposit and they were referred
          $stmt2 = $pdo->prepare('SELECT referred_by FROM users WHERE id = ?');
          $stmt2->execute([$user_id]);
          $referred_by = $stmt2->fetchColumn();
          if ($referred_by) {
              // Check if this is the user's first completed deposit
              $stmt3 = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = ? AND type = "deposit" AND status = "completed"');
              $stmt3->execute([$user_id]);
              $deposit_count = $stmt3->fetchColumn();
              if ($deposit_count == 1) {
                  // Credit commission to referrer
                  $commission_rate = get_setting('referral_commission');
                  $commission = $amount * ($commission_rate / 100);
                  // Find referrer's user_id by username
                  $stmt4 = $pdo->prepare('SELECT id FROM users WHERE username = ?');
                  $stmt4->execute([$referred_by]);
                  $referrer_id = $stmt4->fetchColumn();
                  if ($referrer_id) {
                      $stmt5 = $pdo->prepare('INSERT INTO user_rewards (user_id, amount, type, created_at, note) VALUES (?, ?, "referral", NOW(), ?)');
                      $stmt5->execute([$referrer_id, $commission, 'Referral commission from user #' . $user_id]);
                      // Add commission to referrer's available balance
                      $stmt6 = $pdo->prepare('UPDATE user_balances SET available_balance = available_balance + ? WHERE user_id = ?');
                      $stmt6->execute([$commission, $referrer_id]);
                  }
              }
          }
        } else {
          $error = 'Failed to submit deposit.';
        }
      }
    }
  }
}
// After deposit is approved (simulate approval for demo)
if (isset($_GET['approve']) && isset($_GET['deposit_id'])) {
    $deposit_id = (int)$_GET['deposit_id'];
    // Fetch deposit and user info
    $stmt = $pdo->prepare('SELECT t.*, u.email, u.first_name, u.last_name, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.id = ? AND t.type = "deposit"');
    $stmt->execute([$deposit_id]);
    $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($deposit) {
        // Mark as approved
        $pdo->prepare('UPDATE transactions SET status = "completed" WHERE id = ?')->execute([$deposit_id]);
        // Prepare email
        $template = get_setting('email_template_deposit_approval');
        $replacements = [
            '{USER_NAME}' => $deposit['first_name'] . ' ' . $deposit['last_name'],
            '{AMOUNT}' => $deposit['amount'],
            '{DATE}' => date('Y-m-d H:i'),
        ];
        $body = strtr($template, $replacements);
        $subject = 'Deposit Approved';
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\n";
        mail($deposit['email'], $subject, $body, $headers);
    }
}
// Fetch deposit history
$stmt = $pdo->prepare('SELECT * FROM transactions WHERE user_id = ? AND type = "deposit" ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Deposit | Vault</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #0f172a; color: #e5e7eb; font-family: 'Inter', sans-serif; }
    .deposit-container { max-width: 500px; margin: 40px auto; background: #181f2a; border-radius: 18px; box-shadow: 0 4px 32px #0003; padding: 32px 18px 24px 18px; }
    .gateway-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; background: #232b3b; }
    .step-section { display: none; }
    .step-section.active { display: block; }
    .form-label { color: #60a5fa; font-weight: 500; }
    .form-control, .form-select { background: #232b3b; color: #e5e7eb; border: 1px solid #2563eb33; }
    .form-control:focus, .form-select:focus { border-color: #38bdf8; box-shadow: 0 0 0 2px #38bdf855; }
    .btn-info { background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%); border: none; }
    .btn-info:hover { background: linear-gradient(90deg, #0ea5e9 0%, #2563eb 100%); }
    .alert { font-size: 0.98rem; }
    .history-table { margin-top: 2.5rem; background: #151a23; border-radius: 1rem; box-shadow: 0 2px 12px #0003; }
    .history-table th, .history-table td { color: #e5e7eb; vertical-align: middle; }
    .history-table th { background: #232b3b; color: #60a5fa; font-weight: 700; }
    .badge.bg-success { background: #22c55e !important; color: #fff; }
    .badge.bg-danger { background: #ef4444 !important; color: #fff; }
    .badge.bg-warning { background: #f59e42 !important; color: #fff; }
    .badge.bg-secondary { background: #64748b !important; color: #fff; }
    .proof-link { color: #38bdf8; text-decoration: underline; }
    .proof-link:hover { color: #0ea5e9; }
  </style>
</head>
<body>
  <div class="deposit-container">
    <h2 class="mb-4 text-info fw-bold text-center">Deposit Funds</h2>
    <?php if ($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
    <form id="depositForm" method="post" enctype="multipart/form-data" autocomplete="off">
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
                <a href="../public/<?=htmlspecialchars($dep['proof'])?>" target="_blank" class="proof-link">View</a>
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
  <script>
    // Step logic
    let step = 1;
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    function showStep(n) {
      step1.classList.remove('active');
      step2.classList.remove('active');
      step3.classList.remove('active');
      if (n === 1) step1.classList.add('active');
      if (n === 2) step2.classList.add('active');
      if (n === 3) step3.classList.add('active');
      step = n;
    }
    showStep(1);
    // Gateway selection
    const gatewaySelect = document.getElementById('gateway_id');
    const gatewayDetails = document.getElementById('gatewayDetails');
    let selectedGateway = null;
    gatewaySelect.addEventListener('change', function() {
      const sel = this.options[this.selectedIndex];
      if (!sel.value) { gatewayDetails.innerHTML = ''; selectedGateway = null; return; }
      selectedGateway = {
        id: sel.value,
        name: sel.text,
        currency: sel.dataset.currency,
        rate: sel.dataset.rate,
        min: sel.dataset.min,
        max: sel.dataset.max,
        instructions: sel.dataset.instructions,
        userlabel: sel.dataset.userlabel,
        thumb: sel.dataset.thumb
      };
      gatewayDetails.innerHTML = `<b>Currency:</b> ${selectedGateway.currency} | <b>Rate:</b> 1 USD = ${selectedGateway.rate} ${selectedGateway.currency}<br>
        <b>Range:</b> ${selectedGateway.min} - ${selectedGateway.max} ${selectedGateway.currency}<br>
        <b>Instructions:</b> ${selectedGateway.instructions}` + (selectedGateway.userlabel ? `<br><b>Required:</b> ${selectedGateway.userlabel}` : '');
    });
    document.getElementById('toStep2').onclick = function() {
      if (!gatewaySelect.value) { gatewaySelect.focus(); return; }
      document.getElementById('amount').value = '';
      document.getElementById('amount').min = selectedGateway.min;
      document.getElementById('amount').max = selectedGateway.max;
      document.getElementById('amountRange').textContent = `Min: ${selectedGateway.min} | Max: ${selectedGateway.max} ${selectedGateway.currency}`;
      showStep(2);
    };
    document.getElementById('backTo1').onclick = function() { showStep(1); };
    document.getElementById('toStep3').onclick = function() {
      const amt = parseFloat(document.getElementById('amount').value);
      if (!amt || amt < parseFloat(selectedGateway.min) || amt > parseFloat(selectedGateway.max)) {
        document.getElementById('amount').focus(); return;
      }
      document.getElementById('previewDetails').innerHTML = `<div class='alert alert-info'><b>${selectedGateway.name}</b><br>You are requesting to deposit <b>${amt} ${selectedGateway.currency}</b>.<br>Please pay <b>${amt} ${selectedGateway.currency}</b> for successful payment.</div>
        <div class='mb-2'><b>Instructions:</b> ${selectedGateway.instructions}</div>` + (selectedGateway.userlabel ? `<div class='mb-2'><b>Required:</b> ${selectedGateway.userlabel}</div>` : '');
      showStep(3);
    };
    document.getElementById('backTo2').onclick = function() { showStep(2); };
  </script>
</body>
</html> 