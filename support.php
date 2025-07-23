<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: signin.php');
  exit;
}
require_once 'api/settings_helper.php';
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');
$user_id = $_SESSION['user_id'];
$success = $error = '';

// Fetch user info for sidebar/header
$stmt = $pdo->prepare('SELECT first_name, last_name, email, avatar, username FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'public/placeholder-user.jpg';
$displayName = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : 'Investor';
$email = $user ? $user['email'] : '';

// Handle support ticket submission (with file upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['support_subject'], $_POST['support_message']) && isset($_POST['ticket_submit'])) {
  $subject = trim($_POST['support_subject']);
  $message = trim($_POST['support_message']);
  $filePath = null;
  if (isset($_FILES['support_file']) && $_FILES['support_file']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['support_file']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','pdf','txt','doc','docx'];
    if (in_array($ext, $allowed)) {
      $fname = 'support_file_' . time() . '_' . rand(1000,9999) . '.' . $ext;
      $dest = 'public/' . $fname;
      if (move_uploaded_file($_FILES['support_file']['tmp_name'], $dest)) {
        $filePath = $fname;
      } else {
        $error = 'Failed to upload file.';
      }
    } else {
      $error = 'Invalid file type for attachment.';
    }
  }
  if (!$subject || !$message) {
    $error = 'Subject and message are required.';
  } else if (!$error) {
    $stmt = $pdo->prepare('INSERT INTO support_tickets (user_id, subject, message, file, status, created_at) VALUES (?, ?, ?, ?, "open", NOW())');
    if ($stmt->execute([$user_id, $subject, $message, $filePath])) {
      $success = 'Support ticket submitted! Our team will get back to you soon.';
    } else {
      $error = 'Failed to submit support ticket.';
    }
  }
}
// Handle reply submission (with file upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket_id'], $_POST['reply_message']) && isset($_POST['reply_submit'])) {
  $ticket_id = (int)$_POST['reply_ticket_id'];
  $reply_message = trim($_POST['reply_message']);
  $reply_file = null;
  if (isset($_FILES['reply_file']) && $_FILES['reply_file']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['reply_file']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','pdf','txt','doc','docx'];
    if (in_array($ext, $allowed)) {
      $fname = 'support_reply_' . time() . '_' . rand(1000,9999) . '.' . $ext;
      $dest = 'public/' . $fname;
      if (move_uploaded_file($_FILES['reply_file']['tmp_name'], $dest)) {
        $reply_file = $fname;
      } else {
        $error = 'Failed to upload reply file.';
      }
    } else {
      $error = 'Invalid file type for reply attachment.';
    }
  }
  if (!$reply_message) {
    $error = 'Reply message is required.';
  } else if (!$error) {
    $stmt = $pdo->prepare('INSERT INTO support_replies (ticket_id, user_id, message, file, created_at) VALUES (?, ?, ?, ?, NOW())');
    if ($stmt->execute([$ticket_id, $user_id, $reply_message, $reply_file])) {
      $success = 'Reply submitted!';
    } else {
      $error = 'Failed to submit reply.';
    }
  }
}
// Fetch support tickets
$stmt = $pdo->prepare('SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$support_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch all replies for user's tickets
$ticket_ids = array_column($support_tickets, 'id');
$replies_by_ticket = [];
if ($ticket_ids) {
  $in = str_repeat('?,', count($ticket_ids) - 1) . '?';
  $stmt = $pdo->prepare('SELECT * FROM support_replies WHERE ticket_id IN (' . $in . ') ORDER BY created_at ASC');
  $stmt->execute($ticket_ids);
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $reply) {
    $replies_by_ticket[$reply['ticket_id']][] = $reply;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; }
    .sidebar { background: rgba(10,16,30,0.95); border-right: 1px solid #1e293b; min-height: 100vh; width: 260px; position: fixed; top: 0; left: 0; z-index: 2001; padding: 2rem 1.5rem 1.5rem 1.5rem; display: flex; flex-direction: column; transition: left 0.3s; }
    .sidebar .logo { margin-bottom: 2rem; text-align: center; }
    .sidebar .nav-link { color: #cbd5e1; font-weight: 500; border-radius: 0.75rem; padding: 0.75rem 1rem; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.75rem; transition: background 0.2s, color 0.2s; position: relative; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: linear-gradient(90deg, #2563eb22 0%, #0ea5e922 100%); color: #38bdf8; box-shadow: 0 2px 8px 0 rgba(59,130,246,0.08); }
    .sidebar .logout-btn { color: #f87171; font-weight: 500; border-radius: 0.75rem; padding: 0.75rem 1rem; margin-top: auto; background: none; border: none; display: flex; align-items: center; gap: 0.75rem; transition: background 0.2s, color 0.2s; }
    .sidebar .logout-btn:hover { background: #7f1d1d22; color: #f87171; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; }
    .dashboard-header { border-bottom: 1px solid #1e293b; padding: 1.5rem 2rem 1rem 2rem; background: rgba(17,24,39,0.85); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10; }
    .dashboard-header .logo { height: 48px; }
    .dashboard-content-wrapper { max-width: 900px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.93rem; }
    .table-responsive { overflow-x: auto; border-radius: 1rem; }
    .table { min-width: 600px; font-size: 0.92rem; }
    .table th, .table td { padding: 0.35rem 0.5rem; }
    .reply-thread { background: #181f2a; border-radius: 1rem; margin: 1rem 0 2rem 0; padding: 1.2rem; }
    .reply-message { background: #222b3a; border-radius: 0.75rem; padding: 0.7rem 1rem; margin-bottom: 0.7rem; color: #e5e7eb; }
    .reply-meta { font-size: 0.85em; color: #94a3b8; margin-bottom: 0.2em; }
    .reply-file { display: inline-block; margin-top: 0.2em; }
    @media (max-width: 991px) {
      .sidebar { left: -260px; }
      .sidebar.open { left: 0; }
      .main-content { margin-left: 0; }
      .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; font-size: 0.91rem; }
      .sidebar-close-btn { display: block !important; }
    }
    @media (max-width: 767px) {
      .dashboard-content-wrapper { padding: 0 0.1rem; font-size: 0.89rem; }
      .table { font-size: 0.89rem; min-width: 480px; }
      .table th, .table td { padding: 0.28rem 0.35rem; }
    }
    @media (max-width: 575px) {
      .dashboard-content-wrapper { padding: 0 0.05rem; font-size: 0.87rem; }
      .table { font-size: 0.85rem; min-width: 420px; }
      .table th, .table td { padding: 0.18rem 0.18rem; }
      .table-responsive { border-radius: 0.5rem; }
    }
    /* Make table scrollable on mobile */
    .table-responsive {
      -webkit-overflow-scrolling: touch;
      overflow-x: auto;
    }
    /* Optional: Zebra striping for better readability on mobile */
    .table-striped>tbody>tr:nth-of-type(odd) { background-color: #181f2a; }
    .table-striped>tbody>tr:nth-of-type(even) { background-color: #151a23; }
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
      padding: 2rem;
      background: rgba(17,24,39,0.85);
      color: #a1a1aa;
      text-align: center;
      margin-top: auto;
    }
    .success-checkmark {
      display: inline-block;
      vertical-align: middle;
      margin-right: 0.5em;
      animation: popIn 0.7s cubic-bezier(.23,1.12,.77,1.12);
    }
    @keyframes popIn {
      0% { transform: scale(0.2); opacity: 0; }
      60% { transform: scale(1.2); opacity: 1; }
      80% { transform: scale(0.95); }
      100% { transform: scale(1); opacity: 1; }
    }
    .animate-success {
      animation: fadeInSuccess 0.7s cubic-bezier(.23,1.12,.77,1.12);
    }
    @keyframes fadeInSuccess {
      0% { opacity: 0; }
      100% { opacity: 1; }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar" aria-label="Sidebar navigation">
    <button type="button" class="sidebar-close-btn" aria-label="Close sidebar" onclick="closeSidebar()" style="position:absolute;top:14px;right:14px;display:none;font-size:2rem;background:none;border:none;color:#fff;z-index:2100;line-height:1;cursor:pointer;">&times;</button>
    <div class="logo mb-4">
      <img src="/vault-logo-new.png" alt="Vault Logo" height="48" loading="lazy">
    </div>
    <?php
    $sidebarLinks = [
      ['href' => 'user-dashboard.php', 'label' => 'Dashboard', 'icon' => 'bi-house'],
      ['href' => 'plans.php', 'label' => 'Plans', 'icon' => 'bi-layers'],
      ['href' => 'deposits.php', 'label' => 'Deposits', 'icon' => 'bi-download'],
      ['href' => 'withdrawals.php', 'label' => 'Withdrawals', 'icon' => 'bi-upload'],
      ['href' => 'transactions.php', 'label' => 'Transactions', 'icon' => 'bi-list'],
      ['href' => 'referral.php', 'label' => 'Referral', 'icon' => 'bi-people'],
      ['href' => 'account-settings.php', 'label' => 'Settings', 'icon' => 'bi-gear'],
      ['href' => 'profile.php', 'label' => 'Profile', 'icon' => 'bi-person'],
      ['href' => 'support.php', 'label' => 'Support', 'icon' => 'bi-question-circle'],
    ];
    foreach ($sidebarLinks as $link): ?>
      <a href="<?=$link['href']?>" class="nav-link<?=basename($_SERVER['PHP_SELF']) === basename($link['href']) ? ' active' : ''?>">
        <i class="bi <?=$link['icon']?>"></i> <?=$link['label']?>
      </a>
    <?php endforeach; ?>
    <form method="get" class="mt-auto">
      <button type="submit" name="logout" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</button>
    </form>
  </div>
  <!-- Mobile Sidebar Overlay (after sidebar) -->
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'user/header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="dashboard-content-wrapper mx-auto">
        <h2 class="mb-4 text-info fw-bold">Support</h2>
        <?php if ($success): ?>
          <div class="alert alert-success position-relative animate-success" id="supportSuccessAlert">
            <span class="success-checkmark">
              <svg width="32" height="32" viewBox="0 0 32 32"><circle cx="16" cy="16" r="15" fill="#22c55e" opacity="0.15"/><polyline points="10,17 15,22 23,12" fill="none" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span><?=$success?></span>
          </div>
          <script>
            setTimeout(function() {
              var alert = document.getElementById('supportSuccessAlert');
              if (alert) alert.remove();
              if (window.history.replaceState) {
                window.history.replaceState(null, '', window.location.pathname);
              }
            }, 3000);
          </script>
        <?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
        <div class="mb-4">
          <div class="card shadow-lg border-0" style="background:#181f2a;border-radius:1.25rem;max-width:700px;margin:0 auto 2.5rem auto;box-shadow:0 4px 32px #0003;">
            <div class="card-header bg-transparent border-0 pb-0 text-center">
              <div class="d-flex flex-column align-items-center">
                <div class="bg-info bg-gradient rounded-circle d-flex align-items-center justify-content-center mb-2" style="width:56px;height:56px;">
                  <i class="bi bi-life-preserver text-white" style="font-size:2rem;"></i>
                </div>
                <h4 class="fw-bold text-info mb-1">Submit a Support Ticket</h4>
                <p class="text-secondary mb-0" style="max-width:420px;">Need help? Fill out the form below and our support team will get back to you as soon as possible. Please provide as much detail as you can.</p>
              </div>
            </div>
            <div class="card-body p-4">
              <form id="supportForm" method="post" enctype="multipart/form-data" autocomplete="off">
                <div class="mb-3">
                  <label for="support_subject" class="form-label fw-bold" style="font-size:1.1rem;color:#fff;">Subject <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-lg" id="support_subject" name="support_subject" placeholder="Briefly describe your issue" required>
                </div>
                <div class="mb-3">
                  <label for="support_message" class="form-label fw-bold" style="font-size:1.1rem;color:#fff;">Message <span class="text-danger">*</span></label>
                  <textarea class="form-control form-control-lg" id="support_message" name="support_message" rows="5" placeholder="Please provide as much detail as possible..." required></textarea>
                  <div class="form-text text-info mt-1"><i class="bi bi-info-circle"></i> Our team typically responds within 24 hours.</div>
                </div>
                <div class="mb-3">
                  <label for="support_file" class="form-label fw-bold" style="font-size:1.1rem;color:#fff;">Attachment <span class="text-secondary">(optional)</span></label>
                  <input type="file" class="form-control" id="support_file" name="support_file" accept="image/*,application/pdf,.txt,.doc,.docx">
                  <div class="form-text">Accepted: images, PDF, TXT, DOC, DOCX. Max 5MB.</div>
                </div>
                <div class="d-grid mt-4">
                  <button type="submit" class="btn btn-info btn-lg fw-bold shadow-sm" name="ticket_submit">
                    <i class="bi bi-send me-2"></i>Submit Ticket
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <h4 class="mt-5 mb-3 text-info fw-bold">Support History</h4>
        <div class="table-responsive mb-5" style="border-radius: 1rem; overflow: hidden; background: #111827cc;">
          <table class="table table-dark table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>Date</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Status</th>
                <th>Attachment</th>
                <th>Replies</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($support_tickets as $ticket): ?>
              <tr>
                <td><?=date('M d, Y H:i', strtotime($ticket['created_at']))?></td>
                <td><?=htmlspecialchars($ticket['subject'])?></td>
                <td><?=htmlspecialchars($ticket['message'])?></td>
                <td>
                  <?php if ($ticket['status'] === 'open'): ?>
                    <span class="badge bg-warning text-dark">Open</span>
                  <?php elseif ($ticket['status'] === 'closed'): ?>
                    <span class="badge bg-success">Closed</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Other</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($ticket['file'])): ?>
                    <a href="public/<?=htmlspecialchars($ticket['file'])?>" target="_blank">View</a>
                  <?php else: ?>
                    <span class="text-secondary">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#replies<?=$ticket['id']?>" aria-expanded="false" aria-controls="replies<?=$ticket['id']?>">
                    View Replies
                  </button>
                </td>
              </tr>
              <tr class="collapse" id="replies<?=$ticket['id']?>">
                <td colspan="6">
                  <div class="reply-thread">
                    <?php if (!empty($replies_by_ticket[$ticket['id']])): ?>
                      <?php foreach ($replies_by_ticket[$ticket['id']] as $reply): ?>
                        <div class="reply-message">
                          <div class="reply-meta">
                            <?=date('M d, Y H:i', strtotime($reply['created_at']))?>
                            <?php if ($reply['user_id'] == $user_id): ?>
                              <span class="badge bg-info ms-2">You</span>
                            <?php else: ?>
                              <span class="badge bg-secondary ms-2">Admin</span>
                            <?php endif; ?>
                          </div>
                          <?=nl2br(htmlspecialchars($reply['message']))?>
                          <?php if (!empty($reply['file'])): ?>
                            <div class="reply-file"><a href="public/<?=htmlspecialchars($reply['file'])?>" target="_blank">View Attachment</a></div>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <div class="text-muted">No replies yet.</div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" class="mt-3">
                      <input type="hidden" name="reply_ticket_id" value="<?=$ticket['id']?>">
                      <div class="mb-2">
                        <label for="reply_message_<?=$ticket['id']?>" class="form-label">Your Reply</label>
                        <textarea class="form-control" id="reply_message_<?=$ticket['id']?>" name="reply_message" rows="2" required></textarea>
                      </div>
                      <div class="mb-2">
                        <label for="reply_file_<?=$ticket['id']?>" class="form-label">Attachment (optional)</label>
                        <input type="file" class="form-control" id="reply_file_<?=$ticket['id']?>" name="reply_file" accept="image/*,application/pdf,.txt,.doc,.docx">
                      </div>
                      <button type="submit" class="btn btn-info btn-sm" name="reply_submit">Send Reply</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!count($support_tickets)): ?><tr><td colspan="6" class="text-center text-muted">No support history yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
    <footer class="dashboard-footer">
      <img src="/vault-logo-new.png" alt="Vault Logo" height="32" class="mb-2">
      <div class="mb-2">
        <a href="plans.php" class="text-info me-3">Staking Plans</a>
        <a href="roadmap.php" class="text-info">Roadmap</a>
      </div>
      <div>&copy; <?=date('Y')?> Vault. All rights reserved.</div>
    </footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    var sidebar = document.getElementById('sidebar');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarCloseBtn = document.querySelector('.sidebar-close-btn');
    function openSidebar() {
      sidebar.classList.add('open');
      sidebarOverlay.classList.add('active');
    }
    function closeSidebar() {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('active');
    }
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', openSidebar);
    }
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', closeSidebar);
    }
    if (sidebarCloseBtn) {
      sidebarCloseBtn.addEventListener('click', closeSidebar);
    }
    document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
      link.addEventListener('click', function() { if (window.innerWidth < 992) closeSidebar(); });
    });
  </script>
</body>
</html> 