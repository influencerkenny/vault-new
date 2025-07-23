<?php
session_start();

// Database connection (adjust as needed)
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');

$fields = [
  'email' => ''
];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fields['email'] = trim($_POST['email'] ?? '');
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  // Rate limit: check for recent request
  $stmt = $pdo->prepare('SELECT created_at FROM password_reset_logs WHERE email = ? AND event_type = ? ORDER BY created_at DESC LIMIT 1');
  $stmt->execute([$fields['email'], 'request']);
  $lastRequest = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($lastRequest && strtotime($lastRequest['created_at']) > time() - 300) {
    $errors['email'] = 'A reset link was recently sent. Please wait a few minutes before trying again.';
  }
  // Log request
  $pdo->prepare('INSERT INTO password_reset_logs (user_id, email, event_type, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)')
    ->execute([null, $fields['email'], 'request', $ip, $ua]);
  if (!$fields['email']) $errors['email'] = 'Email is required';
  elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format';
  if (!$errors) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$fields['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
      // Generate token
      $token = bin2hex(random_bytes(32));
      $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
      // Store token
      $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')
        ->execute([$user['id'], $token, $expires]);
      // Send email (template)
      $resetLink = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=$token";
      $subject = "Vault Password Reset";
      $logoUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/vault-logo-new.png';
      $message = '<!DOCTYPE html>
      <html lang="en">
      <head><meta charset="UTF-8"><title>Vault Password Reset</title></head>
      <body style="background:#0f172a;padding:32px 0;">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;background:#111827;border-radius:16px;box-shadow:0 8px 32px 0 rgba(31,41,55,0.18);padding:32px 24px;font-family:Inter,sans-serif;">
          <tr><td align="center" style="padding-bottom:16px;"><img src="'.$logoUrl.'" alt="Vault Logo" width="56" height="56" style="border-radius:12px;"></td></tr>
          <tr><td align="center" style="color:#fff;font-size:22px;font-weight:700;padding-bottom:8px;">Reset your Vault password</td></tr>
          <tr><td align="center" style="color:#a1a1aa;font-size:16px;padding-bottom:24px;">We received a request to reset your password. Click the button below to set a new password. This link will expire in 1 hour.</td></tr>
          <tr><td align="center" style="padding-bottom:24px;">
            <a href="'.$resetLink.'" style="display:inline-block;padding:12px 32px;background:linear-gradient(90deg,#2563eb 0%,#0ea5e9 100%);color:#fff;font-weight:600;font-size:16px;border-radius:8px;text-decoration:none;">Reset Password</a>
          </td></tr>
          <tr><td align="center" style="color:#a1a1aa;font-size:13px;padding-bottom:8px;">If the button above does not work, copy and paste this link into your browser:</td></tr>
          <tr><td align="center" style="word-break:break-all;color:#38bdf8;font-size:13px;padding-bottom:16px;"><a href="'.$resetLink.'" style="color:#38bdf8;text-decoration:underline;">'.$resetLink.'</a></td></tr>
          <tr><td align="center" style="color:#64748b;font-size:12px;">If you did not request a password reset, you can safely ignore this email.</td></tr>
        </table>
      </body></html>';
      $headers = "MIME-Version: 1.0" . "\r\n";
      $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
      $headers .= "From: Vault <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
      @mail($fields['email'], $subject, $message, $headers);
      // Log email sent
      $pdo->prepare('INSERT INTO password_reset_logs (user_id, email, event_type, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$user['id'], $fields['email'], 'email_sent', $ip, $ua, json_encode(['token'=>$token])]);
    }
    $success = true; // Always show success for security
  }
}
?>
<?php include 'user/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; min-height: 100vh; }
    .card { background: rgba(17, 24, 39, 0.85); border: 1px solid #1e293b; box-shadow: 0 8px 32px 0 rgba(31, 41, 55, 0.37); will-change: transform; }
    .form-label, .form-control, .form-select, .form-check-label { color: #e5e7eb; }
    .form-control { background: #1e293b; border: 1px solid #374151; }
    .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 0.2rem rgba(37,99,235,.25); }
    .btn-primary { background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%); border: none; }
    .btn-primary:hover, .btn-primary:focus { background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 100%); }
    .logo-img { width: 64px; height: 64px; margin-bottom: 1rem; }
    .modal-content { border-radius: 1rem; }
    .animated-bg {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
    }
    .animated-bg svg { width: 100vw; height: 100vh; display: block; }
    .toast-container { z-index: 2000; }
    .custom-toast {
      background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%);
      color: #fff;
      border-radius: 1rem;
      box-shadow: 0 8px 32px 0 rgba(31, 41, 55, 0.37);
      min-width: 340px;
      border: none;
      padding: 0.75rem 1.25rem;
      position: relative;
      overflow: hidden;
    }
    .custom-toast .checkmark {
      display: inline-block;
      vertical-align: middle;
      margin-right: 0.5rem;
      font-size: 1.5rem;
      color: #22c55e;
      filter: drop-shadow(0 0 4px #22c55e88);
    }
    .custom-toast .progress {
      background: rgba(255,255,255,0.12);
      border-radius: 2px;
      margin-top: 0.75rem;
      height: 5px;
      box-shadow: 0 1px 4px 0 rgba(31,41,55,0.13);
    }
    .custom-toast .progress-bar {
      background: linear-gradient(90deg, #22d3ee 0%, #2563eb 100%);
      border-radius: 2px;
      transition: width 2.5s linear;
    }
    .custom-toast .btn-close {
      filter: invert(1) grayscale(1);
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">
  <div class="animated-bg">
    <svg viewBox="0 0 1920 1080" fill="none" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="bg-grad" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#2563eb"/>
          <stop offset="100%" stop-color="#0ea5e9"/>
        </linearGradient>
      </defs>
      <g>
        <path id="morph1" fill="url(#bg-grad)" opacity="0.25">
          <animate attributeName="d" dur="14s" repeatCount="indefinite"
            values="M 0 0 Q 960 200 1920 0 Q 1720 540 1920 1080 Q 960 880 0 1080 Q 200 540 0 0 Z;
                    M 0 0 Q 960 300 1920 0 Q 1820 540 1920 1080 Q 960 980 0 1080 Q 120 540 0 0 Z;
                    M 0 0 Q 960 200 1920 0 Q 1720 540 1920 1080 Q 960 880 0 1080 Q 200 540 0 0 Z"/>
        </path>
        <path id="morph2" fill="#0ea5e9" opacity="0.18">
          <animate attributeName="d" dur="18s" repeatCount="indefinite"
            values="M 1920 0 Q 1600 400 1920 1080 Q 1200 900 0 1080 Q 400 600 0 0 Q 1000 200 1920 0 Z;
                    M 1920 0 Q 1700 500 1920 1080 Q 1000 1000 0 1080 Q 600 700 0 0 Q 1200 300 1920 0 Z;
                    M 1920 0 Q 1600 400 1920 1080 Q 1200 900 0 1080 Q 400 600 0 0 Q 1000 200 1920 0 Z"/>
        </path>
        <circle cx="1600" cy="200" r="220" fill="#2563eb" opacity="0.13">
          <animate attributeName="r" values="220;260;220" dur="10s" repeatCount="indefinite"/>
          <animate attributeName="cy" values="200;300;200" dur="10s" repeatCount="indefinite"/>
        </circle>
        <circle cx="400" cy="900" r="180" fill="#0ea5e9" opacity="0.10">
          <animate attributeName="r" values="180;220;180" dur="12s" repeatCount="indefinite"/>
          <animate attributeName="cy" values="900;800;900" dur="12s" repeatCount="indefinite"/>
        </circle>
        <circle cx="960" cy="540" r="320" fill="#2563eb" opacity="0.07">
          <animate attributeName="r" values="320;370;320" dur="16s" repeatCount="indefinite"/>
        </circle>
      </g>
    </svg>
  </div>
  <div class="container py-5 position-relative" style="z-index:1;">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        <div class="card p-4 p-md-5 rounded-4">
          <div class="text-center mb-4">
            <img src="/vault-logo-new.png" alt="Vault Logo" class="logo-img" loading="lazy">
            <h1 class="h3 fw-bold mb-2 text-white">Forgot Password</h1>
            <p class="text-secondary">Enter your email to reset your password</p>
          </div>
          <form method="POST" autocomplete="off" aria-label="Forgot password form">
            <div class="mb-3">
              <label for="email" class="form-label">Email Address</label>
              <input id="email" name="email" type="email" value="<?=htmlspecialchars($fields['email'])?>" class="form-control<?=isset($errors['email'])?' is-invalid':''?>" placeholder="Enter your email" required autofocus aria-invalid="<?=isset($errors['email'])?'true':'false'?>" aria-describedby="email-error">
              <?php if(isset($errors['email'])): ?><div id="email-error" class="invalid-feedback d-block"><?=$errors['email']?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2" <?= $success ? 'disabled' : '' ?>>Send Reset Link</button>
          </form>
          <div class="mt-4 text-center">
            <a href="signin.php" class="text-primary fw-semibold">Back to Sign In</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap Toast for Success -->
  <div class="toast-container position-fixed top-0 end-0 p-3" aria-live="polite" aria-atomic="true">
    <div id="success-toast" class="toast custom-toast align-items-center border-0<?= $success ? ' show' : '' ?>" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex align-items-center">
        <span class="checkmark" aria-hidden="true">&#10003;</span>
        <div class="toast-body ps-0">
          <strong>Check your email!</strong> If your email is registered, a reset link has been sent.<br>
          <div class="progress mt-2" style="height: 5px;">
            <div id="toast-progress" class="progress-bar" role="progressbar" style="width: 100%;"></div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
  <script defer>
    function showToastAndRedirect() {
      var toastEl = document.getElementById('success-toast');
      if (!toastEl) return;
      var toast = new bootstrap.Toast(toastEl, { delay: 3500 });
      toast.show();
      var progress = document.getElementById('toast-progress');
      progress.style.width = '0%';
      setTimeout(function() {
        window.location.href = 'signin.php';
      }, 3500);
    }
    window.onload = function() {
      var err = document.querySelector('[aria-invalid="true"]');
      if (err) err.focus();
      <?php if ($success): ?>
      showToastAndRedirect();
      <?php endif; ?>
    }
  </script>
</body>
</html> 