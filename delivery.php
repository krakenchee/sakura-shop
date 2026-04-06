<?php
require_once 'config.php';
$pageTitle = 'Доставка и оплата — Sakura Shop';
include 'header.php';
?>

<!-- Hero -->
<div class="info-hero">
  <div class="container">
    <div class="section-ornament" style="margin-bottom:16px;"><span style="color:var(--gold);">配送・お支払い</span></div>
    <h1>Доставка и оплата</h1>
    <p>Доставляем японские товары по всей России быстро и безопасно</p>
  </div>
</div>

<!-- Доставка -->
<section class="info-content">
  <div class="container">

    <div class="section-ornament"><span>配送方法</span></div>
    <h2 class="section-title">Способы доставки</h2>
    <p class="section-subtitle">Выберите удобный для вас вариант</p>

    <div class="delivery-grid">
      <div class="delivery-card">
        <div class="delivery-icon">📦</div>
        <h3>СДЭК (до двери)</h3>
        <p>Курьер привезёт заказ прямо к вам домой или в офис. Удобно отслеживать посылку в приложении СДЭК.</p>
        <div class="price">399 ₽</div>
        <div style="font-size:0.8rem;color:var(--charcoal-light);margin-top:4px;">3–5 рабочих дней</div>
      </div>

      <div class="delivery-card">
        <div class="delivery-icon">🏪</div>
        <h3>Boxberry (пункт выдачи)</h3>
        <p>Получите заказ в удобном пункте выдачи Boxberry. Более 4 000 пунктов по всей России.</p>
        <div class="price">299 ₽</div>
        <div style="font-size:0.8rem;color:var(--charcoal-light);margin-top:4px;">4–6 рабочих дней</div>
      </div>

      <div class="delivery-card">
        <div class="delivery-icon">✉️</div>
        <h3>Почта России</h3>
        <p>Классическая доставка через Почту России. Доступна во все населённые пункты страны.</p>
        <div class="price">250 ₽</div>
        <div style="font-size:0.8rem;color:var(--charcoal-light);margin-top:4px;">7–14 рабочих дней</div>
      </div>

      <div class="delivery-card" style="border-color:var(--emerald);border-width:2px;">
        <div class="delivery-icon">🎁</div>
        <h3>Бесплатная доставка</h3>
        <p>При заказе от 3 000 ₽ доставка любым удобным способом абсолютно бесплатно!</p>
        <div class="price" style="color:var(--emerald);">Бесплатно</div>
        <div style="font-size:0.8rem;color:var(--charcoal-light);margin-top:4px;">От 5–7 рабочих дней</div>
      </div>
    </div>

    <!-- Упаковка -->
    <div style="background:var(--ivory-dark);border-radius:8px;padding:32px;margin-top:32px;">
      <h3 style="font-family:var(--font-serif);color:var(--crimson-deep);margin-bottom:16px;font-size:1.1rem;">🌸 Наша упаковка</h3>
      <p style="color:var(--charcoal-mid);font-size:0.9rem;line-height:1.8;">
        Мы любим свои товары и упаковываем каждый заказ с заботой. Все хрупкие предметы дополнительно защищаются пузырчатой плёнкой. Товары, чувствительные к температуре (например, моти), отправляем с термовкладышем. 
        Каждый заказ дополняется небольшим подарком-сюрпризом от Sakura Shop 🎀
      </p>
    </div>

    <!-- Зоны доставки -->
    <div class="info-text" style="margin-top:48px;">
      <h2>Зоны и сроки доставки</h2>
      <p>Мы доставляем во все регионы России. Ориентировочные сроки:</p>
    </div>

    <div style="overflow:auto;margin-top:16px;">
      <table style="width:100%;border-collapse:collapse;background:var(--white);border-radius:8px;overflow:hidden;box-shadow:var(--shadow-sm);">
        <thead>
          <tr style="background:var(--crimson-deep);">
            <th style="padding:14px 20px;text-align:left;color:var(--ivory);font-size:0.85rem;font-family:var(--font-serif);letter-spacing:0.1em;">Регион</th>
            <th style="padding:14px 20px;text-align:left;color:var(--ivory);font-size:0.85rem;font-family:var(--font-serif);">СДЭК</th>
            <th style="padding:14px 20px;text-align:left;color:var(--ivory);font-size:0.85rem;font-family:var(--font-serif);">Почта России</th>
            <th style="padding:14px 20px;text-align:left;color:var(--ivory);font-size:0.85rem;font-family:var(--font-serif);">Boxberry</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $regions = [
            ['Москва и МО', '1–2 дня', '3–5 дней', '2–3 дня'],
            ['Санкт-Петербург и ЛО', '2–3 дня', '4–7 дней', '3–4 дня'],
            ['Крупные города (Екатеринбург, Казань, Новосибирск)', '3–5 дней', '7–10 дней', '4–6 дней'],
            ['Другие регионы', '4–7 дней', '10–14 дней', '5–8 дней'],
            ['Труднодоступные регионы', 'По запросу', '14–21 день', 'Не доступно'],
          ];
          foreach ($regions as $i => $r): ?>
          <tr style="<?= $i % 2 ? 'background:var(--ivory-dark);' : '' ?>">
            <?php foreach ($r as $j => $cell): ?>
            <td style="padding:12px 20px;font-size:0.875rem;<?= $j === 0 ? 'font-weight:600;' : 'color:var(--charcoal-light);' ?>">
              <?= htmlspecialchars($cell) ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Оплата -->
    <div class="section-ornament" style="margin-top:64px;"><span>お支払い</span></div>
    <h2 class="section-title">Способы оплаты</h2>
    <p class="section-subtitle">Выбирайте удобный вам способ</p>

    <div class="delivery-grid">
      <div class="delivery-card">
        <div class="delivery-icon">💳</div>
        <h3>Банковская карта</h3>
        <p>Visa, Mastercard, МИР. Безопасная оплата через зашифрованный платёжный шлюз.</p>
      </div>

      <div class="delivery-card">
        <div class="delivery-icon">⚡</div>
        <h3>СБП</h3>
        <p>Система быстрых платежей. Оплата по QR-коду или переводом между банками.</p>
      </div>

      <div class="delivery-card">
        <div class="delivery-icon">💰</div>
        <h3>ЮMoney</h3>
        <p>Оплата через электронный кошелёк ЮMoney (бывший Яндекс.Деньги).</p>
      </div>

      <div class="delivery-card">
        <div class="delivery-icon">🏠</div>
        <h3>Наложенный платёж</h3>
        <p>Оплата при получении на Почте России. Дополнительная комиссия почты.</p>
      </div>
    </div>

    <!-- Возврат -->
    <div class="info-text" style="margin-top:48px;">
      <h2>Возврат и обмен</h2>
      <p>
        Мы работаем по всем нормам российского законодательства о защите прав потребителей. Если товар вам не подошёл — мы вернём деньги или обменяем.
      </p>
      <p>
        <strong>Сроки возврата:</strong> в течение 14 дней с момента получения, если товар не использовался и сохранил товарный вид. Продукты питания, косметика и товары с нарушенной упаковкой возврату не подлежат.
      </p>
      <p>
        <strong>Как оформить возврат:</strong> напишите нам на info@sakura-shop.ru или позвоните по номеру +7 (800) 555-35-35. Мы всегда идём навстречу покупателям!
      </p>
    </div>

    <!-- FAQ -->
    <div style="margin-top:48px;">
      <h3 style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:1.3rem;margin-bottom:24px;">Часто задаваемые вопросы</h3>
      <?php
      $faq = [
        ['Можно ли отследить заказ?', 'Да! После отправки мы пришлём вам трекинговый номер на email. Вы сможете отслеживать посылку через сайт выбранной службы доставки.'],
        ['Что делать если пришёл повреждённый товар?', 'Сфотографируйте повреждение и свяжитесь с нами. Мы немедленно вышлем замену или вернём деньги без лишних вопросов.'],
        ['Можно ли изменить адрес после оформления?', 'Да, если заказ ещё не передан в службу доставки. Свяжитесь с нами как можно скорее.'],
        ['Отправляете ли вы за рубеж?', 'В настоящее время мы работаем только по России. Международная доставка планируется в ближайшем будущем.'],
      ];
      foreach ($faq as $q): ?>
      <details style="background:var(--white);border:1px solid rgba(139,0,0,0.08);border-radius:6px;padding:18px 20px;margin-bottom:10px;cursor:pointer;">
        <summary style="font-family:var(--font-serif);color:var(--crimson-deep);font-size:0.95rem;list-style:none;display:flex;justify-content:space-between;align-items:center;">
          <?= $q[0] ?>
          <span style="color:var(--gold);font-size:1.2rem;">▾</span>
        </summary>
        <p style="margin-top:12px;font-size:0.875rem;color:var(--charcoal-mid);line-height:1.7;"><?= $q[1] ?></p>
      </details>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<?php include 'footer.php'; ?>
