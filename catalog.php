<?php
require_once 'config.php';

$db = getDB();
$slug = $_GET['cat'] ?? null;
$subSlug = $_GET['sub'] ?? null;
$sort = $_GET['sort'] ?? 'popular';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// Получить текущую категорию
$currentCat = null;
if ($slug) {
    $st = $db->prepare("SELECT * FROM categories WHERE slug = ?");
    $st->execute([$slug]);
    $currentCat = $st->fetch();
}

// Все родительские категории
$topCats = $db->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC")->fetchAll();

// Подкатегории текущей категории
$subCats = [];
if ($currentCat) {
    $st = $db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order ASC");
    $st->execute([$currentCat['id']]);
    $subCats = $st->fetchAll();
}

// Определить category_id(ы) для запроса товаров
$categoryIds = [];
if ($currentCat) {
    // Если выбрана подкатегория через фильтр
    if ($subSlug) {
        $st = $db->prepare("SELECT id FROM categories WHERE slug = ? AND parent_id = ?");
        $st->execute([$subSlug, $currentCat['id']]);
        $sub = $st->fetch();
        if ($sub) $categoryIds = [$sub['id']];
    }
    // Если подкатегорий нет или не выбраны — все товары категории и подкатегорий
    if (!$categoryIds) {
        $categoryIds[] = $currentCat['id'];
        foreach ($subCats as $sc) $categoryIds[] = $sc['id'];
    }
}

// Строим запрос
$where = ['p.stock_quantity > 0'];
$params = [];

