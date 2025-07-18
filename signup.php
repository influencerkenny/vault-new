<?php
// signup.php
session_start();

// Database connection (adjust as needed)
$pdo = new PDO('mysql:host=localhost;dbname=vault_db', 'root', '');

// Country list (ISO 3166-1)
$countries = [
  "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi", "Cabo Verde", "Cambodia", "Cameroon", "Canada", "Central African Republic", "Chad", "Chile", "China", "Colombia", "Comoros", "Congo (Congo-Brazzaville)", "Costa Rica", "Croatia", "Cuba", "Cyprus", "Czechia (Czech Republic)", "Democratic Republic of the Congo", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Eswatini (fmr. Swaziland)", "Ethiopia", "Fiji", "Finland", "France", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Holy See", "Honduras", "Hungary", "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Kuwait", "Kyrgyzstan", "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Montenegro", "Morocco", "Mozambique", "Myanmar (formerly Burma)", "Namibia", "Nauru", "Nepal", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "North Korea", "North Macedonia", "Norway", "Oman", "Pakistan", "Palau", "Palestine State", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland", "Portugal", "Qatar", "Romania", "Russia", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Korea", "South Sudan", "Spain", "Sri Lanka", "Sudan", "Suriname", "Sweden", "Switzerland", "Syria", "Tajikistan", "Tanzania", "Thailand", "Timor-Leste", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe"
];
// Helper: get referral from URL
$referral = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : '';

// Form state
$fields = [
  'username' => '', 'first_name' => '', 'last_name' => '', 'email' => '', 'password' => '', 'phone' => '', 'country' => '', 'referred_by' => $referral
];
$errors = [];
$success = false;

// Password validation rules
function password_errors($pw) {
  $rules = [
    ['test' => fn($p) => strlen($p) >= 8, 'msg' => 'At least 8 characters'],
    ['test' => fn($p) => preg_match('/[A-Z]/', $p), 'msg' => 'At least one uppercase letter'],
    ['test' => fn($p) => preg_match('/[a-z]/', $p), 'msg' => 'At least one lowercase letter'],
    ['test' => fn($p) => preg_match('/[0-9]/', $p), 'msg' => 'At least one number'],
    ['test' => fn($p) => preg_match('/[^A-Za-z0-9]/', $p), 'msg' => 'At least one special character'],
  ];
  $errs = [];
  foreach ($rules as $rule) {
    if (!$rule['test']($pw)) $errs[] = $rule['msg'];
  }
  return $errs;
}

