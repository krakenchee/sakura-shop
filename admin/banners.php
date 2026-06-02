<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

// Удаление
if (isset($_GET['delete'])) {
    verifyCsrf();
    
    // Удаляем файл с диска
    $st = $db->prepare("SELECT image_path FROM banners WHERE id = ?");
    $st->execute([(int)$_GET['delete']]);
    $banner = $st->fetch();
    if ($banner && $banner['image_path']) {
        $filePath = dirname(__DIR__) . '/' . $banner['image_path'];
        if (file_exists($filePath)) unlink($filePath);
    }
    
    $db->prepare("DELETE FROM banners WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: banners.php?msg=' . urlencode('Баннер удалён'));
    exit;
}

// Переключить активность
if (isset($_GET['toggle'])) {
    verifyCsrf();
    $id = (int)$_GET['toggle'];
    $db->prepare("UPDATE banners SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    header('Location: banners.php?msg=' . urlencode('Статус баннера изменён'));
    exit;
}

// Сохранение
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner'])) {
    verifyCsrf();
    $id       = (int)($_POST['id'] ?? 0);
    $title    = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $imgPath  = trim($_POST['image_path']);
    $link     = trim($_POST['link']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!$title || !$imgPath) {
        $msg = 'Ошибка: заполните заголовок и выберите изображение';
    } elseif ($id) {
        // При обновлении удаляем старое фото, если загружено новое
        if ($imgPath) {
            $st = $db->prepare("SELECT image_path FROM banners WHERE id = ?");
            $st->execute([$id]);
            $oldBanner = $st->fetch();
            if ($oldBanner && $oldBanner['image_path'] && $oldBanner['image_path'] !== $imgPath) {
                $oldFilePath = dirname(__DIR__) . '/' . $oldBanner['image_path'];
                if (file_exists($oldFilePath)) unlink($oldFilePath);
            }
        }
        
        $db->prepare("UPDATE banners SET title=?, subtitle=?, image_path=?, link=?, is_active=? WHERE id=?")
           ->execute([$title, $subtitle, $imgPath, $link, $isActive, $id]);
        $msg = 'Баннер обновлён';
    } else {
        $db->prepare("INSERT INTO banners (title, subtitle, image_path, link, is_active) VALUES (?,?,?,?,?)")
           ->execute([$title, $subtitle, $imgPath, $link, $isActive]);
        $msg = 'Баннер добавлен';
    }
    header('Location: banners.php?msg=' . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

$editBanner = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM banners WHERE id = ?");
    $st->execute([$_GET['edit']]);
    $editBanner = $st->fetch();
}

$banners = $db->query("SELECT * FROM banners ORDER BY id DESC")->fetchAll();

$pageTitle = 'Баннеры — Admin';
include 'admin_header.php';
?>
<div class="admin-layout">
    <div class="admin-mobile-header">
        <button class="admin-burger" aria-label="Меню">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <span class="admin-mobile-logo">⛩ Sakura Admin</span>
    </div>
    
    <div class="admin-sidebar-overlay"></div>
    
    <aside class="admin-sidebar">
        <div class="admin-logo">⛩ Sakura Admin</div>
        <nav class="admin-nav">
            <a href="index.php" class="admin-nav-item">📊 Дашборд</a>
            <a href="orders.php" class="admin-nav-item">📦 Заказы</a>
            <a href="products.php" class="admin-nav-item">🛍 Товары</a>
            <a href="categories.php" class="admin-nav-item">📂 Категории</a>
            <a href="banners.php" class="admin-nav-item active">🖼 Баннеры</a>
            <a href="users.php" class="admin-nav-item">👥 Пользователи</a>
            <a href="feedback.php" class="admin-nav-item">✉️ Обратная связь</a>
            <div class="admin-nav-divider"></div>
            <a href="<?= BASE_URL ?>index.php" class="admin-nav-item">🌐 На сайт</a>
            <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item">🚪 Выйти</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-page-title">Управление баннерами</div>

        <?php if ($msg): ?>
        <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="admin-form-card">
            <h2 class="admin-form-title">
                <?= $editBanner ? 'Редактирование баннера' : 'Добавить баннер' ?>
            </h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="save_banner" value="1">
                <?php if ($editBanner): ?><input type="hidden" name="id" value="<?= $editBanner['id'] ?>"><?php endif; ?>

                <div class="admin-form-grid">
                    <div class="admin-form-group">
                        <label class="admin-form-label">Заголовок *</label>
                        <input type="text" class="admin-form-input" name="title" required
                               value="<?= htmlspecialchars($editBanner['title'] ?? '') ?>">
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">Подзаголовок</label>
                        <input type="text" class="admin-form-input" name="subtitle"
                               value="<?= htmlspecialchars($editBanner['subtitle'] ?? '') ?>">
                    </div>
                    
                    <div class="admin-form-group">
			<label class="admin-form-label">
			    <?= $editBanner ? 'Изменить изображение' : 'Изображение баннера *' ?>
			</label>
			<div class="image-uploader" data-type="banner">
			    <div class="upload-area" onclick="document.getElementById('bannerImageInput').click()">
			        <input type="file" id="bannerImageInput" accept="image/*" style="display:none">
			        <div class="upload-placeholder" <?= ($editBanner && $editBanner['image_path']) ? 'style="display:none"' : '' ?>>
			            <span class="upload-icon">📸</span>
			            <p>Нажмите для загрузки изображения</p>
			            <small>JPG, PNG, WEBP до 5MB</small>
			        </div>
			        <div class="upload-preview" <?= ($editBanner && $editBanner['image_path']) ? '' : 'style="display:none"' ?>>
			            <img src="<?= ($editBanner && $editBanner['image_path']) ? BASE_URL . $editBanner['image_path'] : '' ?>" alt="Preview">
			            <button type="button" class="remove-image-btn">✕</button>
			        </div>
			    </div>
			    <input type="hidden" name="image_path" id="imagePathInput" value="<?= htmlspecialchars($editBanner['image_path'] ?? '') ?>">
			    <div class="upload-progress" style="display:none">
			        <div class="progress-bar"></div>
			        <span>Загрузка...</span>
			    </div>
			</div>
		    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">Ссылка</label>
                        <input type="text" class="admin-form-input" name="link"
                               value="<?= htmlspecialchars($editBanner['link'] ?? '') ?>"
                               placeholder="catalog.php?cat=kosmetika">
                    </div>
                    
                    <div class="admin-form-group" style="display:flex; align-items:center; padding-top:24px;">
                        <label class="admin-form-checkbox">
                            <input type="checkbox" name="is_active" <?= ($editBanner['is_active'] ?? 1) ? 'checked' : '' ?>>
                            Активен (показывать на сайте)
                        </label>
                    </div>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        <?= $editBanner ? 'Сохранить' : 'Добавить' ?>
                    </button>
                    <?php if ($editBanner): ?>
                    <a href="banners.php" class="btn btn-secondary">Отмена</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Все баннеры</h2>
            </div>
            
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Заголовок</th>
                            <th>Изображение</th>
                            <th>Ссылка</th>
                            <th>Активен</th>
                            <th>Действия</th>
                        </thead>
                        <tbody>
                            <?php foreach ($banners as $b): ?>
                            <tr>
                                <td><?= $b['id'] ?></td>
                                <td>
                                    <div><?= htmlspecialchars($b['title']) ?></div>
                                    <?php if ($b['subtitle']): ?>
                                    <div class="admin-text-small"><?= htmlspecialchars($b['subtitle']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b['image_path']): ?>
                                    <img src="<?= BASE_URL . $b['image_path'] ?>" style="height: 40px; width: auto; border-radius: 4px;">
                                    <?php else: ?>
                                    —
                                    <?php endif; ?>
                                </td>
                                <td class="admin-text-small"><?= htmlspecialchars($b['link'] ?? '—') ?></td>
                                <td>
                                    <span class="status-badge <?= $b['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $b['is_active'] ? 'Активен' : 'Неактивен' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="banners.php?edit=<?= $b['id'] ?>" class="admin-btn-action admin-btn-edit">Изменить</a>
                                        <a href="banners.php?toggle=<?= $b['id'] ?>&csrf_token=<?= csrfToken() ?>" 
                                           class="admin-btn-action <?= $b['is_active'] ? 'admin-btn-warning' : 'admin-btn-edit' ?>">
                                            <?= $b['is_active'] ? 'Скрыть' : 'Показать' ?>
                                        </a>
                                        <a href="banners.php?delete=<?= $b['id'] ?>&csrf_token=<?= csrfToken() ?>"
                                           class="admin-btn-action admin-btn-delete"
                                           onclick="return confirm('Удалить баннер?')">Удалить</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (!$banners): ?>
                            <tr>
                                <td colspan="6" class="empty-table">
                                    <div class="empty-state">
                                        <span class="empty-icon">🖼</span>
                                        <p>Баннеров не найдено</p>
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

    <script>
    // Загрузчик для баннеров
    const bannerFileInput = document.getElementById('bannerImageInput');
    if (bannerFileInput) {
        bannerFileInput.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'banner');
            
            const progressDiv = document.querySelector('.upload-progress');
            if (progressDiv) progressDiv.style.display = 'block';
            
            try {
                const response = await fetch('upload_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const previewImg = document.querySelector('.upload-preview img');
                    const placeholder = document.querySelector('.upload-placeholder');
                    const previewContainer = document.querySelector('.upload-preview');
                    const hiddenInput = document.getElementById('imagePathInput');
                    const removeBtn = document.querySelector('.remove-image-btn');
                    
                    if (previewImg) previewImg.src = result.url;
                    if (placeholder) placeholder.style.display = 'none';
                    if (previewContainer) previewContainer.style.display = 'block';
                    if (hiddenInput) hiddenInput.value = result.path;
                    
                    if (removeBtn) {
                        removeBtn.onclick = () => {
                            if (placeholder) placeholder.style.display = 'block';
                            if (previewContainer) previewContainer.style.display = 'none';
                            if (hiddenInput) hiddenInput.value = '';
                            bannerFileInput.value = '';
                        };
                    }
                } else {
                    alert('Ошибка: ' + result.error);
                }
            } catch (error) {
                alert('Ошибка загрузки: ' + error.message);
            } finally {
                if (progressDiv) progressDiv.style.display = 'none';
            }
        });
    }
    </script>

<?php include 'admin_footer.php'; ?>
