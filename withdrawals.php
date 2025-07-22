<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];

// Fetch balances
$stmt = $pdo->prepare("SELECT available_balance FROM user_balances WHERE user_id = ?");
$stmt->execute([$user_id]);
$availableBalance = (float)($stmt->fetchColumn() ?: 0);
$stmt = $pdo->prepare("SELECT SUM(amount) FROM user_stake_profits WHERE user_id = ? AND status = 'withdrawable'");
$stmt->execute([$user_id]);
$withdrawableBalance = (float)($stmt->fetchColumn() ?: 0);

// Handle withdrawal request
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_amount'])) {
  $amount = (float)$_POST['withdraw_amount'];
  $wallet = trim($_POST['wallet_address'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  if ($amount <= 0 || $amount > $withdrawableBalance) {
    $error = 'Invalid withdrawal amount.';
  } elseif (empty($wallet)) {
    $error = 'Wallet address is required.';
  } else {
    // Insert withdrawal transaction (pending)
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, description, created_at) VALUES (?, 'withdrawal', ?, 'pending', ?, NOW())");
    $desc = 'Withdrawal to: ' . $wallet . ($notes ? ' | Notes: ' . $notes : '');
    if ($stmt->execute([$user_id, $amount, $desc])) {
      // Deduct from available balance
      $stmt = $pdo->prepare("UPDATE user_balances SET available_balance = available_balance - ? WHERE user_id = ?");
      $stmt->execute([$amount, $user_id]);
      $success = 'Withdrawal request submitted!';
      // Optionally, mark profits as withdrawn (not implemented here for simplicity)
    } else {
      $error = 'Failed to submit withdrawal.';
    }
  }
}

// After withdrawal is approved (simulate approval for demo)
if (isset($_GET['approve']) && isset($_GET['withdrawal_id'])) {
    $withdrawal_id = (int)$_GET['withdrawal_id'];
    // Fetch withdrawal and user info
    $stmt = $pdo->prepare('SELECT t.*, u.email, u.first_name, u.last_name, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.id = ? AND t.type = "withdrawal"');
    $stmt->execute([$withdrawal_id]);
    $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($withdrawal) {
        // Mark as approved
        $pdo->prepare('UPDATE transactions SET status = "completed" WHERE id = ?')->execute([$withdrawal_id]);
        // Prepare email
        $template = get_setting('email_template_withdrawal_approval');
        $replacements = [
            '{USER_NAME}' => $withdrawal['first_name'] . ' ' . $withdrawal['last_name'],
            '{AMOUNT}' => $withdrawal['amount'],
            '{DATE}' => date('Y-m-d H:i'),
        ];
        $body = strtr($template, $replacements);
        $subject = 'Withdrawal Approved';
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\n";
        mail($withdrawal['email'], $subject, $body, $headers);
    }
}