// On form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($fields as $k => $_) {
    $fields[$k] = trim($_POST[$k] ?? '');
  }
  // Validation
  if (!$fields['username']) $errors['username'] = 'Username is required';
  if (!$fields['first_name']) $errors['first_name'] = 'First name is required';
  if (!$fields['last_name']) $errors['last_name'] = 'Last name is required';
  if (!$fields['email']) $errors['email'] = 'Email is required';
  elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format';
  if (!$fields['password']) $errors['password'] = 'Password is required';
  else {
    $pw_errs = password_errors($fields['password']);
    if ($pw_errs) $errors['password'] = implode(', ', $pw_errs);
  }
  if ($fields['phone'] && !preg_match('/^\+?[0-9]{7,15}$/', $fields['phone'])) $errors['phone'] = 'Invalid phone number';
  if (!$fields['country']) $errors['country'] = 'Country is required';
  // Check for existing user
  if (!$errors) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
    $stmt->execute([$fields['email'], $fields['username']]);
    if ($stmt->fetch()) $errors['email'] = 'Email or username already exists';
  }
  // Insert user
  if (!$errors) {
    $hash = password_hash($fields['password'], PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (username, first_name, last_name, email, password_hash, phone, country, referred_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
      $fields['username'], $fields['first_name'], $fields['last_name'], $fields['email'], $hash, $fields['phone'], $fields['country'], $fields['referred_by'] ?: null
    ]);
    $success = true;
    // Clear fields
    foreach ($fields as $k => $_) $fields[$k] = '';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | Vault</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; min-height: 100vh; }
    .card { background: rgba(17, 24, 39, 0.85); border: 1px solid #1e293b; box-shadow: 0 8px 32px 0 rgba(31, 41, 55, 0.37); will-change: transform; }
    .form-label, .form-control, .form-select, .form-check-label { color: #e5e7eb; }
    .form-control, .form-select { background: #1e293b; border: 1px solid #374151; }
    .form-control:focus, .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 0.2rem rgba(37,99,235,.25); }
    .btn-primary { background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%); border: none; }
    .btn-primary:hover, .btn-primary:focus { background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 100%); }
    .logo-img { width: 64px; height: 64px; margin-bottom: 1rem; }
    .modal-content { border-radius: 1rem; }
    .country-dropdown { position: relative; }
    .country-list { position: absolute; z-index: 10; width: 100%; max-height: 180px; overflow-y: auto; background: #1e293b; border: 1px solid #374151; border-radius: 0.5rem; }
    .country-list li { padding: 0.5rem 1rem; cursor: pointer; color: #e5e7eb; }
    .country-list li:hover { background: #2563eb; color: #fff; }
    .input-group-text { background: #1e293b; border: 1px solid #374151; color: #e5e7eb; }
    .animated-bg {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
    }
    .animated-bg svg { width: 100vw; height: 100vh; display: block; }
    .toast-container { z-index: 2000; }
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
        <!-- Main morphing shape -->
        <path id="morph1" fill="url(#bg-grad)" opacity="0.25">
          <animate attributeName="d" dur="14s" repeatCount="indefinite"
            values="M 0 0 Q 960 200 1920 0 Q 1720 540 1920 1080 Q 960 880 0 1080 Q 200 540 0 0 Z;
                    M 0 0 Q 960 300 1920 0 Q 1820 540 1920 1080 Q 960 980 0 1080 Q 120 540 0 0 Z;
                    M 0 0 Q 960 200 1920 0 Q 1720 540 1920 1080 Q 960 880 0 1080 Q 200 540 0 0 Z"/>
        </path>
        <!-- Second morphing shape -->
        <path id="morph2" fill="#0ea5e9" opacity="0.18">
          <animate attributeName="d" dur="18s" repeatCount="indefinite"
            values="M 1920 0 Q 1600 400 1920 1080 Q 1200 900 0 1080 Q 400 600 0 0 Q 1000 200 1920 0 Z;
                    M 1920 0 Q 1700 500 1920 1080 Q 1000 1000 0 1080 Q 600 700 0 0 Q 1200 300 1920 0 Z;
                    M 1920 0 Q 1600 400 1920 1080 Q 1200 900 0 1080 Q 400 600 0 0 Q 1000 200 1920 0 Z"/>
        </path>
        <!-- Floating circles -->
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
            <h1 class="h3 fw-bold mb-2 text-white">Create Account</h1>
            <p class="text-secondary">Join Vault and start earning today</p>
          </div>
          <form method="POST" autocomplete="off" aria-label="Signup form">
            <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input id="username" name="username" type="text" value="<?=htmlspecialchars($fields['username'])?>" class="form-control<?=isset($errors['username'])?' is-invalid':''?>" placeholder="Choose a username" required autofocus aria-invalid="<?=isset($errors['username'])?'true':'false'?>" aria-describedby="username-error">
              <?php if(isset($errors['username'])): ?><div id="username-error" class="invalid-feedback d-block"><?=$errors['username']?></div><?php endif; ?>
            </div>
            <div class="row g-3">
              <div class="col">
                <label for="first_name" class="form-label">First Name</label>
                <input id="first_name" name="first_name" type="text" value="<?=htmlspecialchars($fields['first_name'])?>" class="form-control<?=isset($errors['first_name'])?' is-invalid':''?>" placeholder="First name" required aria-invalid="<?=isset($errors['first_name'])?'true':'false'?>" aria-describedby="first_name-error">
                <?php if(isset($errors['first_name'])): ?><div id="first_name-error" class="invalid-feedback d-block"><?=$errors['first_name']?></div><?php endif; ?>
              </div>
              <div class="col">
                <label for="last_name" class="form-label">Last Name</label>
                <input id="last_name" name="last_name" type="text" value="<?=htmlspecialchars($fields['last_name'])?>" class="form-control<?=isset($errors['last_name'])?' is-invalid':''?>" placeholder="Last name" required aria-invalid="<?=isset($errors['last_name'])?'true':'false'?>" aria-describedby="last_name-error">
                <?php if(isset($errors['last_name'])): ?><div id="last_name-error" class="invalid-feedback d-block"><?=$errors['last_name']?></div><?php endif; ?>
              </div>
            </div>
            <div class="mb-3 mt-3">
              <label for="email" class="form-label">Email Address</label>
              <input id="email" name="email" type="email" value="<?=htmlspecialchars($fields['email'])?>" class="form-control<?=isset($errors['email'])?' is-invalid':''?>" placeholder="Enter your email" required aria-invalid="<?=isset($errors['email'])?'true':'false'?>" aria-describedby="email-error">
              <?php if(isset($errors['email'])): ?><div id="email-error" class="invalid-feedback d-block"><?=$errors['email']?></div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <input id="password" name="password" type="password" value="<?=htmlspecialchars($fields['password'])?>" class="form-control<?=isset($errors['password'])?' is-invalid':''?>" placeholder="Create a password" required aria-invalid="<?=isset($errors['password'])?'true':'false'?>" aria-describedby="password-error">
                <button type="button" class="btn btn-outline-secondary input-group-text" tabindex="0" aria-label="Toggle password visibility" onclick="togglePassword()"><span id="pw-toggle-icon">👁️</span></button>
              </div>
              <?php if(isset($errors['password'])): ?><div id="password-error" class="invalid-feedback d-block"><?=$errors['password']?></div><?php endif; ?>
              <ul class="text-secondary small mt-2 mb-0 ps-3">
                <?php foreach ([
                  'At least 8 characters',
                  'At least one uppercase letter',
                  'At least one lowercase letter',
                  'At least one number',
                  'At least one special character'
                ] as $rule): ?>
                  <li><?=strpos($errors['password']??'', $rule)!==false?'<span class="text-danger">':'<span class="text-success">'?><?=$rule?></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <input id="phone" name="phone" type="text" value="<?=htmlspecialchars($fields['phone'])?>" class="form-control<?=isset($errors['phone'])?' is-invalid':''?>" placeholder="Phone number" aria-invalid="<?=isset($errors['phone'])?'true':'false'?>" aria-describedby="phone-error">
              <?php if(isset($errors['phone'])): ?><div id="phone-error" class="invalid-feedback d-block"><?=$errors['phone']?></div><?php endif; ?>
            </div>
            <div class="mb-3 country-dropdown">
              <label for="country" class="form-label">Country</label>
              <input id="country" name="country" type="text" value="<?=htmlspecialchars($fields['country'])?>" class="form-control<?=isset($errors['country'])?' is-invalid':''?>" placeholder="Select country" autocomplete="off" onkeyup="filterCountries()" aria-invalid="<?=isset($errors['country'])?'true':'false'?>" aria-describedby="country-error">
              <ul id="country-list" class="country-list" style="display:none;">
                <?php foreach ($countries as $c): ?>
                  <li onclick="selectCountry('<?=htmlspecialchars($c)?>')"><?=htmlspecialchars($c)?></li>
                <?php endforeach; ?>
              </ul>
              <?php if(isset($errors['country'])): ?><div id="country-error" class="invalid-feedback d-block"><?=$errors['country']?></div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="referred_by" class="form-label">Referred By (User ID)</label>
              <input id="referred_by" name="referred_by" type="text" value="<?=htmlspecialchars($fields['referred_by'])?>" class="form-control" placeholder="User ID of referrer (optional)">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2" <?= $success ? 'disabled' : '' ?>>Sign Up</button>
          </form>
          <div class="mt-4 text-center">
            <p class="text-secondary">Already have an account? <a href="signin.php" class="text-primary fw-semibold">Sign in</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap Toast for Success -->
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="success-toast" class="toast align-items-center text-bg-primary border-0<?= $success ? ' show' : '' ?>" role="alert" aria-live="assertive" aria-atomic="true" style="min-width:320px;">
      <div class="d-flex">
        <div class="toast-body">
          <strong>Congratulations!</strong> Your account has been created.<br>
          Redirecting to sign in...
          <div class="progress mt-2" style="height: 4px;">
            <div id="toast-progress" class="progress-bar bg-info" role="progressbar" style="width: 100%; transition: width 3s linear;"></div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
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
    function filterCountries() {
      var input = document.getElementById('country');
      var filter = input.value.toLowerCase();
      var ul = document.getElementById('country-list');
      var items = ul.getElementsByTagName('li');
      for (var i = 0; i < items.length; i++) {
        var txt = items[i].textContent || items[i].innerText;
        items[i].style.display = txt.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
      }
      ul.style.display = filter ? 'block' : 'none';
    }
    function selectCountry(val) {
      document.getElementById('country').value = val;
      document.getElementById('country-list').style.display = 'none';
    }
    function showToastAndRedirect() {
      var toastEl = document.getElementById('success-toast');
      if (!toastEl) return;
      var toast = new bootstrap.Toast(toastEl, { delay: 3200 });
      toast.show();
      var progress = document.getElementById('toast-progress');
      progress.style.width = '0%';
      setTimeout(function() {
        window.location.href = 'signin.php';
      }, 3200);
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