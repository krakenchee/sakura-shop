<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$user = currentUser();
$tab = $_GET['tab'] ?? 'orders';

$success = '';
$error = '';

// Обновление профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verifyCsrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $newPass  = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$fullName) {
        $error = 'Имя не может быть пустым';
    } elseif ($newPass && strlen($newPass) < 6) {
        $error = 'Новый пароль должен содержать минимум 6 символов';
    } elseif ($newPass && $newPass !== $confirm) {
        $error = 'Пароли не совпадают';
    } else {
        if ($newPass) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET full_name=?, phone=?, password_hash=? WHERE id=?")->execute([$fullName, $phone, $hash, $user['id']]);
        } else {
            $db->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")->execute([$fullName, $phone, $user['id']]);
        }
        $_SESSION['user']['full_name'] = $fullName;
        $success = 'Профиль успешно обновлён';
        $tab = 'profile';
    }
}

// Получить данные пользователя из БД
$userData = $db->prepare("SELECT * FROM users WHERE id = ?")->execute([$user['id']]) ? $db->prepare("SELECT * FROM users WHERE id = ?") : null;
$st = $db->prepare("SELECT * FROM users WHERE id = ?");
$st->execute([$user['id']]);
$userData = $st->fetch();

// Заказы
$orders = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();

