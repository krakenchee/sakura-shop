// ============================================================
// Sakura Shop — Main JavaScript
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  // --- Слайдер ---
  initSlider();

  // --- Галерея товара ---
  initGallery();

  // --- Рейтинг звёздочками ---
  initStarRating();

  // --- Корзина ---
  initCart();

  // --- Форма обратной связи ---
  initFeedbackForm();

  // --- Количество товара ---
  initQtyControls();

  // --- Тосты ---
  window.showToast = showToast;
});

// ============================================================
// СЛАЙДЕР
// ============================================================
function initSlider() {
  const slider = document.querySelector('.slider-section');
  if (!slider) return;

  const track = slider.querySelector('.slider-track');
  const slides = slider.querySelectorAll('.slide');
  const dots = slider.querySelectorAll('.slider-dot');
  const prevBtn = slider.querySelector('.slider-prev');
  const nextBtn = slider.querySelector('.slider-next');

  if (!slides.length) return;

  let current = 0;
  let timer;

  function goTo(idx) {
    slides[current].classList.remove('active');
    dots[current]?.classList.remove('active');
    current = (idx + slides.length) % slides.length;
    slides[current].classList.add('active');
    dots[current]?.classList.add('active');
    track.style.transform = `translateX(-${current * 100}%)`;
  }

  function startAuto() {
    clearInterval(timer);
    timer = setInterval(() => goTo(current + 1), 5000);
  }

  // Инит
  slides[0]?.classList.add('active');
  dots[0]?.classList.add('active');
  startAuto();

  prevBtn?.addEventListener('click', () => { goTo(current - 1); startAuto(); });
  nextBtn?.addEventListener('click', () => { goTo(current + 1); startAuto(); });
  dots.forEach((dot, i) => dot.addEventListener('click', () => { goTo(i); startAuto(); }));

  // Свайп
  let startX = 0;
  track.addEventListener('touchstart', e => { startX = e.touches[0].clientX; });
  track.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - startX;
    if (Math.abs(dx) > 50) { goTo(dx < 0 ? current + 1 : current - 1); startAuto(); }
  });
}

// ============================================================
// ГАЛЕРЕЯ ТОВАРА
// ============================================================
function initGallery() {
  const mainImg = document.querySelector('.gallery-main img');
  const thumbs = document.querySelectorAll('.gallery-thumb');
  if (!mainImg || !thumbs.length) return;

  thumbs.forEach(thumb => {
    thumb.addEventListener('click', () => {
      const src = thumb.querySelector('img').src;
      mainImg.style.opacity = '0';
      mainImg.style.transform = 'scale(0.97)';
      setTimeout(() => {
        mainImg.src = src;
        mainImg.style.opacity = '1';
        mainImg.style.transform = 'scale(1)';
      }, 200);
      thumbs.forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });
  mainImg.style.transition = 'opacity 0.2s, transform 0.2s';
}

// ============================================================
// ЗВЁЗДНЫЙ РЕЙТИНГ
// ============================================================
function initStarRating() {
  const container = document.querySelector('.star-rating-input');
  const input = document.getElementById('ratingInput');
  if (!container || !input) return;

  const btns = container.querySelectorAll('.star-btn');
  let selected = 0;

  btns.forEach((btn, i) => {
    btn.addEventListener('mouseenter', () => highlightStars(btns, i));
    btn.addEventListener('mouseleave', () => highlightStars(btns, selected - 1));
    btn.addEventListener('click', () => {
      selected = i + 1;
      input.value = selected;
      highlightStars(btns, selected - 1);
      btns.forEach(b => b.classList.remove('active'));
      for (let j = 0; j <= i; j++) btns[j].classList.add('active');
    });
  });
}

function highlightStars(btns, upTo) {
  btns.forEach((btn, i) => {
    btn.style.color = i <= upTo ? 'var(--gold)' : 'rgba(139,0,0,0.2)';
  });
}

// ============================================================
// КОРЗИНА
// ============================================================
function initCart() {
  // Добавление в корзину (AJAX)
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-add-cart]');
    if (btn) {
      e.preventDefault();
      const productId = btn.dataset.addCart;
      const qty = document.getElementById('qty')?.value || 1;
      addToCart(productId, qty, btn);
    }
    // Удаление из корзины
    const removeBtn = e.target.closest('[data-remove-cart]');
    if (removeBtn) {
      e.preventDefault();
      const itemId = removeBtn.dataset.removeCart;
      removeFromCart(itemId, removeBtn.closest('.cart-item'));
    }
  });

  // Изменение количества в корзине
  document.addEventListener('change', e => {
    if (e.target.dataset.updateCart) {
      updateCartQty(e.target.dataset.updateCart, e.target.value);
    }
  });
}