// Fetch withdrawal history
$stmt = $pdo->prepare("SELECT amount, status, description, created_at FROM transactions WHERE user_id = ? AND type = 'withdrawal' ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user info for sidebar/header
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

$displayName = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : 'Investor';
$email = $user ? $user['email'] : '';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Withdrawals | Vault</title>
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
    }
    .sidebar .logo { margin-bottom: 2rem; text-align: center; font-size: 0.95em; }
    .sidebar .nav-link { color: #cbd5e1; font-weight: 500; border-radius: 0.75rem; padding: 0.75rem 1rem; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.75rem; transition: background 0.2s, color 0.2s; position: relative; font-size: 0.95em; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: linear-gradient(90deg, #2563eb22 0%, #0ea5e922 100%); color: #38bdf8; box-shadow: 0 2px 8px 0 rgba(59,130,246,0.08); }
    .sidebar .logout-btn { color: #f87171; font-weight: 500; border-radius: 0.75rem; padding: 0.75rem 1rem; margin-top: auto; background: none; border: none; display: flex; align-items: center; gap: 0.75rem; transition: background 0.2s, color 0.2s; font-size: 0.95em; }
    .sidebar .logout-btn:hover { background: #7f1d1d22; color: #f87171; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; font-size: 0.93rem; }
    .dashboard-header { border-bottom: 1px solid #1e293b; padding: 1.5rem 2rem 1rem 2rem; background: rgba(17,24,39,0.85); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10; }
    .dashboard-header .logo { height: 48px; font-size: 0.95em; }
    .dashboard-header .back-link { color: #38bdf8; font-weight: 500; text-decoration: none; margin-left: 1.5rem; transition: color 0.2s; font-size: 0.95em; }
    .dashboard-header .back-link:hover { color: #0ea5e9; text-decoration: underline; }
    .dashboard-content-wrapper { max-width: 900px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.91rem; }
    .portfolio-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
    .portfolio-card { background: linear-gradient(135deg, #2563eb22 0%, #0ea5e922 100%); border: 1px solid #2563eb33; border-radius: 1.25rem; padding: 2rem 1.5rem 1.5rem 1.5rem; box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10); color: #e5e7eb; position: relative; min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; transition: box-shadow 0.2s, border 0.2s, background 0.2s; overflow: hidden; font-size: 0.91em; }
    .portfolio-card .card-title { font-size: 0.98rem; color: #a1a1aa; font-weight: 600; margin-bottom: 0.5rem; letter-spacing: 0.01em; }
    .portfolio-card .card-value { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem; letter-spacing: 0.01em; }
    .portfolio-card .card-footer { font-size: 0.93rem; color: #38d39f; font-weight: 500; }
    .withdraw-btn { font-size: 0.97rem; border-radius: 0.75rem; padding: 0.5rem 1.1rem; margin-top: 1rem; }
    .table-responsive { overflow-x: auto; }
    .table { min-width: 600px; font-size: 0.89rem; }
    .table th, .table td { padding: 0.28rem 0.35rem; }
    @media (max-width: 991px) {
      .sidebar { left: -260px; }
      .sidebar.active { left: 0; }
      .main-content { margin-left: 0; }
      .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; font-size: 0.89rem; }
    }
    @media (max-width: 767px) {
      .dashboard-content-wrapper { padding: 0 0.1rem; font-size: 0.87rem; }
      .portfolio-cards { grid-template-columns: 1fr; gap: 0.7rem; }
      .portfolio-card { padding: 1rem 0.5rem 0.8rem 0.5rem; min-height: 150px; font-size: 0.89em; }
    }
    @media (max-width: 575px) {
      .dashboard-content-wrapper { padding: 0 0.05rem; font-size: 0.85rem; }
      .portfolio-card { padding: 0.5rem 0.1rem 0.5rem 0.1rem; min-height: 100px; font-size: 0.87em; }
      .table { font-size: 0.85rem; min-width: 480px; }
      .table th, .table td { padding: 0.22rem 0.28rem; }
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
      padding: 1.2rem;
      background: rgba(17,24,39,0.85);
      color: #a1a1aa;
      text-align: center;
      margin-top: auto;
      font-size: 0.89em;
    }
  </style>
</head>
<body>
  <?php include 'user/sidebar.php'; ?>
  <!-- Mobile Sidebar Overlay (after sidebar) -->
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'user/header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="dashboard-content-wrapper mx-auto">
        <h2 class="mb-4 text-info fw-bold">Withdraw Funds</h2>
        <?php if ($success): ?><div class="alert alert-success" id="withdrawSuccess"><?=$success?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger" id="withdrawError"><?=$error?></div><?php endif; ?>
        <!-- Portfolio cards row -->
        <div class="portfolio-cards mb-4">
          <div class="portfolio-card total">
            <div class="card-title">Available Balance</div>
            <div class="card-value">SOL <?=number_format($availableBalance,2)?></div>
            <div class="card-footer">Total available for all actions</div>
          </div>
          <div class="portfolio-card withdrawable">
            <div class="card-title">Withdrawable</div>
            <div class="card-value">SOL <?=number_format($withdrawableBalance,2)?></div>
            <div class="card-footer">Available to withdraw</div>
          </div>
        </div>
        <div class="text-center mb-4">
          <button class="btn btn-info withdraw-btn fw-bold px-4 py-2" data-bs-toggle="modal" data-bs-target="#withdrawModal"><i class="bi bi-upload me-2"></i>Request Withdrawal</button>
        </div>
        <h3 class="mb-3 text-info fw-bold">Withdrawal History</h3>
        <div class="table-responsive mb-5" style="border-radius: 1rem; overflow: hidden; background: #111827cc;">
          <table class="table table-dark table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($withdrawals as $w): ?>
              <tr>
                <td>SOL <?=number_format($w['amount'],2)?></td>
                <td>
                  <?php if ($w['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                  <?php elseif ($w['status'] === 'completed'): ?>
                    <span class="badge bg-success">Successful</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Failed</span>
                  <?php endif; ?>
                </td>
                <td><?=date('M d, Y H:i', strtotime($w['created_at']))?></td>
                <td><?=htmlspecialchars($w['description'])?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!count($withdrawals)): ?><tr><td colspan="4" class="text-center text-muted">No withdrawals yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
    <?php include 'user/footer.php'; ?>
  </div>
  <!-- Withdraw Modal -->
  <div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white rounded-4">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="withdrawModalLabel">Withdraw Funds</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="withdrawForm" method="post" autocomplete="off">
          <div class="modal-body">
            <div class="mb-3">
              <label for="withdraw_amount" class="form-label">Amount</label>
              <input type="number" min="1" max="<?=number_format($withdrawableBalance,2,'.','')?>" step="0.01" class="form-control" id="withdraw_amount" name="withdraw_amount" placeholder="Enter amount" required>
            </div>
            <div class="mb-3">
              <label for="wallet_address" class="form-label">Wallet Address</label>
              <input type="text" class="form-control" id="wallet_address" name="wallet_address" placeholder="Enter your wallet address" required>
            </div>
            <div class="mb-3">
              <label for="notes" class="form-label">Notes (optional)</label>
              <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
            </div>
            <div id="withdrawSuccessModal" class="alert alert-success d-none">Withdrawal request submitted!</div>
            <div id="withdrawErrorModal" class="alert alert-danger d-none"></div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-info">Withdraw</button>
          </div>
        </form>
      </div>
    </div>
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
    // Withdraw Modal AJAX (optional, progressive enhancement)
    const withdrawForm = document.getElementById('withdrawForm');
    if (withdrawForm) {
      withdrawForm.addEventListener('submit', function(e) {
        // If you want AJAX, uncomment below and prevent default
        // e.preventDefault();
        // const btn = withdrawForm.querySelector('button[type=submit]');
        // btn.disabled = true;
        // document.getElementById('withdrawSuccessModal').classList.add('d-none');
        // document.getElementById('withdrawErrorModal').classList.add('d-none');
        // const formData = new FormData(withdrawForm);
        // fetch(window.location.href, {
        //   method: 'POST',
        //   body: formData
        // })
        // .then(res => res.json())
        // .then(data => {
        //   if (data.success) {
        //     document.getElementById('withdrawSuccessModal').classList.remove('d-none');
        //     setTimeout(() => {
        //       document.getElementById('withdrawSuccessModal').classList.add('d-none');
        //       const modal = bootstrap.Modal.getInstance(document.getElementById('withdrawModal'));
        //       modal.hide();
        //       withdrawForm.reset();
        //       window.location.reload();
        //     }, 1500);
        //   } else {
        //     document.getElementById('withdrawErrorModal').textContent = data.error || 'Withdrawal failed.';
        //     document.getElementById('withdrawErrorModal').classList.remove('d-none');
        //   }
        // })
        // .catch(() => {
        //   document.getElementById('withdrawErrorModal').textContent = 'Withdrawal failed.';
        //   document.getElementById('withdrawErrorModal').classList.remove('d-none');
        // })
        // .finally(() => { btn.disabled = false; });
      });
    }
  </script>
</body>
</html> 