<?php
session_start();
require_once '../api/config.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit;
}
// Filters: search, min/max referral count
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_ref = isset($_GET['min_ref']) ? intval($_GET['min_ref']) : '';
$max_ref = isset($_GET['max_ref']) ? intval($_GET['max_ref']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(u.username LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
// For referral count filter, use HAVING after GROUP BY
$having = [];
if ($min_ref !== '' && is_numeric($min_ref)) {
    $having[] = 'referral_count >= ?';
    $params[] = $min_ref;
}
if ($max_ref !== '' && is_numeric($max_ref)) {
    $having[] = 'referral_count <= ?';
    $params[] = $max_ref;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$having_sql = $having ? 'HAVING ' . implode(' AND ', $having) : '';
// Count total for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM (SELECT u.id FROM users u $where_sql GROUP BY u.id $having_sql) as t");
$count_stmt->execute(array_slice($params, 0, count($params) - count($having))); // Only WHERE params for count
$total_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);
// Fetch users with referral info (real data)
$stmt = $pdo->prepare("
SELECT u.id, u.username, u.email, u.created_at, u.referred_by,
  COUNT(r.id) AS referral_count,
  COALESCE(SUM(rw.amount),0) AS referral_rewards
FROM users u
LEFT JOIN users r ON r.referred_by = u.username
LEFT JOIN user_rewards rw ON rw.user_id = u.id AND rw.type = 'referral'
$where_sql
GROUP BY u.id $having_sql
ORDER BY u.created_at DESC
LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="referrals_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['User', 'Email', 'Referred By', 'Referral Count', 'Total Referral Rewards', 'Joined', 'Referred Users']);
    foreach ($users as $u) {
        // Fetch referred users for export
        $ref_stmt = $pdo->prepare('SELECT username, email FROM users WHERE referred_by = ?');
        $ref_stmt->execute([$u['username']]);
        $refs = $ref_stmt->fetchAll(PDO::FETCH_ASSOC);
        $ref_list = [];
        foreach ($refs as $ref) {
            $ref_list[] = $ref['username'] . ' (' . $ref['email'] . ')';
        }
        fputcsv($out, [
            $u['username'],
            $u['email'],
            $u['referred_by'] ?: '-',
            $u['referral_count'],
            $u['referral_rewards'],
            $u['created_at'],
            implode('; ', $ref_list)
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
  <title>Admin - Referrals</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../styles/globals.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; font-size: 0.93rem; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; font-size: 0.93rem; }
    .dashboard-content-wrapper { max-width: 1100px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.91rem; }
    .referrals-table { margin-top: 2.5rem; background: #151a23; border-radius: 1rem; box-shadow: 0 2px 12px #0003; overflow: hidden; font-size: 0.91em; }
    .referrals-table th, .referrals-table td { color: #f3f4f6; vertical-align: middle; font-size: 0.97rem; padding: 0.7rem 0.8rem; }
    .referrals-table th { background: #232b3b; color: #38bdf8; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; border-bottom: 2px solid #2563eb33; font-size: 0.95em; }
    .referrals-table td { font-weight: 600; color: #e0e7ef; background: #181f2a; border-bottom: 1px solid #232b3b; font-size: 0.95em; }
    .referrals-table tr:nth-child(even) td { background: #1e2330; }
    .referrals-table tr:hover td { background: #232b3b; color: #fff; transition: background 0.18s, color 0.18s; }
    .referrals-table tr:last-child td { border-bottom: none; }
    @media (max-width: 991px) { .main-content { margin-left: 0; } .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; font-size: 0.89rem; } }
    @media (max-width: 767px) { .dashboard-content-wrapper { padding: 0 0.1rem; font-size: 0.87rem; } }
    @media (max-width: 575px) { .dashboard-content-wrapper { padding: 0 0.05rem; font-size: 0.85rem; } .referrals-table { font-size: 0.87em; } .referrals-table th, .referrals-table td { padding: 0.35rem 0.18rem; font-size: 0.87em; } }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <?php include 'header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="dashboard-content-wrapper mx-auto">
        <h2 class="mb-4 text-info fw-bold text-center">User Referrals</h2>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <form class="row g-3" method="get" style="flex:1;">
            <div class="col-md-3">
              <input type="text" name="search" class="form-control form-control-sm" placeholder="Search username or email" value="<?=htmlspecialchars($search)?>">
            </div>
            <div class="col-md-2">
              <input type="number" name="min_ref" class="form-control form-control-sm" placeholder="Min Referrals" value="<?=htmlspecialchars($min_ref)?>">
            </div>
            <div class="col-md-2">
              <input type="number" name="max_ref" class="form-control form-control-sm" placeholder="Max Referrals" value="<?=htmlspecialchars($max_ref)?>">
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
          <table class="table referrals-table table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Referred By</th>
                <th>Referral Count</th>
                <th>Total Referral Rewards</th>
                <th>Joined</th>
                <th>Referred Users</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td><?=htmlspecialchars($u['username'])?></td>
                <td><?=htmlspecialchars($u['email'])?></td>
                <td><?=htmlspecialchars($u['referred_by'] ?: '-')?></td>
                <td><?=$u['referral_count']?></td>
                <td>SOL <?=number_format($u['referral_rewards'],2)?></td>
                <td><?=date('Y-m-d', strtotime($u['created_at']))?></td>
                <td>
                  <?php
                  $ref_stmt = $pdo->prepare('SELECT username, email FROM users WHERE referred_by = ?');
                  $ref_stmt->execute([$u['username']]);
                  $refs = $ref_stmt->fetchAll(PDO::FETCH_ASSOC);
                  if (count($refs)) {
                    foreach ($refs as $ref) {
                      echo '<div>'.htmlspecialchars($ref['username']).' <span class="text-secondary">('.htmlspecialchars($ref['email']).')</span></div>';
                    }
                  } else {
                    echo '<span class="text-muted">-</span>';
                  }
                  ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!count($users)): ?><tr><td colspan="7" class="text-center text-muted">No users found.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <nav aria-label="Referrals pagination" class="mt-4">
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