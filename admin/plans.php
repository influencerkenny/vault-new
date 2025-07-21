<?php
// admin/plans.php
session_start();
require_once '../api/config.php'; // DB connection

// Check admin authentication (simple check, adjust as needed)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Handle Add/Edit/Delete actions
$errors = [];
$success = '';

// Add Plan
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    // Basic validation
    $required = ['name','description','lock_in_duration','min_investment','max_investment','status','roi_type','roi_mode','roi_value'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '') {
            $errors[] = ucfirst(str_replace('_',' ',$field)) . ' is required.';
        }
    }
    // Numeric validation
    $numeric = ['lock_in_duration','min_investment','max_investment','roi_value'];
    foreach ($numeric as $field) {
        if (isset($_POST[$field]) && !is_numeric($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_',' ',$field)) . ' must be a number.';
        }
    }
    if (!$errors) {
        try {
            $stmt = $pdo->prepare("INSERT INTO plans (name, description, lock_in_duration, min_investment, max_investment, status, currency, roi_type, roi_mode, roi_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['lock_in_duration'],
                $_POST['min_investment'],
                $_POST['max_investment'],
                $_POST['status'],
                'SOL',
                $_POST['roi_type'],
                $_POST['roi_mode'],
                $_POST['roi_value']
            ]);
            if ($result) {
                $success = 'Plan added successfully!';
            } else {
                $errors[] = 'Failed to add plan.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Failed to add plan: ' . $e->getMessage();
        }
    }
}

// Edit Plan
if (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("UPDATE plans SET name=?, description=?, lock_in_duration=?, min_investment=?, max_investment=?, status=?, currency=?, roi_type=?, roi_mode=?, roi_value=? WHERE id=?");
    $result = $stmt->execute([
        $_POST['name'],
        $_POST['description'],
        $_POST['lock_in_duration'],
        $_POST['min_investment'],
        $_POST['max_investment'],
        $_POST['status'],
        'SOL',
        $_POST['roi_type'],
        $_POST['roi_mode'],
        $_POST['roi_value'],
        $_POST['id']
    ]);
    if ($result) {
        $success = 'Plan updated successfully!';
    } else {
        $errors[] = 'Failed to update plan: ' . ($stmt->errorInfo()[2] ?? 'Unknown error');
    }
}

// Delete Plan
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("DELETE FROM plans WHERE id=?");
    $result = $stmt->execute([$_POST['id']]);
    if ($result) {
        $success = 'Plan deleted successfully!';
    } else {
        $errors[] = 'Failed to delete plan.';
    }
}

