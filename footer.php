<?php
// includes/footer.php
?>
<!-- FOOTER -->
<footer class="site-footer">
  <div class="footer-top">

    <!-- О магазине -->
    <div class="footer-section">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <div class="logo-symbol" style="width:36px;height:36px;font-size:1.1rem;">桜</div>
        <span style="font-family:var(--font-serif);color:var(--gold);font-size:1.1rem;letter-spacing:0.1em;">Sakura Shop</span>
      </div>
      <p>Ваш портал в мир японской культуры. Аутентичные товары прямо из Японии — сладости, косметика, канцелярия, сувениры и многое другое.</p>
      <div class="footer-socials">
        <a href="#" class="social-link" aria-label="ВКонтакте">ВК</a>
        <a href="#" class="social-link" aria-label="Telegram">TG</a>
        <a href="#" class="social-link" aria-label="Instagram">IG</a>
      </div>
    </div>

    <!-- Навигация -->
    <div class="footer-section">
      <h3>Разделы</h3>
      <ul>
        <li><a href="<?= BASE_URL ?>index.php">Главная</a></li>
        <li><a href="<?= BASE_URL ?>catalog.php">Каталог товаров</a></li>
        <li><a href="<?= BASE_URL ?>delivery.php">Доставка и оплата</a></li>
        <li><a href="<?= BASE_URL ?>account.php">Личный кабинет</a></li>
      </ul>
    </div>

    <!-- Контакты -->
    <div class="footer-section">
      <h3>Контакты</h3>
      <div class="footer-contact"><span>📧</span><span><strong>info@sakura-shop.ru</strong></span></div>
      <div class="footer-contact"><span>📞</span><span><strong>+7 (800) 555-35-35</strong></span></div>
      <div class="footer-contact"><span>🕐</span><span>Пн–Пт: 9:00–20:00</span></div>
      <div class="footer-contact" style="margin-top:12px;"><span>📍</span><span>Москва, Россия</span></div>
    </div>

    <!-- Обратная связь -->
    <div class="footer-section">
      <h3>Написать нам</h3>
      <form class="footer-feedback-form" id="feedbackForm">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="text" name="name" placeholder="Ваше имя" required>
        <input type="text" name="contact" placeholder="Email или телефон" required>
        <textarea name="message" placeholder="Сообщение..." required></textarea>
        <button type="submit" class="btn btn-primary btn-sm">Отправить</button>
      </form>
    </div>

  </div>

  <div class="footer-bottom">
    <span>&copy; <?= date('Y') ?> Sakura Shop. Все права защищены.</span>
    <span>Сделано с ♥ к Японии</span>
  </div>
</footer>

<!-- Скрипты -->
<script src="<?= BASE_URL ?>main.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const phones = document.querySelectorAll('input[type="tel"]');

    phones.forEach(function(input) {
        input.addEventListener("input", function(e) {
            let x = input.value.replace(/\D/g, '').substring(0, 11);

            let formatted = '+7 ';
            if (x.length > 1) formatted += '(' + x.substring(1,4);
            if (x.length >= 4) formatted += ') ' + x.substring(4,7);
            if (x.length >= 7) formatted += '-' + x.substring(7,9);
            if (x.length >= 9) formatted += '-' + x.substring(9,11);

            input.value = formatted;
        });
    });
});
</script>
</body>
</html>
