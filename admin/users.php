<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

// Изменение роли
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    verifyCsrf();
    $userId = (int)$_POST['user_id'];
    $role   = $_POST['role'];
    if (in_array($role, ['client', 'admin']) && $userId !== currentUser()['id']) {
        $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $userId]);
        $msg = 'Роль пользователя изменена';
    } else {
        $msg = 'Нельзя изменить роль самому себе';
    }
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

$search = trim($_GET['search'] ?? '');
$params = [];
$where = '';
if ($search) {
    $where = 'WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}

$users = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count FROM users u $where ORDER BY u.created_at DESC");
$users->execute($params);
$users = $users->fetchAll();

$pageTitle = 'Пользователи — Admin';
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
      <a href="banners.php" class="admin-nav-item">🖼 Баннеры</a>
      <a href="users.php" class="admin-nav-item active">👥 Пользователи</a>
      <a href="feedback.php" class="admin-nav-item">✉️ Обратная связь</a>
      <div class="admin-nav-divider"></div>
      <a href="<?= BASE_URL ?>index.php" class="admin-nav-item">🌐 На сайт</a>
      <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item">🚪 Выйти</a>
    </nav>
  </aside>

  <!-- Основной контент -->
  <main class="admin-main">
    <div class="admin-page-title">Управление пользователями</div>

    <?php if ($msg): ?>
    <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Поиск -->
    <div class="search-section">
      <form method="GET" class="search-form">
        <input type="text" name="search" class="search-input"
               value="<?= htmlspecialchars($search) ?>" 
               placeholder="Поиск по имени, email, телефону...">
        <button type="submit" class="btn btn-primary">Найти</button>
        <?php if ($search): ?>
        <a href="users.php" class="btn btn-secondary">Сбросить</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Таблица пользователей -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title">Все пользователи</h2>
      </div>
      
      <div class="admin-table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Пользователь</th>
              <th>Телефон</th>
              <th>Заказов</th>
              <th>Роль</th>
              <th>Дата регистрации</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= $u['id'] ?></td>
              <td>
                <div class="user-name"><?= htmlspecialchars($u['full_name']) ?></div>
                <div class="admin-text-small"><?= htmlspecialchars($u['email']) ?></div>
              </td>
              <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
              <td>
                <a href="orders.php?user=<?= $u['id'] ?>" class="order-count-link">
                  <?= $u['order_count'] ?>
                </a>
              </td>
              <td>
                <span class="role-badge <?= $u['role'] === 'admin' ? 'role-admin' : 'role-client' ?>">
                  <?= $u['role'] === 'admin' ? 'Админ' : 'Клиент' ?>
                </span>
              </td>
              <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
              <td>
                <?php if ($u['id'] !== currentUser()['id']): ?>
                <form method="POST" class="role-form">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="update_role" value="1">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <select name="role" class="role-select">
                    <option value="client" <?= $u['role'] === 'client' ? 'selected' : '' ?>>Клиент</option>
                    <option value="admin"  <?= $u['role'] === 'admin'  ? 'selected' : '' ?>>Админ</option>
                  </select>
                  <button type="submit" class="admin-btn-action admin-btn-edit">Сохранить</button>
                </form>
                <?php else: ?>
                <span class="self-badge">Это вы</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (!$users): ?>
            <tr>
              <td colspan="7" class="empty-table">
                <div class="empty-state">
                  <span class="empty-icon">👥</span>
                  <p>Пользователей не найдено</p>
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