// Отдельный заказ
$viewOrder = null;
$orderItems = [];
if (isset($_GET['order'])) {
    $st = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $st->execute([$_GET['order'], $user['id']]);
    $viewOrder = $st->fetch();
    if ($viewOrder) {
        $st = $db->prepare("SELECT oi.*, p.slug FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
        $st->execute([$viewOrder['id']]);
        $orderItems = $st->fetchAll();
    }
}

$statusLabels = [
    'новый'     => ['label' => 'Новый',      'class' => 'status-new'],
    'оплачен'   => ['label' => 'Оплачен',    'class' => 'status-paid'],
    'отправлен' => ['label' => 'Отправлен',  'class' => 'status-sent'],
    'доставлен' => ['label' => 'Доставлен',  'class' => 'status-done'],
    'отменён'   => ['label' => 'Отменён',    'class' => 'status-cancel'],
];

$pageTitle = 'Личный кабинет — Sakura Shop';
include 'header.php';
?>

<div class="container" style="padding-top:24px;">
  <div class="breadcrumbs">
    <a href="index.php">Главная</a> <span>›</span>
    <span>Личный кабинет</span>
  </div>
</div>

<?php if (isset($_GET['order_success'])): ?>
<div class="container">
  <div style="background:rgba(27,94,66,0.1);border:1px solid var(--emerald);border-radius:6px;padding:20px 24px;display:flex;gap:16px;align-items:center;margin-bottom:24px;">
    <div style="font-size:2rem;">✅</div>
    <div>
      <div style="font-family:var(--font-serif);color:var(--emerald);font-size:1rem;font-weight:600;">Заказ №<?= (int)$_GET['order_success'] ?> успешно оформлен!</div>
      <div style="font-size:0.875rem;color:var(--charcoal-light);margin-top:4px;">Мы свяжемся с вами для подтверждения. Спасибо за покупку!</div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="container">
  <div class="account-layout">

    <!-- Навигация -->
    <aside>
      <div style="background:var(--white);border-radius:8px;border:1px solid rgba(139,0,0,0.08);overflow:hidden;margin-bottom:16px;">
        <div style="background:var(--crimson-deep);padding:20px 16px;text-align:center;">
          <div style="width:56px;height:56px;background:var(--crimson);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:0 auto 12px;border:2px solid var(--gold);">
            👤
          </div>
          <div style="font-family:var(--font-serif);color:var(--ivory);font-size:0.95rem;"><?= htmlspecialchars($user['full_name']) ?></div>
          <div style="font-size:0.75rem;color:rgba(250,245,236,0.6);margin-top:4px;"><?= htmlspecialchars($user['email']) ?></div>
        </div>
        <div style="padding:8px;">
          <a href="account.php?tab=orders" class="account-nav-item <?= $tab === 'orders' ? 'active' : '' ?>">
            📦 История заказов
          </a>
          <a href="account.php?tab=profile" class="account-nav-item <?= $tab === 'profile' ? 'active' : '' ?>">
            ✏️ Профиль
          </a>
        </div>
      </div>
      <a href="logout.php" class="btn btn-secondary btn-full btn-sm">Выйти из аккаунта</a>
    </aside>

    <!-- Контент -->
    <main>
      <?php if ($error): ?>
      <div style="background:rgba(139,0,0,0.08);border:1px solid rgba(139,0,0,0.2);border-radius:4px;padding:12px 16px;color:var(--crimson);margin-bottom:20px;">
        ⚠ <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div style="background:rgba(27,94,66,0.1);border:1px solid var(--emerald);border-radius:4px;padding:12px 16px;color:var(--emerald);margin-bottom:20px;">
        ✓ <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <?php if ($tab === 'orders'): ?>
      <!-- Заказы -->
      <h2 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:1.4rem;margin-bottom:24px;">
        История заказов
      </h2>

      <?php if ($viewOrder): ?>
      <!-- Детали заказа -->
      <div style="margin-bottom:20px;">
        <a href="account.php?tab=orders" style="color:var(--crimson);font-size:0.875rem;">← Назад к заказам</a>
      </div>
      <div class="order-card">
        <div class="order-header">
          <div>
            <div class="order-number">Заказ №<?= $viewOrder['id'] ?></div>
            <div style="font-size:0.8rem;color:var(--charcoal-light);"><?= date('d.m.Y H:i', strtotime($viewOrder['order_date'])) ?></div>
          </div>
          <span class="order-status <?= $statusLabels[$viewOrder['status']]['class'] ?? 'status-new' ?>">
            <?= $statusLabels[$viewOrder['status']]['label'] ?? $viewOrder['status'] ?>
          </span>
        </div>
        <div style="font-size:0.85rem;color:var(--charcoal-light);margin-bottom:16px;">
          <span>📍 <?= htmlspecialchars($viewOrder['delivery_address']) ?></span> ·
          <span>🚚 <?= htmlspecialchars($viewOrder['delivery_method']) ?></span> ·
          <span>💳 <?= htmlspecialchars($viewOrder['payment_method']) ?></span>
        </div>
        <?php foreach ($orderItems as $oi): ?>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(139,0,0,0.07);font-size:0.875rem;">
          <div>
            <?php if ($oi['slug']): ?>
            <a href="product.php?slug=<?= urlencode($oi['slug']) ?>" style="color:var(--crimson);"><?= htmlspecialchars($oi['product_name']) ?></a>
            <?php else: ?><?= htmlspecialchars($oi['product_name']) ?><?php endif; ?>
            <span style="color:var(--charcoal-light);"> × <?= $oi['quantity'] ?></span>
          </div>
          <div style="font-weight:600;"><?= number_format($oi['price_at_purchase'] * $oi['quantity'], 0, ',', ' ') ?> ₽</div>
        </div>
        <?php endforeach; ?>
        <div style="text-align:right;margin-top:16px;font-family:var(--font-serif);font-size:1.1rem;color:var(--crimson);font-weight:700;">
          Итого: <?= number_format($viewOrder['total_amount'], 0, ',', ' ') ?> ₽
        </div>
      </div>

      <?php elseif ($orders): ?>
      <?php foreach ($orders as $order): ?>
      <div class="order-card">
        <div class="order-header">
          <div>
            <div class="order-number">Заказ №<?= $order['id'] ?></div>
            <div style="font-size:0.8rem;color:var(--charcoal-light);"><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></div>
          </div>
          <span class="order-status <?= $statusLabels[$order['status']]['class'] ?? 'status-new' ?>">
            <?= $statusLabels[$order['status']]['label'] ?? $order['status'] ?>
          </span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.875rem;">
          <div style="color:var(--charcoal-light);">
            🚚 <?= htmlspecialchars($order['delivery_method']) ?>
          </div>
          <div style="font-family:var(--font-serif);font-size:1rem;color:var(--crimson);font-weight:700;">
            <?= number_format($order['total_amount'], 0, ',', ' ') ?> ₽
          </div>
        </div>
        <div style="margin-top:12px;">
          <a href="account.php?tab=orders&order=<?= $order['id'] ?>" class="btn btn-secondary btn-sm">Подробнее</a>
        </div>
      </div>
      <?php endforeach; ?>

      <?php else: ?>
      <div style="text-align:center;padding:60px 0;color:var(--charcoal-light);">
        <div style="font-size:3rem;margin-bottom:16px;">📦</div>
        <h3 style="font-family:var(--font-serif);color:var(--crimson-deep);margin-bottom:8px;">Заказов пока нет</h3>
        <p style="margin-bottom:20px;">Начните покупки в нашем каталоге</p>
        <a href="catalog.php" class="btn btn-primary btn-sm">Перейти в каталог</a>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- Профиль -->
      <h2 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:1.4rem;margin-bottom:24px;">
        Редактирование профиля
      </h2>
      <div style="background:var(--white);border-radius:8px;padding:32px;border:1px solid rgba(139,0,0,0.08);">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="update_profile" value="1">

          <div class="form-group">
            <label class="form-label">Полное имя *</label>
            <input type="text" class="form-input" name="full_name"
                   value="<?= htmlspecialchars($userData['full_name']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email (не изменяется)</label>
            <input type="email" class="form-input" value="<?= htmlspecialchars($userData['email']) ?>" disabled>
          </div>
          <div class="form-group">
            <label class="form-label">Телефон</label>
            <input type="tel" class="form-input" name="phone"
                   value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
          </div>

          <hr style="border-color:rgba(139,0,0,0.1);margin:24px 0;">
          <h3 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:0.95rem;margin-bottom:16px;">Сменить пароль</h3>
          <div class="form-group">
            <label class="form-label">Новый пароль</label>
            <input type="password" class="form-input" name="new_password" placeholder="Оставьте пустым, чтобы не менять">
          </div>
          <div class="form-group">
            <label class="form-label">Подтвердите новый пароль</label>
            <input type="password" class="form-input" name="confirm_password" placeholder="••••••••">
          </div>

          <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<?php include 'footer.php'; ?>
