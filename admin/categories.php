<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

// Удаление
if (isset($_GET['delete'])) {
    verifyCsrf();
    $id = (int)$_GET['delete'];
    try {
        $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        $msg = 'Категория удалена';
    } catch (Exception $e) {
        $msg = 'Нельзя удалить: категория используется';
    }
    header('Location: categories.php?msg=' . urlencode($msg));
    exit;
}

// Сохранение
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cat'])) {
    verifyCsrf();
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name']);
    $slug     = trim($_POST['slug']);
    $parentId = $_POST['parent_id'] ? (int)$_POST['parent_id'] : null;
    $image    = trim($_POST['image'] ?? '');
    $sort     = (int)($_POST['sort_order'] ?? 0);

    if (!$name || !$slug) {
        $msg = 'Ошибка: заполните название и slug';
    } else {
        if ($id) {
            $db->prepare("UPDATE categories SET name=?,slug=?,parent_id=?,image=?,sort_order=? WHERE id=?")
               ->execute([$name, $slug, $parentId, $image ?: null, $sort, $id]);
        } else {
            $db->prepare("INSERT INTO categories (name,slug,parent_id,image,sort_order) VALUES (?,?,?,?,?)")
               ->execute([$name, $slug, $parentId, $image ?: null, $sort]);
        }
        $msg = 'Категория сохранена';
    }
    header('Location: categories.php?msg=' . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

// Данные для редактирования
$editCat = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $st->execute([$_GET['edit']]);
    $editCat = $st->fetch();
}

// Категории
$categories = $db->query("
    SELECT c.*, p.name as parent_name
    FROM categories c
    LEFT JOIN categories p ON p.id = c.parent_id
    ORDER BY c.parent_id ASC, c.sort_order ASC
")->fetchAll();

// Родительские категории для select
$parentCats = $db->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY sort_order")->fetchAll();

$pageTitle = 'Категории — Admin';
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
      <a href="products.php" class="admin-nav-item">🛍 Товары</a>
      <a href="categories.php" class="admin-nav-item active">📂 Категории</a>
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
    <div class="admin-page-title">Управление категориями</div>

    <?php if ($msg): ?>
    <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Форма добавления/редактирования -->
    <div class="admin-form-card">
      <h2 class="admin-form-title">
        <?= $editCat ? 'Редактирование: ' . htmlspecialchars($editCat['name']) : 'Добавить категорию' ?>
      </h2>
      
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="save_cat" value="1">
        <?php if ($editCat): ?><input type="hidden" name="id" value="<?= $editCat['id'] ?>"><?php endif; ?>

        <div class="admin-form-grid">
          <div class="admin-form-group">
            <label class="admin-form-label">Название *</label>
            <input type="text" class="admin-form-input" name="name" required
                   value="<?= htmlspecialchars($editCat['name'] ?? '') ?>">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Slug (URL) *</label>
            <input type="text" class="admin-form-input" name="slug" required
                   value="<?= htmlspecialchars($editCat['slug'] ?? '') ?>"
                   placeholder="napr-produkty">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Родительская категория</label>
            <select name="parent_id" class="admin-form-select">
              <option value="">— Нет (корневая) —</option>
              <?php foreach ($parentCats as $pc): ?>
              <option value="<?= $pc['id'] ?>" <?= ($editCat['parent_id'] ?? null) == $pc['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($pc['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Порядок сортировки</label>
            <input type="number" class="admin-form-input" name="sort_order" min="0"
                   value="<?= $editCat['sort_order'] ?? 0 ?>">
          </div>
        </div>

        <div class="admin-form-group">
          <label class="admin-form-label">URL изображения</label>
          <input type="text" class="admin-form-input" name="image"
                 value="<?= htmlspecialchars($editCat['image'] ?? '') ?>"
                 placeholder="assets/images/cat-food.jpg">
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <button type="submit" class="btn btn-primary">
            <?= $editCat ? 'Сохранить' : 'Добавить' ?>
          </button>
          <?php if ($editCat): ?>
          <a href="categories.php" class="btn btn-secondary">Отмена</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Список категорий -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title">Все категории</h2>
      </div>
      
      <div class="admin-table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Название</th>
              <th>Slug</th>
              <th>Родитель</th>
              <th>Порядок</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
              <td><?= $cat['id'] ?></td>
              <td>
                <?php if ($cat['parent_id']): ?>
                <span style="color:var(--charcoal-light); margin-right:4px;">↳</span>
                <?php endif; ?>
                <?= htmlspecialchars($cat['name']) ?>
              </td>
              <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
              <td><?= htmlspecialchars($cat['parent_name'] ?? '—') ?></td>
              <td><?= $cat['sort_order'] ?></td>
              <td>
                <div class="admin-actions">
                  <a href="categories.php?edit=<?= $cat['id'] ?>" class="admin-btn-action admin-btn-edit">Изменить</a>
                  <a href="categories.php?delete=<?= $cat['id'] ?>&csrf_token=<?= csrfToken() ?>"
                     class="admin-btn-action admin-btn-delete"
                     onclick="return confirm('Удалить категорию?')">Удалить</a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (!$categories): ?>
            <tr>
              <td colspan="6" class="empty-table">
                <div class="empty-state">
                  <span class="empty-icon">📂</span>
                  <p>Категорий не найдено</p>
                </div>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php include 'admin_footer.php'; ?>