if ($categoryIds) {
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $where[] = "p.category_id IN ($placeholders)";
    $params = array_merge($params, $categoryIds);
}
if ($minPrice !== null) { $where[] = "p.price >= ?"; $params[] = $minPrice; }
if ($maxPrice !== null) { $where[] = "p.price <= ?"; $params[] = $maxPrice; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

switch ($sort) {
    case 'price_asc':
        $sortSQL = 'p.price ASC';
        break;
    case 'price_desc':
        $sortSQL = 'p.price DESC';
        break;
    case 'new':
        $sortSQL = 'p.created_at DESC';
        break;
    default:
        $sortSQL = 'p.is_popular DESC, p.created_at DESC';
        break;
}

// Подсчёт
$countSt = $db->prepare("SELECT COUNT(*) FROM products p $whereSQL");
$countSt->execute($params);
$total = (int)$countSt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Товары
$sql = "
    SELECT p.*, pi.image_path, c.name as cat_name
    FROM products p
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
    LEFT JOIN categories c ON c.id = p.category_id
    $whereSQL
    ORDER BY $sortSQL
    LIMIT $perPage OFFSET $offset
";
$st = $db->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

// Диапазон цен для фильтра
$priceRange = $db->query("SELECT MIN(price) as min_p, MAX(price) as max_p FROM products WHERE stock_quantity > 0")->fetch();

if ($currentCat) {
    $pageTitle = htmlspecialchars($currentCat['name']) . ' — купить в интернет-магазине Sakura Shop';
    $pageDescription = 'Широкий выбор ' . htmlspecialchars(mb_strtolower($currentCat['name'])) . ' из Японии. ' . (mb_substr($currentCat['description'] ?? 'Аутентичные японские товары с доставкой по России.', 0, 150));
    $pageKeywords = mb_strtolower($currentCat['name']) . ', японские товары, купить ' . mb_strtolower($currentCat['name']);
    $ogImage = $currentCat['image'] ?? BASE_URL . 'assets/images/og-image.jpg'; // Используем изображение категории, если есть
} else {
    $pageTitle = 'Каталог товаров из Японии — Sakura Shop';
    $pageDescription = 'Весь каталог японских товаров: от сладостей до посуды. Удобный поиск, фильтры, быстрая доставка.';
    $pageKeywords = 'каталог японских товаров, все товары из японии';
    $ogImage = BASE_URL . 'assets/images/og-image.jpg';
}

// Канонический URL без параметров сортировки/фильтров
$canonical = BASE_URL . 'catalog.php';
if ($currentCat) {
    $canonical .= '?cat=' . urlencode($slug);
    if ($subSlug) {
        $canonical .= '&sub=' . urlencode($subSlug);
    }
}

include 'header.php';
?>

<!-- Хлебные крошки -->
<div class="container">
  <div class="breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList">
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
    <a itemprop="item" href="index.php"><span itemprop="name">Главная</span></a>
      <meta itemprop="position" content="1">
    </span> ›
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
      <a itemprop="item" href="catalog.php"><span itemprop="name">Каталог</span></a>
      <meta itemprop="position" content="2">
    </span>
    <?php if ($currentCat): ?>
    ›
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
      <span itemprop="name"><?= htmlspecialchars($currentCat['name']) ?></span>
      <meta itemprop="position" content="3">
      <meta itemprop="item" content="<?= $canonical ?>">
    </span>
    <?php endif; ?>
  </div>
</div>

<?php if (!$currentCat): ?>
<!-- ===== Список всех категорий ===== -->
<section class="section">
  <div class="container">
    <div class="section-ornament"><span>カタログ</span></div>
    <h1 class="section-title">Каталог товаров</h1>
    <p class="section-subtitle">Выберите категорию для просмотра</p>

    <div class="categories-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:24px;">
      <?php
      $catPatterns = ['cat-pattern-1','cat-pattern-2','cat-pattern-3','cat-pattern-4','cat-pattern-5'];
      foreach ($topCats as $idx => $cat): ?>
      <a href="catalog.php?cat=<?= urlencode($cat['slug']) ?>" class="category-card" style="aspect-ratio:3/2;">
        <?php if ($cat['image']): ?>
        <div class="cat-bg" style="background-image:url('<?= htmlspecialchars($cat['image']) ?>');"></div>
        <?php else: ?>
        <div class="cat-bg <?= $catPatterns[$idx % 5] ?>"></div>
        <?php endif; ?>
        <div class="cat-overlay">
          <div class="cat-name" style="font-size:1.1rem;"><?= htmlspecialchars($cat['name']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php else: ?>
<!-- ===== Каталог категории ===== -->
<div class="container">
  <div class="catalog-layout">

    <!-- САЙДБАР -->
    <aside class="catalog-sidebar">
      <!-- Навигация по категориям -->
      <div class="filter-card">
        <div class="filter-title">Категории</div>
        <?php foreach ($topCats as $tc): ?>
        <a href="catalog.php?cat=<?= urlencode($tc['slug']) ?>"
           class="subcategory-btn <?= $tc['id'] == $currentCat['id'] ? 'active' : '' ?>">
          <?= htmlspecialchars($tc['name']) ?>
        </a>
        <?php endforeach; ?>
      </div>

      <?php if ($subCats): ?>
      <!-- Подкатегории -->
      <div class="filter-card">
        <div class="filter-title">Подкатегории</div>
        <div class="subcategory-list">
          <button class="subcategory-btn <?= !$subSlug ? 'active' : '' ?>"
                  onclick="document.location='catalog.php?cat=<?= urlencode($slug) ?>'">
            Все
          </button>
          <?php foreach ($subCats as $sc): ?>
          <button class="subcategory-btn <?= $subSlug === $sc['slug'] ? 'active' : '' ?>"
                  data-sub="<?= htmlspecialchars($sc['slug']) ?>">
            <?= htmlspecialchars($sc['name']) ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Фильтр по цене -->
      <div class="filter-card">
        <div class="filter-title">Цена (₽)</div>
        <form id="filterForm" method="GET" action="catalog.php">
          <input type="hidden" name="cat" value="<?= htmlspecialchars($slug) ?>">
          <?php if ($subSlug): ?><input type="hidden" name="sub" value="<?= htmlspecialchars($subSlug) ?>"><?php endif; ?>
          <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
          <div class="price-range">
            <input type="number" class="price-input" name="min_price" placeholder="От"
                   value="<?= $minPrice ? (int)$minPrice : '' ?>"
                   min="0" max="<?= (int)$priceRange['max_p'] ?>">
            <span style="color:var(--charcoal-light);">—</span>
            <input type="number" class="price-input" name="max_price" placeholder="До"
                   value="<?= $maxPrice ? (int)$maxPrice : '' ?>"
                   min="0" max="<?= (int)$priceRange['max_p'] ?>">
          </div>
          <div style="margin-top:12px;">
            <button type="submit" class="btn btn-primary btn-sm btn-full">Применить</button>
          </div>
          <?php if ($minPrice || $maxPrice): ?>
          <a href="catalog.php?cat=<?= urlencode($slug) ?>" style="display:block;text-align:center;margin-top:8px;font-size:0.8rem;color:var(--charcoal-light);">
            Сбросить фильтры
          </a>
          <?php endif; ?>
        </form>
      </div>
    </aside>

    <!-- ТОВАРЫ -->
    <main>
      <div class="catalog-header">
        <div>
          <h1 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:1.4rem;">
            <?= htmlspecialchars($currentCat['name']) ?>
          </h1>
          <p style="font-size:0.85rem;color:var(--charcoal-light);margin-top:4px;">
            Найдено товаров: <?= $total ?>
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
      <div class="products-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
        <?php foreach ($products as $p): ?>
        <?php include 'product_card.php'; ?>
        <?php endforeach; ?>
      </div>

      <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "<?= htmlspecialchars($currentCat['name']) ?>",
        "description": "Список товаров в категории <?= htmlspecialchars($currentCat['name']) ?>",
        "url": "<?= $canonical ?>",
        "numberOfItems": <?= $total ?>,
        "itemListElement": [
          <?php foreach ($products as $index => $p): ?>
          {
            "@type": "ListItem",
            "position": <?= $offset + $index + 1 ?>,
            "url": "<?= BASE_URL ?>product.php?slug=<?= urlencode($p['slug']) ?>"
          }<?= $index < count($products) - 1 ? ',' : '' ?>
          <?php endforeach; ?>
        ]
      }
      </script>


      <!-- Пагинация -->
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
      <div style="text-align:center;padding:80px 0;color:var(--charcoal-light);">
        <div style="font-size:3rem;margin-bottom:16px;">🌸</div>
        <h3 style="font-family:var(--font-serif);color:var(--crimson-deep);margin-bottom:8px;">Товары не найдены</h3>
        <p style="font-size:0.9rem;">Попробуйте изменить фильтры или выбрать другую категорию</p>
      </div>
      <?php endif; ?>
    </main>

  </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
