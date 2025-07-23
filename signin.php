<?php
// signin.php
session_start();
require_once __DIR__ . '/api/settings_helper.php';
$logo = get_setting('logo_path') ?: 'public/vault-logo-new.png';

// Database connection (adjust as needed)
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');

$fields = [
  'email' => '',
  'password' => ''
];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fields['email'] = trim($_POST['email'] ?? '');
  $fields['password'] = $_POST['password'] ?? '';
  // Validation
  if (!$fields['email']) $errors['email'] = 'Email is required';
  elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format';
  if (!$fields['password']) $errors['password'] = 'Password is required';
  // Check credentials
  if (!$errors) {
    $stmt = $pdo->prepare('SELECT id, password_hash, status FROM users WHERE email = ?');
    $stmt->execute([$fields['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($fields['password'], $user['password_hash'])) {
        if ($user['status'] === 'blocked') {
            $errors['email'] = "Your account is blocked. Please contact support.";
        } elseif ($user['status'] === 'suspended') {
            $errors['email'] = "Your account is suspended. Please contact support.";
        } else {
            $success = true;
            // Set session or cookie as needed
            $_SESSION['user_id'] = $user['id'];
        }
    } else {
        $errors['email'] = "Invalid email or password.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #000;
      color: #fff;
      min-height: 100vh;
      position: relative;
    }
    .card-glass {
      background: rgba(24, 24, 32, 0.85);
      border: 1px solid rgba(59, 130, 246, 0.18); /* blue-500 border */
      box-shadow: 0 8px 32px 0 rgba(59, 130, 246, 0.15), 0 1.5px 8px 0 rgba(139, 92, 246, 0.10);
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      border-radius: 1.5rem;
      z-index: 1;
    }
    .form-floating .form-control {
      background: rgba(30,41,59,0.85);
      color: #fff;
      border: 1px solid #334155;
    }
    .form-floating label {
      color: #a1a1aa;
    }
    .form-control:focus {
      border-color: #3B82F6; /* blue-500 */
      box-shadow: 0 0 0 0.2rem rgba(59,130,246,.25), 0 0 0 0.1rem rgba(6,182,212,.15);
      background: rgba(30,41,59,0.95);
      color: #fff;
    }
    .input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #06B6D4; /* cyan-400 */
      font-size: 1.2rem;
      pointer-events: none;
    }
    .form-floating > .form-control, .form-floating > label {
      padding-left: 2.5rem;
    }
    .btn-primary {
      background: linear-gradient(90deg, #3B82F6 0%, #06B6D4 100%);
      border: none;
      font-weight: 600;
      letter-spacing: 0.03em;
      color: #fff;
      box-shadow: 0 2px 8px 0 rgba(59,130,246,0.10), 0 1px 4px 0 rgba(139,92,246,0.08);
      transition: background 0.2s, box-shadow 0.2s;
    }
    .btn-primary:hover, .btn-primary:focus {
      background: linear-gradient(90deg, #8B5CF6 0%, #3B82F6 100%); /* purple-500 to blue-500 */
      box-shadow: 0 4px 16px 0 rgba(139,92,246,0.15), 0 2px 8px 0 rgba(16,185,129,0.10);
    }
    .logo-img {
      width: 64px; height: 64px; margin-bottom: 1rem;
      transition: transform 0.2s;
    }
    .logo-img:hover {
      transform: scale(1.08) rotate(-3deg);
    }
    .forgot-link {
      display: block;
      margin-top: 0.25rem;
      text-align: right;
      font-size: 0.97rem;
    }
    .forgot-link a {
      color: #06B6D4;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }
    .forgot-link a:hover, .forgot-link a:focus {
      color: #3B82F6;
      text-decoration: underline;
      outline: none;
    }
    .invalid-feedback {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #F87171;
      font-size: 0.97rem;
    }
    .invalid-feedback .bi {
      font-size: 1.1rem;
    }
    .toast-container { z-index: 2000; }
    .custom-toast {
      background: linear-gradient(90deg, #3B82F6 0%, #06B6D4 100%);
      color: #fff;
      border-radius: 1rem;
      box-shadow: 0 8px 32px 0 rgba(59,130,246,0.13);
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
      color: #10B981; /* emerald-500 */
      filter: drop-shadow(0 0 4px #10B98188);
    }
    .custom-toast .progress {
      background: rgba(255,255,255,0.12);
      border-radius: 2px;
      margin-top: 0.75rem;
      height: 5px;
      box-shadow: 0 1px 4px 0 rgba(59,130,246,0.13);
    }
    .custom-toast .progress-bar {
      background: linear-gradient(90deg, #06B6D4 0%, #3B82F6 100%);
      border-radius: 2px;
      transition: width 2.5s linear;
    }
    .custom-toast .btn-close {
      filter: invert(1) grayscale(1);
    }
    .show-hide-btn {
      background: none;
      border: none;
      color: #06B6D4;
      font-size: 1.2rem;
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      z-index: 2;
      cursor: pointer;
      transition: color 0.2s;
    }
    .show-hide-btn:hover, .show-hide-btn:focus {
      color: #8B5CF6;
    }
    .spinner-border {
      width: 1.2rem;
      height: 1.2rem;
      border-width: 0.18em;
      margin-left: 0.5rem;
    }
  </style>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="d-flex align-items-center justify-content-center">
  <div class="container py-5 position-relative" style="z-index:1;">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        <div class="card card-glass p-4 p-md-5">
          <div class="text-center mb-4">
            <img src="<?=htmlspecialchars($logo)?>" alt="Vault" class="logo-img" loading="lazy">
            <h1 class="h3 fw-bold mb-2 text-white">Sign In</h1>
            <p class="text-secondary" style="color:#a1a1aa!important;">Welcome back to Vault</p>
          </div>
          <form method="POST" autocomplete="off" aria-label="Signin form" id="signin-form">
            <div class="form-floating mb-3 position-relative">
              <span class="input-icon"><i class="bi bi-envelope"></i></span>
              <input id="email" name="email" type="email" value="<?=htmlspecialchars($fields['email'])?>" class="form-control<?=isset($errors['email'])?' is-invalid':''?>" placeholder="Email Address" required autofocus aria-invalid="<?=isset($errors['email'])?'true':'false'?>" aria-describedby="email-error">
              <label for="email">Email Address</label>
              <?php if(isset($errors['email'])): ?><div id="email-error" class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?=$errors['email']?></div><?php endif; ?>
            </div>
            <div class="form-floating mb-3 position-relative">
              <span class="input-icon"><i class="bi bi-lock"></i></span>
              <input id="password" name="password" type="password" value="<?=htmlspecialchars($fields['password'])?>" class="form-control<?=isset($errors['password'])?' is-invalid':''?>" placeholder="Password" required aria-invalid="<?=isset($errors['password'])?'true':'false'?>" aria-describedby="password-error">
              <label for="password">Password</label>
              <button type="button" class="show-hide-btn" tabindex="0" aria-label="Toggle password visibility" onclick="togglePassword()"><i id="pw-toggle-icon" class="bi bi-eye"></i></button>
              <?php if(isset($errors['password'])): ?><div id="password-error" class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?=$errors['password']?></div><?php endif; ?>
              <div class="forgot-link"><a href="forgot-password.php" tabindex="0">Forgot password?</a></div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2 d-flex align-items-center justify-content-center" id="signin-btn" <?= $success ? 'disabled' : '' ?>>
              <span>Sign In</span>
              <span id="signin-spinner" class="spinner-border text-light ms-2 d-none" role="status" aria-hidden="true"></span>
            </button>
          </form>
          <div class="mt-4 text-center">
            <p class="text-secondary" style="color:#a1a1aa!important;">Don't have an account? <a href="signup.php" style="color:#3B82F6;font-weight:600;">Sign up</a></p>
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
          <strong>Welcome!</strong> Sign in successful.<br>
          Redirecting to your dashboard...
          <div class="progress mt-2" style="height: 5px;">
            <div id="toast-progress" class="progress-bar" role="progressbar" style="width: 100%;"></div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <?php if (!empty($errors['email'])): ?>
  <!-- Bootstrap Modal for Login Error -->
  <div class="modal fade" id="loginErrorModal" tabindex="-1" aria-labelledby="loginErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-danger text-white">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="loginErrorModalLabel">Login Error</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?=htmlspecialchars($errors['email'])?>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    var loginErrorModal = new bootstrap.Modal(document.getElementById('loginErrorModal'));
    loginErrorModal.show();
  });
  </script>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
  <script defer>
    function togglePassword() {
      var pw = document.getElementById('password');
      var icon = document.getElementById('pw-toggle-icon');
      if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        pw.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }
    function showToastAndRedirect() {
      var toastEl = document.getElementById('success-toast');
      if (!toastEl) return;
      var toast = new bootstrap.Toast(toastEl, { delay: 2500 });
      toast.show();
      var progress = document.getElementById('toast-progress');
      progress.style.width = '0%';
      setTimeout(function() {
        window.location.href = 'user-dashboard.php';
      }, 2500);
    }
    window.onload = function() {
      var err = document.querySelector('[aria-invalid="true"]');
      if (err) err.focus();
      <?php if ($success): ?>
      showToastAndRedirect();
      <?php endif; ?>
    }
    // Show spinner on submit
    document.addEventListener('DOMContentLoaded', function() {
      var form = document.getElementById('signin-form');
      var btn = document.getElementById('signin-btn');
      var spinner = document.getElementById('signin-spinner');
      if (form && btn && spinner) {
        form.addEventListener('submit', function() {
          btn.setAttribute('disabled', 'disabled');
          spinner.classList.remove('d-none');
        });
      }
    });
  </script>
</body>
</html> 