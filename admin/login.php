<?php
session_start();
// Always reset admin session on login page
if (isset($_SESSION['admin_logged_in'])) {
  unset($_SESSION['admin_logged_in']);
}
$showCongrats = false;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
  $showCongrats = true;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  // Use database for admin authentication
  $pdo = new PDO('mysql:host=localhost;dbname=vault', 'root', '');
  $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ?');
  $stmt->execute([$username]);
  $admin = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($admin && password_verify($password, $admin['password_hash'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_just_logged_in'] = true;
    header('Location: dashboard.php');
    exit;
  } else {
    $error = 'Invalid username or password.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Vault</title>
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
    .admin-badge { display: inline-block; background: #ef4444; color: #fff; font-weight: 700; border-radius: 0.5rem; padding: 0.25rem 0.75rem; font-size: 1rem; margin-bottom: 0.5rem; letter-spacing: 0.05em; box-shadow: 0 2px 8px 0 rgba(239,68,68,0.12); }
    .security-notice { color: #f87171; background: rgba(239,68,68,0.08); border-left: 4px solid #ef4444; border-radius: 0.5rem; padding: 0.5rem 1rem; font-size: 0.97rem; margin-bottom: 1.25rem; }
    .animated-bg {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
    }
    .animated-bg svg { width: 100vw; height: 100vh; display: block; }
    .congrats-modal .modal-content { background: #111827; color: #e5e7eb; border-radius: 1.25rem; border: 1px solid #2563eb44; }
    .gradient-bg { background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%); color: #e5e7eb; border: none; }
    .animate-fadein { animation: fadein 0.7s; }
    @keyframes fadein { from { opacity: 0; transform: translateY(30px);} to { opacity: 1; transform: none; } }
    .animate-pop { animation: pop 0.5s cubic-bezier(.68,-0.55,.27,1.55); }
    @keyframes pop { 0% { transform: scale(0.7); opacity: 0; } 80% { transform: scale(1.1); opacity: 1; } 100% { transform: scale(1); } }
    .confetti { position: absolute; left: 0; top: 0; width: 100%; height: 100%; pointer-events: none; z-index: 2; }
    .progress { height: 6px; background: #1e293b; border-radius: 3px; }
    .progress-bar { background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%); }
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
            <img src="/public/vault-logo.png" alt="Vault Logo" class="logo-img" loading="lazy">
            <h1 class="h4 fw-bold mb-2 text-white">Welcome, Admin</h1>
            <p class="text-secondary">Sign in to access the admin dashboard</p>
          </div>
          <div class="security-notice" role="alert">
            <strong>Security Notice:</strong> Admin access only. Unauthorized use is prohibited.
          </div>
          <?php if ($error): ?><div class="alert alert-danger" role="alert"><?=$error?></div><?php endif; ?>
          <form method="POST" autocomplete="off" aria-label="Admin signin form">
            <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input id="username" name="username" type="text" class="form-control<?= $error ? ' is-invalid' : '' ?>" placeholder="Enter admin username" required autofocus aria-invalid="<?= $error ? 'true' : 'false' ?>" aria-describedby="username-error">
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <input id="password" name="password" type="password" class="form-control<?= $error ? ' is-invalid' : '' ?>" placeholder="Enter password" required aria-invalid="<?= $error ? 'true' : 'false' ?>" aria-describedby="password-error">
                <button type="button" class="btn btn-outline-secondary input-group-text" tabindex="0" aria-label="Toggle password visibility" onclick="togglePassword()"><span id="pw-toggle-icon">👁️</span></button>
              </div>
            </div>
            <div class="forgot-link"><a href="forgot-password.php" tabindex="0">Forgot password?</a></div>
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2" <?= $showCongrats ? 'disabled' : '' ?>>Sign In</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
  <script defer>
    function togglePassword() {
      var pw = document.getElementById('password');
      var icon = document.getElementById('pw-toggle-icon');
      if (pw.type === 'password') {
        pw.type = 'text';
        icon.textContent = '🙈';
      } else {
        pw.type = 'password';
        icon.textContent = '👁️';
      }
    }
    // Confetti animation
    function launchConfetti() {
      const confetti = document.getElementById('confetti');
      confetti.innerHTML = '';
      for (let i = 0; i < 32; i++) {
        const el = document.createElement('div');
        el.style.position = 'absolute';
        el.style.left = (Math.random() * 100) + '%';
        el.style.top = '-16px';
        el.style.width = '8px';
        el.style.height = '16px';
        el.style.background = `hsl(${Math.random()*360},90%,60%)`;
        el.style.opacity = 0.85;
        el.style.borderRadius = '2px';
        el.style.transform = `rotate(${Math.random()*360}deg)`;
        el.style.transition = 'top 1.2s cubic-bezier(.68,-0.55,.27,1.55), opacity 1.2s';
        setTimeout(() => {
          el.style.top = (60 + Math.random()*30) + '%';
          el.style.opacity = 0;
        }, 100);
        confetti.appendChild(el);
      }
    }
    // Animate checkmark
    function animateCheckmark() {
      const path = document.getElementById('checkmarkPath');
      path.style.transition = 'stroke-dashoffset 0.7s cubic-bezier(.68,-0.55,.27,1.55)';
      setTimeout(() => { path.style.strokeDashoffset = 0; }, 200);
    }
    <?php if ($showCongrats): ?>
      var congratsModal = new bootstrap.Modal(document.getElementById('congratsModal'));
      congratsModal.show();
      setTimeout(function() {
        document.getElementById('congratsProgress').style.width = '0%';
        launchConfetti();
        animateCheckmark();
      }, 100);
      setTimeout(function() {
        window.location.href = 'dashboard.php';
      }, 2500);
    <?php endif; ?>
    window.onload = function() {
      var err = document.querySelector('[aria-invalid="true"]');
      if (err) err.focus();
    }
  </script>
</body>
</html> 