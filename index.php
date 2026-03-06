<?php
require_once 'config.php';
$pageTitle = 'Sakura Shop — Интернет-магазин японских товаров в России';
$pageDescription = 'Sakura Shop: всё из Японии! Сладости (моти, киткат), косметика, посуда, сувениры. Аутентичные товары с доставкой по Москве и РФ. Заходите!';
$pageKeywords = 'японский магазин, товары из японии, японские сладости, японская косметика, сакура шоп, sakura shop';
$ogImage = BASE_URL . 'assets/images/og-image.jpg';
$canonical = BASE_URL;

$db = getDB();

// Активные баннеры
$banners = $db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Популярные товары
$popular = $db->query("
    SELECT p.*, pi.image_path, c.name as cat_name
    FROM products p
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_popular = 1 AND p.stock_quantity > 0
    ORDER BY p.created_at DESC LIMIT 8
")->fetchAll();

// Новинки
$newProducts = $db->query("
    SELECT p.*, pi.image_path, c.name as cat_name
    FROM products p
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_new = 1 AND p.stock_quantity > 0
    ORDER BY p.created_at DESC LIMIT 4
")->fetchAll();

// Родительские категории
$categories = $db->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC")->fetchAll();

include 'header.php';
?>

<!-- ===================== СЛАЙДЕР ===================== -->
<section class="slider-section">
  <div class="slider-track">
    <?php if ($banners): foreach ($banners as $i => $banner): ?>
    <div class="slide <?= $i === 0 ? 'active' : '' ?>">
      <div class="slide-bg" style="background-image:url('<?= htmlspecialchars($banner['image_path']) ?>');"></div>
      <div class="slide-overlay"></div>
      <div class="container" style="position:relative;z-index:2;width:100%;">
        <div class="slide-content">
          <span class="slide-kana">さくらショップ · Специальное предложение</span>
          <h1 class="slide-title"><?= htmlspecialchars($banner['title']) ?></h1>
          <?php if ($banner['subtitle']): ?>
          <p class="slide-subtitle"><?= htmlspecialchars($banner['subtitle']) ?></p>
          <?php endif; ?>
          <?php if ($banner['link']): ?>
          <a href="<?= htmlspecialchars($banner['link']) ?>" class="btn btn-gold">Смотреть предложение</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; else: ?>
    <!-- Заглушка если нет баннеров -->
    <div class="slide active">
      <div class="slide-bg" style="background:linear-gradient(135deg,var(--crimson-deep),var(--charcoal));"></div>
      <div class="slide-overlay"></div>
      <div class="container" style="position:relative;z-index:2;width:100%;">
        <div class="slide-content">
          <span class="slide-kana">さくらショップ · Добро пожаловать</span>
          <h1 class="slide-title">Магазин японской культуры</h1>
          <p class="slide-subtitle">Аутентичные товары из Японии с доставкой по всей России</p>
          <a href="catalog.php" class="btn btn-gold">Перейти в каталог</a>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Точки -->
  <?php if (count($banners) > 1): ?>
  <div class="slider-controls">
    <?php foreach ($banners as $i => $b): ?>
    <button class="slider-dot <?= $i === 0 ? 'active' : '' ?>" aria-label="Слайд <?= $i+1 ?>"></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- ===================== КАТЕГОРИИ ===================== -->
<section class="section">
  <div class="container">
    <div class="section-ornament"><span>カテゴリー</span></div>
    <h2 class="section-title">Категории товаров</h2>
    <p class="section-subtitle">Откройте для себя мир японской культуры</p>

    <div class="categories-grid">
      <?php
      $catPatterns = ['cat-pattern-1','cat-pattern-2','cat-pattern-3','cat-pattern-4','cat-pattern-5'];
      foreach ($categories as $idx => $cat): ?>
      <a href="catalog.php?cat=<?= urlencode($cat['slug']) ?>" class="category-card">
        <?php if ($cat['image']): ?>
        <div class="cat-bg" style="background-image:url('<?= htmlspecialchars($cat['image']) ?>');"></div>
        <?php else: ?>
        <div class="cat-bg <?= $catPatterns[$idx % 5] ?>"></div>
        <?php endif; ?>
        <div class="cat-overlay">
          <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== ПОПУЛЯРНЫЕ ТОВАРЫ ===================== -->
<?php if ($popular): ?>
<section class="section section-ivory">
  <div class="container">
    <div class="section-ornament"><span>人気商品</span></div>
    <h2 class="section-title">Популярные товары</h2>
    <p class="section-subtitle">Самые любимые товары наших покупателей</p>

    <div class="products-grid">
      <?php foreach ($popular as $p): ?>
      <?php include 'product_card.php'; ?>
      <?php endforeach; ?>
    </div>

    <!-- Микроразметка Schema.org для списка товаров (ItemList) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ItemList",
      "name": "Популярные товары в Sakura Shop",
      "description": "Список самых популярных товаров нашего магазина.",
      "url": "<?= BASE_URL ?>#popular",
      "numberOfItems": <?= count($popular) ?>,
      "itemListElement": [
        <?php foreach ($popular as $index => $p): ?>
        {
          "@type": "ListItem",
          "position": <?= $index + 1 ?>,
          "url": "<?= BASE_URL ?>product.php?slug=<?= urlencode($p['slug']) ?>"
        }<?= $index < count($popular) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      ]
    }
    </script>

    <div style="text-align:center;margin-top:40px;">
      <a href="catalog.php" class="btn btn-secondary btn-lg">Смотреть весь каталог</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===================== БАННЕР ===================== -->
<section style="background:var(--crimson-deep);padding:64px 0;position:relative;overflow:hidden;">
  <div style="position:absolute;right:-20px;top:-60px;font-size:18rem;color:rgba(255,255,255,0.04);font-family:var(--font-serif);line-height:1;pointer-events:none;user-select:none;">日</div>
  <div class="container" style="text-align:center;position:relative;">
    <div class="section-ornament"><span style="color:var(--gold);">私たちについて</span></div>
    <h2 style="font-family:var(--font-serif);font-size:clamp(1.6rem,3vw,2.4rem);color:var(--white);margin-bottom:16px;">
      Популяризируем японскую культуру в России
    </h2>
    <p style="color:rgba(250,245,236,0.75);max-width:560px;margin:0 auto 32px;">
      Мы тщательно отбираем каждый товар, чтобы передать настоящий дух Японии — её вкусы, запахи, эстетику и традиции.
    </p>
  </div>
</section>

<!-- ===================== НОВИНКИ ===================== -->
<?php if ($newProducts): ?>
<section class="section">
  <div class="container">
    <div class="section-ornament"><span>新着商品</span></div>
    <h2 class="section-title">Новинки</h2>
    <p class="section-subtitle">Свежие поступления в нашем магазине</p>

    <div class="products-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">
      <?php foreach ($newProducts as $p): ?>
      <?php include 'product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>



<?php include 'footer.php'; ?>
