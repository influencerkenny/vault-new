<?php
session_start();
require_once '../api/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$action_success = $action_error = '';

// Handle Add/Edit/Disable actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_gateway') {
        // Handle image upload
        $thumbPath = '';
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $fname = 'gateway_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $dest = '../public/' . $fname;
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dest)) {
                    $thumbPath = $fname;
                }
            }
        }
        $stmt = $pdo->prepare('INSERT INTO payment_gateways (name, currency, rate_to_usd, min_amount, max_amount, instructions, user_data_label, status, thumbnail) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $ok = $stmt->execute([
            trim($_POST['name']),
            trim($_POST['currency']),
            floatval($_POST['rate_to_usd']),
            floatval($_POST['min_amount']),
            floatval($_POST['max_amount']),
            trim($_POST['instructions']),
            trim($_POST['user_data_label']),
            'enabled',
            $thumbPath
        ]);
        $action_success = $ok ? 'Payment gateway added.' : 'Failed to add gateway.';
    } elseif (isset($_POST['action'], $_POST['id']) && $_POST['action'] === 'disable') {
        $stmt = $pdo->prepare('UPDATE payment_gateways SET status = "disabled" WHERE id = ?');
        $ok = $stmt->execute([intval($_POST['id'])]);
        $action_success = $ok ? 'Gateway disabled.' : 'Failed to disable gateway.';
    } elseif (isset($_POST['action'], $_POST['id']) && $_POST['action'] === 'enable') {
        $stmt = $pdo->prepare('UPDATE payment_gateways SET status = "enabled" WHERE id = ?');
        $ok = $stmt->execute([intval($_POST['id'])]);
        $action_success = $ok ? 'Gateway enabled.' : 'Failed to enable gateway.';
    } elseif (isset($_POST['action'], $_POST['id']) && $_POST['action'] === 'edit_gateway') {
        // Handle edit gateway
        $thumbPath = $_POST['existing_thumbnail'] ?? '';
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $fname = 'gateway_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $dest = '../public/' . $fname;
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dest)) {
                    $thumbPath = $fname;
                }
            }
        }
        $stmt = $pdo->prepare('UPDATE payment_gateways SET name=?, currency=?, rate_to_usd=?, min_amount=?, max_amount=?, instructions=?, user_data_label=?, thumbnail=? WHERE id=?');
        $ok = $stmt->execute([
            trim($_POST['name']),
            trim($_POST['currency']),
            floatval($_POST['rate_to_usd']),
            floatval($_POST['min_amount']),
            floatval($_POST['max_amount']),
            trim($_POST['instructions']),
            trim($_POST['user_data_label']),
            $thumbPath,
            intval($_POST['id'])
        ]);
        $action_success = $ok ? 'Payment gateway updated.' : 'Failed to update gateway.';
    }
}

