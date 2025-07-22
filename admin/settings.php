<?php
session_start();
require_once '../api/config.php';
require_once '../api/settings_helper.php';
// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
$success = $error = '';
// Handle system settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['system_settings_save'])) {
    set_setting('system_name', trim($_POST['system_name'] ?? 'Vault'));
    // Logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['png','jpg','jpeg','svg'];
        if (in_array($ext, $allowed)) {
            // Delete old logo files with other extensions
            $logoBase = '../public/vault-logo-uploaded';
            foreach ($allowed as $oldExt) {
                $oldFile = $logoBase . '.' . $oldExt;
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
            $target = $logoBase . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                $cache_buster = '?v=' . time();
                set_setting('logo_path', '/public/vault-logo-uploaded.' . $ext . $cache_buster);
            } else {
                $error = 'Failed to upload logo.';
            }
        } else {
            $error = 'Invalid logo file type.';
        }
    }
    set_setting('primary_color', trim($_POST['primary_color'] ?? '#2563eb'));
    set_setting('referral_commission', trim($_POST['referral_commission'] ?? '0.05'));
    $success = 'System settings updated!';
}
// Handle email config update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_config_save'])) {
    set_setting('email_host', trim($_POST['email_host'] ?? ''));
    set_setting('email_port', trim($_POST['email_port'] ?? ''));
    set_setting('email_username', trim($_POST['email_username'] ?? ''));
    set_setting('email_password', trim($_POST['email_password'] ?? ''));
    set_setting('email_from', trim($_POST['email_from'] ?? ''));
    $success = 'Email configuration updated!';
}
// Handle email template update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_template_save'])) {
    set_setting('email_template', trim($_POST['email_template'] ?? ''));
    $success = 'Email template updated!';
}
// Handle withdrawal approval email template update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_template_withdrawal_approval_save'])) {
    set_setting('email_template_withdrawal_approval', trim($_POST['email_template_withdrawal_approval'] ?? ''));
    $success = 'Withdrawal approval email template updated!';
}
// Handle deposit approval email template update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_template_deposit_approval_save'])) {
    set_setting('email_template_deposit_approval', trim($_POST['email_template_deposit_approval'] ?? ''));
    $success = 'Deposit approval email template updated!';
}
// Handle staking email template update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_template_staking_save'])) {
    set_setting('email_template_staking', trim($_POST['email_template_staking'] ?? ''));
    $success = 'Staking email template updated!';
}
// Handle staking earnings email template update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_template_staking_earnings_save'])) {
    set_setting('email_template_staking_earnings', trim($_POST['email_template_staking_earnings'] ?? ''));
    $success = 'Staking earnings email template updated!';
}
// Handle registration congratulatory email template update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_template_registration_congrats_save'])) {
    set_setting('email_template_registration_congrats', trim($_POST['email_template_registration_congrats'] ?? ''));
    $success = 'Registration congratulatory email template updated!';
}
// Handle SOL to USDT rate update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sol_usdt_rate_save'])) {
    set_setting('sol_usdt_rate', trim($_POST['sol_usdt_rate'] ?? '203.36'));
    $success = 'SOL to USDT rate updated!';
    $settings['sol_usdt_rate'] = trim($_POST['sol_usdt_rate'] ?? '203.36');
}
// Get all settings
$settings = get_all_settings();
// Set default email templates if not set
if (empty($settings['email_template_withdrawal_approval'])) {
    $settings['email_template_withdrawal_approval'] = "Hi {USER_NAME},<br><br>Your withdrawal request of {AMOUNT} has been approved and processed on {DATE}.<br><br>Thank you for using our platform!<br><br>Best regards,<br>Vault Team";
}
if (empty($settings['email_template_deposit_approval'])) {
    $settings['email_template_deposit_approval'] = "Hi {USER_NAME},<br><br>Your deposit of {AMOUNT} has been approved and credited to your account on {DATE}.<br><br>Thank you for trusting us with your funds!<br><br>Best regards,<br>Vault Team";
}
if (empty($settings['email_template_staking'])) {
    $settings['email_template_staking'] = "Hi {USER_NAME},<br><br>You have successfully staked {AMOUNT} in the {PLAN_NAME} plan on {DATE}.<br><br>We wish you great returns!<br><br>Best regards,<br>Vault Team";
}
if (empty($settings['email_template_staking_earnings'])) {
    $settings['email_template_staking_earnings'] = "Hi {USER_NAME},<br><br>Congratulations! You have received staking earnings of {AMOUNT} from your {PLAN_NAME} plan on {DATE}.<br><br>Keep staking and earning with us!<br><br>Best regards,<br>Vault Team";
}
if (empty($settings['email_template_registration_congrats'])) {
    $settings['email_template_registration_congrats'] = "Hi {USER_NAME},<br><br>Congratulations on successfully registering with Vault on {DATE}!<br><br>We’re excited to have you on board. Start exploring our platform and enjoy the benefits of secure and rewarding investing.<br><br>Best regards,<br>Vault Team";
}
// Get admin info for account management section
$stmt = $pdo->prepare('SELECT id, username, email, notify_email, twofa_enabled FROM admins WHERE username = ? LIMIT 1');
$stmt->execute([$_SESSION['admin_username'] ?? 'admin']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin) {
    $error = 'Admin not found.';
}
// Handle admin account update (keep as before)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings_save'])) {
    $email = trim($_POST['email'] ?? '');
    $notify_email = isset($_POST['notify_email']) ? 1 : 0;
    if (!$email) {
        $error = 'Email is required.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $stmt = $pdo->prepare('UPDATE admins SET email=?, notify_email=? WHERE id=?');
        if ($stmt->execute([$email, $notify_email, $admin['id']])) {
            $success = 'Admin account updated!';
            $admin['email'] = $email;
            $admin['notify_email'] = $notify_email;
        } else {
            $error = 'Failed to update admin account.';
        }
    }
}
$password_success = $password_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_save'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$current || !$new || !$confirm) {
        $password_error = 'All password fields are required.';
    } else if ($new !== $confirm) {
        $password_error = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id=?');
        $stmt->execute([$admin['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $password_error = 'Current password is incorrect.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE admins SET password_hash=? WHERE id=?');
            if ($stmt->execute([$hash, $admin['id']])) {
                $password_success = 'Password updated successfully!';
            } else {
                $password_error = 'Failed to update password.';
            }
        }
    }
}
$twofa_success = $twofa_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['twofa_save'])) {
    $twofa_enabled = isset($_POST['twofa_enabled']) ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE admins SET twofa_enabled=? WHERE id=?');
    if ($stmt->execute([$twofa_enabled, $admin['id']])) {
        $twofa_success = $twofa_enabled ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.';
        $admin['twofa_enabled'] = $twofa_enabled;
    } else {
        $twofa_error = 'Failed to update 2FA setting.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - System Settings</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../styles/globals.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e5e7eb; font-size: 0.93rem; }
    .main-content { margin-left: 260px; min-height: 100vh; background: #0f172a; position: relative; z-index: 1; display: flex; flex-direction: column; font-size: 0.93rem; }
    .dashboard-content-wrapper { max-width: 900px; width: 100%; margin: 0 auto; padding: 0 1rem; font-size: 0.91rem; }
    .settings-table { margin-top: 2.5rem; background: #151a23; border-radius: 1rem; box-shadow: 0 2px 12px #0003; overflow: hidden; font-size: 0.91em; }
    .settings-table th, .settings-table td { color: #f3f4f6; vertical-align: middle; font-size: 0.97rem; padding: 0.7rem 0.8rem; }
    .settings-table th { background: #232b3b; color: #38bdf8; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; border-bottom: 2px solid #2563eb33; font-size: 0.95em; }
    .settings-table td { font-weight: 600; color: #e0e7ef; background: #181f2a; border-bottom: 1px solid #232b3b; font-size: 0.95em; }
    .settings-table tr:nth-child(even) td { background: #1e2330; }
    .settings-table tr:hover td { background: #232b3b; color: #fff; transition: background 0.18s, color 0.18s; }
    .settings-table tr:last-child td { border-bottom: none; }
    .action-btn { font-size: 0.93em; border-radius: 0.5rem; padding: 0.3rem 0.9rem; margin-right: 0.3rem; }
    .modal-content { background: #111827cc; color: #e5e7eb; border-radius: 1.25rem; }
    .modal-header { border-bottom: 1px solid #2563eb33; }
    .modal-footer { border-top: 1px solid #2563eb33; }
    .alert-success { background: #22c55e22; color: #22c55e; border: none; }
    .alert-danger { background: #ef444422; color: #ef4444; border: none; }
    @media (max-width: 991px) { .main-content { margin-left: 0; } .dashboard-content-wrapper { max-width: 100vw; margin: 0; padding: 0 0.3rem; font-size: 0.89rem; } }
    @media (max-width: 767px) { .dashboard-content-wrapper { padding: 0 0.1rem; font-size: 0.87rem; } }
    @media (max-width: 575px) { .dashboard-content-wrapper { padding: 0 0.05rem; font-size: 0.85rem; } .settings-table { font-size: 0.87em; } .settings-table th, .settings-table td { padding: 0.35rem 0.18rem; font-size: 0.87em; } }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <?php include 'header.php'; ?>
    <main class="flex-grow-1 p-4">
      <div class="dashboard-content-wrapper mx-auto">
        <h2 class="mb-4 text-info fw-bold text-center">System Settings</h2>
        <?php if ($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
        <div class="table-responsive">
          <table class="table settings-table align-middle mb-0">
            <thead>
              <tr>
                <th>Setting</th>
                <th>Description</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>System Name</td>
                <td>The name of your platform as shown in the UI and emails.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#systemSettingsModal"><i class="bi bi-pencil"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Logo</td>
                <td>Upload a logo for your platform. Current: <img src="<?=$settings['logo_path'] ?? '/vault-logo-new.png'?>" alt="Logo" style="height:32px;vertical-align:middle;"> </td>
                <td class="text-center action-btn">
                  <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#systemSettingsModal"><i class="bi bi-upload"></i> Change</button>
                </td>
              </tr>
              <tr>
                <td>Primary Color</td>
                <td>Main color for UI highlights. Current: <span style="display:inline-block;width:24px;height:24px;background:<?=$settings['primary_color'] ?? '#2563eb'?>;border-radius:4px;"></span></td>
                <td class="text-center action-btn">
                  <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#systemSettingsModal"><i class="bi bi-palette"></i> Change</button>
                </td>
              </tr>
              <tr>
                <td>Referral Commission</td>
                <td>Percentage of commission for referrals. Current: <b><?=isset($settings['referral_commission']) ? floatval($settings['referral_commission']) : 0?>%</b></td>
                <td class="text-center action-btn">
                  <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#referralCommissionModal"><i class="bi bi-percent"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Email Configuration</td>
                <td>SMTP host, port, username, password, from address.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#emailConfigModal"><i class="bi bi-envelope"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Email Template</td>
                <td>Template for system emails (HTML allowed).</td>
                <td class="text-center action-btn">
                  <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#emailTemplateModal"><i class="bi bi-file-earmark-text"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Withdrawal Approval Email Template</td>
                <td>Template for the email sent to users when their withdrawal request is approved.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#withdrawalApprovalTemplateModal"><i class="bi bi-file-earmark-text"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Deposit Approval Email Template</td>
                <td>Template for the email sent to users when their deposit is approved.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#depositApprovalTemplateModal"><i class="bi bi-file-earmark-text"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Staking Email Template</td>
                <td>Template for the email sent to users when they start a staking plan.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#stakingTemplateModal"><i class="bi bi-file-earmark-text"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Staking Earnings Email Template</td>
                <td>Template for the email sent to users when they receive staking earnings.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#stakingEarningsTemplateModal"><i class="bi bi-file-earmark-text"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Registration Congratulatory Email Template</td>
                <td>Template for the email sent to users when they register successfully.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#registrationCongratsTemplateModal"><i class="bi bi-file-earmark-text"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Conversion Rate (SOL to USDT)</td>
                <td>Current rate for converting SOL to USDT. Current: <b>$<?=isset($settings['sol_usdt_rate']) ? floatval($settings['sol_usdt_rate']) : '203.36'?></b></td>
                <td class="text-center action-btn">
                  <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#solUsdtRateModal"><i class="bi bi-currency-exchange"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td colspan="3" class="text-center text-info">Admin Account Management</td>
              </tr>
              <tr>
                <td>Email & Notifications</td>
                <td>Manage your admin email and notification preferences.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#adminSettingsModal"><i class="bi bi-pencil"></i> Edit</button>
                </td>
              </tr>
              <tr>
                <td>Password</td>
                <td>Change your admin account password.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#passwordModal"><i class="bi bi-key"></i> Change</button>
                </td>
              </tr>
              <tr>
                <td>Two-Factor Authentication (2FA)</td>
                <td>Enable or disable two-factor authentication for extra security.</td>
                <td class="text-center action-btn">
                  <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#twofaModal"><i class="bi bi-shield-lock"></i> Configure</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- System Settings Modal -->
        <div class="modal fade" id="systemSettingsModal" tabindex="-1" aria-labelledby="systemSettingsModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="systemSettingsModalLabel">Edit System Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" enctype="multipart/form-data" autocomplete="off">
                  <div class="mb-3">
                    <label for="system_name" class="form-label">System Name</label>
                    <input type="text" class="form-control" id="system_name" name="system_name" value="<?=htmlspecialchars($settings['system_name'] ?? 'Vault')?>" required>
                  </div>
                  <div class="mb-3">
                    <label for="logo" class="form-label">Logo</label>
                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                  </div>
                  <div class="mb-3">
                    <label for="primary_color" class="form-label">Primary Color</label>
                    <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="<?=htmlspecialchars($settings['primary_color'] ?? '#2563eb')?>">
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="system_settings_save">Save System Settings</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Email Config Modal -->
        <div class="modal fade" id="emailConfigModal" tabindex="-1" aria-labelledby="emailConfigModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="emailConfigModalLabel">Edit Email Configuration</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="email_host" class="form-label">SMTP Host</label>
                    <input type="text" class="form-control" id="email_host" name="email_host" value="<?=htmlspecialchars($settings['email_host'] ?? '')?>">
                  </div>
                  <div class="mb-3">
                    <label for="email_port" class="form-label">SMTP Port</label>
                    <input type="text" class="form-control" id="email_port" name="email_port" value="<?=htmlspecialchars($settings['email_port'] ?? '')?>">
                  </div>
                  <div class="mb-3">
                    <label for="email_username" class="form-label">SMTP Username</label>
                    <input type="text" class="form-control" id="email_username" name="email_username" value="<?=htmlspecialchars($settings['email_username'] ?? '')?>">
                  </div>
                  <div class="mb-3">
                    <label for="email_password" class="form-label">SMTP Password</label>
                    <input type="password" class="form-control" id="email_password" name="email_password" value="<?=htmlspecialchars($settings['email_password'] ?? '')?>">
                  </div>
                  <div class="mb-3">
                    <label for="email_from" class="form-label">From Email Address</label>
                    <input type="email" class="form-control" id="email_from" name="email_from" value="<?=htmlspecialchars($settings['email_from'] ?? '')?>">
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="email_config_save">Save Email Config</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Email Template Modal -->
        <div class="modal fade" id="emailTemplateModal" tabindex="-1" aria-labelledby="emailTemplateModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="emailTemplateModalLabel">Edit Email Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="email_template" class="form-label">Email Template (HTML allowed)</label>
                    <textarea class="form-control" id="email_template" name="email_template" rows="8"><?=htmlspecialchars($settings['email_template'] ?? '')?></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="email_template_save">Save Email Template</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Referral Commission Modal -->
        <div class="modal fade" id="referralCommissionModal" tabindex="-1" aria-labelledby="referralCommissionModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="referralCommissionModalLabel">Edit Referral Commission</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="referral_commission" class="form-label">Referral Commission (%)</label>
                    <input type="number" class="form-control" id="referral_commission" name="referral_commission" min="0" max="100" step="0.01" value="<?=isset($settings['referral_commission']) ? floatval($settings['referral_commission']) : 0?>">
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="referral_commission_save">Save Referral Commission</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Withdrawal Approval Template Modal -->
        <div class="modal fade" id="withdrawalApprovalTemplateModal" tabindex="-1" aria-labelledby="withdrawalApprovalTemplateModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="withdrawalApprovalTemplateModalLabel">Edit Withdrawal Approval Email Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="email_template_withdrawal_approval" class="form-label">Withdrawal Approval Email Template (HTML allowed)</label>
                    <textarea class="form-control" id="email_template_withdrawal_approval" name="email_template_withdrawal_approval" rows="8"><?=htmlspecialchars($settings['email_template_withdrawal_approval'] ?? '')?></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="email_template_withdrawal_approval_save">Save Withdrawal Approval Email Template</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Deposit Approval Template Modal -->
        <div class="modal fade" id="depositApprovalTemplateModal" tabindex="-1" aria-labelledby="depositApprovalTemplateModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="depositApprovalTemplateModalLabel">Edit Deposit Approval Email Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="email_template_deposit_approval" class="form-label">Deposit Approval Email Template (HTML allowed)</label>
                    <textarea class="form-control" id="email_template_deposit_approval" name="email_template_deposit_approval" rows="8"><?=htmlspecialchars($settings['email_template_deposit_approval'] ?? '')?></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="email_template_deposit_approval_save">Save Deposit Approval Email Template</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Staking Template Modal -->
        <div class="modal fade" id="stakingTemplateModal" tabindex="-1" aria-labelledby="stakingTemplateModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="stakingTemplateModalLabel">Edit Staking Email Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="email_template_staking" class="form-label">Staking Email Template (HTML allowed)</label>
                    <textarea class="form-control" id="email_template_staking" name="email_template_staking" rows="8"><?=htmlspecialchars($settings['email_template_staking'] ?? '')?></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="email_template_staking_save">Save Staking Email Template</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Staking Earnings Template Modal -->
        <div class="modal fade" id="stakingEarningsTemplateModal" tabindex="-1" aria-labelledby="stakingEarningsTemplateModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="stakingEarningsTemplateModalLabel">Edit Staking Earnings Email Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="email_template_staking_earnings" class="form-label">Staking Earnings Email Template (HTML allowed)</label>
                    <textarea class="form-control" id="email_template_staking_earnings" name="email_template_staking_earnings" rows="8"><?=htmlspecialchars($settings['email_template_staking_earnings'] ?? '')?></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="email_template_staking_earnings_save">Save Staking Earnings Email Template</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Registration Congratulatory Template Modal -->
        <div class="modal fade" id="registrationCongratsTemplateModal" tabindex="-1" aria-labelledby="registrationCongratsTemplateModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="registrationCongratsTemplateModalLabel">Edit Registration Congratulatory Email Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="email_template_registration_congrats" class="form-label">Registration Congratulatory Email Template (HTML allowed)</label>
                    <textarea class="form-control" id="email_template_registration_congrats" name="email_template_registration_congrats" rows="8"><?=htmlspecialchars($settings['email_template_registration_congrats'] ?? '')?></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="email_template_registration_congrats_save">Save Registration Congratulatory Email Template</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- SOL to USDT Rate Modal -->
        <div class="modal fade" id="solUsdtRateModal" tabindex="-1" aria-labelledby="solUsdtRateModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="solUsdtRateModalLabel">Edit SOL to USDT Conversion Rate</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="sol_usdt_rate" class="form-label">SOL to USDT Rate</label>
                    <input type="number" class="form-control" id="sol_usdt_rate" name="sol_usdt_rate" min="0" step="0.0001" value="<?=isset($settings['sol_usdt_rate']) ? floatval($settings['sol_usdt_rate']) : '203.36'?>" required>
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="sol_usdt_rate_save">Save Rate</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Admin Account Modal -->
        <div class="modal fade" id="adminSettingsModal" tabindex="-1" aria-labelledby="adminSettingsModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="adminSettingsModalLabel">Edit Admin Email & Notifications</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="settingsForm" method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?=htmlspecialchars($admin['email'] ?? '')?>" required>
                  </div>
                  <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notify_email" name="notify_email" value="1" <?=($admin['notify_email']?'checked':'')?> >
                    <label class="form-check-label" for="notify_email">Email me about important account activity</label>
                  </div>
                  <button type="submit" class="btn btn-primary w-100" name="settings_save">Save Admin Settings</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Password Modal -->
        <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="passwordModalLabel">Change Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <?php if ($password_success): ?><div class="alert alert-success" id="passwordSuccess"><?=$password_success?></div><?php endif; ?>
                <?php if ($password_error): ?><div class="alert alert-danger" id="passwordError"><?=$password_error?></div><?php endif; ?>
                <form id="passwordForm" method="post" autocomplete="off">
                  <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                  </div>
                  <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                  </div>
                  <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                  </div>
                  <button type="submit" class="btn btn-warning w-100" name="password_save">Change Password</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- 2FA Modal -->
        <div class="modal fade" id="twofaModal" tabindex="-1" aria-labelledby="twofaModalLabel" aria-hidden="true" data-bs-backdrop="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="twofaModalLabel">Configure Two-Factor Authentication (2FA)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <?php if ($twofa_success): ?><div class="alert alert-success" id="twofaSuccess"><?=$twofa_success?></div><?php endif; ?>
                <?php if ($twofa_error): ?><div class="alert alert-danger" id="twofaError"><?=$twofa_error?></div><?php endif; ?>
                <form id="twofaForm" method="post" autocomplete="off">
                  <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="twofa_enabled" name="twofa_enabled" value="1" <?=($admin['twofa_enabled']?'checked':'')?> >
                    <label class="form-check-label" for="twofa_enabled">Enable two-factor authentication for extra security</label>
                  </div>
                  <button type="submit" class="btn btn-secondary w-100" name="twofa_save">Update 2FA Setting</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 