<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$user = currentUser();

// Получить товары корзины
$items = $db->prepare("
    SELECT ci.id as cart_id, ci.quantity, p.*, pi.image_path, c.name as cat_name
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE ci.user_id = ?
    ORDER BY ci.added_at DESC
");
$items->execute([$user['id']]);
$cartItems = $items->fetchAll();

$total = 0;
foreach ($cartItems as $i) {
    $total += $i['price'] * $i['quantity'];
}

$pageTitle = 'Корзина — Sakura Shop';
include 'header.php';
?>

<div class="container" style="padding-top:24px;">
  <div class="breadcrumbs">
    <a href="index.php">Главная</a> <span>›</span>
    <span>Корзина</span>
  </div>
</div>

<div class="container">
  <div class="section-ornament" style="margin-top:16px;"><span>カート</span></div>
  <h1 class="section-title" style="text-align:left;margin-bottom:8px;font-size:1.8rem;">Корзина</h1>

  <?php if (!$cartItems): ?>
  <div style="text-align:center;padding:80px 0;">
    <div style="font-size:4rem;margin-bottom:20px;">🛒</div>
    <h3 style="font-family:var(--font-serif);color:var(--crimson-deep);margin-bottom:12px;">Корзина пуста</h3>
    <p style="color:var(--charcoal-light);margin-bottom:28px;">Добавьте товары из каталога</p>
    <a href="catalog.php" class="btn btn-primary">Перейти в каталог</a>
  </div>
  <?php else: ?>

  <div class="cart-layout">
    <!-- Товары -->
    <div>
      <input type="hidden" name="csrf_token" id="csrfToken" value="<?= csrfToken() ?>">

      <?php foreach ($cartItems as $item): ?>
      <div class="cart-item" id="cart-item-<?= $item['cart_id'] ?>">
        <div class="cart-item-img">
          <?php if ($item['image_path']): ?>
          <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
          <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;background:var(--ivory-dark);">🌸</div>
          <?php endif; ?>
        </div>

        <div>
          <div class="cart-item-name">
            <a href="product.php?slug=<?= urlencode($item['slug']) ?>"><?= htmlspecialchars($item['name']) ?></a>
          </div>
          <div class="cart-item-cat"><?= htmlspecialchars($item['cat_name']) ?></div>
          <div style="margin-top:8px;font-size:0.85rem;color:var(--charcoal-light);">
            <?= number_format($item['price'], 0, ',', ' ') ?> ₽ за шт.
          </div>
        </div>

        <div>
          <div class="qty-control">
            <button class="qty-btn" onclick="updateQty(<?= $item['cart_id'] ?>, -1)">–</button>
            <input type="number" class="qty-val" id="qty-<?= $item['cart_id'] ?>"
                   value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_quantity'] ?>"
                   onchange="updateQtyDirect(<?= $item['cart_id'] ?>, this.value)">
            <button class="qty-btn" onclick="updateQty(<?= $item['cart_id'] ?>, 1)">+</button>
          </div>
          <div class="cart-item-price" id="item-price-<?= $item['cart_id'] ?>" style="text-align:center;margin-top:8px;">
            <?= number_format($item['price'] * $item['quantity'], 0, ',', ' ') ?> ₽
          </div>
        </div>

        <button class="cart-remove" onclick="removeItem(<?= $item['cart_id'] ?>)" title="Удалить">✕</button>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Итог -->
    <div class="cart-summary">
      <h3>Итог заказа</h3>

      <div class="summary-row">
        <span>Товаров: <?= count($cartItems) ?></span>
        <span><?= number_format($total, 0, ',', ' ') ?> ₽</span>
      </div>
      <div class="summary-row">
        <span>Доставка</span>
        <span style="color:var(--emerald);">Рассчитается при оформлении</span>
      </div>
      <div class="summary-row total">
        <span>Итого</span>
        <span id="cartTotal" class="cart-total-amount"><?= number_format($total, 0, ',', ' ') ?> ₽</span>
      </div>

      <a href="checkout.php" class="btn btn-primary btn-full" style="margin-top:20px;">
        Оформить заказ
      </a>
      <a href="catalog.php" class="btn btn-secondary btn-full" style="margin-top:10px;">
        Продолжить покупки
      </a>

      <div style="margin-top:20px;padding:16px;background:var(--ivory-dark);border-radius:4px;font-size:0.8rem;color:var(--charcoal-light);">
        🎌 Бесплатная доставка при заказе от 3 000 ₽
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>

<script>
const csrf = document.getElementById('csrfToken')?.value || '';

async function updateQty(cartId, delta) {
  const input = document.getElementById('qty-' + cartId);
  let newVal = parseInt(input.value) + delta;
  if (newVal < 1) newVal = 1;
  if (newVal > parseInt(input.max)) newVal = parseInt(input.max);
  input.value = newVal;
  await syncQty(cartId, newVal);
}

async function updateQtyDirect(cartId, val) {
  await syncQty(cartId, parseInt(val));
}

async function syncQty(cartId, qty) {
  const fd = new FormData();
  fd.append('cart_item_id', cartId);
  fd.append('quantity', qty);
  fd.append('csrf_token', csrf);
  const res = await fetch('api/cart.php?action=update', { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) {
    document.querySelector('.cart-total-amount').textContent = formatRub(data.total);
    // Обновить цену строки
    if (data.item_total !== undefined) {
      const el = document.getElementById('item-price-' + cartId);
      if (el) el.textContent = formatRub(data.item_total);
    }
    document.querySelector('.cart-count').textContent = data.cart_count;
  }
}

async function removeItem(cartId) {
  const fd = new FormData();
  fd.append('cart_item_id', cartId);
  fd.append('csrf_token', csrf);
  const res = await fetch('api/cart.php?action=remove', { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) {
    document.getElementById('cart-item-' + cartId)?.remove();
    document.querySelector('.cart-total-amount').textContent = formatRub(data.total);
    document.querySelector('.cart-count').textContent = data.cart_count;
    if (data.cart_count == 0) location.reload();
  }
}

function formatRub(num) {
  return new Intl.NumberFormat('ru-RU').format(num) + ' ₽';
}
</script>

<?php include 'footer.php'; ?>
