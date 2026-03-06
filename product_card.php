<?php
// includes/product_card.php
// Ожидает переменную $p — массив данных о товаре
$discount = ($p['old_price'] && $p['old_price'] > $p['price'])
    ? round((1 - $p['price'] / $p['old_price']) * 100) : 0;
?>
<div class="product-card">
  <div class="product-img-wrap">
    <?php if (!empty($p['image_path'])): ?>
    <a href="product.php?slug=<?= urlencode($p['slug']) ?>">
      <img src="<?= htmlspecialchars($p['image_path']) ?>"
           alt="<?= htmlspecialchars($p['name']) ?>"
           loading="lazy">
    </a>
    <?php else: ?>
    <a href="product.php?slug=<?= urlencode($p['slug']) ?>" class="product-img-placeholder">
      🌸
    </a>
    <?php endif; ?>

    <div class="product-badges">
      <?php if ($p['is_new']): ?><span class="badge badge-new">Новинка</span><?php endif; ?>
      <?php if ($discount): ?><span class="badge badge-sale">-<?= $discount ?>%</span><?php endif; ?>
      <?php if ($p['is_popular']): ?><span class="badge badge-popular">Хит</span><?php endif; ?>
    </div>
  </div>

  <div class="product-body">
    <?php if (!empty($p['cat_name'])): ?>
    <div class="product-category-tag"><?= htmlspecialchars($p['cat_name']) ?></div>
    <?php endif; ?>

    <div class="product-name">
      <a href="product.php?slug=<?= urlencode($p['slug']) ?>"><?= htmlspecialchars($p['name']) ?></a>
    </div>

    <div class="product-price-row">
      <span class="product-price"><?= number_format($p['price'], 0, ',', ' ') ?> ₽</span>
      <?php if ($p['old_price']): ?>
      <span class="product-old-price"><?= number_format($p['old_price'], 0, ',', ' ') ?> ₽</span>
      <?php endif; ?>
    </div>

    <div class="product-actions">
      <?php if (isLoggedIn()): ?>
        <?php if ($p['stock_quantity'] > 0): ?>
        <button class="btn btn-primary btn-sm"
                data-add-cart="<?= $p['id'] ?>"
                style="flex:1;">
          В корзину
        </button>
        <?php else: ?>
        <button class="btn btn-secondary btn-sm" disabled style="flex:1;opacity:0.5;">Нет в наличии</button>
        <?php endif; ?>
      <?php else: ?>
        <a href="login.php" class="btn btn-secondary btn-sm" style="flex:1;">Войдите, чтобы купить</a>
      <?php endif; ?>
      <a href="product.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-sm" style="border:1px solid rgba(139,0,0,0.2);padding:8px 12px;">👁</a>
    </div>
  </div>
</div>
