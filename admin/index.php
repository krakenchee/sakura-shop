<?php
require_once '../config.php';
requireAdmin();

$db = getDB();

$stats = [
    'users'    => $db->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn(),
    'orders'   => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'revenue'  => $db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'отменён'")->fetchColumn(),
];

$recentOrders = $db->query("
    SELECT o.*, u.full_name, u.email
    FROM orders o JOIN users u ON u.id = o.user_id
    ORDER BY o.order_date DESC LIMIT 8
")->fetchAll();

$statusLabels = [
    'новый'     => 'status-new',
    'оплачен'   => 'status-paid',
    'отправлен' => 'status-sent',
    'доставлен' => 'status-done',
    'отменён'   => 'status-cancel',
];

$pageTitle = 'Панель администратора — Sakura Shop';
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
  
  <!-- Оверлей для затемнения -->
  <div class="admin-sidebar-overlay"></div>
  
  <!-- Сайдбар -->
  <aside class="admin-sidebar">
    <div class="admin-logo">⛩ Sakura Admin</div>
    <nav class="admin-nav">
      <a href="index.php" class="admin-nav-item active">📊 Дашборд</a>
      <a href="orders.php" class="admin-nav-item">📦 Заказы</a>
      <a href="products.php" class="admin-nav-item">🛍 Товары</a>
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
    <div class="admin-page-title">Дашборд</div>

    <!-- Карточки статистики -->
    <div class="admin-stats-grid">
      <div class="stat-card">
        <div class="stat-value"><?= number_format($stats['orders']) ?></div>
        <div class="stat-label">Всего заказов</div>
      </div>
      <div class="stat-card stat-card-emerald">
        <div class="stat-value"><?= number_format($stats['revenue'], 0, ',', ' ') ?> ₽</div>
        <div class="stat-label">Выручка</div>
      </div>
      <div class="stat-card stat-card-gold">
        <div class="stat-value"><?= number_format($stats['products']) ?></div>
        <div class="stat-label">Товаров</div>
      </div>
      <div class="stat-card stat-card-bordeaux">
        <div class="stat-value"><?= number_format($stats['users']) ?></div>
        <div class="stat-label">Покупателей</div>
      </div>
    </div>

    <!-- Последние заказы -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title">Последние заказы</h2>
        <a href="orders.php" class="btn btn-secondary btn-sm">Все заказы</a>
      </div>
      
      <div class="admin-table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>№</th>
              <th>Покупатель</th>
              <th>Дата</th>
              <th>Сумма</th>
              <th>Статус</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $order): ?>
            <tr>
              <td><strong>#<?= $order['id'] ?></strong></td>
              <td>
                <div><?= htmlspecialchars($order['full_name']) ?></div>
                <div class="admin-text-small"><?= htmlspecialchars($order['email']) ?></div>
              </td>
              <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
              <td><strong><?= number_format($order['total_amount'], 0, ',', ' ') ?> ₽</strong></td>
              <td><span class="order-status <?= $statusLabels[$order['status']] ?? 'status-new' ?>"><?= $order['status'] ?></span></td>
              <td>
                <a href="orders.php?view=<?= $order['id'] ?>" class="admin-btn-action admin-btn-edit">Открыть</a>
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