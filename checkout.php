<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$user = currentUser();

// Получить корзину
$items = $db->prepare("
    SELECT ci.id as cart_id, ci.quantity, p.*, pi.image_path
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
    WHERE ci.user_id = ?
");
$items->execute([$user['id']]);
$cartItems = $items->fetchAll();

if (!$cartItems) {
    header('Location: cart.php');
    exit;
}

$subtotal = 0;
foreach ($cartItems as $i) {
    $subtotal += $i['price'] * $i['quantity'];
}

$deliveryOptions = [
    'cdek'      => ['label' => 'СДЭК (до двери)',          'price' => 399,  'days' => '3–5'],
    'pochta'    => ['label' => 'Почта России',              'price' => 250,  'days' => '7–14'],
    'boxberry'  => ['label' => 'Boxberry (пункт выдачи)',   'price' => 299,  'days' => '4–6'],
    'free'      => ['label' => 'Бесплатная (от 3 000 ₽)',  'price' => 0,    'days' => '5–7'],
];

$paymentOptions = [
    'card_online'   => 'Карта онлайн (Visa, MasterCard, МИР)',
    'sbp'           => 'СБП (Система быстрых платежей)',
    'yoomoney'      => 'ЮMoney',
    'cod'           => 'Наложенный платёж (при получении)',
];

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $address  = trim($_POST['address'] ?? '');
    $delivery = $_POST['delivery_method'] ?? '';
    $payment  = $_POST['payment_method'] ?? '';

    if (!$address || !$delivery || !$payment) {
        $error = 'Заполните все обязательные поля';
    } elseif (!isset($deliveryOptions[$delivery])) {
        $error = 'Выберите корректный способ доставки';
    } else {
        $deliveryPrice = ($subtotal >= 3000 && $delivery === 'free') ? 0 : $deliveryOptions[$delivery]['price'];
        if ($delivery !== 'free') $deliveryPrice = $deliveryOptions[$delivery]['price'];
        $total = $subtotal + $deliveryPrice;

        $db->beginTransaction();
        try {
            $st = $db->prepare("INSERT INTO orders (user_id, status, total_amount, delivery_address, delivery_method, payment_method)
                                VALUES (?, 'новый', ?, ?, ?, ?)");
            $st->execute([$user['id'], $total, $address, $deliveryOptions[$delivery]['label'], $paymentOptions[$payment]]);
            $orderId = $db->lastInsertId();

            foreach ($cartItems as $item) {
                $st = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, price_at_purchase, quantity) VALUES (?,?,?,?,?)");
                $st->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['quantity']]);
                // Уменьшить склад
                $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$item['quantity'], $item['id']]);
            }

            // Очистить корзину
            $db->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$user['id']]);

            $db->commit();
            header("Location: account.php?order_success=$orderId");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Ошибка при оформлении заказа. Попробуйте ещё раз.';
        }
    }
}

$pageTitle = 'Оформление заказа — Sakura Shop';
include 'header.php';
?>

<div class="container" style="padding-top:24px;">
  <div class="breadcrumbs">
    <a href="index.php">Главная</a> <span>›</span>
    <a href="cart.php">Корзина</a> <span>›</span>
    <span>Оформление заказа</span>
  </div>
</div>

