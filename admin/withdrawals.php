<?php
session_start();
require_once '../api/config.php';
// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
$success = $error = '';
// Handle withdrawal status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdrawal_id'], $_POST['action'])) {
    $withdrawal_id = (int)$_POST['withdrawal_id'];
    $action = $_POST['action'];
    // Fetch withdrawal info
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND type = "withdrawal"');
    $stmt->execute([$withdrawal_id]);
    $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($withdrawal && $withdrawal['status'] === 'pending') {
        $user_id = $withdrawal['user_id'];
        $amount = (float)$withdrawal['amount'];
        if ($action === 'complete') {
            // Deduct amount from user balance if not already deducted
            $stmt = $pdo->prepare('UPDATE user_balances SET available_balance = GREATEST(0, available_balance - ?) WHERE user_id = ?');
            $stmt->execute([$amount, $user_id]);
            $stmt = $pdo->prepare('UPDATE transactions SET status = "completed" WHERE id = ? AND type = "withdrawal"');
            if ($stmt->execute([$withdrawal_id])) {
                $success = 'Withdrawal marked as completed and amount deducted.';
            } else {
                $error = 'Failed to update withdrawal.';
            }
        } elseif ($action === 'fail') {
            // Return amount to user balance
            $stmt = $pdo->prepare('UPDATE user_balances SET available_balance = available_balance + ? WHERE user_id = ?');
            $stmt->execute([$amount, $user_id]);
            $stmt = $pdo->prepare('UPDATE transactions SET status = "failed" WHERE id = ? AND type = "withdrawal"');
            if ($stmt->execute([$withdrawal_id])) {
                $success = 'Withdrawal marked as failed and amount returned to user.';
            } else {
                $error = 'Failed to update withdrawal.';
            }
        }
    } else {
        $error = 'Withdrawal not found or already processed.';
    }
}
// Handle filters and search
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = [];
$params = [];
if ($status && $status !== 'all') {
    $where[] = 't.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(u.username LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$where_sql = $where ? 'WHERE t.type = "withdrawal" AND ' . implode(' AND ', $where) : 'WHERE t.type = "withdrawal"';
// Fetch filtered withdrawal transactions with user info
$stmt = $pdo->prepare('SELECT t.*, u.username, u.email FROM transactions t JOIN users u ON t.user_id = u.id ' . $where_sql . ' ORDER BY t.created_at DESC');
$stmt->execute($params);
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch summary stats
$total_withdrawals = $pdo->query('SELECT SUM(amount) FROM transactions WHERE type = "withdrawal" AND status = "completed"')->fetchColumn();
$total_pending = $pdo->query('SELECT SUM(amount) FROM transactions WHERE type = "withdrawal" AND status = "pending"')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Withdrawals</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../styles/globals.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; font-size: 0.93rem; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; font-size: 0.93rem; }
    .dashboard-content-wrapper { max-width: 1100px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.91rem; }
    .withdrawals-table { margin-top: 2.5rem; background: #151a23; border-radius: 1rem; box-shadow: 0 2px 12px #0003; overflow: hidden; font-size: 0.91em; }
    .withdrawals-table th, .withdrawals-table td { color: #f3f4f6; vertical-align: middle; font-size: 0.97rem; padding: 0.7rem 0.8rem; }
    .withdrawals-table th { background: #232b3b; color: #38bdf8; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; border-bottom: 2px solid #2563eb33; font-size: 0.95em; }
    .withdrawals-table td { font-weight: 600; color: #e0e7ef; background: #181f2a; border-bottom: 1px solid #232b3b; font-size: 0.95em; }
    .withdrawals-table tr:nth-child(even) td { background: #1e2330; }
    .withdrawals-table tr:hover td { background: #232b3b; color: #fff; transition: background 0.18s, color 0.18s; }
    .withdrawals-table tr:last-child td { border-bottom: none; }
    .badge.bg-success { background: #22c55e !important; color: #fff !important; font-weight: 700; font-size: 0.95em; }
    .badge.bg-danger { background: #ef4444 !important; color: #fff !important; font-weight: 700; font-size: 0.95em; }
    .badge.bg-warning { background: #fbbf24 !important; color: #181f2a !important; font-weight: 700; font-size: 0.95em; }
    .badge.bg-secondary { background: #64748b !important; color: #fff !important; font-weight: 700; font-size: 0.95em; }
    .action-btn { font-size: 0.93em; border-radius: 0.5rem; padding: 0.3rem 0.9rem; margin-right: 0.3rem; }
    @media (max-width: 991px) { .main-content { margin-left: 0; } .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; font-size: 0.89rem; } }
    @media (max-width: 767px) { .dashboard-content-wrapper { padding: 0 0.1rem; font-size: 0.87rem; } }
    @media (max-width: 575px) { .dashboard-content-wrapper { padding: 0 0.05rem; font-size: 0.85rem; } .withdrawals-table { font-size: 0.87em; } .withdrawals-table th, .withdrawals-table td { padding: 0.35rem 0.18rem; font-size: 0.87em; } }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <?php include 'header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="dashboard-content-wrapper mx-auto">
        <h2 class="mb-4 text-info fw-bold text-center">Withdrawal Requests</h2>
        <div class="row mb-3">
          <div class="col-md-4 mb-2">
            <form method="get" class="d-flex align-items-center gap-2">
              <input type="text" name="search" class="form-control form-control-sm" placeholder="Search username or email" value="<?=htmlspecialchars($search)?>">
              <button type="submit" class="btn btn-info btn-sm"><i class="bi bi-search"></i></button>
            </form>
          </div>
          <div class="col-md-3 mb-2">
            <form method="get">
              <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="all"<?=$status===''||$status==='all'?' selected':''?>>All Statuses</option>
                <option value="pending"<?=$status==='pending'?' selected':''?>>Pending</option>
                <option value="completed"<?=$status==='completed'?' selected':''?>>Completed</option>
                <option value="failed"<?=$status==='failed'?' selected':''?>>Failed</option>
              </select>
              <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?=htmlspecialchars($search)?>"><?php endif; ?>
            </form>
          </div>
          <div class="col-md-5 text-end">
            <div class="fw-bold text-info">Total Withdrawn: <span class="text-white">SOL <?=number_format($total_withdrawals,2)?></span></div>
            <div class="fw-bold text-warning">Total Pending: <span class="text-white">SOL <?=number_format($total_pending,2)?></span></div>
          </div>
        </div>
        <?php if ($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
        <div class="table-responsive">
          <table class="table withdrawals-table table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Description</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($withdrawals as $w): ?>
              <tr>
                <td><?=htmlspecialchars($w['username'])?></td>
                <td><?=htmlspecialchars($w['email'])?></td>
                <td>SOL <?=number_format($w['amount'],2)?></td>
                <td>
                  <?php if ($w['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                  <?php elseif ($w['status'] === 'completed'): ?>
                    <span class="badge bg-success">Completed</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Failed</span>
                  <?php endif; ?>
                </td>
                <td><?=date('Y-m-d H:i', strtotime($w['created_at']))?></td>
                <td><?=htmlspecialchars($w['description'])?></td>
                <td>
                  <?php if ($w['status'] === 'pending'): ?>
                  <form method="post" style="display:inline-block;">
                    <input type="hidden" name="withdrawal_id" value="<?=$w['id']?>">
                    <button type="submit" name="action" value="complete" class="btn btn-success btn-sm action-btn" onclick="return confirm('Mark as completed?')"><i class="bi bi-check-circle"></i></button>
                    <button type="submit" name="action" value="fail" class="btn btn-danger btn-sm action-btn" onclick="return confirm('Mark as failed?')"><i class="bi bi-x-circle"></i></button>
                  </form>
                  <?php else: ?>
                    <span class="text-secondary">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!count($withdrawals)): ?><tr><td colspan="7" class="text-center text-muted">No withdrawal requests found.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html> 