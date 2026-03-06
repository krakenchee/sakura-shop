<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';
$error = '';

// Получить категории для select
$allCats = $db->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id ASC, sort_order ASC")->fetchAll();

// Удаление
if (isset($_GET['delete'])) {
    verifyCsrf();
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    $msg = 'Товар удалён';
    header('Location: products.php?msg=' . urlencode($msg));
    exit;
}

// Сохранение (добавление/редактирование)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    verifyCsrf();
    $id          = (int)($_POST['id'] ?? 0);
    $categoryId  = (int)$_POST['category_id'];
    $name        = trim($_POST['name']);
    $slug        = trim($_POST['slug']) ?: mb_strtolower(preg_replace('/[^a-z0-9]+/i', '-', transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $name)));
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $oldPrice    = $_POST['old_price'] ? (float)$_POST['old_price'] : null;
    $stock       = (int)$_POST['stock_quantity'];
    $isNew       = isset($_POST['is_new']) ? 1 : 0;
    $isPopular   = isset($_POST['is_popular']) ? 1 : 0;

    if (!$name || !$categoryId || $price <= 0) {
        $error = 'Заполните обязательные поля';
    } else {
        if ($id) {
            $db->prepare("UPDATE products SET category_id=?,name=?,slug=?,description=?,price=?,old_price=?,stock_quantity=?,is_new=?,is_popular=? WHERE id=?")
               ->execute([$categoryId, $name, $slug, $description, $price, $oldPrice, $stock, $isNew, $isPopular, $id]);
            $msg = 'Товар обновлён';
        } else {
            $db->prepare("INSERT INTO products (category_id,name,slug,description,price,old_price,stock_quantity,is_new,is_popular) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([$categoryId, $name, $slug, $description, $price, $oldPrice, $stock, $isNew, $isPopular]);
            $newId = $db->lastInsertId();

            // Добавить изображение если указано
            $imagePath = trim($_POST['image_path'] ?? '');
            if ($imagePath) {
                $db->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?,?,1)")->execute([$newId, $imagePath]);
            }

            $msg = 'Товар добавлен';
        }
        header('Location: products.php?msg=' . urlencode($msg));
        exit;
    }
}

// Сохранение характеристик
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_features'])) {
    verifyCsrf();
    $productId = (int)$_POST['product_id'];
    $db->prepare("DELETE FROM product_features WHERE product_id = ?")->execute([$productId]);
    $names  = $_POST['feature_name'] ?? [];
    $values = $_POST['feature_value'] ?? [];
    foreach ($names as $i => $fname) {
        $fval = $values[$i] ?? '';
        if (trim($fname) && trim($fval)) {
            $db->prepare("INSERT INTO product_features (product_id, feature_name, feature_value) VALUES (?,?,?)")->execute([$productId, trim($fname), trim($fval)]);
        }
    }
    $msg = 'Характеристики сохранены';
    header('Location: products.php?msg=' . urlencode($msg));
    exit;
}

// Редактируемый товар
$editProduct = null;
$productFeatures = [];
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM products WHERE id = ?");
    $st->execute([$_GET['edit']]);
    $editProduct = $st->fetch();
    if ($editProduct) {
        $st = $db->prepare("SELECT * FROM product_features WHERE product_id = ?");
        $st->execute([$editProduct['id']]);
        $productFeatures = $st->fetchAll();
    }
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

