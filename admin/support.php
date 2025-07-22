<?php
session_start();
require_once '../api/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$action_success = $action_error = '';

// Handle delete ticket
if (isset($_POST['action'], $_POST['ticket_id']) && $_POST['action'] === 'delete') {
    $ticketId = intval($_POST['ticket_id']);
    $stmt = $pdo->prepare('DELETE FROM support_tickets WHERE id = ?');
    if ($stmt->execute([$ticketId])) {
        $action_success = 'Ticket deleted.';
    } else {
        $action_error = 'Failed to delete ticket.';
    }
}
// Handle reply to ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket_id'], $_POST['reply_message']) && isset($_POST['reply_submit'])) {
    $ticket_id = (int)$_POST['reply_ticket_id'];
    $reply_message = trim($_POST['reply_message']);
    $reply_file = null;
    if (isset($_FILES['reply_file']) && $_FILES['reply_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['reply_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','pdf','txt','doc','docx'];
        if (in_array($ext, $allowed)) {
            $fname = 'support_reply_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $dest = '../public/' . $fname;
            if (move_uploaded_file($_FILES['reply_file']['tmp_name'], $dest)) {
                $reply_file = $fname;
            } else {
                $action_error = 'Failed to upload reply file.';
            }
        } else {
            $action_error = 'Invalid file type for reply attachment.';
        }
    }
    if (!$reply_message) {
        $action_error = 'Reply message is required.';
    } else if (!$action_error) {
        // Admin reply: user_id is NULL
        $stmt = $pdo->prepare('INSERT INTO support_replies (ticket_id, user_id, message, file, created_at) VALUES (?, NULL, ?, ?, NOW())');
        if ($stmt->execute([$ticket_id, $reply_message, $reply_file])) {
            $action_success = 'Reply submitted!';
        } else {
            $action_error = 'Failed to submit reply.';
        }
    }
}
// Handle close/resolve ticket
if (isset($_POST['action'], $_POST['ticket_id']) && $_POST['action'] === 'close') {
    $ticketId = intval($_POST['ticket_id']);
    $stmt = $pdo->prepare('UPDATE support_tickets SET status = "closed" WHERE id = ?');
    if ($stmt->execute([$ticketId])) {
        $action_success = 'Ticket closed.';
    } else {
        $action_error = 'Failed to close ticket.';
    }
}
// Search/filter logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(u.username LIKE ? OR u.email LIKE ? OR t.subject LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_filter !== '' && $status_filter !== 'all') {
    $where[] = 't.status = ?';
    $params[] = $status_filter;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT t.*, u.username, u.email FROM support_tickets t JOIN users u ON t.user_id = u.id $where_sql ORDER BY t.created_at DESC";
