<?php
session_start();
require_once '../api/config.php'; // DB connection

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Handle user actions
$action_success = $action_error = '';

// Handle success/error messages from balance actions
if (isset($_GET['success'])) {
    $action_success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $action_error = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action_type'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action_type'];
    if ($action === 'block') {
        $stmt = $pdo->prepare('UPDATE users SET status = "blocked" WHERE id = ?');
        $action_success = $stmt->execute([$user_id]) ? 'User blocked.' : 'Failed to block user.';
    } elseif ($action === 'suspend') {
        $stmt = $pdo->prepare('UPDATE users SET status = "suspended" WHERE id = ?');
        $action_success = $stmt->execute([$user_id]) ? 'User suspended.' : 'Failed to suspend user.';
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $action_success = $stmt->execute([$user_id]) ? 'User deleted.' : 'Failed to delete user.';
    } elseif ($action === 'unblock') {
        $stmt = $pdo->prepare('UPDATE users SET status = "active" WHERE id = ?');
        $action_success = $stmt->execute([$user_id]) ? 'User unblocked.' : 'Failed to unblock user.';
    } elseif ($action === 'unsuspend') {
        $stmt = $pdo->prepare('UPDATE users SET status = "active" WHERE id = ?');
        $action_success = $stmt->execute([$user_id]) ? 'User unsuspended.' : 'Failed to unsuspend user.';
    }
}

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(username LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_filter !== '' && $status_filter !== 'all') {
    $where[] = 'status = ?';
    $params[] = $status_filter;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
// Fetch all users with balance and referral count
$sql = "SELECT u.id, u.username, u.email, u.status, u.created_at, 
        IFNULL(b.available_balance,0) AS available_balance,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = u.id AND type = 'deposit' AND status = 'completed') AS total_deposits,
        (SELECT COALESCE(SUM(amount), 0) FROM user_rewards WHERE user_id = u.id) AS total_interest,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = u.id AND type = 'withdrawal' AND status = 'completed') AS total_withdrawals,
        (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) AS transaction_count
        FROM users u 
        LEFT JOIN user_balances b ON u.id = b.user_id 
        $where_sql ORDER BY u.created_at DESC";
