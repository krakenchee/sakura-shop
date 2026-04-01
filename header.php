<?php

if (!isset($pageTitle)) $pageTitle = 'Sakura Shop — японский магазин в России';
if (!isset($pageDescription)) $pageDescription = 'Интернет-магазин японских товаров: сладости, косметика, посуда, сувениры. Прямые поставки из Японии. Доставка по всей России.';
if (!isset($pageKeywords)) $pageKeywords = 'японские товары, японские сладости, косметика из японии, сувениры япония, сакура шоп, sakura shop';
if (!isset($ogImage)) $ogImage = BASE_URL . 'assets/images/og-image.jpg'; // Стандартное изображение для соцсетей
if (!isset($canonical)) $canonical = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if (!isset($bodyClass)) $bodyClass = '';

// Получить количество товаров в корзине
$cartCount = 0;
if (isLoggedIn()) {
    $db = getDB();
    $st = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?");
    $st->execute([currentUser()['id']]);
    $cartCount = (int)$st->fetchColumn();
}
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($pageKeywords) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
   <!-- Open Graph / Социальные сети -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
  <meta property="og:site_name" content="Sakura Shop">

  <link rel="stylesheet" href="<?= BASE_URL ?>css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/header.css">
  <link rel="icon" href="<?= BASE_URL ?>assets/images/favicon.svg" type="image/svg+xml">

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Store",
    "@id": "<?= BASE_URL ?>#organization",
    "name": "Sakura Shop",
    "url": "<?= BASE_URL ?>",
    "logo": "<?= BASE_URL ?>assets/images/logo.svg",
    "image": "<?= BASE_URL ?>assets/images/og-image.jpg",
    "description": "<?= htmlspecialchars($pageDescription) ?>",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Москва, Россия",
      "streetAddress": "ул. Примерная, д. 1"
    },
    "sameAs": [
      "https://vk.com/sakurashop",
      "https://t.me/sakurashop"
    ]
  }
  </script>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">

<div id="toast-container" class="toast-container"></div>

