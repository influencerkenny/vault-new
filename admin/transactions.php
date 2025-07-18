<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit;
}
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
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
        $stmt = $pdo->prepare('UPDATE user_balances SET available_balance = available_balance - ? WHERE user_id = ?');
        $stmt->execute([$tx['amount'], $tx['user_id']]);
      }
      $pdo->prepare('UPDATE transactions SET status = "completed" WHERE id = ?')->execute([$tx_id]);
      $alert = '<div class="alert alert-success">Transaction approved and user balance updated.</div>';
    } elseif ($action === 'reject') {
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
    $where[] = 'u.email LIKE ?'; $params[] = "%$search%";
  }
}
if ($date_from) { $where[] = 't.created_at >= ?'; $params[] = $date_from . ' 00:00:00'; }
if ($date_to) { $where[] = 't.created_at <= ?'; $params[] = $date_to . ' 23:59:59'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$stmt = $pdo->prepare("SELECT t.*, u.email FROM transactions t JOIN users u ON t.user_id = u.id $whereSql ORDER BY t.created_at DESC");
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Transactions | Vault</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #0f172a; font-family: 'Inter', sans-serif; }
    .table { background: #111827; color: #e5e7eb; }
    .table th, .table td { vertical-align: middle; }
    .badge-success { background: #22c55e; }
    .badge-warning { background: #facc15; color: #22292f; }
    .badge-danger { background: #ef4444; }
    .badge-info { background: #38bdf8; }
    .btn-approve { background: #22c55e; color: #fff; }
    .btn-approve:hover { background: #16a34a; color: #fff; }
    .btn-reject { background: #ef4444; color: #fff; }
    .btn-reject:hover { background: #b91c1c; color: #fff; }
    .dashboard-header { background: #111827; border-radius: 0 0 1.25rem 1.25rem; padding: 2rem 2rem 1rem 2rem; color: #e5e7eb; }
    .nav-link { color: #38bdf8; font-weight: 500; }
    .nav-link:hover { color: #0ea5e9; text-decoration: underline; }
    .filter-form .form-control, .filter-form .form-select { background: #1e293b; color: #e5e7eb; border: 1px solid #374151; }
    .filter-form .form-control:focus, .filter-form .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 0.2rem rgba(37,99,235,.25); }
  </style>
</head>
<body>
  <div class="dashboard-header mb-4 d-flex justify-content-between align-items-center">
    <h2 class="mb-0">Manage Transactions</h2>
    <nav>
      <a href="dashboard.php" class="nav-link">Dashboard</a>
      <a href="transactions.php" class="nav-link ms-3">Transactions</a>
      <a href="wallets.php" class="nav-link ms-3">Crypto Wallets</a>
      <a href="logout.php" class="nav-link ms-3 text-danger">Logout</a>
    </nav>
  </div>
  <div class="container">
    <?=$alert?>
    <form class="row g-3 mb-4 filter-form" method="get">
      <div class="col-md-2">
        <select name="type" class="form-select" onchange="this.form.submit()">
          <option value="">All Types</option>
          <option value="deposit" <?=$type==='deposit'?'selected':''?>>Deposit</option>
          <option value="withdrawal" <?=$type==='withdrawal'?'selected':''?>>Withdrawal</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="pending" <?=$status==='pending'?'selected':''?>>Pending</option>
          <option value="completed" <?=$status==='completed'?'selected':''?>>Completed</option>
          <option value="failed" <?=$status==='failed'?'selected':''?>>Failed</option>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="date_from" value="<?=htmlspecialchars($date_from)?>" class="form-control" onchange="this.form.submit()" placeholder="From">
      </div>
      <div class="col-md-2">
        <input type="date" name="date_to" value="<?=htmlspecialchars($date_to)?>" class="form-control" onchange="this.form.submit()" placeholder="To">
      </div>
      <div class="col-md-3">
        <input type="text" name="search" value="<?=htmlspecialchars($search)?>" class="form-control" placeholder="Search by email or ID">
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-info w-100">Search</button>
      </div>
    </form>
    <div class="table-responsive">
      <table class="table table-dark table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
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
            <td><?=$tx['email']?></td>
            <td><span class="badge badge-info text-uppercase"><?=htmlspecialchars($tx['type'])?></span></td>
            <td>$<?=number_format($tx['amount'],2)?></td>
            <td>
              <?php if ($tx['status'] === 'completed'): ?>
                <span class="badge badge-success">Completed</span>
              <?php elseif ($tx['status'] === 'pending'): ?>
                <span class="badge badge-warning">Pending</span>
              <?php else: ?>
                <span class="badge badge-danger">Failed</span>
              <?php endif; ?>
            </td>
            <td><?=date('M j, Y H:i', strtotime($tx['created_at']))?></td>
            <td>
              <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailsModal<?=$tx['id']?>">View</button>
              <!-- Details Modal -->
              <div class="modal fade" id="detailsModal<?=$tx['id']?>" tabindex="-1" aria-labelledby="detailsModalLabel<?=$tx['id']?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content bg-dark text-white rounded-4">
                    <div class="modal-header border-0">
                      <h5 class="modal-title" id="detailsModalLabel<?=$tx['id']?>">Transaction Details</h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-2"><strong>ID:</strong> <?=$tx['id']?></div>
                      <div class="mb-2"><strong>User:</strong> <?=$tx['email']?></div>
                      <div class="mb-2"><strong>Type:</strong> <?=htmlspecialchars($tx['type'])?></div>
                      <div class="mb-2"><strong>Amount:</strong> $<?=number_format($tx['amount'],2)?></div>
                      <div class="mb-2"><strong>Status:</strong> <?=htmlspecialchars($tx['status'])?></div>
                      <div class="mb-2"><strong>Date:</strong> <?=date('M j, Y H:i', strtotime($tx['created_at']))?></div>
                      <div class="mb-2"><strong>Description:</strong> <?=htmlspecialchars($tx['description'])?></div>
                    </div>
                  </div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($tx['status'] === 'pending'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="tx_id" value="<?=$tx['id']?>">
                  <button type="submit" name="action" value="approve" class="btn btn-approve btn-sm me-1">Approve</button>
                  <button type="submit" name="action" value="reject" class="btn btn-reject btn-sm">Reject</button>
                </form>
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 