<div class="container" style="padding-bottom:72px;">
  <div class="section-ornament" style="margin-top:16px;"><span>注文</span></div>
  <h1 class="section-title" style="text-align:left;font-size:1.8rem;margin-bottom:32px;">Оформление заказа</h1>

  <?php if ($error): ?>
  <div style="background:rgba(139,0,0,0.08);border:1px solid rgba(139,0,0,0.2);border-radius:4px;padding:12px 16px;color:var(--crimson);margin-bottom:24px;">
    ⚠ <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:32px;" class="checkout-grid">
    <div>
      <form method="POST" id="checkoutForm">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <!-- Адрес -->
        <div style="background:var(--white);border-radius:8px;padding:28px;border:1px solid rgba(139,0,0,0.08);margin-bottom:20px;">
          <h2 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:1.1rem;margin-bottom:20px;letter-spacing:0.05em;">
            📍 Адрес доставки
          </h2>
          <div class="form-group">
            <label class="form-label">Полное имя получателя *</label>
            <input type="text" class="form-input" name="recipient_name"
                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Адрес (город, улица, дом, квартира) *</label>
            <textarea class="form-textarea" name="address" required rows="3"
                      placeholder="Москва, ул. Примерная, д. 1, кв. 1"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
              <label class="form-label">Почтовый индекс</label>
              <input type="text" class="form-input" name="postal_code" placeholder="123456" pattern="\d{6}" required>
            </div>
            <div class="form-group">
              <label class="form-label">Телефон *</label>
              <input type="tel" class="form-input" name="phone"
                     value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                     required placeholder="+7 (999) 000-00-00">
            </div>
          </div>
        </div>

        <!-- Доставка -->
        <div style="background:var(--white);border-radius:8px;padding:28px;border:1px solid rgba(139,0,0,0.08);margin-bottom:20px;">
          <h2 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:1.1rem;margin-bottom:20px;letter-spacing:0.05em;">
            🚚 Способ доставки *
          </h2>
          <div class="radio-group">
            <?php foreach ($deliveryOptions as $key => $opt):
              $disabled = ($key === 'free' && $subtotal < 3000);
            ?>
            <label class="radio-option <?= ($key === 'cdek') ? 'selected' : '' ?> <?= $disabled ? 'disabled' : '' ?>"
                   style="<?= $disabled ? 'opacity:0.5;cursor:not-allowed;' : 'cursor:pointer;' ?>">
              <input type="radio" name="delivery_method" value="<?= $key ?>"
                     <?= $key === 'cdek' ? 'checked' : '' ?>
                     <?= $disabled ? 'disabled' : '' ?>
                     onchange="updateDelivery(this)">
              <div style="flex:1;">
                <div style="font-weight:600;font-size:0.9rem;"><?= $opt['label'] ?></div>
                <div style="font-size:0.8rem;color:var(--charcoal-light);">Срок: <?= $opt['days'] ?> дней</div>
              </div>
              <div style="font-family:var(--font-serif);color:var(--crimson);font-weight:700;">
                <?= $opt['price'] > 0 ? number_format($opt['price'], 0, ',', ' ') . ' ₽' : 'Бесплатно' ?>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Оплата -->
        <div style="background:var(--white);border-radius:8px;padding:28px;border:1px solid rgba(139,0,0,0.08);margin-bottom:20px;">
          <h2 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:1.1rem;margin-bottom:20px;letter-spacing:0.05em;">
            💳 Способ оплаты *
          </h2>
          <div class="radio-group">
            <?php foreach ($paymentOptions as $key => $label): ?>
            <label class="radio-option <?= $key === 'card_online' ? 'selected' : '' ?>" style="cursor:pointer;">
              <input type="radio" name="payment_method" value="<?= $key ?>"
                     <?= $key === 'card_online' ? 'checked' : '' ?>>
              <span><?= htmlspecialchars($label) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Комментарий к заказу</label>
          <textarea class="form-textarea" name="comment" rows="2"
                    placeholder="Дополнительная информация..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-full">
          🌸 Оформить заказ
        </button>
      </form>
    </div>

    <!-- Итог -->
    <div>
      <div class="cart-summary" style="position:sticky;top:90px;">
        <h3>Ваш заказ</h3>

        <?php foreach ($cartItems as $item): ?>
        <div style="display:flex;gap:12px;margin-bottom:12px;align-items:center;">
          <div style="width:48px;height:48px;border-radius:4px;overflow:hidden;background:var(--ivory-dark);flex-shrink:0;">
            <?php if ($item['image_path']): ?>
            <img src="<?= htmlspecialchars($item['image_path']) ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🌸</div>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:0.8rem;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <?= htmlspecialchars($item['name']) ?>
            </div>
            <div style="font-size:0.75rem;color:var(--charcoal-light);">× <?= $item['quantity'] ?></div>
          </div>
          <div style="font-size:0.9rem;font-weight:600;color:var(--crimson);white-space:nowrap;">
            <?= number_format($item['price'] * $item['quantity'], 0, ',', ' ') ?> ₽
          </div>
        </div>
        <?php endforeach; ?>

        <hr style="border-color:rgba(139,0,0,0.1);margin:16px 0;">

        <div class="summary-row">
          <span>Товары</span>
          <span><?= number_format($subtotal, 0, ',', ' ') ?> ₽</span>
        </div>
        <div class="summary-row">
          <span>Доставка</span>
          <span id="deliveryPrice">399 ₽</span>
        </div>
        <div class="summary-row total">
          <span>Итого</span>
          <span id="orderTotal"><?= number_format($subtotal + 399, 0, ',', ' ') ?> ₽</span>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@media (max-width: 768px) {
  .checkout-grid { grid-template-columns: 1fr !important; }
}
.radio-option label { cursor: pointer; }
</style>

<script>
const subtotal = <?= $subtotal ?>;
const deliveryPrices = {
  'cdek': 399, 'pochta': 250, 'boxberry': 299, 'free': 0
};

function updateDelivery(el) {
  const price = deliveryPrices[el.value] || 0;
  const priceEl = document.getElementById('deliveryPrice');
  const totalEl = document.getElementById('orderTotal');
  priceEl.textContent = price > 0 ? price.toLocaleString('ru') + ' ₽' : 'Бесплатно';
  totalEl.textContent = (subtotal + price).toLocaleString('ru') + ' ₽';

  document.querySelectorAll('.radio-option').forEach(opt => {
    opt.classList.remove('selected');
    const radio = opt.querySelector('input[type=radio]');
    if (radio && radio.checked) opt.classList.add('selected');
  });
}

document.querySelectorAll('.radio-option input[type=radio]').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.radio-option').forEach(o => o.classList.remove('selected'));
    this.closest('.radio-option')?.classList.add('selected');
    if (this.name === 'delivery_method') updateDelivery(this);
  });
});
</script>

<?php include 'footer.php'; ?>
