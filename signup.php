<?php
// signup.php
session_start();
require_once __DIR__ . '/api/settings_helper.php';
$logo = get_setting('logo_path') ?: 'public/vault-logo-new.png';

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
require_once __DIR__ . '/api/settings_helper.php';
// After successful registration (inside if ($success) block)
if ($success) {
    $template = get_setting('email_template_registration_congrats');
    $replacements = [
        '{USER_NAME}' => $fields['first_name'] . ' ' . $fields['last_name'],
        '{DATE}' => date('Y-m-d H:i'),
    ];
    $body = strtr($template, $replacements);
    $subject = 'Welcome to Vault!';
    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\n";
    mail($fields['email'], $subject, $body, $headers);
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
    .form-floating .form-control, .form-floating .form-select {
      background: rgba(30,41,59,0.85);
      color: #fff;
      border: 1px solid #334155;
    }
    .form-floating label {
      color: #a1a1aa;
    }
    .form-control:focus, .form-select:focus {
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
      display: block;
      margin-left: auto;
      margin-right: auto;
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
    .country-list {
      position: absolute;
      z-index: 10;
      width: 100%;
      max-height: 180px;
      overflow-y: auto;
      background: #1e293b;
      border: 1px solid #374151;
      border-radius: 0.5rem;
    }
    .country-list li {
      padding: 0.5rem 1rem;
      cursor: pointer;
      color: #e5e7eb;
    }
    .country-list li:hover {
      background: #3B82F6;
      color: #fff;
    }
    .input-group-text {
      background: #1e293b;
      border: 1px solid #374151;
      color: #e5e7eb;
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
</head>
<body class="d-flex align-items-center justify-content-center">
  <div class="container py-5 position-relative" style="z-index:1;">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        <div class="card card-glass p-4 p-md-5">
          <div class="text-center mb-4">
            <img src="<?=htmlspecialchars($logo)?>" alt="Vault" class="logo-img" loading="lazy">
            <h1 class="h3 fw-bold mb-2 text-white">Create Account</h1>
            <p class="text-secondary" style="color:#a1a1aa!important;">Join Vault and start earning today</p>
          </div>
          <form method="POST" autocomplete="off" aria-label="Signup form">
            <div class="form-floating mb-3 position-relative">
              <span class="input-icon"><i class="bi bi-person"></i></span>
              <input id="username" name="username" type="text" value="<?=htmlspecialchars($fields['username'])?>" class="form-control<?=isset($errors['username'])?' is-invalid':''?>" placeholder="Choose a username" required autofocus aria-invalid="<?=isset($errors['username'])?'true':'false'?>" aria-describedby="username-error">
              <label for="username">Username</label>
              <?php if(isset($errors['username'])): ?><div id="username-error" class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?=$errors['username']?></div><?php endif; ?>
            </div>
            <div class="row g-3">
              <div class="col">
                <div class="form-floating mb-3 position-relative">
                  <span class="input-icon"><i class="bi bi-person"></i></span>
                  <input id="first_name" name="first_name" type="text" value="<?=htmlspecialchars($fields['first_name'])?>" class="form-control<?=isset($errors['first_name'])?' is-invalid':''?>" placeholder="First name" required aria-invalid="<?=isset($errors['first_name'])?'true':'false'?>" aria-describedby="first_name-error">
                  <label for="first_name">First Name</label>
                  <?php if(isset($errors['first_name'])): ?><div id="first_name-error" class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?=$errors['first_name']?></div><?php endif; ?>
                </div>
              </div>
              <div class="col">
                <div class="form-floating mb-3 position-relative">
                  <span class="input-icon"><i class="bi bi-person"></i></span>
                  <input id="last_name" name="last_name" type="text" value="<?=htmlspecialchars($fields['last_name'])?>" class="form-control<?=isset($errors['last_name'])?' is-invalid':''?>" placeholder="Last name" required aria-invalid="<?=isset($errors['last_name'])?'true':'false'?>" aria-describedby="last_name-error">
                  <label for="last_name">Last Name</label>
                  <?php if(isset($errors['last_name'])): ?><div id="last_name-error" class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?=$errors['last_name']?></div><?php endif; ?>
                </div>
              </div>
            </div>
            <div class="form-floating mb-3 position-relative">
              <span class="input-icon"><i class="bi bi-envelope"></i></span>
              <input id="email" name="email" type="email" value="<?=htmlspecialchars($fields['email'])?>" class="form-control<?=isset($errors['email'])?' is-invalid':''?>" placeholder="Enter your email" required aria-invalid="<?=isset($errors['email'])?'true':'false'?>" aria-describedby="email-error">
              <label for="email">Email Address</label>
              <?php if(isset($errors['email'])): ?><div id="email-error" class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?=$errors['email']?></div><?php endif; ?>
            </div>
            <div class="form-floating mb-3 position-relative">
              <span class="input-icon"><i class="bi bi-lock"></i></span>
              <input id="password" name="password" type="password" value="<?=htmlspecialchars($fields['password'])?>" class="form-control<?=isset($errors['password'])?' is-invalid':''?>" placeholder="Create a password" required aria-invalid="<?=isset($errors['password'])?'true':'false'?>" aria-describedby="password-error">
              <label for="password">Password</label>
              <button type="button" class="show-hide-btn" tabindex="0" aria-label="Toggle password visibility" onclick="togglePassword()"><i id="pw-toggle-icon" class="bi bi-eye"></i></button>
              <?php if(isset($errors['password'])): ?><div id="password-error" class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?=$errors['password']?></div><?php endif; ?>
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
            <div class="form-floating mb-3 position-relative">
              <span class="input-icon"><i class="bi bi-telephone"></i></span>
              <input id="phone" name="phone" type="text" value="<?=htmlspecialchars($fields['phone'])?>" class="form-control<?=isset($errors['phone'])?' is-invalid':''?>" placeholder="Phone number" aria-invalid="<?=isset($errors['phone'])?'true':'false'?>" aria-describedby="phone-error">
              <label for="phone">Phone</label>
              <?php if(isset($errors['phone'])): ?><div id="phone-error" class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?=$errors['phone']?></div><?php endif; ?>
            </div>
            <div class="form-floating mb-3 position-relative country-dropdown">
              <span class="input-icon"><i class="bi bi-globe"></i></span>
              <input id="country" name="country" type="text" value="<?=htmlspecialchars($fields['country'])?>" class="form-control<?=isset($errors['country'])?' is-invalid':''?>" placeholder="Select country" autocomplete="off" onkeyup="filterCountries()" aria-invalid="<?=isset($errors['country'])?'true':'false'?>" aria-describedby="country-error">
              <label for="country">Country</label>
              <ul id="country-list" class="country-list" style="display:none;">
                <?php foreach ($countries as $c): ?>
                  <li onclick="selectCountry('<?=htmlspecialchars($c)?>')"><?=htmlspecialchars($c)?></li>
                <?php endforeach; ?>
              </ul>
              <?php if(isset($errors['country'])): ?><div id="country-error" class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?=$errors['country']?></div><?php endif; ?>
            </div>
            <div class="form-floating mb-3 position-relative">
              <span class="input-icon"><i class="bi bi-person-plus"></i></span>
              <input id="referred_by" name="referred_by" type="text" value="<?=htmlspecialchars($fields['referred_by'])?>" class="form-control" placeholder="User ID of referrer (optional)">
              <label for="referred_by">Referred By (User ID)</label>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2" <?= $success ? 'disabled' : '' ?>>Sign Up</button>
          </form>
          <div class="mt-4 text-center">
            <p class="text-secondary" style="color:#a1a1aa!important;">Already have an account? <a href="signin.php" style="color:#3B82F6;font-weight:600;">Sign in</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap Toast for Success -->
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="success-toast" class="toast custom-toast align-items-center border-0<?= $success ? ' show' : '' ?>" role="alert" aria-live="assertive" aria-atomic="true" style="min-width:320px;">
      <div class="d-flex align-items-center">
        <span class="checkmark" aria-hidden="true">&#10003;</span>
        <div class="toast-body ps-0">
          <strong>Congratulations!</strong> Your account has been created.<br>
          Redirecting to sign in...
          <div class="progress mt-2" style="height: 4px;">
            <div id="toast-progress" class="progress-bar" role="progressbar" style="width: 100%; transition: width 3s linear;"></div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
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
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        pw.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
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