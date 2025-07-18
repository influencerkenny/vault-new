<?php
session_start();
require_once '../api/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

function calculateUserBalance($pdo, $user_id) {
    // Total Deposits (sum of all completed deposits)
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total_deposits FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'completed'");
    $stmt->execute([$user_id]);
    $totalDeposits = 0.00;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalDeposits = (float)$row['total_deposits'] ?: 0.00;
    }

    // Staked Amount (sum of all active stakes)
    $stmt = $pdo->prepare("SELECT SUM(amount) AS staked FROM user_stakes WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $stakedAmount = 0.00;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stakedAmount = (float)$row['staked'] ?: 0.00;
    }

    // Total Withdrawals (sum of all completed withdrawals)
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total_withdrawals FROM transactions WHERE user_id = ? AND type = 'withdrawal' AND status = 'completed'");
    $stmt->execute([$user_id]);
    $totalWithdrawals = 0.00;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalWithdrawals = (float)$row['total_withdrawals'] ?: 0.00;
    }

    // Total Rewards
    $stmt = $pdo->prepare('SELECT SUM(amount) AS rewards FROM user_rewards WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $totalRewards = 0.00;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalRewards = (float)$row['rewards'] ?: 0.00;
    }

    // Calculate Available Balance: Total Deposits - Staked Amount - Total Withdrawals + Total Rewards
    $availableBalance = $totalDeposits - $stakedAmount - $totalWithdrawals + $totalRewards;

    // Ensure available balance doesn't go negative
    if ($availableBalance < 0) {
        $availableBalance = 0.00;
    }

    return [
        'total_deposits' => $totalDeposits,
        'staked_amount' => $stakedAmount,
        'total_withdrawals' => $totalWithdrawals,
        'total_rewards' => $totalRewards,
        'available_balance' => $availableBalance
    ];
}

$action_success = $action_error = '';
// Handle Approve/Reject actions
if (isset($_POST['action'], $_POST['id']) && in_array($_POST['action'], ['approve', 'reject'])) {
    $depositId = intval($_POST['id']);
    $action = $_POST['action'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND type = "deposit"');
        $stmt->execute([$depositId]);
        $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$deposit) throw new Exception('Deposit not found.');
        if ($deposit['status'] !== 'pending') throw new Exception('Deposit already processed.');
        $userId = $deposit['user_id'];
        $amount = $deposit['amount'];
        if ($action === 'approve') {
            $stmt = $pdo->prepare('UPDATE transactions SET status = "completed" WHERE id = ?');
            $stmt->execute([$depositId]);
            
            // Update user balance using the new calculation system
            $balance = calculateUserBalance($pdo, $userId);
            $stmt = $pdo->prepare('INSERT INTO user_balances (user_id, available_balance, total_deposits, staked_amount, total_withdrawals, total_rewards, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, NOW()) 
                                   ON DUPLICATE KEY UPDATE 
                                   available_balance = VALUES(available_balance),
                                   total_deposits = VALUES(total_deposits),
                                   staked_amount = VALUES(staked_amount),
                                   total_withdrawals = VALUES(total_withdrawals),
                                   total_rewards = VALUES(total_rewards),
                                   updated_at = NOW()');
            $stmt->execute([
                $userId,
                $balance['available_balance'],
                $balance['total_deposits'],
                $balance['staked_amount'],
                $balance['total_withdrawals'],
                $balance['total_rewards']
            ]);
            
            // Send notification to user
            $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
            $stmt->execute([
                $userId,
                'Deposit Approved',
                'Your deposit of ' . number_format($amount, 2) . ' has been approved and credited to your account.'
            ]);
            
            $action_success = 'Deposit approved and user credited.';
        } else {
            $stmt = $pdo->prepare('UPDATE transactions SET status = "failed" WHERE id = ?');
            $stmt->execute([$depositId]);
            
            // Send notification to user
            $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
            $stmt->execute([
                $userId,
                'Deposit Declined',
                'Your deposit of ' . number_format($amount, 2) . ' has been declined. Please contact support for more information.'
            ]);
            
            $action_success = 'Deposit rejected.';
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $action_error = $e->getMessage();
    }
}

// Fetch all deposits
$stmt = $pdo->query('SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type = "deposit" ORDER BY t.created_at DESC');
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);

