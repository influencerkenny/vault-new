<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=vault', 'root', '');
$fields = ['username' => '', 'email' => ''];
$errors = [];
$success = false;
$token = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fields['username'] = trim($_POST['username'] ?? '');
  $fields['email'] = trim($_POST['email'] ?? '');
  if (!$fields['username'] && !$fields['email']) {
    $errors['username'] = 'Username or email is required.';
  } else {
    $query = $fields['username'] ? 'username = ?' : 'email = ?';
    $value = $fields['username'] ?: $fields['email'];
    $stmt = $pdo->prepare("SELECT id, username, email FROM admins WHERE $query");
    $stmt->execute([$value]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
      $errors['username'] = 'Admin not found.';
    } else {
      // Generate token
      $token = bin2hex(random_bytes(32));
      $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
      $pdo->prepare('INSERT INTO admin_password_resets (admin_id, token, expires_at) VALUES (?, ?, ?)')->execute([$admin['id'], $token, $expires_at]);
      $success = true;
      // Send email with PHPMailer
      // If PHPMailer is not installed, run: composer require phpmailer/phpmailer
      require_once __DIR__ . '/../vendor/autoload.php';
      $mail = new PHPMailer\PHPMailer\PHPMailer(true);
      // Load email config if available
      $email_config_file = __DIR__ . '/email_config.php';
      $email_config = [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'your@email.com',
        'password' => 'yourpassword',
        'from' => 'no-reply@yourdomain.com',
        'encryption' => 'tls'
      ];
      if (file_exists($email_config_file)) {
        $saved = include $email_config_file;
        foreach ($email_config as $k => $v) {
          if (!empty($saved[$k])) $email_config[$k] = $saved[$k];
        }
      }
      try {
        $mail->isSMTP();
        $mail->Host = $email_config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $email_config['username'];
        $mail->Password = $email_config['password'];
        $mail->SMTPSecure = $email_config['encryption'] === 'ssl' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $email_config['port'];
        $mail->setFrom($email_config['from'], 'Vault Admin');
        $mail->addAddress($admin['email'] ?: 'admin@yourdomain.com', $admin['username']);
        $mail->isHTML(true);
        $mail->Subject = 'Admin Password Reset Request';
        $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset-password.php?token=' . $token;
        $mail->Body = '<h2>Password Reset Request</h2>' .
          '<p>Hello <b>' . htmlspecialchars($admin['username']) . '</b>,</p>' .
          '<p>You requested a password reset for your admin account. Click the link below to reset your password:</p>' .
          '<p><a href="' . $resetLink . '">' . $resetLink . '</a></p>' .
          '<p>If you did not request this, please ignore this email.</p>';
        $mail->send();
        $emailSent = true;
      } catch (Exception $e) {
        $emailSent = false;
        $emailError = $mail->ErrorInfo;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Forgot Password | Vault</title>
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
    .forgot-link { display: block; margin-top: 0.25rem; text-align: right; font-size: 0.97rem; }
    .forgot-link a { color: #38bdf8; text-decoration: none; font-weight: 500; transition: color 0.2s; }
    .forgot-link a:hover, .forgot-link a:focus { color: #0ea5e9; text-decoration: underline; outline: none; }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">
  <div class="container py-5 position-relative" style="z-index:1;">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        <div class="card p-4 p-md-5 rounded-4">
          <div class="text-center mb-4">
            <img src="/public/vault-logo.png" alt="Vault Logo" class="logo-img" loading="lazy">
            <h1 class="h4 fw-bold mb-2 text-white">Admin Password Reset</h1>
            <p class="text-secondary">Enter your admin username or email to reset your password.</p>
          </div>
          <div class="security-notice" role="alert">
            <strong>Security Notice:</strong> Admin access only. Unauthorized use is prohibited.
          </div>
          <?php if ($success): ?>
            <div class="alert alert-success">
              A reset link has been generated and sent to your email address (if available).<br>
              <?php if (isset($emailSent) && !$emailSent): ?>
                <div class="alert alert-warning mt-2">Email could not be sent: <?=htmlspecialchars($emailError)?></div>
              <?php endif; ?>
              For testing, your token is:<br><code><?=htmlspecialchars($token)?></code><br>
              Use it on the <a href="reset-password.php">reset page</a>.
            </div>
          <?php else: ?>
          <form method="POST" autocomplete="off" aria-label="Admin forgot password form">
            <div class="mb-3">
              <label for="username" class="form-label">Username (or Email)</label>
              <input id="username" name="username" type="text" value="<?=htmlspecialchars($fields['username'])?>" class="form-control<?=isset($errors['username'])?' is-invalid':''?>" placeholder="Enter your admin username or email" autofocus aria-invalid="<?=isset($errors['username'])?'true':'false'?>" aria-describedby="username-error">
              <?php if(isset($errors['username'])): ?><div id="username-error" class="invalid-feedback d-block"><?=$errors['username']?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Send Reset Link</button>
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