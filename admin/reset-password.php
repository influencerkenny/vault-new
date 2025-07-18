<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=vault', 'root', '');
$fields = ['token' => '', 'password' => '', 'confirm_password' => ''];
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fields['token'] = trim($_POST['token'] ?? '');
  $fields['password'] = $_POST['password'] ?? '';
  $fields['confirm_password'] = $_POST['confirm_password'] ?? '';
  if (!$fields['token']) $errors['token'] = 'Token is required.';
  if (!$fields['password']) $errors['password'] = 'Password is required.';
  if ($fields['password'] !== $fields['confirm_password']) $errors['confirm_password'] = 'Passwords do not match.';
  if (!$errors) {
    $stmt = $pdo->prepare('SELECT * FROM admin_password_resets WHERE token = ? AND expires_at > NOW()');
    $stmt->execute([$fields['token']]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reset) {
      $errors['token'] = 'Invalid or expired token.';
    } else {
      $admin_id = $reset['admin_id'];
      $password_hash = password_hash($fields['password'], PASSWORD_DEFAULT);
      $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$password_hash, $admin_id]);
      $pdo->prepare('DELETE FROM admin_password_resets WHERE admin_id = ?')->execute([$admin_id]);
      $success = true;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Reset Password | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; min-height: 100vh; }
    .card { background: rgba(17, 24, 39, 0.85); border: 2px solid #2563eb; box-shadow: 0 8px 32px 0 rgba(31, 41, 55, 0.37); will-change: transform; }
    .form-label, .form-control, .form-select, .form-check-label { color: #e5e7eb; }
    .form-control { background: #1e293b; border: 1px solid #374151; }
    .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 0.2rem rgba(37,99,235,.25); }
    .btn-primary { background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%); border: none; }
    .btn-primary:hover, .btn-primary:focus { background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 100%); }
    .logo-img { width: 64px; height: 64px; margin-bottom: 1rem; }
    .security-notice { color: #f87171; background: rgba(239,68,68,0.08); border-left: 4px solid #ef4444; border-radius: 0.5rem; padding: 0.5rem 1rem; font-size: 0.97rem; margin-bottom: 1.25rem; }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">
  <div class="container py-5 position-relative" style="z-index:1;">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        <div class="card p-4 p-md-5 rounded-4">
          <div class="text-center mb-4">
            <img src="/public/vault-logo.png" alt="Vault Logo" class="logo-img" loading="lazy">
            <h1 class="h4 fw-bold mb-2 text-white">Reset Admin Password</h1>
            <p class="text-secondary">Enter your reset token and new password.</p>
          </div>
          <div class="security-notice" role="alert">
            <strong>Security Notice:</strong> Admin access only. Unauthorized use is prohibited.
          </div>
          <?php if ($success): ?>
            <div class="alert alert-success">Your password has been reset. <a href="login.php">Sign in</a> with your new password.</div>
          <?php else: ?>
          <form method="POST" autocomplete="off" aria-label="Admin reset password form">
            <div class="mb-3">
              <label for="token" class="form-label">Reset Token</label>
              <input id="token" name="token" type="text" value="<?=htmlspecialchars($fields['token'])?>" class="form-control<?=isset($errors['token'])?' is-invalid':''?>" placeholder="Enter your reset token" required aria-invalid="<?=isset($errors['token'])?'true':'false'?>" aria-describedby="token-error">
              <?php if(isset($errors['token'])): ?><div id="token-error" class="invalid-feedback d-block"><?=$errors['token']?></div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">New Password</label>
              <input id="password" name="password" type="password" class="form-control<?=isset($errors['password'])?' is-invalid':''?>" placeholder="Enter new password" required aria-invalid="<?=isset($errors['password'])?'true':'false'?>" aria-describedby="password-error">
              <?php if(isset($errors['password'])): ?><div id="password-error" class="invalid-feedback d-block"><?=$errors['password']?></div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm New Password</label>
              <input id="confirm_password" name="confirm_password" type="password" class="form-control<?=isset($errors['confirm_password'])?' is-invalid':''?>" placeholder="Confirm new password" required aria-invalid="<?=isset($errors['confirm_password'])?'true':'false'?>" aria-describedby="confirm-password-error">
              <?php if(isset($errors['confirm_password'])): ?><div id="confirm-password-error" class="invalid-feedback d-block"><?=$errors['confirm_password']?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Reset Password</button>
          </form>
          <?php endif; ?>
          <div class="mt-4 text-center">
            <a href="login.php" class="text-primary fw-semibold">Back to Admin Login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html> 