if (!empty($params)) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $pdo->query($sql);
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Users</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../styles/globals.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; }
    .users-container { max-width: 1200px; width: 100%; margin: 10px auto; background: #181f2a; border-radius: 18px; box-shadow: 0 4px 32px #0003; padding: 24px 8px 18px 8px; }
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
    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 0.25rem;
      justify-content: center;
      align-items: center;
    }
    .action-buttons .btn {
      min-width: 32px;
      height: 32px;
      padding: 0.25rem;
      border-radius: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.85rem;
      transition: all 0.2s ease;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .action-buttons .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .action-buttons .btn:active {
      transform: translateY(0);
    }
    .action-buttons .btn-outline-info {
      border-color: #38bdf8;
      color: #38bdf8;
    }
    .action-buttons .btn-outline-info:hover {
      background-color: #38bdf8;
      border-color: #38bdf8;
      color: #fff;
    }
    .action-buttons .btn-success {
      background-color: #22c55e;
      border-color: #22c55e;
      color: #fff;
    }
    .action-buttons .btn-success:hover {
      background-color: #16a34a;
      border-color: #16a34a;
    }
    .action-buttons .btn-warning {
      background-color: #f59e42;
      border-color: #f59e42;
      color: #fff;
    }
    .action-buttons .btn-warning:hover {
      background-color: #d97706;
      border-color: #d97706;
    }
    .action-buttons .btn-outline-warning {
      border-color: #f59e42;
      color: #f59e42;
    }
    .action-buttons .btn-outline-warning:hover {
      background-color: #f59e42;
      border-color: #f59e42;
      color: #fff;
    }
    .action-buttons .btn-danger {
      background-color: #ef4444;
      border-color: #ef4444;
      color: #fff;
    }
    .action-buttons .btn-danger:hover {
      background-color: #dc2626;
      border-color: #dc2626;
    }
    @media (max-width: 991px) { 
      .main-content { margin-left: 0; } 
      .sidebar { left: -260px; } 
      .sidebar.active { left: 0; } 
      .users-container { margin: 8px 2px; padding: 16px; } 
    }
    @media (max-width: 700px) { 
      .users-container { padding: 12px; margin: 4px; } 
      table.table { min-width: 700px; font-size: 0.97rem; } 
      table.table th, table.table td { padding: 8px 2px; } 
      .form-control-sm, .form-select-sm { font-size: 0.9rem; }
      .action-buttons {
        gap: 0.15rem;
      }
      .action-buttons .btn {
        min-width: 28px;
        height: 28px;
        font-size: 0.8rem;
      }
    }
    @media (max-width: 575px) { 
      .users-container { padding: 8px; margin: 2px; } 
      table.table { font-size: 0.91rem; min-width: 480px; } 
      table.table th, table.table td { padding: 0.5rem 0.25rem; } 
      .btn-sm { font-size: 0.85rem; padding: 4px 8px; }
      .d-flex.flex-wrap { flex-direction: column; }
      .form-control-sm, .form-select-sm { max-width: 100% !important; }
      .action-buttons {
        gap: 0.1rem;
        justify-content: flex-start;
      }
      .action-buttons .btn {
        min-width: 26px;
        height: 26px;
        font-size: 0.75rem;
        padding: 0.2rem;
      }
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
    /* Modal compactness */
    @media (max-width: 700px) {
      .modal-dialog.modal-sm { max-width: 320px; margin: 0.5rem auto; }
      .modal-content { font-size: 0.93rem; }
      .modal-body, .modal-footer, .modal-header { padding-left: 0.7rem !important; padding-right: 0.7rem !important; }
    }
    @media (max-width: 400px) {
      .modal-dialog.modal-sm { max-width: 98vw; }
      .modal-content { font-size: 0.89rem; }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>
  <!-- Mobile Sidebar Overlay (after sidebar) -->
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'header.php'; ?>
    <div class="users-container">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-2">
        <h1 class="text-info fw-bold mb-0">All Users</h1>
        <form class="d-flex flex-wrap gap-2" method="get" action="">
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or email" value="<?=htmlspecialchars($search)?>" style="max-width:180px;">
          <select name="status" class="form-select form-select-sm" style="max-width:120px;">
            <option value="all"<?=($status_filter===''||$status_filter==='all')?' selected':''?>>All Status</option>
            <option value="active"<?=($status_filter==='active')?' selected':''?>>Active</option>
            <option value="blocked"<?=($status_filter==='blocked')?' selected':''?>>Blocked</option>
            <option value="suspended"<?=($status_filter==='suspended')?' selected':''?>>Suspended</option>
          </select>
          <button class="btn btn-info btn-sm" type="submit"><i class="bi bi-search"></i> Search</button>
        </form>
      </div>
      <?php if ($action_success): ?><div class="alert alert-success"><?=htmlspecialchars($action_success)?></div><?php endif; ?>
      <?php if ($action_error): ?><div class="alert alert-danger"><?=htmlspecialchars($action_error)?></div><?php endif; ?>
      <div class="table-responsive mb-5">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Status</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
              <td><?=htmlspecialchars($user['username'])?></td>
              <td><?=htmlspecialchars($user['email'])?></td>
              <td>
                <?php if ($user['status'] === 'active'): ?>
                  <span class="badge bg-success">Active</span>
                <?php elseif ($user['status'] === 'blocked'): ?>
                  <span class="badge bg-danger">Blocked</span>
                <?php elseif ($user['status'] === 'suspended'): ?>
                  <span class="badge bg-warning">Suspended</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?=htmlspecialchars(ucfirst($user['status']))?></span>
                <?php endif; ?>
              </td>
              <td><?=date('M d, Y', strtotime($user['created_at']))?></td>
              <td>
                <div class="action-buttons">
                  <!-- View Button -->
                  <button class="btn btn-sm btn-outline-info view-user-btn" type="button"
                    data-user-id="<?=$user['id']?>"
                    data-username="<?=htmlspecialchars($user['username'])?>"
                    data-email="<?=htmlspecialchars($user['email'])?>"
                    data-status="<?=htmlspecialchars($user['status'])?>"
                    data-created="<?=htmlspecialchars($user['created_at'])?>"
                    data-available-balance="<?=htmlspecialchars($user['available_balance'])?>"
                    data-total-deposits="<?=htmlspecialchars($user['total_deposits'])?>"
                    data-total-interest="<?=htmlspecialchars($user['total_interest'])?>"
                    data-total-withdrawals="<?=htmlspecialchars($user['total_withdrawals'])?>"
                    data-transaction-count="<?=htmlspecialchars($user['transaction_count'])?>"
                    data-bs-toggle="modal" data-bs-target="#userDetailModal"
                    title="View User Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  
                  <?php if ($user['status'] === 'blocked'): ?>
                    <!-- Unblock Button -->
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="user_id" value="<?=$user['id']?>">
                      <input type="hidden" name="action_type" value="unblock">
                      <button class="btn btn-sm btn-success" type="submit" onclick="return confirm('Unblock this user?')" title="Unblock User">
                        <i class="bi bi-unlock"></i>
                      </button>
                    </form>
                  <?php elseif ($user['status'] === 'suspended'): ?>
                    <!-- Unsuspend Button -->
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="user_id" value="<?=$user['id']?>">
                      <input type="hidden" name="action_type" value="unsuspend">
                      <button class="btn btn-sm btn-success" type="submit" onclick="return confirm('Unsuspend this user?')" title="Unsuspend User">
                        <i class="bi bi-play-circle"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <!-- Block Button -->
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="user_id" value="<?=$user['id']?>">
                      <input type="hidden" name="action_type" value="block">
                      <button class="btn btn-sm btn-warning" type="submit" onclick="return confirm('Block this user?')" title="Block User">
                        <i class="bi bi-slash-circle"></i>
                      </button>
                    </form>
                    
                    <!-- Suspend Button -->
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="user_id" value="<?=$user['id']?>">
                      <input type="hidden" name="action_type" value="suspend">
                      <button class="btn btn-sm btn-outline-warning" type="submit" onclick="return confirm('Suspend this user?')" title="Suspend User">
                        <i class="bi bi-pause-circle"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  
                  <!-- Delete Button -->
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?=$user['id']?>">
                    <input type="hidden" name="action_type" value="delete">
                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this user? This cannot be undone.')" title="Delete User">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php include 'footer.php'; ?>
  </div>
  <!-- User Detail Modal -->
  <div class="modal fade" id="userDetailModal" tabindex="-1" aria-labelledby="userDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark text-light" style="border-radius: 1rem; background: #181f2a; color: #e5e7eb;">
        <div class="modal-header bg-info text-white" style="border-top-left-radius: 1rem; border-top-right-radius: 1rem; padding: 1rem;">
          <h5 class="modal-title mb-0" id="userDetailModalLabel">User Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="userDetailBody" style="padding: 1.5rem; font-size: 0.97rem; background: #181f2a; color: #e5e7eb;">
          <!-- Populated by JS -->
        </div>
        <div class="modal-footer" style="padding: 1rem; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem; background: #181f2a;">
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" id="topUpBtn" data-bs-toggle="modal" data-bs-target="#topUpModal">
              <i class="bi bi-plus-circle me-2"></i>Top Up
            </button>
            <button type="button" class="btn btn-warning" id="deductBtn" data-bs-toggle="modal" data-bs-target="#deductModal">
              <i class="bi bi-dash-circle me-2"></i>Deduct
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Top Up Modal -->
  <div class="modal fade" id="topUpModal" tabindex="-1" aria-labelledby="topUpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-light" style="border-radius: 1rem; background: #181f2a; color: #e5e7eb;">
        <div class="modal-header bg-success text-white" style="border-top-left-radius: 1rem; border-top-right-radius: 1rem; padding: 1rem;">
          <h5 class="modal-title mb-0" id="topUpModalLabel">Top Up User Balance</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="topUpForm" method="post" action="admin_balance_action.php">
          <div class="modal-body" style="padding: 1.5rem; background: #181f2a; color: #e5e7eb;">
            <div class="user-info mb-3 p-3 bg-secondary bg-opacity-10 rounded">
              <h6 class="text-info mb-2">User Information</h6>
              <div id="topUpUserInfo" class="small">
                <!-- Populated by JS -->
              </div>
            </div>
            <div class="mb-3">
              <label for="topUpAmount" class="form-label">Amount (SOL)</label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="topUpAmount" name="amount" required>
            </div>
            <div class="mb-3">
              <label for="topUpWalletType" class="form-label">Wallet Type</label>
              <select class="form-select" id="topUpWalletType" name="wallet_type" required>
                <option value="">Select Wallet Type</option>
                <option value="deposit">Deposit</option>
                <option value="interest">Interest</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="topUpNotes" class="form-label">Notes (Optional)</label>
              <textarea class="form-control" id="topUpNotes" name="notes" rows="2" placeholder="Reason for top up..."></textarea>
            </div>
            <input type="hidden" id="topUpUserId" name="user_id">
            <input type="hidden" name="action" value="top_up">
          </div>
          <div class="modal-footer" style="padding: 1rem; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem; background: #181f2a;">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success">
              <i class="bi bi-plus-circle me-2"></i>Top Up Now
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Deduct Modal -->
  <div class="modal fade" id="deductModal" tabindex="-1" aria-labelledby="deductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-light" style="border-radius: 1rem; background: #181f2a; color: #e5e7eb;">
        <div class="modal-header bg-warning text-dark" style="border-top-left-radius: 1rem; border-top-right-radius: 1rem; padding: 1rem;">
          <h5 class="modal-title mb-0" id="deductModalLabel">Deduct User Balance</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="deductForm" method="post" action="admin_balance_action.php">
          <div class="modal-body" style="padding: 1.5rem; background: #181f2a; color: #e5e7eb;">
            <div class="user-info mb-3 p-3 bg-secondary bg-opacity-10 rounded">
              <h6 class="text-warning mb-2">User Information</h6>
              <div id="deductUserInfo" class="small">
                <!-- Populated by JS -->
              </div>
            </div>
            <div class="mb-3">
              <label for="deductAmount" class="form-label">Amount (SOL)</label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="deductAmount" name="amount" required>
            </div>
            <div class="mb-3">
              <label for="deductWalletType" class="form-label">Wallet Type</label>
              <select class="form-select" id="deductWalletType" name="wallet_type" required>
                <option value="">Select Wallet Type</option>
                <option value="deposit">Deposit</option>
                <option value="interest">Interest</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="deductNotes" class="form-label">Notes (Optional)</label>
              <textarea class="form-control" id="deductNotes" name="notes" rows="2" placeholder="Reason for deduction..."></textarea>
            </div>
            <input type="hidden" id="deductUserId" name="user_id">
            <input type="hidden" name="action" value="deduct">
          </div>
          <div class="modal-footer" style="padding: 1rem; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem; background: #181f2a;">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning">
              <i class="bi bi-dash-circle me-2"></i>Deduct Now
            </button>
          </div>
        </form>
      </div>
    </div>
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
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 992) { sidebar.classList.remove('d-none'); sidebar.classList.add('d-flex'); sidebarOverlay.classList.remove('active'); } else { sidebar.classList.remove('d-flex'); sidebar.classList.add('d-none'); }
    });
  </script>
  <script>
    // Global variable to store current user data
    window.currentUser = null;

    // Handle view user button clicks
    document.addEventListener('DOMContentLoaded', function() {
      // Event delegation for view buttons
      document.addEventListener('click', function(e) {
        if (e.target.closest('.view-user-btn')) {
          var btn = e.target.closest('.view-user-btn');
          var user = {
            id: btn.getAttribute('data-user-id'),
            username: btn.getAttribute('data-username'),
            email: btn.getAttribute('data-email'),
            status: btn.getAttribute('data-status'),
            created_at: btn.getAttribute('data-created'),
            available_balance: btn.getAttribute('data-available-balance'),
            total_deposits: btn.getAttribute('data-total-deposits'),
            total_interest: btn.getAttribute('data-total-interest'),
            total_withdrawals: btn.getAttribute('data-total-withdrawals'),
            transaction_count: btn.getAttribute('data-transaction-count')
          };
          
          // Store user data globally for use in other modals
          window.currentUser = user;
          
          // Create professional modal content
          var html = `
            <div class="row g-4">
              <div class="col-md-6">
                <div class="card bg-dark border-info">
                  <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-person-circle me-2"></i>User Information</h6>
                  </div>
                  <div class="card-body text-white">
                    <div class="d-flex align-items-center mb-3">
                      <div class="flex-shrink-0">
                        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                          <i class="bi bi-person text-white fs-4"></i>
                        </div>
                      </div>
                      <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1 text-white">${user.username}</h6>
                        <small class="text-light">${user.email}</small>
                      </div>
                    </div>
                    <div class="row g-2">
                      <div class="col-6">
                        <div class="p-2 bg-secondary bg-opacity-20 rounded">
                          <small class="text-white d-block">Status</small>
                          <span class="badge bg-${user.status==='active'?'success':(user.status==='blocked'?'danger':(user.status==='suspended'?'warning':'secondary'))}">${user.status.charAt(0).toUpperCase()+user.status.slice(1)}</span>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="p-2 bg-secondary bg-opacity-20 rounded">
                          <small class="text-white d-block">Registered</small>
                          <span class="text-white">${new Date(user.created_at).toLocaleDateString()}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card bg-dark border-success">
                  <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Financial Summary</h6>
                  </div>
                  <div class="card-body text-white">
                    <div class="row g-3">
                      <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center p-3 bg-info bg-opacity-20 rounded border border-info">
                          <div>
                            <small class="text-white d-block">Available Balance</small>
                            <span class="fs-5 fw-bold text-dark">SOL ${parseFloat(user.available_balance || 0).toFixed(2)}</span>
                          </div>
                          <i class="bi bi-cash-coin text-info fs-4"></i>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="p-2 bg-success bg-opacity-20 rounded border border-success">
                          <small class="text-white d-block">Total Deposits</small>
                          <span class="text-dark fw-bold">SOL ${parseFloat(user.total_deposits || 0).toFixed(2)}</span>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="p-2 bg-warning bg-opacity-20 rounded border border-warning">
                          <small class="text-white d-block">Total Interest</small>
                          <span class="text-white fw-bold">SOL ${parseFloat(user.total_interest || 0).toFixed(2)}</span>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="p-2 bg-danger bg-opacity-20 rounded border border-danger">
                          <small class="text-white d-block">Total Withdrawals</small>
                          <span class="text-white fw-bold">SOL ${parseFloat(user.total_withdrawals || 0).toFixed(2)}</span>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="p-2 bg-secondary bg-opacity-20 rounded border border-secondary">
                          <small class="text-white d-block">Transactions</small>
                          <span class="text-white fw-bold">${user.transaction_count || 0}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          `;

          document.getElementById('userDetailBody').innerHTML = html;
        }
      });

      // Handle Top Up button click
      var topUpBtn = document.getElementById('topUpBtn');
      if (topUpBtn) {
        topUpBtn.addEventListener('click', function() {
          if (window.currentUser) {
            var user = window.currentUser;
            var userInfo = `
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-person-circle text-info me-2"></i>
                <div>
                  <strong class="text-white">${user.username}</strong><br>
                  <small class="text-light">${user.email}</small>
                </div>
              </div>
              <div class="alert alert-info mb-0 bg-info bg-opacity-20 border border-info">
                <i class="bi bi-wallet2 me-2"></i>
                <strong class="text-white">Current Balance:</strong> <span class="text-info fw-bold">SOL ${parseFloat(user.available_balance || 0).toFixed(2)}</span>
              </div>
            `;
            document.getElementById('topUpUserInfo').innerHTML = userInfo;
            document.getElementById('topUpUserId').value = user.id;
          }
        });
      }

      // Handle Deduct button click
      var deductBtn = document.getElementById('deductBtn');
      if (deductBtn) {
        deductBtn.addEventListener('click', function() {
          if (window.currentUser) {
            var user = window.currentUser;
            var userInfo = `
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-person-circle text-warning me-2"></i>
                <div>
                  <strong class="text-white">${user.username}</strong><br>
                  <small class="text-light">${user.email}</small>
                </div>
              </div>
              <div class="alert alert-warning mb-0 bg-warning bg-opacity-20 border border-warning">
                <i class="bi bi-wallet2 me-2"></i>
                <strong class="text-white">Current Balance:</strong> <span class="text-warning fw-bold">SOL ${parseFloat(user.available_balance || 0).toFixed(2)}</span>
              </div>
            `;
            document.getElementById('deductUserInfo').innerHTML = userInfo;
            document.getElementById('deductUserId').value = user.id;
          }
        });
      }
    });
  </script>