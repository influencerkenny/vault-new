<?php
session_start();
require_once '../api/config.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit;
}
$alert = '';
// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['tx_id'])) {
  $tx_id = intval($_POST['tx_id']);
  $action = $_POST['action'];
  $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ?');
  $stmt->execute([$tx_id]);
  $tx = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($tx && $tx['status'] === 'pending') {
    if ($action === 'approve') {
      if ($tx['type'] === 'deposit') {
        $stmt = $pdo->prepare('UPDATE user_balances SET available_balance = available_balance + ? WHERE user_id = ?');
        $stmt->execute([$tx['amount'], $tx['user_id']]);
      } elseif ($tx['type'] === 'withdrawal') {
        $stmt = $pdo->prepare('UPDATE user_balances SET available_balance = GREATEST(0, available_balance - ?) WHERE user_id = ?');
        $stmt->execute([$tx['amount'], $tx['user_id']]);
      }
      $pdo->prepare('UPDATE transactions SET status = "completed" WHERE id = ?')->execute([$tx_id]);
      $alert = '<div class="alert alert-success">Transaction approved and user balance updated.</div>';
    } elseif ($action === 'reject') {
      // On reject, return amount for deposit, do nothing for withdrawal
      if ($tx['type'] === 'deposit') {
        $stmt = $pdo->prepare('UPDATE user_balances SET available_balance = GREATEST(0, available_balance - ?) WHERE user_id = ?');
        $stmt->execute([$tx['amount'], $tx['user_id']]);
      } elseif ($tx['type'] === 'withdrawal') {
        $stmt = $pdo->prepare('UPDATE user_balances SET available_balance = available_balance + ? WHERE user_id = ?');
        $stmt->execute([$tx['amount'], $tx['user_id']]);
      }
      $pdo->prepare('UPDATE transactions SET status = "failed" WHERE id = ?')->execute([$tx_id]);
      $alert = '<div class="alert alert-warning">Transaction rejected.</div>';
    }
  }
}
// Filters
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$where = [];
$params = [];
if ($type) { $where[] = 't.type = ?'; $params[] = $type; }
if ($status) { $where[] = 't.status = ?'; $params[] = $status; }
if ($search) {
  if (is_numeric($search)) {
    $where[] = 't.id = ?'; $params[] = $search;
  } else {
    $where[] = '(u.email LIKE ? OR u.username LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%";
  }
}
if ($date_from) { $where[] = 't.created_at >= ?'; $params[] = $date_from . ' 00:00:00'; }
if ($date_to) { $where[] = 't.created_at <= ?'; $params[] = $date_to . ' 23:59:59'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;
// Count total for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t JOIN users u ON t.user_id = u.id $whereSql");
$count_stmt->execute($params);
$total_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);
// Fetch paginated transactions
$stmt = $pdo->prepare("SELECT t.*, u.email, u.username FROM transactions t JOIN users u ON t.user_id = u.id $whereSql ORDER BY t.created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'User', 'Email', 'Type', 'Amount', 'Status', 'Date', 'Description']);
    foreach ($transactions as $tx) {
        fputcsv($out, [
            $tx['id'],
            $tx['username'],
            $tx['email'],
            $tx['type'],
            $tx['amount'],
            $tx['status'],
            $tx['created_at'],
            $tx['description'],
        ]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Transactions | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../styles/globals.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; font-size: 0.93rem; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; font-size: 0.93rem; }
    .dashboard-content-wrapper { max-width: 1200px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.91rem; }
    .transactions-table { margin-top: 2.5rem; background: #151a23; border-radius: 1rem; box-shadow: 0 2px 12px #0003; overflow: hidden; font-size: 0.91em; }
    .transactions-table th, .transactions-table td { color: #f3f4f6; vertical-align: middle; font-size: 0.97rem; padding: 0.7rem 0.8rem; }
    .transactions-table th { background: #232b3b; color: #38bdf8; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; border-bottom: 2px solid #2563eb33; font-size: 0.95em; }
    .transactions-table td { font-weight: 600; color: #e0e7ef; background: #181f2a; border-bottom: 1px solid #232b3b; font-size: 0.95em; }
    .transactions-table tr:nth-child(even) td { background: #1e2330; }
    .transactions-table tr:hover td { background: #232b3b; color: #fff; transition: background 0.18s, color 0.18s; }
    .transactions-table tr:last-child td { border-bottom: none; }
    .badge.bg-success { background: #22c55e !important; color: #fff !important; font-weight: 700; font-size: 0.95em; }
    .badge.bg-danger { background: #ef4444 !important; color: #fff !important; font-weight: 700; font-size: 0.95em; }
    .badge.bg-warning { background: #fbbf24 !important; color: #181f2a !important; font-weight: 700; font-size: 0.95em; }
    .badge.bg-secondary { background: #64748b !important; color: #fff !important; font-weight: 700; font-size: 0.95em; }
    .action-btn { font-size: 0.93em; border-radius: 0.5rem; padding: 0.3rem 0.9rem; margin-right: 0.3rem; }
    @media (max-width: 991px) { .main-content { margin-left: 0; } .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; font-size: 0.89rem; } }
    @media (max-width: 767px) { .dashboard-content-wrapper { padding: 0 0.1rem; font-size: 0.87rem; } }
    @media (max-width: 575px) { .dashboard-content-wrapper { padding: 0 0.05rem; font-size: 0.85rem; } .transactions-table { font-size: 0.87em; } .transactions-table th, .transactions-table td { padding: 0.35rem 0.18rem; font-size: 0.87em; } }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <?php include 'header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="dashboard-content-wrapper mx-auto">
        <h2 class="mb-4 text-info fw-bold text-center">All Transactions</h2>
        <?=$alert?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <form class="row g-3 filter-form" method="get" style="flex:1;">
            <div class="col-md-2">
              <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="deposit" <?=$type==='deposit'?'selected':''?>>Deposit</option>
                <option value="withdrawal" <?=$type==='withdrawal'?'selected':''?>>Withdrawal</option>
                <option value="investment" <?=$type==='investment'?'selected':''?>>Stake</option>
                <option value="reward" <?=$type==='reward'?'selected':''?>>Reward</option>
              </select>
            </div>
            <div class="col-md-2">
              <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" <?=$status==='pending'?'selected':''?>>Pending</option>
                <option value="completed" <?=$status==='completed'?'selected':''?>>Completed</option>
                <option value="failed" <?=$status==='failed'?'selected':''?>>Failed</option>
              </select>
            </div>
            <div class="col-md-2">
              <input type="text" name="search" class="form-control form-control-sm" placeholder="Search email, username, or ID" value="<?=htmlspecialchars($search)?>">
            </div>
            <div class="col-md-2">
              <input type="date" name="date_from" class="form-control form-control-sm" value="<?=htmlspecialchars($date_from)?>">
            </div>
            <div class="col-md-2">
              <input type="date" name="date_to" class="form-control form-control-sm" value="<?=htmlspecialchars($date_to)?>">
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-info btn-sm w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
          </form>
          <form method="get" class="ms-2">
            <?php foreach ($_GET as $k => $v) { if ($k !== 'export') echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'">'; } ?>
            <button type="submit" name="export" value="csv" class="btn btn-outline-info btn-sm"><i class="bi bi-download"></i> Export CSV</button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table transactions-table table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>User</th>
                <th>Email</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Description</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $tx): ?>
              <tr>
                <td><?=$tx['id']?></td>
                <td><?=htmlspecialchars($tx['username'] ?? '')?></td>
                <td><?=htmlspecialchars($tx['email'])?></td>
                <td><?=ucfirst($tx['type'])?></td>
                <td>SOL <?=number_format($tx['amount'],2)?></td>
                <td>
                  <?php if ($tx['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                  <?php elseif ($tx['status'] === 'completed'): ?>
                    <span class="badge bg-success">Completed</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Failed</span>
                  <?php endif; ?>
                </td>
                <td><?=date('Y-m-d H:i', strtotime($tx['created_at']))?></td>
                <td><?=htmlspecialchars($tx['description'])?></td>
                <td>
                  <?php if ($tx['status'] === 'pending'): ?>
                  <form method="post" style="display:inline-block;">
                    <input type="hidden" name="tx_id" value="<?=$tx['id']?>">
                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm action-btn" onclick="return confirm('Approve this transaction?')"><i class="bi bi-check-circle"></i></button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm action-btn" onclick="return confirm('Reject this transaction?')"><i class="bi bi-x-circle"></i></button>
                  </form>
                  <?php else: ?>
                    <span class="text-secondary">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!count($transactions)): ?><tr><td colspan="9" class="text-center text-muted">No transactions found.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <nav aria-label="Transactions pagination" class="mt-4">
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item<?=$i==$page?' active':''?>">
                <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>$i]))?>"><?=$i?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    </main>
  </div>
</body>
</html> 