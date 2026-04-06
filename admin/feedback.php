<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

// Отметить как обработанное
if (isset($_GET['process'])) {
    verifyCsrf();
    $id = (int)$_GET['process'];
    $db->prepare("UPDATE feedback_messages SET is_processed = 1 - is_processed WHERE id = ?")->execute([$id]);
    header('Location: feedback.php?msg=' . urlencode('Статус обновлён'));
    exit;
}

// Удалить
if (isset($_GET['delete'])) {
    verifyCsrf();
    $db->prepare("DELETE FROM feedback_messages WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: feedback.php?msg=' . urlencode('Сообщение удалено'));
    exit;
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

$filter = $_GET['filter'] ?? 'all';
switch ($filter) {
    case 'new':
        $where = 'WHERE is_processed = 0';
        break;
    case 'processed':
        $where = 'WHERE is_processed = 1';
        break;
    default:
        $where = '';
        break;
}

$messages = $db->query("SELECT * FROM feedback_messages $where ORDER BY created_at DESC")->fetchAll();
$newCount = $db->query("SELECT COUNT(*) FROM feedback_messages WHERE is_processed = 0")->fetchColumn();

$pageTitle = 'Обратная связь — Admin';
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
      <a href="users.php" class="admin-nav-item">👥 Пользователи</a>
      <a href="feedback.php" class="admin-nav-item active">✉️ Обратная связь</a>
      <div class="admin-nav-divider"></div>
      <a href="<?= BASE_URL ?>index.php" class="admin-nav-item">🌐 На сайт</a>
      <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item">🚪 Выйти</a>
    </nav>
  </aside>

  <!-- Основной контент -->
  <main class="admin-main">
    <div class="admin-page-title">
      <span>Обратная связь</span>
      <?php if ($newCount > 0): ?>
      <span class="new-count-badge"><?= $newCount ?> новых</span>
      <?php endif; ?>
    </div>

    <?php if ($msg): ?>
    <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Фильтры -->
    <div class="filter-section">
      <a href="feedback.php" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">Все</a>
      <a href="feedback.php?filter=new" class="filter-btn <?= $filter === 'new' ? 'active' : '' ?>">Новые</a>
      <a href="feedback.php?filter=processed" class="filter-btn <?= $filter === 'processed' ? 'active' : '' ?>">Обработанные</a>
    </div>

    <!-- Список сообщений -->
    <?php if ($messages): ?>
    <div class="feedback-list">
      <?php foreach ($messages as $m): ?>
      <div class="feedback-card <?= $m['is_processed'] ? 'processed' : 'new' ?>">
        <div class="feedback-header">
          <div class="feedback-user-info">
            <div class="feedback-name"><?= htmlspecialchars($m['name']) ?></div>
            <div class="feedback-meta">
              <?php if ($m['email']): ?>
              <span class="feedback-email">📧 <?= htmlspecialchars($m['email']) ?></span>
              <?php endif; ?>
              <?php if ($m['phone']): ?>
              <span class="feedback-phone">📞 <?= htmlspecialchars($m['phone']) ?></span>
              <?php endif; ?>
              <span class="feedback-date">📅 <?= date('d.m.Y H:i', strtotime($m['created_at'])) ?></span>
            </div>
          </div>
          
          <div class="feedback-actions">
            <span class="status-badge <?= $m['is_processed'] ? 'status-processed' : 'status-new' ?>">
              <?= $m['is_processed'] ? 'Обработано' : 'Новое' ?>
            </span>
            
            <a href="feedback.php?process=<?= $m['id'] ?>&csrf_token=<?= csrfToken() ?>" 
               class="admin-btn-action <?= $m['is_processed'] ? 'admin-btn-warning' : 'admin-btn-edit' ?>">
              <?= $m['is_processed'] ? 'В новые' : 'В обработанные' ?>
            </a>
            
            <a href="feedback.php?delete=<?= $m['id'] ?>&csrf_token=<?= csrfToken() ?>"
               class="admin-btn-action admin-btn-delete"
               onclick="return confirm('Удалить сообщение?')">Удалить</a>
          </div>
        </div>
        
        <div class="feedback-message">
          <?= nl2br(htmlspecialchars($m['message'])) ?>
        </div>
        
        <?php if ($m['email']): ?>
        <div class="feedback-reply">
          <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="btn btn-primary btn-sm">
            ✉️ Ответить на email
          </a>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon">✉️</div>
      <p>Сообщений не найдено</p>
    </div>
    <?php endif; ?>
  </main>
</div>

<?php include 'admin_footer.php'; ?>