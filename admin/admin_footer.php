<?php
// admin_footer.php
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Мобильное бургер-меню для админки
  const adminHeader = document.querySelector('.admin-mobile-header');
  const adminSidebar = document.querySelector('.admin-sidebar');
  const overlay = document.querySelector('.admin-sidebar-overlay');
  
  if (adminHeader && adminSidebar) {
    const burgerBtn = adminHeader.querySelector('.admin-burger');
    
    burgerBtn.addEventListener('click', function() {
      adminSidebar.classList.toggle('active');
      if (overlay) overlay.classList.toggle('active');
      document.body.classList.toggle('admin-menu-open');
    });
    
    if (overlay) {
      overlay.addEventListener('click', function() {
        adminSidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.classList.remove('admin-menu-open');
      });
    }
  }
  
  // Закрытие меню при ресайзе
  window.addEventListener('resize', function() {
    if (window.innerWidth > 767) {
      const sidebar = document.querySelector('.admin-sidebar');
      const overlay = document.querySelector('.admin-sidebar-overlay');
      if (sidebar) sidebar.classList.remove('active');
      if (overlay) overlay.classList.remove('active');
      document.body.classList.remove('admin-menu-open');
    }
  });
});
</script>
</body>
</html>