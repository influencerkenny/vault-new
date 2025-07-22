<?php
// admin/header.php
require_once __DIR__ . '/../api/settings_helper.php';
$logo = get_setting('logo_path') ?: '/vault-logo-new.png';
$system_name = get_setting('system_name') ?: 'Vault Admin';
?>
<div class="dashboard-header" style="border-bottom: 1px solid #1e293b; padding: 1.5rem 2rem 1rem 2rem; background: rgba(17,24,39,0.85); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10;">
  <div class="logo d-flex align-items-center">
    <!-- Sidebar toggle for mobile -->
    <button id="sidebarToggle" aria-label="Toggle sidebar" style="display:none;background:none;border:none;margin-right:12px;font-size:2rem;color:#38bdf8;cursor:pointer;">
      <i class="bi bi-list"></i>
    </button>
    <img src="<?=htmlspecialchars($logo)?>" alt="Logo" class="me-2" style="height:36px;">
    <span style="font-weight:700;font-size:1.2rem;color:#38bdf8;"><?=htmlspecialchars($system_name)?></span>
  </div>
  <div class="profile-dropdown" id="profileDropdown">
    <button class="profile-btn" id="profileBtn" aria-haspopup="true" aria-expanded="false" aria-label="Admin profile menu" style="background: none; border: none; color: #e5e7eb; font-size: 2rem; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
      <i class="bi bi-person-circle"></i>
    </button>
    <div class="profile-menu" id="profileMenu" role="menu" style="display: none; position: absolute; right: 0; top: 120%; background: #1e293b; border-radius: 0.75rem; box-shadow: 0 8px 32px 0 rgba(31,41,55,0.18); min-width: 180px; z-index: 2000; padding: 0.5rem 0;">
      <a href="#" tabindex="0">Profile</a>
      <a href="#" tabindex="0">Settings</a>
      <a href="logout.php" tabindex="0" class="text-danger">Logout</a>
    </div>
  </div>
</div>
<script>
// Sidebar toggle for mobile
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener('click', function(e) {
    e.stopPropagation();
    sidebar.classList.toggle('open');
  });
  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(e) {
    if (window.innerWidth <= 991 && sidebar.classList.contains('open')) {
      if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    }
  });
}
// Show toggle only on mobile
function handleSidebarToggleVisibility() {
  if (window.innerWidth <= 991) {
    sidebarToggle.style.display = 'inline-block';
  } else {
    sidebarToggle.style.display = 'none';
    sidebar.classList.remove('open');
  }
}
window.addEventListener('resize', handleSidebarToggleVisibility);
document.addEventListener('DOMContentLoaded', handleSidebarToggleVisibility);
// Profile dropdown
const profileBtn = document.getElementById('profileBtn');
const profileDropdown = document.getElementById('profileDropdown');
const profileMenu = document.getElementById('profileMenu');
if (profileBtn) {
  profileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    profileDropdown.classList.toggle('open');
    profileBtn.setAttribute('aria-expanded', profileDropdown.classList.contains('open'));
    if (profileDropdown.classList.contains('open')) profileMenu.style.display = 'block';
    else profileMenu.style.display = 'none';
  });
  document.addEventListener('click', function(e) {
    if (!profileDropdown.contains(e.target)) {
      profileDropdown.classList.remove('open');
      profileBtn.setAttribute('aria-expanded', 'false');
      profileMenu.style.display = 'none';
    }
  });
}
</script> 