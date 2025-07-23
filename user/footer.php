<?php
require_once __DIR__ . '/../api/settings_helper.php';
$logo = get_setting('logo_path') ?: 'public/vault-logo-new.png';
?>
<footer class="dashboard-footer">
  <img src="<?=htmlspecialchars($logo)?>" alt="Vault Logo" height="32" class="mb-2">
  <div class="mb-2">
    <a href="plans.php" class="text-info me-3">Staking Plans</a>
    <!-- <a href="roadmap.php" class="text-info">Roadmap</a> -->
  </div>
  <div>&copy; <?=date('Y')?> Vault. All rights reserved.</div>
</footer> 