function parse_payment_method($desc) {
    if (preg_match('/Deposit via ([^ ]+) \(([^)]+)\)/i', $desc, $m)) {
        return htmlspecialchars($m[1] . ' (' . $m[2] . ')');
    }
    return 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Deposits</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../styles/globals.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; }
    .deposits-container { max-width: 950px; width: 100%; margin: 10px auto; background: #181f2a; border-radius: 18px; box-shadow: 0 4px 32px #0003; padding: 24px 8px 18px 8px; }
    .table-responsive { border-radius: 1rem; overflow-x: auto; }
    table.table { min-width: 900px; border-radius: 1rem; overflow: hidden; background: #151a23; }
    table.table th, table.table td { vertical-align: middle; }
    table.table th { background: #232b3b; color: #60a5fa; font-weight: 700; position: sticky; top: 0; z-index: 2; }
    table.table tr:nth-child(even) { background: #181f2a; }
    table.table tr:nth-child(odd) { background: #151a23; }
    table.table td, table.table th { padding: 12px 8px; }
    .badge.bg-success { background: #22c55e !important; color: #fff; }
    .badge.bg-secondary { background: #64748b !important; color: #fff; }
    .badge.bg-danger { background: #ef4444 !important; color: #fff; }
    .badge.bg-warning { background: #f59e42 !important; color: #fff; }
    .btn-sm { font-size: 0.97rem; padding: 5px 14px; border-radius: 6px; }
    @media (max-width: 991px) { 
      .main-content { margin-left: 0; } 
      .sidebar { left: -260px; } 
      .sidebar.active { left: 0; } 
      .deposits-container { margin: 8px 2px; padding: 16px; } 
    }
    @media (max-width: 700px) { 
      .deposits-container { padding: 12px; margin: 4px; } 
      table.table { min-width: 700px; font-size: 0.97rem; } 
      table.table th, table.table td { padding: 8px 2px; } 
    }
    @media (max-width: 575px) { 
      .deposits-container { padding: 8px; margin: 2px; } 
      table.table { font-size: 0.91rem; min-width: 480px; } 
      table.table th, table.table td { padding: 0.5rem 0.25rem; } 
      .btn-sm { font-size: 0.85rem; padding: 4px 8px; }
    }
    .sidebar { background: rgba(10,16,30,0.95); border-right: 1px solid #1e293b; min-height: 100vh; width: 260px; position: fixed; top: 0; left: 0; z-index: 2001; padding: 2rem 1.5rem 1.5rem 1.5rem; display: flex; flex-direction: column; transition: left 0.3s; }
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
  <?php include 'sidebar.php'; ?>
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'header.php'; ?>
    <div class="deposits-container">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-2">
        <h1 class="text-info fw-bold mb-0">User Deposits</h1>
      </div>
      <?php if ($action_success): ?><div class="alert alert-success"><?=htmlspecialchars($action_success)?></div><?php endif; ?>
      <?php if ($action_error): ?><div class="alert alert-danger"><?=htmlspecialchars($action_error)?></div><?php endif; ?>
      <div class="table-responsive mb-5">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Amount</th>
              <th>Payment Method</th>
              <th>Proof of Payment</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($deposits as $dep): ?>
            <tr>
              <td><?=$dep['id']?></td>
              <td><?=htmlspecialchars($dep['username'])?> (<?=$dep['user_id']?>)</td>
              <td><?=number_format($dep['amount'],2)?></td>
              <td><?=parse_payment_method($dep['description'])?></td>
              <td>
                <?php if ($dep['proof']): ?>
                  <a href="../public/<?=htmlspecialchars($dep['proof'])?>" target="_blank" class="btn btn-sm btn-info">View Proof</a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($dep['status'] === 'pending'): ?>
                  <span class="badge bg-warning text-dark">Pending</span>
                <?php elseif ($dep['status'] === 'completed'): ?>
                  <span class="badge bg-success">Approved</span>
                <?php else: ?>
                  <span class="badge bg-danger">Rejected</span>
                <?php endif; ?>
              </td>
              <td><?=date('Y-m-d H:i', strtotime($dep['created_at']))?></td>
              <td>
                <?php if ($dep['status'] === 'pending'): ?>
                <div class="dropdown">
                  <button class="btn btn-sm btn-info dropdown-toggle" type="button" id="dropdownMenuButtonDep<?=$dep['id']?>" data-bs-toggle="dropdown" aria-expanded="false">
                    Actions
                  </button>
                  <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="dropdownMenuButtonDep<?=$dep['id']?>">
                    <li>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="id" value="<?=$dep['id']?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="dropdown-item text-success" type="submit" onclick="return confirm('Approve this deposit?')"><i class="bi bi-check-circle me-2"></i>Approve</button>
                      </form>
                    </li>
                    <li>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="id" value="<?=$dep['id']?>">
                        <input type="hidden" name="action" value="reject">
                        <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Reject this deposit?')"><i class="bi bi-x-circle me-2"></i>Reject</button>
                      </form>
                    </li>
                  </ul>
                </div>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php include 'footer.php'; ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle logic (reuse from other admin pages)
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
  </script>
</body>
</html> 