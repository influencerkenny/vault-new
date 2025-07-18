<?php
session_start();

// Database connection (adjust as needed)
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');

$token = $_GET['token'] ?? '';
$fields = [
  'password' => '',
  'confirm_password' => ''
];
$errors = [];
$success = false;
$showForm = true;

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Validate token
if ($token) {
  $stmt = $pdo->prepare('SELECT pr.user_id, pr.expires_at, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ?');
  $stmt->execute([$token]);
  $reset = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$reset || strtotime($reset['expires_at']) < time()) {
    $errors['token'] = 'This password reset link is invalid or has expired.';
    $showForm = false;
    // Log reset fail
    $pdo->prepare('INSERT INTO password_reset_logs (user_id, email, event_type, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?)')
      ->execute([$reset['user_id'] ?? null, $reset['email'] ?? '', 'reset_fail', $ip, $ua, json_encode(['reason'=>'invalid_or_expired_token','token'=>$token])]);
  }
} else {
  $errors['token'] = 'Invalid password reset link.';
  $showForm = false;
  // Log reset fail
  $pdo->prepare('INSERT INTO password_reset_logs (user_id, email, event_type, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([null, '', 'reset_fail', $ip, $ua, json_encode(['reason'=>'missing_token'])]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showForm) {
  $fields['password'] = $_POST['password'] ?? '';
  $fields['confirm_password'] = $_POST['confirm_password'] ?? '';
  // Password validation
  if (!$fields['password']) $errors['password'] = 'Password is required';
  elseif (strlen($fields['password']) < 8) $errors['password'] = 'Password must be at least 8 characters';
  elseif (!preg_match('/[A-Z]/', $fields['password'])) $errors['password'] = 'Password must contain an uppercase letter';
  elseif (!preg_match('/[a-z]/', $fields['password'])) $errors['password'] = 'Password must contain a lowercase letter';
  elseif (!preg_match('/[0-9]/', $fields['password'])) $errors['password'] = 'Password must contain a number';
  elseif (!preg_match('/[^A-Za-z0-9]/', $fields['password'])) $errors['password'] = 'Password must contain a special character';
  if ($fields['password'] !== $fields['confirm_password']) $errors['confirm_password'] = 'Passwords do not match';
  if (!$errors) {
    // Update password
    $hash = password_hash($fields['password'], PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $reset['user_id']]);
    // Delete all tokens for this user
    $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$reset['user_id']]);
    $success = true;
    $showForm = false;
    // Log reset success
    $pdo->prepare('INSERT INTO password_reset_logs (user_id, email, event_type, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?)')
      ->execute([$reset['user_id'], $reset['email'], 'reset_success', $ip, $ua, json_encode([])]);
    // Send notification email
    $logoUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/vault-logo-new.png';
    $subject = "Your Vault password was changed";
    $message = '<!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>Password Changed</title></head>
    <body style="background:#0f172a;padding:32px 0;">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;background:#111827;border-radius:16px;box-shadow:0 8px 32px 0 rgba(31,41,55,0.18);padding:32px 24px;font-family:Inter,sans-serif;">
        <tr><td align="center" style="padding-bottom:16px;"><img src="'.$logoUrl.'" alt="Vault Logo" width="56" height="56" style="border-radius:12px;"></td></tr>
        <tr><td align="center" style="color:#fff;font-size:22px;font-weight:700;padding-bottom:8px;">Your Vault password was changed</td></tr>
        <tr><td align="center" style="color:#a1a1aa;font-size:16px;padding-bottom:24px;">This is a confirmation that your Vault account password was recently changed. If you did not perform this action, please contact support immediately.</td></tr>
        <tr><td align="center" style="color:#64748b;font-size:12px;">If you did change your password, you can safely ignore this email.</td></tr>
      </table>
    </body></html>';
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Vault <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    @mail($reset['email'], $subject, $message, $headers);
  } else {
    // Log reset fail
    $pdo->prepare('INSERT INTO password_reset_logs (user_id, email, event_type, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?)')
      ->execute([$reset['user_id'], $reset['email'], 'reset_fail', $ip, $ua, json_encode(['errors'=>$errors])]);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Vault</title>
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
            <h1 class="h3 fw-bold mb-2 text-white">Reset Password</h1>
            <p class="text-secondary">Set a new password for your account</p>
          </div>
          <?php if(isset($errors['token'])): ?>
            <div class="alert alert-danger" role="alert"><?=$errors['token']?></div>
          <?php endif; ?>
          <?php if($showForm): ?>
          <form method="POST" autocomplete="off" aria-label="Reset password form">
            <div class="mb-3">
              <label for="password" class="form-label">New Password</label>
              <input id="password" name="password" type="password" value="<?=htmlspecialchars($fields['password'])?>" class="form-control<?=isset($errors['password'])?' is-invalid':''?>" placeholder="Enter new password" required aria-invalid="<?=isset($errors['password'])?'true':'false'?>" aria-describedby="password-error">
              <?php if(isset($errors['password'])): ?><div id="password-error" class="invalid-feedback d-block"><?=$errors['password']?></div><?php endif; ?>
              <div class="form-text text-secondary">At least 8 chars, uppercase, lowercase, number, special char.</div>
            </div>
            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <input id="confirm_password" name="confirm_password" type="password" value="<?=htmlspecialchars($fields['confirm_password'])?>" class="form-control<?=isset($errors['confirm_password'])?' is-invalid':''?>" placeholder="Confirm new password" required aria-invalid="<?=isset($errors['confirm_password'])?'true':'false'?>" aria-describedby="confirm-password-error">
              <?php if(isset($errors['confirm_password'])): ?><div id="confirm-password-error" class="invalid-feedback d-block"><?=$errors['confirm_password']?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Reset Password</button>
          </form>
          <?php endif; ?>
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
          <strong>Password reset!</strong> You can now sign in with your new password.<br>
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