async function addToCart(productId, qty, btn) {
  try {
    btn.disabled = true;
    btn.textContent = '...';
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', qty);
    formData.append('csrf_token', getCSRF());

    const res = await fetch('api/cart.php?action=add', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('Товар добавлен в корзину!', 'success');
      updateCartBadge(data.cart_count);
      btn.textContent = '✓ В корзине';
    } else {
      showToast(data.message || 'Ошибка', 'error');
      btn.textContent = 'В корзину';
    }
  } catch {
    showToast('Ошибка соединения', 'error');
    btn.textContent = 'В корзину';
  }
  btn.disabled = false;
}

async function removeFromCart(cartItemId, row) {
  const formData = new FormData();
  formData.append('cart_item_id', cartItemId);
  formData.append('csrf_token', getCSRF());

  const res = await fetch('api/cart.php?action=remove', { method: 'POST', body: formData });
  const data = await res.json();
  if (data.success) {
    row?.remove();
    updateCartBadge(data.cart_count);
    updateCartTotal(data.total);
    showToast('Товар удалён из корзины');
  }
}

async function updateCartQty(cartItemId, qty) {
  const formData = new FormData();
  formData.append('cart_item_id', cartItemId);
  formData.append('quantity', qty);
  formData.append('csrf_token', getCSRF());

  const res = await fetch('api/cart.php?action=update', { method: 'POST', body: formData });
  const data = await res.json();
  if (data.success) {
    updateCartTotal(data.total);
    updateCartBadge(data.cart_count);
  }
}

function updateCartBadge(count) {
  const badge = document.querySelector('.cart-count');
  if (badge) badge.textContent = count;
}

function updateCartTotal(total) {
  const el = document.querySelector('.cart-total-amount');
  if (el) el.textContent = formatPrice(total);
}

// ============================================================
// ФОРМА ОБРАТНОЙ СВЯЗИ
// ============================================================
function initFeedbackForm() {
  const form = document.getElementById('feedbackForm');
  if (!form) return;
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Отправляется...';
    try {
      const res = await fetch('api/feedback.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showToast('Сообщение отправлено! Мы свяжемся с вами.', 'success');
        form.reset();
      } else {
        showToast(data.message || 'Ошибка при отправке', 'error');
      }
    } catch {
      showToast('Ошибка соединения', 'error');
    }
    btn.disabled = false;
    btn.textContent = 'Отправить';
  });
}

// ============================================================
// СЧЁТЧИК КОЛИЧЕСТВА
// ============================================================
function initQtyControls() {
  document.addEventListener('click', e => {
    const btn = e.target.closest('.qty-btn');
    if (!btn) return;
    const input = btn.closest('.qty-control')?.querySelector('.qty-val');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    if (btn.dataset.action === 'plus') val = Math.min(val + 1, 99);
    if (btn.dataset.action === 'minus') val = Math.max(val - 1, 1);
    input.value = val;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
}

// ============================================================
// ТОСТЫ
// ============================================================
function showToast(message, type = 'info') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = message;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(20px)';
    toast.style.transition = 'all 0.3s';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// ============================================================
// ХЕЛПЕРЫ
// ============================================================
function getCSRF() {
  return document.querySelector('[name="csrf_token"]')?.value || '';
}

function formatPrice(num) {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(num);
}

// ============================================================
// ФИЛЬТРЫ КАТАЛОГА
// ============================================================
function applyFilters() {
  const form = document.getElementById('filterForm');
  if (!form) return;
  const params = new URLSearchParams(new FormData(form));
  // Сохранить текущую категорию
  const cat = new URLSearchParams(window.location.search).get('cat');
  if (cat) params.set('cat', cat);
  window.location.href = 'catalog.php?' + params.toString();
}

// Автоотправка при изменении сортировки
document.addEventListener('change', e => {
  if (e.target.id === 'sortSelect') {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', e.target.value);
    window.location.href = url.toString();
  }
});

// Подкатегории в каталоге — переключение
document.addEventListener('click', e => {
  const btn = e.target.closest('.subcategory-btn');
  if (!btn) return;
  const url = new URL(window.location.href);
  const sub = btn.dataset.sub;
  if (sub) url.searchParams.set('sub', sub);
  else url.searchParams.delete('sub');
  window.location.href = url.toString();
});