// Список товаров
$products = $db->query("
    SELECT p.*, c.name as cat_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.created_at DESC
")->fetchAll();

$pageTitle = 'Товары — Admin';
include 'admin_header.php';
?>
<div class="admin-layout">
  <!-- Мобильный хедер с бургером -->
  <div class="admin-mobile-header">
    <button class="admin-burger" aria-label="Меню">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <span class="admin-mobile-logo">⛩ Sakura Admin</span>
  </div>
  
  <!-- Оверлей -->
  <div class="admin-sidebar-overlay"></div>
  
  <!-- Сайдбар -->
  <aside class="admin-sidebar">
    <div class="admin-logo">⛩ Sakura Admin</div>
    <nav class="admin-nav">
      <a href="index.php" class="admin-nav-item">📊 Дашборд</a>
      <a href="orders.php" class="admin-nav-item">📦 Заказы</a>
      <a href="products.php" class="admin-nav-item active">🛍 Товары</a>
      <a href="categories.php" class="admin-nav-item">📂 Категории</a>
      <a href="banners.php" class="admin-nav-item">🖼 Баннеры</a>
      <a href="users.php" class="admin-nav-item">👥 Пользователи</a>
      <a href="feedback.php" class="admin-nav-item">✉️ Обратная связь</a>
      <div class="admin-nav-divider"></div>
      <a href="<?= BASE_URL ?>index.php" class="admin-nav-item">🌐 На сайт</a>
      <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item">🚪 Выйти</a>
    </nav>
  </aside>

  <!-- Основной контент -->
  <main class="admin-main">
    <div class="admin-page-title">Управление товарами</div>

    <?php if ($msg): ?>
    <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="admin-message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Форма добавления/редактирования -->
    <div class="admin-form-card">
      <h2 class="admin-form-title">
        <?= $editProduct ? 'Редактирование: ' . htmlspecialchars($editProduct['name']) : 'Добавить товар' ?>
      </h2>
      
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="save_product" value="1">
        <?php if ($editProduct): ?><input type="hidden" name="id" value="<?= $editProduct['id'] ?>"><?php endif; ?>

        <div class="admin-form-grid">
          <div class="admin-form-group">
            <label class="admin-form-label">Название *</label>
            <input type="text" class="admin-form-input" name="name" required
                   value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Slug (URL)</label>
            <input type="text" class="admin-form-input" name="slug"
                   value="<?= htmlspecialchars($editProduct['slug'] ?? '') ?>"
                   placeholder="Оставьте пустым">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Категория *</label>
            <select name="category_id" class="admin-form-select" required>
              <option value="">— Выберите —</option>
              <?php foreach ($allCats as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($editProduct['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                <?= $cat['parent_id'] ? '↳ ' : '' ?><?= htmlspecialchars($cat['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Остаток *</label>
            <input type="number" class="admin-form-input" name="stock_quantity" min="0"
                   value="<?= $editProduct['stock_quantity'] ?? 0 ?>">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Цена (₽) *</label>
            <input type="number" class="admin-form-input" name="price" step="0.01" min="0" required
                   value="<?= $editProduct['price'] ?? '' ?>">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Старая цена (₽)</label>
            <input type="number" class="admin-form-input" name="old_price" step="0.01" min="0"
                   value="<?= $editProduct['old_price'] ?? '' ?>">
          </div>
        </div>

        <div class="admin-form-group">
          <label class="admin-form-label">Описание</label>
          <textarea class="admin-form-textarea" name="description" rows="4"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
        </div>

        <?php if (!$editProduct): ?>
        <div class="admin-form-group">
          <label class="admin-form-label">URL главного изображения</label>
          <input type="text" class="admin-form-input" name="image_path" placeholder="assets/uploads/products/product.jpg">
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;">
          <label class="admin-form-checkbox">
            <input type="checkbox" name="is_new" <?= ($editProduct['is_new'] ?? 0) ? 'checked' : '' ?>>
            Новинка
          </label>
          <label class="admin-form-checkbox">
            <input type="checkbox" name="is_popular" <?= ($editProduct['is_popular'] ?? 0) ? 'checked' : '' ?>>
            Популярный товар
          </label>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <button type="submit" class="btn btn-primary">
            <?= $editProduct ? 'Сохранить изменения' : 'Добавить товар' ?>
          </button>
          <?php if ($editProduct): ?>
          <a href="products.php" class="btn btn-secondary">Отмена</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Список товаров -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title">Все товары</h2>
      </div>
      
      <div class="admin-table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Название</th>
              <th>Категория</th>
              <th>Цена</th>
              <th>Склад</th>
              <th>Флаги</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td class="admin-text-small"><?= htmlspecialchars($p['cat_name']) ?></td>
              <td><?= number_format($p['price'], 0, ',', ' ') ?> ₽</td>
              <td><?= $p['stock_quantity'] ?></td>
              <td>
                <?= $p['is_new'] ? '<span class="badge badge-new">NEW</span>' : '' ?>
                <?= $p['is_popular'] ? '<span class="badge badge-popular">HIT</span>' : '' ?>
              </td>
              <td>
                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                  <a href="products.php?edit=<?= $p['id'] ?>" class="admin-btn-action admin-btn-edit">Изменить</a>
                  <a href="../product.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="admin-btn-action admin-btn-edit">Просмотр</a>
                  <a href="products.php?delete=<?= $p['id'] ?>&csrf_token=<?= csrfToken() ?>"
                     class="admin-btn-action admin-btn-delete"
                     onclick="return confirm('Удалить товар?')">Удалить</a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php include 'admin_footer.php'; ?>