<!-- HEADER -->
<header class="site-header">
  <div class="header-inner container">
    <!-- Логотип -->
    <a href="<?= BASE_URL ?>index.php" class="site-logo">
      <div class="logo-symbol">桜</div>
      <div class="logo-text">
        <span class="en">Sakura Shop</span>
        <span class="jp">さくらショップ</span>
      </div>
    </a>

    <!-- Поиск (всегда виден на десктопе и планшете) -->
    <form class="search-form desktop-search" action="<?= BASE_URL ?>search.php" method="GET">
      <input type="text" name="q" class="search-input"
             placeholder="Поиск товаров..."
             value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
             autocomplete="off">
      <button type="submit" class="search-btn" aria-label="Поиск">
        🔍
      </button>
    </form>

    <!-- Действия хедера (всегда видны на десктопе и планшете) -->
    <div class="header-actions">
      <?php if ($user): ?>
        <?php if (!isAdmin()): ?>
          <a href="<?= BASE_URL ?>account.php" class="btn-header btn-header-outline">
            👤 <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>
          </a>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
          <a href="<?= BASE_URL ?>admin/index.php" class="btn-header btn-header-outline">
            ⚙️ Админ
          </a>
        <?php endif; ?>

        <?php if (!isAdmin()): ?>
          <a href="<?= BASE_URL ?>cart.php" class="btn-header btn-header-cart">
            🛒
            <span class="cart-count"><?= $cartCount ?></span>
          </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>logout.php" class="btn-header btn-header-outline">Выйти</a>

      <?php else: ?>
        <a href="<?= BASE_URL ?>login.php" class="btn-header btn-header-outline">Войти</a>
        <a href="<?= BASE_URL ?>register.php" class="btn-header btn-header-cart">Регистрация</a>
      <?php endif; ?>
    </div>

    <!-- Мобильные кнопки (только для телефонов) -->
    <button class="mobile-menu-toggle mobile-only" id="mobileMenuToggle" aria-label="Меню">
      <span></span>
      <span></span>
      <span></span>
    </button>
    
    <button class="mobile-search-toggle mobile-only" id="mobileSearchToggle" aria-label="Поиск">
      🔍
    </button>
  </div>

  <!-- Поиск для мобильных (появляется по кнопке) -->
  <div class="mobile-search-wrapper mobile-only" id="mobileSearchWrapper">
    <div class="container">
      <form class="mobile-search-form" action="<?= BASE_URL ?>search.php" method="GET">
        <input type="text" name="q" class="mobile-search-input"
               placeholder="Поиск товаров..."
               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
               autocomplete="off">
        <button type="submit" class="mobile-search-btn" aria-label="Поиск">Найти</button>
      </form>
    </div>
  </div>

  <!-- Навигация (десктоп и планшет) -->
  <nav class="site-nav desktop-nav">
    <div class="nav-inner">
      <a href="<?= BASE_URL ?>index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Главная</a>
      <a href="<?= BASE_URL ?>catalog.php" class="nav-link <?= (strpos(basename($_SERVER['PHP_SELF']), 'catalog') !== false) ? 'active' : '' ?>">Каталог</a>   
      <a href="<?= BASE_URL ?>delivery.php" class="nav-link">Доставка и оплата</a>
      <?php if ($user): ?>
        <a href="<?= BASE_URL ?>account.php" class="nav-link">Личный кабинет</a>
      <?php endif; ?>
    </div>
  </nav>

  <!-- Мобильное меню (только для телефонов) -->
  <div class="mobile-menu mobile-only" id="mobileMenu">
    <div class="mobile-menu-inner">
      <nav class="mobile-nav">
        <a href="<?= BASE_URL ?>index.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Главная</a>
        <a href="<?= BASE_URL ?>catalog.php" class="mobile-nav-link <?= (strpos(basename($_SERVER['PHP_SELF']), 'catalog') !== false) ? 'active' : '' ?>">Каталог</a>   
        <a href="<?= BASE_URL ?>delivery.php" class="mobile-nav-link">Доставка и оплата</a>
        <?php if ($user): ?>
          <a href="<?= BASE_URL ?>account.php" class="mobile-nav-link">Личный кабинет</a>
        <?php endif; ?>
      </nav>

      <div class="mobile-actions">
        <?php if ($user): ?>
          <?php if (!isAdmin()): ?>
            <a href="<?= BASE_URL ?>account.php" class="mobile-action-btn">
              <span class="mobile-action-icon">👤</span>
              <span class="mobile-action-text"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></span>
            </a>
          <?php endif; ?>

          <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>admin/index.php" class="mobile-action-btn">
              <span class="mobile-action-icon">⚙️</span>
              <span class="mobile-action-text">Админ</span>
            </a>
          <?php endif; ?>

          <?php if (!isAdmin()): ?>
            <a href="<?= BASE_URL ?>cart.php" class="mobile-action-btn">
              <span class="mobile-action-icon">🛒</span>
              <span class="mobile-action-text">Корзина</span>
              <?php if ($cartCount > 0): ?>
                <span class="mobile-cart-count"><?= $cartCount ?></span>
              <?php endif; ?>
            </a>
          <?php endif; ?>

          <a href="<?= BASE_URL ?>logout.php" class="mobile-action-btn">
            <span class="mobile-action-icon">🚪</span>
            <span class="mobile-action-text">Выйти</span>
          </a>

        <?php else: ?>
          <a href="<?= BASE_URL ?>login.php" class="mobile-action-btn">
            <span class="mobile-action-icon">🔑</span>
            <span class="mobile-action-text">Войти</span>
          </a>
          <a href="<?= BASE_URL ?>register.php" class="mobile-action-btn">
            <span class="mobile-action-icon">📝</span>
            <span class="mobile-action-text">Регистрация</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Только для мобильных устройств (ширина < 768px)
  function isMobile() {
    return window.innerWidth < 768;
  }
  
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const mobileSearchToggle = document.getElementById('mobileSearchToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileSearchWrapper = document.getElementById('mobileSearchWrapper');
  
  if (mobileMenuToggle && mobileMenu) {
    mobileMenuToggle.addEventListener('click', function() {
      if (!isMobile()) return;
      
      this.classList.toggle('active');
      mobileMenu.classList.toggle('active');
      document.body.classList.toggle('menu-open');
      
      // Закрываем поиск, если открыт
      if (mobileSearchWrapper && mobileSearchWrapper.classList.contains('active')) {
        mobileSearchWrapper.classList.remove('active');
        mobileSearchToggle.classList.remove('active');
      }
    });
  }
  
  if (mobileSearchToggle && mobileSearchWrapper) {
    mobileSearchToggle.addEventListener('click', function() {
      if (!isMobile()) return;
      
      mobileSearchWrapper.classList.toggle('active');
      this.classList.toggle('active');
      
      // Закрываем меню, если открыто
      if (mobileMenu && mobileMenu.classList.contains('active')) {
        mobileMenu.classList.remove('active');
        mobileMenuToggle.classList.remove('active');
      }
      
      // Фокус на поиск
      if (mobileSearchWrapper.classList.contains('active')) {
        setTimeout(() => {
          mobileSearchWrapper.querySelector('.mobile-search-input').focus();
        }, 100);
      }
    });
  }
  
  // Закрытие при клике вне меню
  document.addEventListener('click', function(event) {
    if (!isMobile()) return;
    
    if (!event.target.closest('.mobile-menu') && 
        !event.target.closest('.mobile-menu-toggle') &&
        mobileMenu && mobileMenu.classList.contains('active')) {
      mobileMenu.classList.remove('active');
      mobileMenuToggle.classList.remove('active');
    }
    
    if (!event.target.closest('.mobile-search-wrapper') && 
        !event.target.closest('.mobile-search-toggle') &&
        mobileSearchWrapper && mobileSearchWrapper.classList.contains('active')) {
      mobileSearchWrapper.classList.remove('active');
      mobileSearchToggle.classList.remove('active');
    }
  });
  
  // При изменении размера окна
  window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) {
      // Если перешли на десктоп/планшет, закрываем мобильные меню
      if (mobileMenu) mobileMenu.classList.remove('active');
      if (mobileMenuToggle) mobileMenuToggle.classList.remove('active');
      if (mobileSearchWrapper) mobileSearchWrapper.classList.remove('active');
      if (mobileSearchToggle) mobileSearchToggle.classList.remove('active');
      document.body.classList.remove('menu-open');
    }
  });
});
</script>