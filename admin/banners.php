<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

// Удаление
if (isset($_GET['delete'])) {
    verifyCsrf();
    $db->prepare("DELETE FROM banners WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: banners.php?msg=' . urlencode('Баннер удалён'));
    exit;
}

// Переключить активность
if (isset($_GET['toggle'])) {
    verifyCsrf();
    $id = (int)$_GET['toggle'];
    $db->prepare("UPDATE banners SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    header('Location: banners.php?msg=' . urlencode('Статус баннера изменён'));
    exit;
}

// Сохранение
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner'])) {
    verifyCsrf();
    $id       = (int)($_POST['id'] ?? 0);
    $title    = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $imgPath  = trim($_POST['image_path']);
    $link     = trim($_POST['link']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sort     = (int)($_POST['sort_order'] ?? 0);

    if (!$title || !$imgPath) {
        $msg = 'Ошибка: заполните заголовок и путь к изображению';
    } elseif ($id) {
        $db->prepare("UPDATE banners SET title=?,subtitle=?,image_path=?,link=?,is_active=?,sort_order=? WHERE id=?")
           ->execute([$title, $subtitle, $imgPath, $link, $isActive, $sort, $id]);
        $msg = 'Баннер обновлён';
    } else {
        $db->prepare("INSERT INTO banners (title,subtitle,image_path,link,is_active,sort_order) VALUES (?,?,?,?,?,?)")
           ->execute([$title, $subtitle, $imgPath, $link, $isActive, $sort]);
        $msg = 'Баннер добавлен';
    }
    header('Location: banners.php?msg=' . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

$editBanner = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM banners WHERE id = ?");
    $st->execute([$_GET['edit']]);
    $editBanner = $st->fetch();
}

$banners = $db->query("SELECT * FROM banners ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'Баннеры — Admin';
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
      <a href="categories.php" class="admin-nav-item">📂 Категории</a>
      <a href="banners.php" class="admin-nav-item active">🖼 Баннеры</a>
      <a href="users.php" class="admin-nav-item">👥 Пользователи</a>
      <a href="feedback.php" class="admin-nav-item">✉️ Обратная связь</a>
      <div class="admin-nav-divider"></div>
      <a href="<?= BASE_URL ?>index.php" class="admin-nav-item">🌐 На сайт</a>
      <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item">🚪 Выйти</a>
    </nav>
  </aside>

  <!-- Основной контент -->
  <main class="admin-main">
    <div class="admin-page-title">Управление баннерами</div>

    <?php if ($msg): ?>
    <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Форма добавления/редактирования -->
    <div class="admin-form-card">
      <h2 class="admin-form-title">
        <?= $editBanner ? 'Редактирование баннера' : 'Добавить баннер' ?>
      </h2>
      
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="save_banner" value="1">
        <?php if ($editBanner): ?><input type="hidden" name="id" value="<?= $editBanner['id'] ?>"><?php endif; ?>

        <div class="admin-form-grid">
          <div class="admin-form-group">
            <label class="admin-form-label">Заголовок *</label>
            <input type="text" class="admin-form-input" name="title" required
                   value="<?= htmlspecialchars($editBanner['title'] ?? '') ?>">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Подзаголовок</label>
            <input type="text" class="admin-form-input" name="subtitle"
                   value="<?= htmlspecialchars($editBanner['subtitle'] ?? '') ?>">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Путь к изображению *</label>
            <input type="text" class="admin-form-input" name="image_path" required
                   value="<?= htmlspecialchars($editBanner['image_path'] ?? '') ?>"
                   placeholder="assets/uploads/banners/banner.jpg">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Ссылка</label>
            <input type="text" class="admin-form-input" name="link"
                   value="<?= htmlspecialchars($editBanner['link'] ?? '') ?>"
                   placeholder="catalog.php?cat=kosmetika">
          </div>
          
          <div class="admin-form-group">
            <label class="admin-form-label">Порядок сортировки</label>
            <input type="number" class="admin-form-input" name="sort_order" min="0"
                   value="<?= $editBanner['sort_order'] ?? 0 ?>">
          </div>
          
          <div class="admin-form-group" style="display:flex; align-items:center; padding-top:24px;">
            <label class="admin-form-checkbox">
              <input type="checkbox" name="is_active" <?= ($editBanner['is_active'] ?? 1) ? 'checked' : '' ?>>
              Активен (показывать на сайте)
            </label>
          </div>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <button type="submit" class="btn btn-primary">
            <?= $editBanner ? 'Сохранить' : 'Добавить' ?>
          </button>
          <?php if ($editBanner): ?>
          <a href="banners.php" class="btn btn-secondary">Отмена</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Список баннеров -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title">Все баннеры</h2>
      </div>
      
      <div class="admin-table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Заголовок</th>
              <th>Изображение</th>
              <th>Ссылка</th>
              <th>Порядок</th>
              <th>Активен</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($banners as $b): ?>
            <tr>
              <td><?= $b['id'] ?></td>
              <td>
                <div><?= htmlspecialchars($b['title']) ?></div>
                <?php if ($b['subtitle']): ?>
                <div class="admin-text-small"><?= htmlspecialchars($b['subtitle']) ?></div>
                <?php endif; ?>
              </td>
              <td class="admin-text-small" style="max-width:200px;">
                <?= htmlspecialchars($b['image_path']) ?>
              </td>
              <td class="admin-text-small"><?= htmlspecialchars($b['link'] ?? '—') ?></td>
              <td><?= $b['sort_order'] ?></td>
              <td>
                <span class="status-badge <?= $b['is_active'] ? 'status-active' : 'status-inactive' ?>">
                  <?= $b['is_active'] ? 'Активен' : 'Неактивен' ?>
                </span>
              </td>
              <td>
                <div class="admin-actions">
                  <a href="banners.php?edit=<?= $b['id'] ?>" class="admin-btn-action admin-btn-edit">Изменить</a>
                  <a href="banners.php?toggle=<?= $b['id'] ?>&csrf_token=<?= csrfToken() ?>" 
                     class="admin-btn-action <?= $b['is_active'] ? 'admin-btn-warning' : 'admin-btn-edit' ?>">
                    <?= $b['is_active'] ? 'Скрыть' : 'Показать' ?>
                  </a>
                  <a href="banners.php?delete=<?= $b['id'] ?>&csrf_token=<?= csrfToken() ?>"
                     class="admin-btn-action admin-btn-delete"
                     onclick="return confirm('Удалить баннер?')">Удалить</a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (!$banners): ?>
            <tr>
              <td colspan="7" class="empty-table">
                <div class="empty-state">
                  <span class="empty-icon">🖼</span>
                  <p>Баннеров не найдено</p>
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