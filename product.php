<?php
require_once 'config.php';

$db = getDB();
$slug = $_GET['slug'] ?? '';

$st = $db->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ?");
$st->execute([$slug]);
$product = $st->fetch();

if (!$product) { header('Location: catalog.php'); exit; }

// Изображения
$images = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC");
$images->execute([$product['id']]);
$images = $images->fetchAll();

// Характеристики
$features = $db->prepare("SELECT * FROM product_features WHERE product_id = ?");
$features->execute([$product['id']]);
$features = $features->fetchAll();

// С этим товаром покупают
$related = $db->prepare("
    SELECT p.*, pi.image_path, c.name as cat_name
    FROM products p
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.category_id = ? AND p.id != ? AND p.stock_quantity > 0
    ORDER BY RAND() LIMIT 4
");
$related->execute([$product['category_id'], $product['id']]);
$related = $related->fetchAll();

// Отзывы
$reviews = $db->prepare("
    SELECT r.*, u.full_name FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$reviews->execute([$product['id']]);
$reviews = $reviews->fetchAll();

$avgRating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;



// Обработка отзыва
$reviewError = $reviewSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) { $reviewError = 'Войдите, чтобы оставить отзыв'; }
    else {
        verifyCsrf();
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5) $reviewError = 'Выберите оценку от 1 до 5';
        else {
            try {
                $st = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?,?,?,?)");
                $st->execute([$product['id'], currentUser()['id'], $rating, $comment]);
                header('Location: product.php?slug=' . urlencode($slug) . '&reviewed=1');
                exit;
            } catch (PDOException $e) {
                $reviewError = 'Вы уже оставляли отзыв на этот товар';
            }
        }
    }
}

$pageTitle = htmlspecialchars($product['name']) . ' — купить в интернет-магазине Sakura Shop | Цена, отзывы';
$pageDescription = htmlspecialchars(mb_substr($product['description'] ?? 'Японский товар ' . $product['name'] . ' с доставкой по России.', 0, 160));
$pageKeywords = mb_strtolower($product['name']) . ', японские товары, купить ' . mb_strtolower($product['name']);
$ogImage = $images[0]['image_path'] ?? BASE_URL . 'assets/images/og-image.jpg'; // Первое изображение товара
$canonical = BASE_URL . 'product.php?slug=' . urlencode($slug);
include 'header.php';
?>