// Fetch all plans
$plans = [];
$stmt = $pdo->query("SELECT * FROM plans ORDER BY created_at DESC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: Display ROI as 'X% per week' or '$X per month'
function display_roi($plan) {
  $type = isset($plan['roi_type']) ? ucfirst($plan['roi_type']) : 'Daily';
  $mode = isset($plan['roi_mode']) && $plan['roi_mode'] === 'fixed' ? '$' : '%';
  $value = isset($plan['roi_value']) ? $plan['roi_value'] : 0;
  return ($mode === '%' ? $value.'%' : '$'.$value) . ' per ' . strtolower($type);
}

// After processing Add/Edit/Delete and fetching $plans:
if (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    ob_start();
    // Output any success/error messages
    if ($success) {
        echo '<div style="background:#22c55e;padding:10px 18px;border-radius:6px;margin-bottom:12px;">' . $success . '</div>';
    }
    if ($errors) {
        echo '<div style="background:#ef4444;padding:10px 18px;border-radius:6px;margin-bottom:12px;">' . implode('<br>', $errors) . '</div>';
    }
    // Output only the plans grid and mobile list
    ?>
    <div class="plans-grid">
      <?php foreach ($plans as $plan): ?>
        <div class="plan-dashboard-card">
          <div class="plan-icon"><i class="bi bi-layers"></i></div>
          <div class="plan-title"><?= htmlspecialchars($plan['name']) ?> <span class="badge-status badge-<?= $plan['status'] === 'active' ? 'active' : 'inactive' ?>"> <?= ucfirst($plan['status']) ?> </span></div>
          <div class="plan-desc"><?= htmlspecialchars($plan['description']) ?></div>
          <div class="plan-row">
            <span><i class="bi bi-graph-up"></i> <?= display_roi($plan) ?></span>
            <span><i class="bi bi-lock"></i> Lock-in: <b><?= $plan['lock_in_duration'] ?>d</b></span>
          </div>
          <div class="plan-row">
            <span><i class="bi bi-cash-coin"></i> Min: <b><?= $plan['min_investment'] ?></b></span>
            <span><i class="bi bi-cash-coin"></i> Max: <b><?= $plan['max_investment'] ?></b></span>
          </div>
          <div class="plan-actions">
            <button class="btn btn-edit" onclick='openEditModal(<?= json_encode($plan) ?>)'><i class="bi bi-pencil"></i> Edit</button>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this plan?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $plan['id'] ?>">
              <button class="btn btn-delete"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="plans-mobile-list">
      <?php foreach ($plans as $plan): ?>
        <div class="plan-card">
          <div class="plan-card-header">
            <span class="plan-card-title"><?= htmlspecialchars($plan['name']) ?></span>
            <span class="badge-status badge-<?= $plan['status'] === 'active' ? 'active' : 'inactive' ?> plan-card-status">
              <?= ucfirst($plan['status']) ?>
            </span>
          </div>
          <div class="plan-card-desc"><?= htmlspecialchars($plan['description']) ?></div>
          <div class="plan-card-row">
            <span><i class="bi bi-graph-up"></i> <?= display_roi($plan) ?></span>
            <span><i class="bi bi-lock"></i> Lock-in: <b><?= $plan['lock_in_duration'] ?>d</b></span>
          </div>
          <div class="plan-card-row">
            <span><i class="bi bi-cash-coin"></i> Min: <b><?= $plan['min_investment'] ?></b></span>
            <span><i class="bi bi-cash-coin"></i> Max: <b><?= $plan['max_investment'] ?></b></span>
          </div>
          <div class="plan-card-actions" style="margin-top:10px;">
            <button class="icon-btn icon-btn-edit" title="Edit" onclick='openEditModal(<?= json_encode($plan) ?>)'><i class="bi bi-pencil"></i></button>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this plan?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $plan['id'] ?>">
              <button class="icon-btn icon-btn-delete" title="Delete"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Plans</title>
    <!-- Removed Google Fonts link due to network issues -->
    <!--<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/globals.css">
    <style>
        body { font-family: 'Inter', Arial, sans-serif; background: #0f172a; color: #e5e7eb; }
        .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; }
        .plans-container { max-width: 1200px; width: 100%; margin: 40px auto; background: #181f2a; border-radius: 18px; box-shadow: 0 4px 32px #0003; padding: 36px 20px 28px 20px; }
        .plans-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 12px; }
        .plans-header h1 { font-size: 2.1rem; margin: 0; font-weight: 700; letter-spacing: -1px; }
        .plans-header p { color: #94a3b8; margin: 0; font-size: 1.08rem; }
        .add-btn {
            background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%);
            color: #fff;
            border: none;
            padding: 14px 36px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.15rem;
            box-shadow: 0 4px 16px #2563eb33, 0 1.5px 8px 0 rgba(31,41,55,0.10);
            transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
            letter-spacing: 0.01em;
            outline: none;
            position: relative;
            overflow: hidden;
        }
        .add-btn:hover, .add-btn:focus {
            background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 100%);
            box-shadow: 0 6px 24px #2563eb44, 0 1.5px 8px 0 rgba(31,41,55,0.13);
            transform: translateY(-2px) scale(1.03);
        }
        .add-btn:active {
            background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%);
            box-shadow: 0 2px 8px #2563eb22;
            transform: scale(0.98);
        }
        /* Dashboard-style plan cards grid */
        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 18px; }
        .plan-dashboard-card { background: linear-gradient(135deg, #2563eb22 0%, #0ea5e922 100%); border: 1px solid #2563eb33; border-radius: 1.25rem; box-shadow: 0 6px 32px 0 rgba(37,99,235,0.10), 0 1.5px 8px 0 rgba(31,41,55,0.10); color: #e5e7eb; padding: 2rem 1.5rem 1.5rem 1.5rem; display: flex; flex-direction: column; justify-content: space-between; min-height: 220px; position: relative; transition: box-shadow 0.2s, border 0.2s, background 0.2s; overflow: hidden; }
        .plan-dashboard-card .plan-icon { font-size: 2.2rem; margin-bottom: 0.5rem; border-radius: 0.75rem; padding: 0.5rem; background: linear-gradient(90deg, #2563eb 0%, #38bdf8 100%); color: #fff; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px 0 rgba(59,130,246,0.10); }
        .plan-dashboard-card .plan-title { font-size: 1.25rem; font-weight: 700; color: #60a5fa; margin-bottom: 0.2rem; }
        .plan-dashboard-card .plan-desc { color: #cbd5e1; font-size: 1.01rem; margin-bottom: 0.7rem; }
        .plan-dashboard-card .plan-row { display: flex; flex-wrap: wrap; gap: 18px; font-size: 1.01rem; color: #a1a1aa; margin-bottom: 0.2rem; }
        .plan-dashboard-card .plan-row span { min-width: 120px; display: inline-block; }
        .plan-dashboard-card .badge-status { margin-left: 8px; }
        .plan-dashboard-card .plan-actions { display: flex; gap: 10px; margin-top: 10px; }
        .plan-dashboard-card .btn { padding: 6px 16px; font-size: 0.98em; border-radius: 6px; }
        .plan-dashboard-card .btn-edit { background: #f59e42; color: #fff; }
        .plan-dashboard-card .btn-edit:hover { background: #d97706; }
        .plan-dashboard-card .btn-delete { background: #ef4444; color: #fff; }
        .plan-dashboard-card .btn-delete:hover { background: #b91c1c; }
        .table-responsive, .plans-mobile-list { display: none; }
        @media (max-width: 991px) { .main-content { margin-left: 0; } .plans-container { margin: 24px 0; } }
        @media (max-width: 700px) {
            .plans-container { padding: 2px 18px; }
            .plans-header { flex-direction: column; align-items: flex-start; gap: 4px; margin-bottom: 10px; }
            .plans-header h1 { font-size: 1.3rem; }
            .plans-header p { font-size: 0.98rem; }
            .plan-card, .plan-dashboard-card { padding: 10px 6px 10px 6px !important; margin-bottom: 10px !important; border-radius: 10px !important; margin-left: 12px; margin-right: 12px; }
            .plan-card-header, .plan-dashboard-card .plan-title { font-size: 1.05rem; }
            .plan-card-row, .plan-dashboard-card .plan-row { gap: 6px; font-size: 0.93rem; }
            .plan-card-actions, .plan-dashboard-card .plan-actions { gap: 6px; margin-top: 6px; }
            .plan-card .btn, .plan-dashboard-card .btn { padding: 5px 10px; font-size: 0.95em; }
            .plan-card-title { font-size: 1.05rem; }
            .plan-card-desc { font-size: 0.95rem; }
            .plan-card-row span { min-width: 90px; }
            .plans-mobile-list { margin-top: 8px; }
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 2001;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.65);
            align-items: center;
            justify-content: center;
            transition: background 0.25s;
        }
        .modal.active {
            display: flex !important;
            animation: modalFadeIn 0.25s;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            background: linear-gradient(135deg, #232b3b 80%, #202736 100%);
            color: #e5e7eb;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 12px 48px #0008, 0 2px 12px 0 rgba(31,41,55,0.15);
            min-width: 280px;
            max-width: 98vw;
            width: 100%;
            max-width: 420px;
            max-height: 85vh;
            overflow: hidden;
            animation: modalScaleIn 0.25s;
            position: relative;
            border: 1.5px solid #2563eb33;
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%);
            color: #fff;
            padding: 16px 20px 12px 20px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
        }
        
        .close-modal {
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
        }
        
        .close-modal:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }
        
        .modal-section {
            padding: 12px 20px;
            border-bottom: 1px solid #374151;
        }
        
        .modal-section:last-of-type {
            border-bottom: none;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            color: #60a5fa;
        }
        
        .section-header i {
            margin-right: 6px;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .modal-content label {
            font-weight: 500;
            color: #94a3b8;
            margin-bottom: 4px;
            display: block;
            font-size: 0.9rem;
        }
        
        .modal-content input,
        .modal-content textarea,
        .modal-content select {
            background: #1e293b;
            color: #fff;
            border: 1.5px solid #374151;
            border-radius: 6px;
            padding: 8px 10px;
            width: 100%;
            font-size: 0.9rem;
            transition: all 0.2s;
            box-shadow: 0 1px 3px #0002;
        }
        
        .modal-content input:focus,
        .modal-content textarea:focus,
        .modal-content select:focus {
            border: 1.5px solid #38bdf8;
            outline: none;
            box-shadow: 0 0 0 3px #38bdf833;
            background: #1e293b;
        }
        
        .modal-content input::placeholder,
        .modal-content textarea::placeholder {
            color: #64748b;
        }
        
        .modal-content textarea {
            min-height: 60px;
            resize: vertical;
        }
        
        .modal-footer {
            padding: 16px 20px;
            background: #1e293b;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-shrink: 0;
        }
        
        .modal-footer .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-secondary {
            background: #64748b;
            color: #fff;
        }
        
        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%);
            color: #fff;
            box-shadow: 0 2px 8px #2563eb33;
        }
        
        .btn-primary:hover {
            background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 100%);
            box-shadow: 0 4px 16px #2563eb44;
            transform: translateY(-1px);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Scrollable content area */
        .modal-form-content {
            flex: 1;
            overflow-y: auto;
            max-height: calc(85vh - 120px); /* Subtract header and footer height */
        }
        @media (max-width: 700px) {
            .modal-content {
                max-width: 95vw;
                margin: 10px;
                border-radius: 12px;
                max-height: 85vh;
            }
            
            .modal-header {
                padding: 12px 16px 8px 16px;
            }
            
            .modal-header h2 {
                font-size: 1rem;
            }
            
            .modal-section {
                padding: 10px 16px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .modal-footer {
                padding: 12px 16px;
                flex-direction: column;
            }
            
            .modal-footer .btn {
                width: 100%;
                justify-content: center;
                padding: 10px 16px;
            }
        }
        .table-responsive { border-radius: 1rem; overflow-x: auto; }
        table.table { min-width: 900px; border-radius: 1rem; overflow: hidden; background: #151a23; }
        table.table th, table.table td { vertical-align: middle; }
        table.table th { background: #232b3b; color: #60a5fa; font-weight: 700; position: sticky; top: 0; z-index: 2; }
        table.table tr:nth-child(even) { background: #181f2a; }
        table.table tr:nth-child(odd) { background: #151a23; }
        table.table td, table.table th { padding: 12px 8px; }
        .badge.bg-success { background: #22c55e !important; color: #fff; }
        .badge.bg-secondary { background: #64748b !important; color: #fff; }
        .btn-sm { font-size: 0.97rem; padding: 5px 14px; border-radius: 6px; }
        @media (max-width: 991px) {
            .table-responsive { border-radius: 0.7rem; }
            table.table { min-width: 700px; font-size: 0.97rem; }
            table.table th, table.table td { padding: 8px 4px; }
        }
        @media (max-width: 700px) {
            .table-responsive, table.table { display: none !important; }
            .plans-mobile-list { display: block !important; margin-top: 8px; }
            .plan-card {
                background: linear-gradient(135deg, #232b3b 80%, #202736 100%);
                box-shadow: 0 2px 12px #0003;
                border: 1.5px solid #2563eb33;
                padding: 14px 10px 12px 10px !important;
                margin-bottom: 16px !important;
                border-radius: 13px !important;
                font-size: 1.01rem;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .plan-card-header { font-size: 1.13rem; font-weight: 700; color: #38bdf8; display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px; }
            .plan-card-title { font-size: 1.08rem; }
            .plan-card-desc { font-size: 0.97rem; color: #a1a1aa; margin-bottom: 2px; }
            .plan-card-row { gap: 6px; font-size: 0.95rem; display: flex; flex-wrap: wrap; }
            .plan-card-actions { gap: 8px; margin-top: 8px; display: flex; }
            .plan-card .btn { padding: 7px 14px; font-size: 0.97em; border-radius: 7px; }
        }
        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            border: none;
            outline: none;
            box-shadow: 0 2px 8px #0002;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            cursor: pointer;
            padding: 0;
        }
        .icon-btn-edit {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e42 100%);
            color: #fff;
            border: 1.5px solid #fbbf2444;
        }
        .icon-btn-edit:hover, .icon-btn-edit:focus {
            background: linear-gradient(135deg, #f59e42 0%, #fbbf24 100%);
            box-shadow: 0 4px 16px #fbbf2444;
            transform: scale(1.08);
        }
        .icon-btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            color: #fff;
            border: 1.5px solid #ef444444;
        }
        .icon-btn-delete:hover, .icon-btn-delete:focus {
            background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);
            box-shadow: 0 4px 16px #ef444444;
            transform: scale(1.08);
        }
        .icon-btn i { font-size: 1.25rem; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
  <?php include 'header.php'; ?>
  <div class="plans-container">
    <div class="plans-header">
      <div>
        <h1>Staking Plans</h1>
        <p>Manage all available staking plans for users. Add, edit, or remove plans as needed.</p>
      </div>
      <button class="add-btn" onclick="openAddModal()"><i class="bi bi-plus-circle me-2"></i>Add Stake</button>
    </div>
    <?php if ($success): ?>
        <div style="background:#22c55e;padding:10px 18px;border-radius:6px;margin-bottom:12px;"> <?= $success ?> </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div style="background:#ef4444;padding:10px 18px;border-radius:6px;margin-bottom:12px;">
            <?php foreach ($errors as $e) echo $e.'<br>'; ?>
        </div>
    <?php endif; ?>
    <!-- Responsive plans table for desktop -->
    <div class="table-responsive d-none d-md-block" style="margin-bottom: 2rem;">
      <table class="table table-dark table-striped table-hover align-middle" style="border-radius: 1rem; overflow: hidden;">
        <thead>
          <tr>
            <th>Name</th>
            <th>Description</th>
            <th class="text-end">Daily ROI (%)</th>
            <th class="text-end">Monthly ROI (%)</th>
            <th class="text-end">Lock-in (days)</th>
            <th class="text-end">Min Invest</th>
            <th class="text-end">Max Invest</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plans as $plan): ?>
          <tr>
            <td><?= htmlspecialchars($plan['name']) ?></td>
            <td><?= htmlspecialchars($plan['description']) ?></td>
            <td class="text-end"><?= display_roi($plan) ?></td>
            <td class="text-end"><?= display_roi($plan) ?></td>
            <td class="text-end"><?= $plan['lock_in_duration'] ?></td>
            <td class="text-end"><?= number_format($plan['min_investment'], 2) ?></td>
            <td class="text-end"><?= number_format($plan['max_investment'], 2) ?></td>
            <td><span class="badge bg-<?= $plan['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($plan['status']) ?></span></td>
            <td>
              <div class="dropdown">
                <button class="btn btn-sm btn-info dropdown-toggle" type="button" id="dropdownMenuButtonPlan<?=$plan['id']?>" data-bs-toggle="dropdown" aria-expanded="false">
                  Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="dropdownMenuButtonPlan<?=$plan['id']?>">
                  <li>
                    <button class="dropdown-item" type="button" onclick='openEditModal(<?= json_encode($plan) ?>)'><i class="bi bi-pencil me-2"></i>Edit</button>
                  </li>
                  <li>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $plan['id'] ?>">
                      <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Delete this plan?');"><i class="bi bi-trash me-2"></i>Delete</button>
                    </form>
                  </li>
                </ul>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <!-- Mobile card/list view -->
    <div class="plans-mobile-list d-md-none">
      <?php foreach ($plans as $plan): ?>
        <div class="plan-card">
          <div class="plan-card-header">
            <span class="plan-card-title"><?= htmlspecialchars($plan['name']) ?></span>
            <span class="badge-status badge-<?= $plan['status'] === 'active' ? 'active' : 'inactive' ?> plan-card-status">
              <?= ucfirst($plan['status']) ?>
            </span>
          </div>
          <div class="plan-card-desc"><?= htmlspecialchars($plan['description']) ?></div>
          <ul style="list-style:none;padding:0;margin:0;">
            <li><b>Minimum Stake:</b> <span style="float:right; color:#38bdf8; font-weight:600;">$<?= number_format($plan['min_investment'], 2) ?></span></li>
            <li><b>Maximum Stake:</b> <span style="float:right; color:#38bdf8; font-weight:600;">$<?= number_format($plan['max_investment'], 2) ?></span></li>
            <li><b>Daily ROI:</b> <span style="float:right; color:#38bdf8; font-weight:600;"><?= display_roi($plan) ?></span></li>
            <li><b>Monthly ROI:</b> <span style="float:right; color:#38bdf8; font-weight:600;"><?= display_roi($plan) ?></span></li>
            <li><b>Lock-in:</b> <span style="float:right; color:#38bdf8; font-weight:600;"><?= $plan['lock_in_duration'] ?> days</span></li>
          </ul>
          <div class="plan-card-actions" style="margin-top:10px;">
            <button class="icon-btn icon-btn-edit" title="Edit" onclick='openEditModal(<?= json_encode($plan) ?>)'><i class="bi bi-pencil"></i></button>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this plan?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $plan['id'] ?>">
              <button class="icon-btn icon-btn-delete" title="Delete"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php include 'footer.php'; ?>
<!-- Modal at end of body for proper overlay -->
<div class="modal" id="planModal">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <button class="close-modal" onclick="closeModal()" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
            <h2 id="modalTitle"><i class="bi bi-plus-circle me-2"></i>Add Plan</h2>
        </div>
        
        <div class="modal-form-content">
            <form method="post" id="planForm">
                <!-- Basic Information Section -->
                <div class="modal-section">
                    <div class="section-header">
                        <i class="bi bi-info-circle text-info"></i>
                        <span>Basic Information</span>
                    </div>
                    <div class="form-group">
                        <label for="planName">
                            <i class="bi bi-tag me-1"></i>Plan Name
                        </label>
                        <input type="text" name="name" id="planName" placeholder="Enter plan name" required>
                    </div>
                    <div class="form-group">
                        <label for="planDesc">
                            <i class="bi bi-text-paragraph me-1"></i>Description
                        </label>
                        <textarea name="description" id="planDesc" placeholder="Enter plan description" required></textarea>
                    </div>
                </div>

                <!-- ROI Configuration Section -->
                <div class="modal-section">
                    <div class="section-header">
                        <i class="bi bi-graph-up text-success"></i>
                        <span>ROI Configuration</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="planRoiType">
                                <i class="bi bi-calendar me-1"></i>ROI Type
                            </label>
                            <select name="roi_type" id="planRoiType" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="planRoiMode">
                                <i class="bi bi-percent me-1"></i>ROI Mode
                            </label>
                            <select name="roi_mode" id="planRoiMode" required>
                                <option value="percent">Percent (%)</option>
                                <option value="fixed">Fixed ($)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="planRoiValue" id="planRoiValueLabel">
                            <i class="bi bi-calculator me-1"></i>ROI Value
                        </label>
                        <input type="number" step="0.0001" name="roi_value" id="planRoiValue" placeholder="Enter ROI value" required>
                    </div>
                </div>

                <!-- Investment Limits Section -->
                <div class="modal-section">
                    <div class="section-header">
                        <i class="bi bi-cash-coin text-warning"></i>
                        <span>Investment Limits</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="planMinInvest">
                                <i class="bi bi-arrow-down me-1"></i>Minimum Investment
                            </label>
                            <input type="number" step="0.00000001" name="min_investment" id="planMinInvest" placeholder="Min amount" required>
                        </div>
                        <div class="form-group">
                            <label for="planMaxInvest">
                                <i class="bi bi-arrow-up me-1"></i>Maximum Investment
                            </label>
                            <input type="number" step="0.00000001" name="max_investment" id="planMaxInvest" placeholder="Max amount" required>
                        </div>
                    </div>
                </div>

                <!-- Plan Settings Section -->
                <div class="modal-section">
                    <div class="section-header">
                        <i class="bi bi-gear text-primary"></i>
                        <span>Plan Settings</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="planLockIn">
                                <i class="bi bi-lock me-1"></i>Lock-in Period (days)
                            </label>
                            <input type="number" name="lock_in_duration" id="planLockIn" placeholder="Enter days" required>
                        </div>
                        <div class="form-group">
                            <label for="planStatus">
                                <i class="bi bi-toggle-on me-1"></i>Status
                            </label>
                            <select name="status" id="planStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="currency" value="SOL">
                <input type="hidden" name="action" value="add" id="formAction">
                <input type="hidden" name="id" id="planId">
            </form>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                <i class="bi bi-x-circle me-2"></i>Cancel
            </button>
            <button class="btn btn-primary" type="submit" id="modalSubmitBtn" form="planForm">
                <i class="bi bi-plus-circle me-2"></i>Add Plan
            </button>
        </div>
    </div>
</div>
<script>
function openAddModal() {
    document.body.style.overflow = 'hidden';
    document.getElementById('modalTitle').innerText = 'Add Plan';
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalSubmitBtn').innerText = 'Add Plan';
    document.getElementById('planForm').reset();
    document.getElementById('planId').value = '';
    document.getElementById('planRoiType').value = 'daily';
    document.getElementById('planRoiMode').value = 'percent';
    document.getElementById('planModal').classList.add('active');
}
window.openAddModal = openAddModal;

function openEditModal(plan) {
    document.getElementById('modalTitle').innerText = 'Edit Plan';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalSubmitBtn').innerText = 'Update Plan';
    document.getElementById('planId').value = plan.id;
    document.getElementById('planName').value = plan.name;
    document.getElementById('planDesc').value = plan.description;
    document.getElementById('planRoiType').value = plan.roi_type || 'daily';
    document.getElementById('planRoiMode').value = plan.roi_mode || 'percent';
    document.getElementById('planRoiValue').value = plan.roi_value;
    document.getElementById('planLockIn').value = plan.lock_in_duration;
    document.getElementById('planMinInvest').value = plan.min_investment;
    document.getElementById('planMaxInvest').value = plan.max_investment;
    document.getElementById('planStatus').value = plan.status;
    document.getElementById('planModal').classList.add('active');
}
function closeModal() {
    document.getElementById('planModal').classList.remove('active');
    document.body.style.overflow = '';
}
// Close modal on outside click
window.onclick = function(event) {
    var modal = document.getElementById('planModal');
    if (event.target == modal) closeModal();
}

// AJAX for Add/Edit Plan
const planForm = document.getElementById('planForm');
if (planForm) {
    planForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(planForm);
        const action = formData.get('action');
        let url = window.location.href;
        
        // Show loading state
        document.getElementById('modalSubmitBtn').disabled = true;
        document.getElementById('modalSubmitBtn').innerText = (action === 'add' ? 'Adding...' : 'Updating...');
        
        try {
            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const text = await res.text();
            console.log('Response text:', text); // Debug log
            
            // Try to parse returned HTML and update the plans grid
            const parser = new DOMParser();
            const doc = parser.parseFromString(text, 'text/html');
            
            // Check for parsing errors
            const parseError = doc.querySelector('parsererror');
            if (parseError) {
                console.error('HTML parsing error:', parseError.textContent);
                throw new Error('Invalid response format');
            }
            
            // Replace the plans grid and mobile list
            const newGrid = doc.querySelector('.plans-grid');
            const newMobile = doc.querySelector('.plans-mobile-list');
            
            if (newGrid && newMobile) {
                const currentGrid = document.querySelector('.plans-grid');
                const currentMobile = document.querySelector('.plans-mobile-list');
                
                if (currentGrid && currentMobile) {
                    currentGrid.innerHTML = newGrid.innerHTML;
                    currentMobile.innerHTML = newMobile.innerHTML;
                }
            }
            
            // Show success/error messages
            const newSuccess = doc.querySelector('div[style*="background:#22c55e"]');
            const newError = doc.querySelector('div[style*="background:#ef4444"]');
            
            if (newSuccess) {
                alert(newSuccess.textContent.trim());
                closeModal();
                // Reload the page to show updated data
                window.location.reload();
            } else if (newError) {
                alert(newError.textContent.trim());
            } else {
                // If no specific message found, check if the response contains success indicators
                if (text.includes('successfully') || text.includes('Plan added') || text.includes('Plan updated')) {
                    alert('Operation completed successfully!');
                    closeModal();
                    window.location.reload();
                } else {
                    throw new Error('No success or error message found in response');
                }
            }
            
        } catch (err) {
            console.error('Form submission error:', err);
            alert('An error occurred: ' + err.message);
        } finally {
            document.getElementById('modalSubmitBtn').disabled = false;
            document.getElementById('modalSubmitBtn').innerText = (action === 'add' ? 'Add Plan' : 'Update Plan');
        }
    });
}
// AJAX for Delete Plan
// Delegate to all delete forms
function handleDeleteForms() {
    document.querySelectorAll('.plan-dashboard-card form, .plan-card form').forEach(form => {
        form.onsubmit = async function(e) {
            e.preventDefault();
            if (!confirm('Delete this plan?')) return;
            const formData = new FormData(form);
            let url = window.location.href;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const text = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(text, 'text/html');
                const newGrid = doc.querySelector('.plans-grid');
                const newMobile = doc.querySelector('.plans-mobile-list');
                
                if (newGrid && newMobile) {
                    const currentGrid = document.querySelector('.plans-grid');
                    const currentMobile = document.querySelector('.plans-mobile-list');
                    
                    if (currentGrid && currentMobile) {
                        currentGrid.innerHTML = newGrid.innerHTML;
                        currentMobile.innerHTML = newMobile.innerHTML;
                    }
                }
                const newSuccess = doc.querySelector('div[style*="background:#22c55e"]');
                const newError = doc.querySelector('div[style*="background:#ef4444"]');
                if (newSuccess) {
                    alert(newSuccess.textContent.trim());
                } else if (newError) {
                    alert(newError.textContent.trim());
                }
                handleDeleteForms(); // Re-bind after DOM update
            } catch (err) {
                alert('Network or server error.');
            }
        };
    });
}
handleDeleteForms();

// Simple function to update ROI label text only
function updateRoiValueLabel() {
    try {
        var type = document.getElementById('planRoiType');
        var mode = document.getElementById('planRoiMode');
        var labelElem = document.getElementById('planRoiValueLabel');
        
        // Only proceed if all elements exist
        if (!type || !mode || !labelElem) {
            return false;
        }
        
        var typeValue = type.value || 'daily';
        var modeValue = mode.value || 'percent';
        var label = 'ROI Value';
        
        if (modeValue === 'percent') {
            label = 'ROI Value (' + typeValue.charAt(0).toUpperCase() + typeValue.slice(1) + ' %)';
        } else {
            label = 'ROI Value (' + typeValue.charAt(0).toUpperCase() + typeValue.slice(1) + ' $)';
        }
        
        // Use textContent instead of innerHTML for safety
        labelElem.textContent = label;
        return true;
    } catch (error) {
        console.error('Error in updateRoiValueLabel:', error);
        return false;
    }
}

// Add event listeners for ROI type and mode changes
document.addEventListener('DOMContentLoaded', function() {
    // Wait for elements to be ready
    setTimeout(function() {
        const roiTypeSelect = document.getElementById('planRoiType');
        const roiModeSelect = document.getElementById('planRoiMode');
        
        if (roiTypeSelect) {
            roiTypeSelect.addEventListener('change', function() {
                setTimeout(updateRoiValueLabel, 100);
            });
        }
        
        if (roiModeSelect) {
            roiModeSelect.addEventListener('change', function() {
                setTimeout(updateRoiValueLabel, 100);
            });
        }
    }, 200);
});
</script>
<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
<!-- Sidebar -->
<!-- The sidebar is likely included via include 'sidebar.php'; -->
<!-- Hamburger for mobile -->
<!-- Removed extra mobile sidebar toggle button here -->
<!-- Place Bootstrap JS CDN before closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Prevent dropdown from closing when clicking inside the form
  document.querySelectorAll('.dropdown-menu form').forEach(function(form) {
    form.addEventListener('click', function(e) {
      e.stopPropagation();
    });
  });
</script>
</body>
</html> 