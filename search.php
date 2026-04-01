<?php
require_once 'config.php';

$db = getDB();
$q    = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'popular';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$products = [];
$total = 0;
$totalPages = 1;

if ($q) {
    $like = '%' . $q . '%';
    $params = [$like, $like];

    $countSt = $db->prepare("SELECT COUNT(*) FROM products p WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.stock_quantity > 0");
    $countSt->execute($params);
    $total = (int)$countSt->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));
    $offset = ($page - 1) * $perPage;

    switch ($sort) {
      case 'price_asc':
          $sortSQL = 'price ASC';
          break;
      case 'price_desc':
          $sortSQL = 'price DESC';
          break;
      default:
          $sortSQL = 'created_at DESC';
          break;
    }

    $st = $db->prepare("
        SELECT p.*, pi.image_path, c.name as cat_name
        FROM products p
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.stock_quantity > 0
        ORDER BY $sortSQL
        LIMIT $perPage OFFSET $offset
    ");
    $st->execute($params);
    $products = $st->fetchAll();
}

$pageTitle = $q ? "Поиск: «{$q}» — Sakura Shop" : 'Поиск — Sakura Shop';
include 'header.php';
?>

<div class="container" style="padding-top:24px;">
  <div class="breadcrumbs">
    <a href="index.php">Главная</a> <span>›</span>
    <span>Поиск</span>
  </div>
</div>

<div class="container" style="padding-bottom:72px;">
  <div class="section-ornament" style="margin-top:16px;"><span>検索</span></div>

  <!-- Строка поиска -->
  <div style="max-width:600px;margin:0 auto 48px;">
    <form action="search.php" method="GET">
      <div style="display:flex;background:var(--white);border:2px solid rgba(139,0,0,0.15);border-radius:6px;overflow:hidden;box-shadow:var(--shadow-sm);">
        <input type="text" name="q" class="search-input"
               value="<?= htmlspecialchars($q) ?>"
               placeholder="Поиск японских товаров..."
               style="background:var(--white);color:var(--charcoal);padding:14px 20px;font-size:1rem;border:none;flex:1;outline:none;">
        <button type="submit" class="btn btn-primary" style="border-radius:0;padding:14px 24px;">
          Найти
        </button>
      </div>
    </form>
  </div>

  <?php if ($q): ?>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:1.4rem;margin-bottom:4px;">
        Результаты поиска: «<?= htmlspecialchars($q) ?>»
      </h1>
      <p style="font-size:0.85rem;color:var(--charcoal-light);">
        Найдено: <?= $total ?> <?= $total === 1 ? 'товар' : ($total < 5 ? 'товара' : 'товаров') ?>
      </p>
    </div>
    <select id="sortSelect" class="sort-select">
      <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>По популярности</option>
      <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Сначала новые</option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Цена: по возрастанию</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Цена: по убыванию</option>
    </select>
  </div>

  <?php if ($products): ?>
  <div class="products-grid">
    <?php foreach ($products as $p): ?>
    <?php include 'product_card.php'; ?>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-btn">‹</a>
    <?php endif; ?>
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
       class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-btn">›</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div style="text-align:center;padding:80px 0;">
    <div style="font-size:4rem;margin-bottom:20px;">🔍</div>
    <h3 style="font-family:var(--font-serif);color:var(--crimson-deep);margin-bottom:8px;">Ничего не найдено</h3>
    <p style="color:var(--charcoal-light);margin-bottom:24px;">
      По запросу «<?= htmlspecialchars($q) ?>» товаров не найдено.<br>
      Попробуйте другое ключевое слово.
    </p>
    <a href="catalog.php" class="btn btn-primary">Перейти в каталог</a>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div style="text-align:center;padding:60px 0;">
    <p style="color:var(--charcoal-light);font-size:0.95rem;">Введите запрос для поиска товаров</p>
    <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:24px;">
      <?php
      $hints = ['Рамэн', 'Моти', 'Маска для лица', 'Ручка Pilot', 'Оригами', 'Дарума', 'Каллиграфия'];
      foreach ($hints as $h): ?>
      <a href="search.php?q=<?= urlencode($h) ?>"
         style="padding:8px 16px;background:var(--sakura-blush);border:1px solid rgba(139,0,0,0.12);border-radius:20px;font-size:0.85rem;color:var(--crimson);transition:var(--transition);"
         onmouseover="this.style.background='var(--crimson)';this.style.color='white';"
         onmouseout="this.style.background='var(--sakura-blush)';this.style.color='var(--crimson)';">
        <?= htmlspecialchars($h) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