if (!empty($params)) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $pdo->query($sql);
}
$support_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch all replies for tickets
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
  <title>Admin - Support Tickets</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../styles/globals.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; }
    .support-container { max-width: 950px; width: 100%; margin: 10px auto; background: #181f2a; border-radius: 18px; box-shadow: 0 4px 32px #0003; padding: 24px 8px 18px 8px; }
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
    .reply-thread { background: #181f2a; border-radius: 1rem; margin: 1rem 0 2rem 0; padding: 1.2rem; }
    .reply-message { background: #222b3a; border-radius: 0.75rem; padding: 0.7rem 1rem; margin-bottom: 0.7rem; color: #e5e7eb; word-break: break-word; }
    .reply-meta { font-size: 0.85em; color: #94a3b8; margin-bottom: 0.2em; }
    .reply-file { display: inline-block; margin-top: 0.2em; }
    @media (max-width: 991px) { .main-content { margin-left: 0; } .sidebar { left: -260px; } .sidebar.active { left: 0; } .support-container { margin: 8px 2px; padding: 16px; } }
    @media (max-width: 700px) {
      .reply-thread { padding: 0.7rem 0.4rem; border-radius: 0.7rem; }
      .reply-message { padding: 0.5rem 0.5rem; font-size: 0.97rem; }
      .reply-meta { font-size: 0.82em; }
      .reply-file { font-size: 0.95em; }
      .reply-thread form { margin-top: 1rem; }
    }
    @media (max-width: 575px) {
      .support-container { padding: 8px; margin: 2px; }
      .table-responsive, table.table { display: none !important; }
      .admin-support-card-list { display: block; }
      .admin-support-card { background: #232b3b; border-radius: 1rem; box-shadow: 0 2px 12px #0002; margin-bottom: 1.2rem; padding: 1.1rem 1rem; font-size: 0.97rem; }
      .admin-support-card .card-label { color: #60a5fa; font-weight: 600; font-size: 0.98em; margin-bottom: 0.2em; display: block; }
      .admin-support-card .badge { font-size: 0.93em; }
      .admin-support-card .actions { margin-top: 0.7em; }
      .admin-support-card .actions .btn, .admin-support-card .actions .action-popover-btn { font-size: 0.97em; padding: 0.35em 0.9em; }
    }
    @media (min-width: 576px) {
      .admin-support-card-list { display: none; }
    }
    /* Make table scrollable on mobile */
    .table-responsive { -webkit-overflow-scrolling: touch; overflow-x: auto; }
    .table-striped>tbody>tr:nth-of-type(odd) { background-color: #181f2a; }
    .table-striped>tbody>tr:nth-of-type(even) { background-color: #151a23; }
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
    .action-popover-menu {
      z-index: 1055 !important;
      animation: fadeInPopover 0.22s cubic-bezier(.4,0,.2,1);
      outline: none;
      left: 50%;
      transform: translate(-50%, -110%);
      right: auto;
      bottom: auto;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div id="sidebarOverlay" class="sidebar-mobile-overlay"></div>
  <div class="main-content">
    <?php include 'header.php'; ?>
    <div class="support-container">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-2">
        <h1 class="text-info fw-bold mb-0">Support Tickets</h1>
        <form class="d-flex gap-2" method="get" action="">
          <input type="text" class="form-control form-control-sm" name="search" placeholder="Search user, email, subject" value="<?=htmlspecialchars($search)?>" style="max-width:200px;">
          <select class="form-select form-select-sm" name="status" style="max-width:120px;">
            <option value="all"<?=($status_filter===''||$status_filter==='all')?' selected':''?>>All Status</option>
            <option value="open"<?=$status_filter==='open'?' selected':''?>>Open</option>
            <option value="closed"<?=$status_filter==='closed'?' selected':''?>>Closed</option>
          </select>
          <button class="btn btn-info btn-sm" type="submit"><i class="bi bi-search"></i></button>
        </form>
      </div>
      <?php if ($action_success): ?><div class="alert alert-success"><?=htmlspecialchars($action_success)?></div><?php endif; ?>
      <?php if ($action_error): ?><div class="alert alert-danger"><?=htmlspecialchars($action_error)?></div><?php endif; ?>
      <?php if ($action_success && strpos($action_success, 'Reply') !== false): ?>
        <div class="alert alert-success position-relative animate-success" id="replySuccessAlert">
          <span class="success-checkmark">
            <svg width="32" height="32" viewBox="0 0 32 32"><circle cx="16" cy="16" r="15" fill="#22c55e" opacity="0.15"/><polyline points="10,17 15,22 23,12" fill="none" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          <span><?=htmlspecialchars($action_success)?></span>
        </div>
      <?php endif; ?>
      <div class="table-responsive mb-5">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Email</th>
              <th>Subject</th>
              <th>Message</th>
              <th>Status</th>
              <th>Attachment</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($support_tickets as $ticket): ?>
            <tr>
              <td><?=$ticket['id']?></td>
              <td><?=htmlspecialchars($ticket['username'])?> (<?=$ticket['user_id']?>)</td>
              <td><?=htmlspecialchars($ticket['email'])?></td>
              <td><?=htmlspecialchars($ticket['subject'])?></td>
              <td>
                <button type="button" class="btn btn-sm btn-outline-info read-message-btn" data-message="<?=htmlspecialchars($ticket['message'], ENT_QUOTES)?>" data-subject="<?=htmlspecialchars($ticket['subject'], ENT_QUOTES)?>" data-index="<?=$loopIndex = isset($loopIndex) ? $loopIndex+1 : 0?>">
                  Read
                </button>
              </td>
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
                  <a href="../public/<?=htmlspecialchars($ticket['file'])?>" target="_blank">View</a>
                <?php else: ?>
                  <span class="text-secondary">-</span>
                <?php endif; ?>
              </td>
              <td><?=date('Y-m-d H:i', strtotime($ticket['created_at']))?></td>
              <td>
                <div class="position-relative d-inline-block">
                  <button class="btn btn-sm btn-outline-info action-popover-btn" type="button" data-ticket-id="<?=$ticket['id']?>" aria-haspopup="true" aria-expanded="false" aria-controls="actionPopoverMenu<?=$ticket['id']?>">
                    Actions
                  </button>
                  <div class="action-popover-menu shadow-lg bg-dark text-white rounded-3 p-2" id="actionPopoverMenu<?=$ticket['id']?>" style="display:none;position:absolute;min-width:170px;" tabindex="-1" role="menu" aria-labelledby="actionPopoverMenu<?=$ticket['id']?>">
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="ticket_id" value="<?=$ticket['id']?>">
                      <input type="hidden" name="action" value="delete">
                      <button class="dropdown-item text-danger bg-dark" type="submit" onclick="return confirm('Delete this ticket?')"><i class="bi bi-trash me-2"></i>Delete</button>
                    </form>
                    <?php if ($ticket['status'] === 'open'): ?>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="ticket_id" value="<?=$ticket['id']?>">
                      <input type="hidden" name="action" value="close">
                      <button class="dropdown-item text-success bg-dark" type="submit" onclick="return confirm('Close this ticket?')"><i class="bi bi-check-circle me-2"></i>Close</button>
                    </form>
                    <?php endif; ?>
                    <button class="dropdown-item bg-dark text-white" type="button" data-bs-toggle="modal" data-bs-target="#replyModal" data-ticket-id="<?=$ticket['id']?>"><i class="bi bi-reply me-2"></i>Reply</button>
                    <button class="dropdown-item bg-dark text-white replies-modal-btn" type="button" data-ticket-id="<?=$ticket['id']?>"><i class="bi bi-chat-dots me-2"></i>Replies</button>
                  </div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!count($support_tickets)): ?><tr><td colspan="9" class="text-center text-muted">No support tickets found.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <!-- Mobile card view for support tickets -->
      <div class="admin-support-card-list">
        <?php foreach ($support_tickets as $ticket): ?>
          <div class="admin-support-card mb-3">
            <div class="mb-1"><span class="card-label">ID:</span> <?=$ticket['id']?></div>
            <div class="mb-1"><span class="card-label">User:</span> <?=htmlspecialchars($ticket['username'])?> (<?=$ticket['user_id']?>)</div>
            <div class="mb-1"><span class="card-label">Email:</span> <?=htmlspecialchars($ticket['email'])?></div>
            <div class="mb-1"><span class="card-label">Subject:</span> <?=htmlspecialchars($ticket['subject'])?></div>
            <div class="mb-1"><span class="card-label">Message:</span> <button type="button" class="btn btn-sm btn-outline-info read-message-btn" data-message="<?=htmlspecialchars($ticket['message'], ENT_QUOTES)?>" data-subject="<?=htmlspecialchars($ticket['subject'], ENT_QUOTES)?>">Read</button></div>
            <div class="mb-1"><span class="card-label">Status:</span> <?php if ($ticket['status'] === 'open'): ?><span class="badge bg-warning text-dark">Open</span><?php elseif ($ticket['status'] === 'closed'): ?><span class="badge bg-success">Closed</span><?php else: ?><span class="badge bg-secondary">Other</span><?php endif; ?></div>
            <div class="mb-1"><span class="card-label">Attachment:</span> <?php if (!empty($ticket['file'])): ?><a href="../public/<?=htmlspecialchars($ticket['file'])?>" target="_blank">View</a><?php else: ?><span class="text-secondary">-</span><?php endif; ?></div>
            <div class="mb-1"><span class="card-label">Date:</span> <?=date('Y-m-d H:i', strtotime($ticket['created_at']))?></div>
            <div class="actions d-flex flex-wrap gap-2 mt-2">
              <form method="post" style="display:inline;">
                <input type="hidden" name="ticket_id" value="<?=$ticket['id']?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this ticket?')"><i class="bi bi-trash"></i></button>
              </form>
              <?php if ($ticket['status'] === 'open'): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="ticket_id" value="<?=$ticket['id']?>">
                <input type="hidden" name="action" value="close">
                <button class="btn btn-sm btn-success" type="submit" onclick="return confirm('Close this ticket?')"><i class="bi bi-check-circle"></i></button>
              </form>
              <?php endif; ?>
              <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="modal" data-bs-target="#replyModal" data-ticket-id="<?=$ticket['id']?>">Reply</button>
              <button class="btn btn-sm btn-outline-info replies-modal-btn" type="button" data-ticket-id="<?=$ticket['id']?>">Replies</button>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!count($support_tickets)): ?><div class="text-center text-muted">No support tickets found.</div><?php endif; ?>
      </div>
    </div>
    <?php include 'footer.php'; ?>
  </div>
  <!-- Add modal HTML just before </body> -->
  <div class="modal fade" id="readMessageModal" tabindex="-1" aria-labelledby="readMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header border-0">
          <h5 class="modal-title text-info" id="readMessageModalLabel">Ticket Message</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2"><strong>Subject:</strong> <span id="readMessageModalSubject"></span></div>
          <div id="readMessageModalBody"></div>
        </div>
        <div class="modal-footer border-0 d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" id="prevTicketBtn">Previous</button>
          <button type="button" class="btn btn-secondary" id="nextTicketBtn">Next</button>
          <button type="button" class="btn btn-outline-light ms-auto" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Add reply modal HTML just before </body> -->
  <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header border-0">
          <h5 class="modal-title text-info" id="replyModalLabel">Ticket Replies</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="replyModalThread"></div>
          <form id="replyModalForm" method="post" enctype="multipart/form-data" class="mt-3">
            <input type="hidden" name="reply_ticket_id" id="replyModalTicketId">
            <div class="mb-2">
              <label for="replyModalMessage" class="form-label fw-bold" style="color:#fff;">Your Reply</label>
              <textarea class="form-control" id="replyModalMessage" name="reply_message" rows="3" required></textarea>
            </div>
            <div class="mb-2">
              <label for="replyModalFile" class="form-label fw-bold" style="color:#fff;">Attachment (optional)</label>
              <input type="file" class="form-control" id="replyModalFile" name="reply_file" accept="image/*,application/pdf,.txt,.doc,.docx">
            </div>
            <div class="d-grid mt-3">
              <button type="submit" class="btn btn-info btn-lg fw-bold shadow-sm" name="reply_submit">
                <i class="bi bi-send me-2"></i>Send Reply
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!-- Add replies modal HTML just before </body> -->
  <div class="modal fade" id="repliesModal" tabindex="-1" aria-labelledby="repliesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header border-0">
          <h5 class="modal-title text-info" id="repliesModalLabel">Ticket Replies</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="repliesModalThread"></div>
        </div>
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
  <script>
    // Modal logic for Read button with navigation
    const readBtns = document.querySelectorAll('.read-message-btn');
    const readModal = document.getElementById('readMessageModal');
    const readModalBody = document.getElementById('readMessageModalBody');
    const readModalSubject = document.getElementById('readMessageModalSubject');
    const prevBtn = document.getElementById('prevTicketBtn');
    const nextBtn = document.getElementById('nextTicketBtn');
    let currentIndex = 0;
    const tickets = Array.from(readBtns).map(btn => ({
      message: btn.getAttribute('data-message'),
      subject: btn.getAttribute('data-subject')
    }));
    function showModalAtIndex(idx) {
      if (idx < 0 || idx >= tickets.length) return;
      currentIndex = idx;
      readModalBody.textContent = tickets[idx].message;
      readModalSubject.textContent = tickets[idx].subject;
      prevBtn.disabled = idx === 0;
      nextBtn.disabled = idx === tickets.length - 1;
      const modal = bootstrap.Modal.getOrCreateInstance(readModal);
      modal.show();
    }
    readBtns.forEach((btn, idx) => {
      btn.addEventListener('click', function() {
        showModalAtIndex(idx);
      });
    });
    prevBtn.addEventListener('click', function() {
      if (currentIndex > 0) showModalAtIndex(currentIndex - 1);
    });
    nextBtn.addEventListener('click', function() {
      if (currentIndex < tickets.length - 1) showModalAtIndex(currentIndex + 1);
    });
  </script>
  <script>
    // Modal logic for Reply button
    const replyBtns = document.querySelectorAll('button[data-bs-target="#replyModal"]');
    const replyModal = document.getElementById('replyModal');
    const replyModalThread = document.getElementById('replyModalThread');
    const replyModalForm = document.getElementById('replyModalForm');
    const replyModalTicketId = document.getElementById('replyModalTicketId');
    const replyModalMessage = document.getElementById('replyModalMessage');
    const replyModalFile = document.getElementById('replyModalFile');
    const repliesData = <?php echo json_encode($replies_by_ticket); ?>;
    const ticketsData = <?php echo json_encode(array_column($support_tickets, null, 'id')); ?>;
    replyBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        const ticketId = this.getAttribute('data-ticket-id');
        replyModalTicketId.value = ticketId;
        replyModalMessage.value = '';
        replyModalFile.value = '';
        // Build replies thread
        let threadHtml = '';
        if (repliesData[ticketId] && repliesData[ticketId].length > 0) {
          repliesData[ticketId].forEach(function(reply) {
            threadHtml += `<div class='reply-message mb-3'><div class='reply-meta'>${new Date(reply.created_at).toLocaleString()} ` +
              (reply.user_id ? `<span class='badge bg-info ms-2'>User</span>` : `<span class='badge bg-secondary ms-2'>Admin</span>`) +
              `</div>${reply.message.replace(/\n/g, '<br>')}` +
              (reply.file ? `<div class='reply-file'><a href='../public/${reply.file}' target='_blank'>View Attachment</a></div>` : '') +
              `</div>`;
          });
        } else {
          threadHtml = `<div class='text-muted mb-3'>No replies yet.</div>`;
        }
        replyModalThread.innerHTML = threadHtml;
      });
    });
  </script>
  <script>
    // Replies pop-up modal logic
    const repliesBtns = document.querySelectorAll('.replies-modal-btn');
    const repliesModal = document.getElementById('repliesModal');
    const repliesModalThread = document.getElementById('repliesModalThread');
    repliesBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        const ticketId = this.getAttribute('data-ticket-id');
        let threadHtml = '';
        if (repliesData[ticketId] && repliesData[ticketId].length > 0) {
          repliesData[ticketId].forEach(function(reply) {
            threadHtml += `<div class='reply-message mb-3'><div class='reply-meta'>${new Date(reply.created_at).toLocaleString()} ` +
              (reply.user_id ? `<span class='badge bg-info ms-2'>User</span>` : `<span class='badge bg-secondary ms-2'>Admin</span>`) +
              `</div>${reply.message.replace(/\n/g, '<br>')}` +
              (reply.file ? `<div class='reply-file'><a href='../public/${reply.file}' target='_blank'>View Attachment</a></div>` : '') +
              `</div>`;
          });
        } else {
          threadHtml = `<div class='text-muted mb-3'>No replies yet.</div>`;
        }
        repliesModalThread.innerHTML = threadHtml;
        const modal = new bootstrap.Modal(repliesModal);
        modal.show();
      });
    });
  </script>
  <script>
    // Mini popover/modal for Actions
    const actionBtns = document.querySelectorAll('.action-popover-btn');
    actionBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const ticketId = this.getAttribute('data-ticket-id');
        const menu = document.getElementById('actionPopoverMenu' + ticketId);
        // Hide all other popovers
        document.querySelectorAll('.action-popover-menu').forEach(m => { if (m !== menu) m.style.display = 'none'; });
        // Toggle this one
        if (menu.style.display === 'block') {
          menu.style.display = 'none';
          btn.setAttribute('aria-expanded', 'false');
        } else {
          menu.style.display = 'block';
          btn.setAttribute('aria-expanded', 'true');
          menu.focus();
          // Focus trap
          const focusable = menu.querySelectorAll('button, [tabindex]:not([tabindex="-1"])');
          if (focusable.length) focusable[0].focus();
        }
      });
    });
    // Hide popover when clicking outside
    window.addEventListener('click', function(e) {
      if (!e.target.closest('.action-popover-btn') && !e.target.closest('.action-popover-menu')) {
        document.querySelectorAll('.action-popover-menu').forEach(m => m.style.display = 'none');
        actionBtns.forEach(btn => btn.setAttribute('aria-expanded', 'false'));
      }
    });
    // Keyboard navigation and auto-close on action
    const actionMenus = document.querySelectorAll('.action-popover-menu');
    actionMenus.forEach(menu => {
      menu.addEventListener('keydown', function(e) {
        const focusable = menu.querySelectorAll('button, [tabindex]:not([tabindex="-1"])');
        const idx = Array.prototype.indexOf.call(focusable, document.activeElement);
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          if (idx < focusable.length - 1) focusable[idx + 1].focus();
          else focusable[0].focus();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          if (idx > 0) focusable[idx - 1].focus();
          else focusable[focusable.length - 1].focus();
        } else if (e.key === 'Escape') {
          menu.style.display = 'none';
          actionBtns.forEach(btn => btn.setAttribute('aria-expanded', 'false'));
        }
      });
      // Auto-close on action
      menu.querySelectorAll('form,button').forEach(el => {
        el.addEventListener('click', function() {
          setTimeout(() => { menu.style.display = 'none'; actionBtns.forEach(btn => btn.setAttribute('aria-expanded', 'false')); }, 150);
        });
      });
    });
  </script>
</body>
</html> 