// Fetch all gateways
$stmt = $pdo->query('SELECT * FROM payment_gateways ORDER BY created_at DESC');
$gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Payment Gateways</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../styles/globals.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; }
    .gateways-container { max-width: 950px; width: 100%; margin: 10px auto; background: #181f2a; border-radius: 18px; box-shadow: 0 4px 32px #0003; padding: 24px 8px 18px 8px; }
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
      .gateways-container { margin: 8px 2px; padding: 16px; } 
    }
    @media (max-width: 700px) { 
      .gateways-container { padding: 12px; margin: 4px; } 
      table.table { min-width: 700px; font-size: 0.97rem; } 
      table.table th, table.table td { padding: 8px 2px; } 
      .gateway-thumb { width: 40px; height: 40px; }
    }
    @media (max-width: 575px) { 
      .gateways-container { padding: 8px; margin: 2px; } 
      table.table { font-size: 0.91rem; min-width: 480px; } 
      table.table th, table.table td { padding: 0.5rem 0.25rem; } 
      .btn-sm { font-size: 0.85rem; padding: 4px 8px; }
      .gateway-thumb { width: 36px; height: 36px; }
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
    .gateway-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; background: #232b3b; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'header.php'; ?>
    <div class="gateways-container">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-2">
        <h1 class="text-info fw-bold mb-0">Payment Gateways</h1>
        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#addGatewayModal"><i class="bi bi-plus-circle me-2"></i>Add New</button>
      </div>
      <?php if ($action_success): ?><div class="alert alert-success"><?=htmlspecialchars($action_success)?></div><?php endif; ?>
      <?php if ($action_error): ?><div class="alert alert-danger"><?=htmlspecialchars($action_error)?></div><?php endif; ?>
      <div class="table-responsive mb-5">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Thumbnail</th>
              <th>Gateway</th>
              <th>Currency</th>
              <th>Rate (1 USD)</th>
              <th>Range</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($gateways as $gw): ?>
            <tr>
              <td>
                <?php if ($gw['thumbnail']): ?>
                  <img src="../public/<?=htmlspecialchars($gw['thumbnail'])?>" class="gateway-thumb" alt="Gateway Thumbnail">
                <?php else: ?>
                  <span class="text-secondary">N/A</span>
                <?php endif; ?>
              </td>
              <td><?=htmlspecialchars($gw['name'])?></td>
              <td><?=htmlspecialchars($gw['currency'])?></td>
              <td><?=number_format($gw['rate_to_usd'], 4)?></td>
              <td><?=number_format($gw['min_amount'],2)?> - <?=number_format($gw['max_amount'],2)?></td>
              <td>
                <?php if ($gw['status'] === 'enabled'): ?>
                  <span class="badge bg-success">Enabled</span>
                <?php else: ?>
                  <span class="badge bg-danger">Disabled</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex gap-2">
                  <button class="btn btn-sm btn-info" type="button" onclick='openEditGatewayModal(<?=json_encode($gw)?>)'><i class="bi bi-pencil-square me-1"></i>Edit</button>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?=$gw['id']?>">
                    <input type="hidden" name="action" value="<?=$gw['status'] === 'enabled' ? 'disable' : 'enable'?>">
                    <button class="btn btn-sm <?= $gw['status'] === 'enabled' ? 'btn-danger' : 'btn-success' ?>" type="submit" onclick="return confirm('<?=$gw['status'] === 'enabled' ? 'Disable' : 'Enable'?> this gateway?')">
                      <i class="bi <?=$gw['status'] === 'enabled' ? 'bi-x-circle' : 'bi-check-circle'?> me-1"></i><?=$gw['status'] === 'enabled' ? 'Disable' : 'Enable'?>
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
  <!-- Add Gateway Modal -->
  <div class="modal fade" id="addGatewayModal" tabindex="-1" aria-labelledby="addGatewayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content bg-dark text-light" style="border-radius: 1rem; background: #181f2a; color: #e5e7eb;">
        <form method="post" enctype="multipart/form-data">
          <div class="modal-header bg-info text-white" style="border-top-left-radius: 1rem; border-top-right-radius: 1rem; padding: 0.7rem 1rem;">
            <h6 class="modal-title mb-0" id="addGatewayModalLabel">Add Payment Gateway</h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="padding: 0.8rem 1rem; font-size: 0.97rem; background: #181f2a; color: #e5e7eb;">
            <input type="hidden" name="action" value="add_gateway">
            <div class="mb-3">
              <label for="gatewayThumb" class="form-label">Thumbnail (Image)</label>
              <input type="file" class="form-control" id="gatewayThumb" name="thumbnail" accept="image/*">
            </div>
            <div class="mb-3">
              <label for="gatewayName" class="form-label">Gateway Name</label>
              <input type="text" class="form-control" id="gatewayName" name="name" required>
            </div>
            <div class="mb-3">
              <label for="gatewayCurrency" class="form-label">Currency</label>
              <input type="text" class="form-control" id="gatewayCurrency" name="currency" required>
            </div>
            <div class="mb-3">
              <label for="gatewayRate" class="form-label">Rate to 1 USD</label>
              <input type="number" step="0.0001" class="form-control" id="gatewayRate" name="rate_to_usd" required>
            </div>
            <div class="mb-3 row g-2">
              <div class="col">
                <label for="gatewayMin" class="form-label">Minimum</label>
                <input type="number" step="0.01" class="form-control" id="gatewayMin" name="min_amount" required>
              </div>
              <div class="col">
                <label for="gatewayMax" class="form-label">Maximum</label>
                <input type="number" step="0.01" class="form-control" id="gatewayMax" name="max_amount" required>
              </div>
            </div>
            <div class="mb-3">
              <label for="gatewayInstructions" class="form-label">Deposit Instructions</label>
              <textarea class="form-control" id="gatewayInstructions" name="instructions" rows="2" required></textarea>
            </div>
            <div class="mb-3">
              <label for="gatewayUserData" class="form-label">User Data (e.g. Proof of Payment)</label>
              <input type="text" class="form-control" id="gatewayUserData" name="user_data_label" placeholder="e.g. Upload payment screenshot" required>
            </div>
          </div>
          <div class="modal-footer" style="padding: 0.5rem 1rem; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem; background: #181f2a;">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-info btn-sm">Add Gateway</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Edit Gateway Modal -->
  <div class="modal fade" id="editGatewayModal" tabindex="-1" aria-labelledby="editGatewayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content bg-dark text-light" style="border-radius: 1rem; background: #181f2a; color: #e5e7eb;">
        <form method="post" enctype="multipart/form-data">
          <div class="modal-header bg-info text-white" style="border-top-left-radius: 1rem; border-top-right-radius: 1rem; padding: 0.7rem 1rem;">
            <h6 class="modal-title mb-0" id="editGatewayModalLabel">Edit Payment Gateway</h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="padding: 0.8rem 1rem; font-size: 0.97rem; background: #181f2a; color: #e5e7eb;">
            <input type="hidden" name="action" value="edit_gateway">
            <input type="hidden" name="id" id="editGatewayId">
            <input type="hidden" name="existing_thumbnail" id="editGatewayExistingThumb">
            <div class="mb-3">
              <label for="editGatewayThumb" class="form-label">Thumbnail (Image)</label>
              <input type="file" class="form-control" id="editGatewayThumb" name="thumbnail" accept="image/*">
              <div id="editGatewayThumbPreview" class="mt-2"></div>
            </div>
            <div class="mb-3">
              <label for="editGatewayName" class="form-label">Gateway Name</label>
              <input type="text" class="form-control" id="editGatewayName" name="name" required>
            </div>
            <div class="mb-3">
              <label for="editGatewayCurrency" class="form-label">Currency</label>
              <input type="text" class="form-control" id="editGatewayCurrency" name="currency" required>
            </div>
            <div class="mb-3">
              <label for="editGatewayRate" class="form-label">Rate to 1 USD</label>
              <input type="number" step="0.0001" class="form-control" id="editGatewayRate" name="rate_to_usd" required>
            </div>
            <div class="mb-3 row g-2">
              <div class="col">
                <label for="editGatewayMin" class="form-label">Minimum</label>
                <input type="number" step="0.01" class="form-control" id="editGatewayMin" name="min_amount" required>
              </div>
              <div class="col">
                <label for="editGatewayMax" class="form-label">Maximum</label>
                <input type="number" step="0.01" class="form-control" id="editGatewayMax" name="max_amount" required>
              </div>
            </div>
            <div class="mb-3">
              <label for="editGatewayInstructions" class="form-label">Deposit Instructions</label>
              <textarea class="form-control" id="editGatewayInstructions" name="instructions" rows="2" required></textarea>
            </div>
            <div class="mb-3">
              <label for="editGatewayUserData" class="form-label">User Data (e.g. Proof of Payment)</label>
              <input type="text" class="form-control" id="editGatewayUserData" name="user_data_label" required>
            </div>
          </div>
          <div class="modal-footer" style="padding: 0.5rem 1rem; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem; background: #181f2a;">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-info btn-sm">Update Gateway</button>
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
  </script>
  <!-- Edit Gateway Modal logic -->
  <script>
    function openEditGatewayModal(gw) {
      var modal = new bootstrap.Modal(document.getElementById('editGatewayModal'));
      document.getElementById('editGatewayId').value = gw.id;
      document.getElementById('editGatewayName').value = gw.name;
      document.getElementById('editGatewayCurrency').value = gw.currency;
      document.getElementById('editGatewayRate').value = gw.rate_to_usd;
      document.getElementById('editGatewayMin').value = gw.min_amount;
      document.getElementById('editGatewayMax').value = gw.max_amount;
      document.getElementById('editGatewayInstructions').value = gw.instructions;
      document.getElementById('editGatewayUserData').value = gw.user_data_label;
      document.getElementById('editGatewayExistingThumb').value = gw.thumbnail || '';
      var thumbPreview = document.getElementById('editGatewayThumbPreview');
      if (gw.thumbnail) {
        thumbPreview.innerHTML = '<img src="../public/' + gw.thumbnail + '" alt="Current Thumbnail" style="max-width:60px;max-height:60px;border-radius:6px;">';
      } else {
        thumbPreview.innerHTML = '<span class="text-secondary">No thumbnail</span>';
      }
      document.getElementById('editGatewayThumb').value = '';
      modal.show();
    }
  </script>
</body>
</html> 