<div class="container product-page">
  <!-- Хлебные крошки -->
  <div class="breadcrumbs">
    <a href="index.php">Главная</a> <span>›</span>
    <a href="catalog.php">Каталог</a> <span>›</span>
    <a href="catalog.php?cat=<?= urlencode($product['cat_slug']) ?>"><?= htmlspecialchars($product['cat_name']) ?></a>
    <span>›</span>
    <span><?= htmlspecialchars($product['name']) ?></span>
  </div>

  <!-- Основная секция -->
  <div class="product-page-layout">
    <!-- Галерея -->
    <div class="product-gallery">
      <div class="gallery-main">
        <?php if ($images): ?>
        <img id="mainProductImg"
             src="<?= htmlspecialchars($images[0]['image_path']) ?>"
             alt="<?= htmlspecialchars($product['name']) ?>">
        <?php else: ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:5rem;background:var(--ivory-dark);">🌸</div>
        <?php endif; ?>
      </div>
      <?php if (count($images) > 1): ?>
      <div class="gallery-thumbs">
        <?php foreach ($images as $i => $img): ?>
        <div class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>">
          <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="">
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Информация -->
    <div class="product-info">
      <div class="product-badges" style="position:static;flex-direction:row;margin-bottom:12px;">
        <?php if ($product['is_new']): ?><span class="badge badge-new">Новинка</span><?php endif; ?>
        <?php if ($product['is_popular']): ?><span class="badge badge-popular">Хит продаж</span><?php endif; ?>
        <?php
        $discount = ($product['old_price'] && $product['old_price'] > $product['price'])
            ? round((1 - $product['price'] / $product['old_price']) * 100) : 0;
        if ($discount): ?>
        <span class="badge badge-sale">-<?= $discount ?>%</span>
        <?php endif; ?>
      </div>

      <h1 class="product-page-name"><?= htmlspecialchars($product['name']) ?></h1>

      <!-- Рейтинг -->
      <div class="product-rating-row">
        <div class="stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <span class="star <?= $i <= $avgRating ? '' : 'empty' ?>">★</span>
          <?php endfor; ?>
        </div>
        <span class="rating-count">
          <?= count($reviews) ?> <?= count($reviews) === 1 ? 'отзыв' : 'отзывов' ?>
          <?php if ($reviews): ?> · <?= number_format($avgRating, 1) ?>/5<?php endif; ?>
        </span>
      </div>

      <!-- Цена -->
      <div class="product-page-price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</div>
      <?php if ($product['old_price']): ?>
      <div class="product-page-old-price"><?= number_format($product['old_price'], 0, ',', ' ') ?> ₽</div>
      <?php endif; ?>

      <!-- Наличие -->
      <div class="product-stock <?= $product['stock_quantity'] > 0 ? 'stock-in' : 'stock-out' ?>">
        <?= $product['stock_quantity'] > 0 ? '✓ В наличии' : '✗ Нет в наличии' ?>
      </div>

      <!-- Количество и кнопка -->
      <?php if ($product['stock_quantity'] > 0): ?>
      <div class="product-qty-row">
        <div class="qty-control">
          <button class="qty-btn" data-action="minus">–</button>
          <input type="number" class="qty-val" id="qty" value="1" min="1" max="<?= $product['stock_quantity'] ?>">
          <button class="qty-btn" data-action="plus">+</button>
        </div>
        <?php if (isLoggedIn()): ?>
        <button class="btn btn-primary" data-add-cart="<?= $product['id'] ?>" style="flex:1;">
          🛒 В корзину
        </button>
        <?php else: ?>
        <a href="login.php" class="btn btn-primary" style="flex:1;">Войти для покупки</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Описание -->
      <div class="product-description">
        <?= nl2br(htmlspecialchars($product['description'])) ?>
      </div>

      <!-- Характеристики -->
      <?php if ($features): ?>
      <div style="margin-top:8px;">
        <h3 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:0.9rem;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:12px;">Характеристики</h3>
        <table class="product-features-table">
          <?php foreach ($features as $f): ?>
          <tr>
            <td><?= htmlspecialchars($f['feature_name']) ?></td>
            <td><?= htmlspecialchars($f['feature_value']) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <?php endif; ?>

      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    </div>
  </div>

  <!-- Отзывы -->
  <div class="reviews-section">
    <div class="reviews-header">
      <h2 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:1.4rem;">Отзывы покупателей</h2>
      <?php if ($reviews): ?>
      <div style="display:flex;align-items:center;gap:8px;">
        <div class="stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <span class="star <?= $i <= round($avgRating) ? '' : 'empty' ?>">★</span>
          <?php endfor; ?>
        </div>
        <span style="font-family:var(--font-serif);font-size:1.2rem;color:var(--crimson);"><?= number_format($avgRating, 1) ?></span>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($_GET['reviewed'] ?? false): ?>
    <div style="background:rgba(27,94,66,0.1);border:1px solid var(--emerald);border-radius:4px;padding:12px 16px;color:var(--emerald);margin-bottom:24px;">
      ✓ Ваш отзыв успешно опубликован!
    </div>
    <?php endif; ?>

    <?php if ($reviews): ?>
    <?php foreach ($reviews as $r): ?>
    <div class="review-card">
      <div class="review-header">
        <div>
          <div class="reviewer-name"><?= htmlspecialchars($r['full_name']) ?></div>
          <div class="stars" style="margin-top:4px;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="star <?= $i <= $r['rating'] ? '' : 'empty' ?>" style="font-size:0.85rem;">★</span>
            <?php endfor; ?>
          </div>
        </div>
        <div class="review-date"><?= date('d.m.Y', strtotime($r['created_at'])) ?></div>
      </div>
      <?php if ($r['comment']): ?>
      <p class="review-comment"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <p style="color:var(--charcoal-light);font-size:0.9rem;">Пока нет отзывов. Будьте первым!</p>
    <?php endif; ?>

    <!-- Форма отзыва -->
    <?php if (isLoggedIn()): ?>
    <div class="review-form">
      <h3>Оставить отзыв</h3>
      <?php if ($reviewError): ?>
      <div class="form-error" style="margin-bottom:16px;">⚠ <?= htmlspecialchars($reviewError) ?></div>
      <?php endif; ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="submit_review" value="1">
        <input type="hidden" name="rating" id="ratingInput" required>

        <div style="margin-bottom:16px;">
          <div style="font-size:0.85rem;color:var(--charcoal-light);margin-bottom:8px;">Ваша оценка *</div>
          <div class="star-rating-input">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="star-btn" data-val="<?= $i ?>">★</button>
            <?php endfor; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Комментарий</label>
          <textarea class="form-textarea" name="comment" rows="4"
                    placeholder="Расскажите о товаре..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Отправить отзыв</button>
      </form>
    </div>
    <?php else: ?>
    <div style="background:var(--ivory-dark);border-radius:6px;padding:20px;text-align:center;margin-top:24px;">
      <p style="color:var(--charcoal-light);margin-bottom:12px;">Чтобы оставить отзыв, войдите в аккаунт</p>
      <a href="login.php" class="btn btn-primary btn-sm">Войти</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- С этим товаром покупают -->
<?php if ($related): ?>
<div class="related-products-wrapper">
  <div class="container">
    <div class="section-ornament">
      <span>関連商品</span>
    </div>
    <h2 class="section-title">С этим товаром покупают</h2>
    
    <div class="related-products-grid">
      <?php foreach ($related as $p): ?>
        <?php include 'product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

</div>

<?php include 'footer.php'; ?>
