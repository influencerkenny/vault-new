// Sidebar Toggle Script (Reusable)
// Requires: #sidebar, #sidebarToggle, #sidebarOverlay in your HTML
(function() {
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const sidebarToggle = document.getElementById('sidebarToggle');

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('active','d-flex');
    sidebar.classList.remove('d-none');
    if (sidebarOverlay) sidebarOverlay.classList.add('active');
  }
  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('active','d-flex');
    if (sidebarOverlay) sidebarOverlay.classList.remove('active');
    if (window.innerWidth < 992) sidebar.classList.add('d-none');
  }
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', openSidebar);
  }
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
  }
  // Close sidebar when a link is clicked (on mobile)
  if (sidebar) {
    sidebar.querySelectorAll('.nav-link').forEach(function(link) {
      link.addEventListener('click', function() {
        if (window.innerWidth < 992) closeSidebar();
      });
    });
  }
  // Responsive: reset sidebar on resize
  window.addEventListener('resize', function() {
    if (!sidebar) return;
    if (window.innerWidth >= 992) {
      sidebar.classList.remove('d-none');
      sidebar.classList.add('d-flex');
      if (sidebarOverlay) sidebarOverlay.classList.remove('active');
    } else {
      sidebar.classList.remove('d-flex');
      sidebar.classList.add('d-none');
    }
  });
})(); 