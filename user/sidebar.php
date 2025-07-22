<?php
// user/sidebar.php
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
  <div class="logo mb-4">
    <img src="/vault-logo-new.png" alt="Vault Logo" height="48">
  </div>
  <?php foreach ($sidebarLinks as $link): ?>
    <a href="<?=$link['href']?>" class="nav-link<?=basename($_SERVER['PHP_SELF']) === basename($link['href']) ? ' active' : ''?>">
      <i class="bi <?=$link['icon']?>"></i> <?=$link['label']?>
    </a>
  <?php endforeach; ?>
  <form method="get" class="mt-auto">
    <button type="submit" name="logout" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</button>
  </form>
</div>
<div id="sidebarOverlay" class="sidebar-mobile-overlay"></div> 