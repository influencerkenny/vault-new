<?php
// user/sidebar.php
require_once __DIR__ . '/../api/settings_helper.php';
$logo = get_setting('logo_path') ?: 'public/vault-logo-new.png';
$sidebarLinks = [
  ['href' => 'user-dashboard.php', 'label' => 'Dashboard', 'icon' => 'bi-house'],
  ['href' => 'plans.php', 'label' => 'Plans', 'icon' => 'bi-layers'],
  ['href' => 'deposits.php', 'label' => 'Deposits', 'icon' => 'bi-download'],
  ['href' => 'withdrawals.php', 'label' => 'Withdrawals', 'icon' => 'bi-upload'],
  ['href' => 'transactions.php', 'label' => 'Transactions', 'icon' => 'bi-list'],
  ['href' => 'referral.php', 'label' => 'Referral', 'icon' => 'bi-people'],
  ['href' => 'account-settings.php', 'label' => 'Settings', 'icon' => 'bi-gear'],
  ['href' => 'profile.php', 'label' => 'Profile', 'icon' => 'bi-person'],
  ['href' => 'support.php', 'label' => 'Support', 'icon' => 'bi-question-circle'],
];
?>
<div id="sidebar" class="sidebar d-none">
  <button type="button" class="sidebar-close-btn" aria-label="Close sidebar" onclick="closeSidebar()" style="position:absolute;top:14px;right:14px;display:none;font-size:2rem;background:none;border:none;color:#fff;z-index:2100;line-height:1;cursor:pointer;">&times;</button>
  <div class="logo mb-4">
    <img src="<?=htmlspecialchars($logo)?>" alt="Vault Logo" height="48">
  </div>
  <?php foreach ($sidebarLinks as $link): ?>
    <a href="<?=$link['href']?>" class="nav-link<?=basename($_SERVER['PHP_SELF']) === basename($link['href']) ? ' active' : ''?>">
      <i class="bi <?=$link['icon']?>"></i> <?=$link['label']?>
    </a>
  <?php endforeach; ?>
  <form method="get" class="mt-auto">
    <a href="?logout=1" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </form>
</div>
<div id="sidebarOverlay" class="sidebar-mobile-overlay"></div> 