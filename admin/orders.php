<?php
require_once '../config.php';
requireAdmin();

$db = getDB();

$statusOptions = ['новый', 'оплачен', 'отправлен', 'доставлен', 'отменён'];
$statusLabels = [
    'новый'     => 'status-new',
    'оплачен'   => 'status-paid',
    'отправлен' => 'status-sent',
    'доставлен' => 'status-done',
    'отменён'   => 'status-cancel',
];

$msg = '';

// Смена статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verifyCsrf();
    $orderId = (int)$_POST['order_id'];
    $status  = $_POST['status'];
    if (in_array($status, $statusOptions)) {
        $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $orderId]);
        $msg = 'Статус заказа обновлён';
    }
}

// Просмотр заказа
$viewOrder = null;
$orderItems = [];
if (isset($_GET['view'])) {
    $st = $db->prepare("SELECT o.*, u.full_name, u.email, u.phone FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?");
    $st->execute([$_GET['view']]);
    $viewOrder = $st->fetch();
    if ($viewOrder) {
        $st = $db->prepare("SELECT oi.*, p.slug FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
        $st->execute([$viewOrder['id']]);
        $orderItems = $st->fetchAll();
    }
}

// Список заказов
$filterStatus = $_GET['status'] ?? '';
$params = [];
$where = '';
if ($filterStatus && in_array($filterStatus, $statusOptions)) {
    $where = 'WHERE o.status = ?';
    $params = [$filterStatus];
}

$orders = $db->prepare("
    SELECT o.*, u.full_name, u.email
    FROM orders o JOIN users u ON u.id = o.user_id
    $where
    ORDER BY o.order_date DESC
");
$orders->execute($params);
$orders = $orders->fetchAll();

$pageTitle = 'Заказы — Admin';
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
      <a href="orders.php" class="admin-nav-item active">📦 Заказы</a>
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

  <main class="admin-main">
    <?php if ($viewOrder): ?>
    <!-- Детальный вид заказа -->
    <div class="back-link">
      <a href="orders.php" class="back-link-inner">← Назад к заказам</a>
    </div>
    
    <div class="admin-page-title">Заказ #<?= $viewOrder['id'] ?></div>

    <?php if ($msg): ?>
    <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="order-detail-grid">
      <!-- Левая колонка с информацией -->
      <div class="order-detail-left">
        <!-- Информация о покупателе -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title">Покупатель</h3>
          </div>
          <div class="admin-card-body">
            <div class="info-row">
              <span class="info-label">Имя:</span>
              <span class="info-value"><?= htmlspecialchars($viewOrder['full_name']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Email:</span>
              <span class="info-value"><?= htmlspecialchars($viewOrder['email']) ?></span>
            </div>
            <?php if ($viewOrder['phone']): ?>
            <div class="info-row">
              <span class="info-label">Телефон:</span>
              <span class="info-value"><?= htmlspecialchars($viewOrder['phone']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
              <span class="info-label">Адрес:</span>
              <span class="info-value"><?= htmlspecialchars($viewOrder['delivery_address']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Доставка:</span>
              <span class="info-value"><?= htmlspecialchars($viewOrder['delivery_method']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Оплата:</span>
              <span class="info-value"><?= htmlspecialchars($viewOrder['payment_method']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Дата:</span>
              <span class="info-value"><?= date('d.m.Y H:i', strtotime($viewOrder['order_date'])) ?></span>
            </div>
          </div>
        </div>

        <!-- Товары в заказе -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title">Состав заказа</h3>
          </div>
          
          <div class="admin-table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Товар</th>
                  <th>Цена</th>
                  <th>Кол-во</th>
                  <th>Итого</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orderItems as $oi): ?>
                <tr>
                  <td><?= htmlspecialchars($oi['product_name']) ?></td>
                  <td><?= number_format($oi['price_at_purchase'], 0, ',', ' ') ?> ₽</td>
                  <td><?= $oi['quantity'] ?></td>
                  <td><strong><?= number_format($oi['price_at_purchase'] * $oi['quantity'], 0, ',', ' ') ?> ₽</strong></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <div class="order-total">
            Итого: <?= number_format($viewOrder['total_amount'], 0, ',', ' ') ?> ₽
          </div>
        </div>
      </div>

      <!-- Правая колонка - управление статусом -->
      <div class="order-detail-right">
        <div class="admin-card status-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title">Статус заказа</h3>
          </div>
          <div class="admin-card-body">
            <div class="current-status">
              <span class="order-status <?= $statusLabels[$viewOrder['status']] ?? 'status-new' ?>">
                <?= $viewOrder['status'] ?>
              </span>
            </div>
            
            <form method="POST" class="status-form">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="update_status" value="1">
              <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
              
              <div class="admin-form-group">
                <label class="admin-form-label">Изменить статус</label>
                <select name="status" class="admin-form-select">
                  <?php foreach ($statusOptions as $s): ?>
                  <option value="<?= $s ?>" <?= $s === $viewOrder['status'] ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <button type="submit" class="btn btn-primary btn-full">Обновить статус</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php else: ?>
    <!-- Список заказов -->
    <div class="admin-page-title">Управление заказами</div>

    <?php if ($msg): ?>
    <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Фильтр по статусу -->
    <div class="status-filters">
      <a href="orders.php" class="filter-btn <?= !$filterStatus ? 'active' : '' ?>">Все</a>
      <?php foreach ($statusOptions as $s): ?>
      <a href="orders.php?status=<?= urlencode($s) ?>" class="filter-btn <?= $filterStatus === $s ? 'active' : '' ?>">
        <?= $s ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Таблица заказов -->
    <div class="admin-card">
      <div class="admin-table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>№</th>
              <th>Покупатель</th>
              <th>Дата</th>
              <th>Доставка</th>
              <th>Сумма</th>
              <th>Статус</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
              <td><strong>#<?= $order['id'] ?></strong></td>
              <td>
                <div><?= htmlspecialchars($order['full_name']) ?></div>
                <div class="admin-text-small"><?= htmlspecialchars($order['email']) ?></div>
              </td>
              <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
              <td><?= htmlspecialchars($order['delivery_method']) ?></td>
              <td><strong><?= number_format($order['total_amount'], 0, ',', ' ') ?> ₽</strong></td>
              <td>
                <span class="order-status <?= $statusLabels[$order['status']] ?? 'status-new' ?>">
                  <?= $order['status'] ?>
                </span>
              </td>
              <td>
                <a href="orders.php?view=<?= $order['id'] ?>" class="admin-btn-action admin-btn-edit">
                  Открыть
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (!$orders): ?>
            <tr>
              <td colspan="7" class="empty-table">
                <div class="empty-state">
                  <span class="empty-icon">📦</span>
                  <p>Заказов не найдено</p>
                </div>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </main>
</div>

<?php include 'admin_footer.php'; ?>