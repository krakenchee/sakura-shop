<?php
require_once '../config.php';
requireAdmin();

// Функция для генерации slug
function generateSlug($string) {
    $cyrillic = array(
        'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
        'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
        'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
        'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
    );
    $latin = array(
        'a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p',
        'r','s','t','u','f','h','ts','ch','sh','shch','','y','','e','yu','ya',
        'A','B','V','G','D','E','E','Zh','Z','I','Y','K','L','M','N','O','P',
        'R','S','T','U','F','H','Ts','Ch','Sh','Shch','','Y','','E','Yu','Ya'
    );
    
    $string = str_replace($cyrillic, $latin, $string);
    $string = preg_replace('/[^a-z0-9\s-]/i', '', $string);
    $string = preg_replace('/[\s_]+/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = strtolower($string);
    $string = trim($string, '-');
    
    if (empty($string)) $string = 'product';
    return $string;
}

$db = getDB();
$msg = '';
$error = '';

// Получаем подкатегории
$subcategories = $db->query("
    SELECT c.id, c.name, mc.name as main_cat_name, mc.id as main_cat_id
    FROM categories c
    LEFT JOIN main_categories mc ON mc.id = c.main_category_id
    ORDER BY mc.id, c.name
")->fetchAll();

$groupedSubcats = [];
foreach ($subcategories as $subcat) {
    $mainCatName = $subcat['main_cat_name'] ?? 'Без категории';
    if (!isset($groupedSubcats[$mainCatName])) {
        $groupedSubcats[$mainCatName] = [];
    }
    $groupedSubcats[$mainCatName][] = $subcat;
}

// Удаление товара
if (isset($_GET['delete'])) {
    verifyCsrf();
    $id = (int)$_GET['delete'];
    
    // Получаем путь к изображению перед удалением
    $st = $db->prepare("SELECT pi.image_path FROM products p LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1 WHERE p.id = ?");
    $st->execute([$id]);
    $image = $st->fetch();
    
    // Удаляем файл с диска
    if ($image && $image['image_path']) {
        $filePath = dirname(__DIR__) . '/' . $image['image_path'];
        if (file_exists($filePath)) unlink($filePath);
    }
    
    $st = $db->prepare("SELECT name FROM products WHERE id = ?");
    $st->execute([$id]);
    $productName = $st->fetchColumn();
    
    if ($productName) {
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $msg = 'Товар "' . htmlspecialchars($productName) . '" удален';
    }
    
    header('Location: products.php?msg=' . urlencode($msg));
    exit;
}

// Сохранение товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    verifyCsrf();
    $id          = (int)($_POST['id'] ?? 0);
    $categoryId  = (int)$_POST['category_id'];
    $name        = trim($_POST['name']);
    $slug        = trim($_POST['slug']) ?: generateSlug($name);
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $oldPrice    = $_POST['old_price'] ? (float)$_POST['old_price'] : null;
    $stock       = (int)$_POST['stock_quantity'];
    $imagePath   = trim($_POST['image_path'] ?? '');

    if (!$name || !$categoryId || $price <= 0) {
        $error = 'Заполните обязательные поля';
    } else {
        if ($id) {
            // Обновление товара
            $db->prepare("UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, old_price=?, stock_quantity=? WHERE id=?")
               ->execute([$categoryId, $name, $slug, $description, $price, $oldPrice, $stock, $id]);
            
            // Обновляем изображение ТОЛЬКО если загружено новое
    	    if (!empty($imagePath)) {
	        // Получаем текущее изображение
	        $st = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1");
	        $st->execute([$id]);
	        $oldImage = $st->fetch();
	    
	        // Если новое изображение отличается от старого
	        if (!$oldImage || $oldImage['image_path'] !== $imagePath) {
	            // Удаляем старое изображение с диска
	            if ($oldImage && $oldImage['image_path']) {
	                $oldFilePath = dirname(__DIR__) . '/' . $oldImage['image_path'];
	                if (file_exists($oldFilePath)) unlink($oldFilePath);
	            }
	        
	            // Обновляем или вставляем новое
	            if ($oldImage) {
	                $db->prepare("UPDATE product_images SET image_path = ? WHERE product_id = ? AND is_main = 1")->execute([$imagePath, $id]);
	            } else {
	                $db->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?,?,1)")->execute([$id, $imagePath]);
	            }
	        }
	    }
            
            $msg = 'Товар обновлён';
        } else {
            // Добавление нового товара
            $db->prepare("INSERT INTO products (category_id, name, slug, description, price, old_price, stock_quantity) VALUES (?,?,?,?,?,?,?)")
               ->execute([$categoryId, $name, $slug, $description, $price, $oldPrice, $stock]);
            $newId = $db->lastInsertId();

            if ($imagePath) {
                $db->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?,?,1)")->execute([$newId, $imagePath]);
            }

            $msg = 'Товар добавлен';
        }
        header('Location: products.php?msg=' . urlencode($msg));
        exit;
    }
}

// Сохранение характеристик (оставляем)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_features'])) {
    verifyCsrf();
    $productId = (int)$_POST['product_id'];
    $db->prepare("DELETE FROM product_features WHERE product_id = ?")->execute([$productId]);
    $names  = $_POST['feature_name'] ?? [];
    $values = $_POST['feature_value'] ?? [];
    foreach ($names as $i => $fname) {
        $fval = $values[$i] ?? '';
        if (trim($fname) && trim($fval)) {
            $db->prepare("INSERT INTO product_features (product_id, feature_name, feature_value) VALUES (?,?,?)")->execute([$productId, trim($fname), trim($fval)]);
        }
    }
    $msg = 'Характеристики сохранены';
    header('Location: products.php?msg=' . urlencode($msg));
    exit;
}

// Редактируемый товар
$editProduct = null;
$productImage = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM products WHERE id = ?");
    $st->execute([$_GET['edit']]);
    $editProduct = $st->fetch();
    if ($editProduct) {
        $st = $db->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_main = 1");
        $st->execute([$editProduct['id']]);
        $productImage = $st->fetch();
        
        $st = $db->prepare("SELECT * FROM product_features WHERE product_id = ?");
        $st->execute([$editProduct['id']]);
        $productFeatures = $st->fetchAll();
    }
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

// Список товаров
$products = $db->query("
    SELECT p.*, c.name as cat_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image_path
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.created_at DESC
")->fetchAll();

$pageTitle = 'Товары — Admin';
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
            <a href="products.php" class="admin-nav-item active">🛍 Товары</a>
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
        <div class="admin-page-title">Управление товарами</div>

        <?php if ($msg): ?>
        <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="admin-message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Форма добавления/редактирования -->
        <div class="admin-form-card">
            <h2 class="admin-form-title">
                <?= $editProduct ? 'Редактирование: ' . htmlspecialchars($editProduct['name']) : 'Добавить товар' ?>
            </h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="save_product" value="1">
                <?php if ($editProduct): ?><input type="hidden" name="id" value="<?= $editProduct['id'] ?>"><?php endif; ?>

                <div class="admin-form-grid">
                    <div class="admin-form-group">
                        <label class="admin-form-label">Название *</label>
                        <input type="text" class="admin-form-input" name="name" required
                               value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">Slug (URL)</label>
                        <input type="text" class="admin-form-input" name="slug"
                               value="<?= htmlspecialchars($editProduct['slug'] ?? '') ?>"
                               placeholder="Оставьте пустым для автоматической генерации">
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">Категория *</label>
                        <select name="category_id" class="admin-form-select" required>
                            <option value="">— Выберите подкатегорию —</option>
                            <?php foreach ($groupedSubcats as $mainCatName => $subcats): ?>
                            <optgroup label="<?= htmlspecialchars($mainCatName) ?>">
                                <?php foreach ($subcats as $subcat): ?>
                                <option value="<?= $subcat['id'] ?>" <?= ($editProduct['category_id'] ?? 0) == $subcat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subcat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">Остаток *</label>
                        <input type="number" class="admin-form-input" name="stock_quantity" min="0"
                               value="<?= $editProduct['stock_quantity'] ?? 0 ?>">
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">Цена (₽) *</label>
                        <input type="number" class="admin-form-input" name="price" step="0.01" min="0" required
                               value="<?= $editProduct['price'] ?? '' ?>">
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">Старая цена (₽)</label>
                        <input type="number" class="admin-form-input" name="old_price" step="0.01" min="0"
                               value="<?= $editProduct['old_price'] ?? '' ?>">
                    </div>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Описание</label>
                    <textarea class="admin-form-textarea" name="description" rows="4"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
                </div>

                <!-- ЗАГРУЗЧИК ИЗОБРАЖЕНИЙ (одно фото) -->
                <div class="admin-form-group">
                    <label class="admin-form-label">
                        <?= $editProduct ? 'Изменить изображение' : 'Изображение товара *' ?>
                    </label>
                    <div class="image-uploader" data-type="product">
                        <div class="upload-area" onclick="document.getElementById('productImageInput').click()">
                            <input type="file" id="productImageInput" accept="image/*" style="display:none">
                            <div class="upload-placeholder" <?= ($editProduct && $productImage) ? 'style="display:none"' : '' ?>>
                                <span class="upload-icon">📸</span>
                                <p>Нажмите для загрузки изображения</p>
                                <small>JPG, PNG, WEBP до 5MB</small>
                            </div>
                            <div class="upload-preview" <?= ($editProduct && $productImage) ? '' : 'style="display:none"' ?>>
                                <img src="<?= ($editProduct && $productImage) ? BASE_URL . $productImage['image_path'] : '' ?>" alt="Preview">
                                <button type="button" class="remove-image-btn">✕</button>
                            </div>
                        </div>
                        <input type="hidden" name="image_path" id="imagePathInput" value="<?= htmlspecialchars($productImage['image_path'] ?? '') ?>">
                        <div class="upload-progress" style="display:none">
                            <div class="progress-bar"></div>
                            <span>Загрузка...</span>
                        </div>
                    </div>
                </div>

                <!-- Автоматические статусы -->
                <?php if ($editProduct): ?>
                <div class="admin-form-group">
                    <label class="admin-form-label">Статусы (автоматические)</label>
                    <div style="display: flex; gap: 15px; margin-top: 8px;">
                        <?php if ($editProduct['is_new']): ?>
                        <div class="status-badge-auto status-new">
                            ✨ НОВИНКА
                            <span class="status-hint">(до <?= date('d.m.Y', strtotime($editProduct['created_at'] . ' +30 days')) ?>)</span>
                        </div>
                        <?php else: ?>
                        <div class="status-badge-auto status-normal">📅 Не новинка</div>
                        <?php endif; ?>
                        
                        <?php if ($editProduct['is_popular']): ?>
                        <div class="status-badge-auto status-hit">
                            🔥 ХИТ
                            <span class="status-hint">(<?= $editProduct['order_count_30d'] ?> заказов за 30 дней)</span>
                        </div>
                        <?php else: ?>
                        <div class="status-badge-auto status-normal">
                            📊 Не хит
                            <?php if ($editProduct['order_count_30d'] > 0): ?>
                            <span class="status-hint">(<?= $editProduct['order_count_30d'] ?> заказов, нужно 10+)</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        <?= $editProduct ? 'Сохранить изменения' : 'Добавить товар' ?>
                    </button>
                    <?php if ($editProduct): ?>
                    <a href="products.php" class="btn btn-secondary">Отмена</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Характеристики товара (если редактируем) -->
        <?php if ($editProduct): ?>
        <div class="admin-form-card" style="margin-top: 30px;">
            <h2 class="admin-form-title">Характеристики товара</h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="save_features" value="1">
                <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                
                <div id="features-container">
                    <?php if (empty($productFeatures)): ?>
                    <div class="feature-row">
                        <input type="text" class="admin-form-input" name="feature_name[]" placeholder="Название (например: Состав)" style="width: 30%;">
                        <input type="text" class="admin-form-input" name="feature_value[]" placeholder="Значение (например: Рис, сахар)" style="width: 65%;">
                    </div>
                    <?php else: ?>
                        <?php foreach ($productFeatures as $f): ?>
                        <div class="feature-row">
                            <input type="text" class="admin-form-input" name="feature_name[]" value="<?= htmlspecialchars($f['feature_name']) ?>" style="width: 30%;">
                            <input type="text" class="admin-form-input" name="feature_value[]" value="<?= htmlspecialchars($f['feature_value']) ?>" style="width: 65%;">
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" id="add-feature-btn" class="btn btn-secondary btn-sm" style="margin: 10px 0;">+ Добавить характеристику</button>
                
                <div>
                    <button type="submit" class="btn btn-primary">Сохранить характеристики</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Список товаров -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Все товары</h2>
                <div class="admin-stats">
                    <span class="stat-badge">✨ Новинок: <?= $db->query("SELECT COUNT(*) FROM products WHERE is_new = 1")->fetchColumn() ?></span>
                    <span class="stat-badge">🔥 Хитов: <?= $db->query("SELECT COUNT(*) FROM products WHERE is_popular = 1")->fetchColumn() ?></span>
                </div>
            </div>
            
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Фото</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Цена</th>
                            <th>Склад</th>
                            <th>Статусы</th>
                            <th>Действия</th>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?> </td>
                                <td class="product-thumb">
                                    <?php if ($p['image_path']): ?>
                                    <img src="<?= BASE_URL . $p['image_path'] ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">🌸</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($p['name']) ?> </td>
                                <td class="admin-text-small"><?= htmlspecialchars($p['cat_name'] ?? '—') ?> </td>
                                <td><?= number_format($p['price'], 0, ',', ' ') ?> ₽</td>
                                <td><?= $p['stock_quantity'] ?> </td>
                                <td>
                                    <?php if ($p['is_new']): ?>
                                    <span class="badge badge-new">✨ NEW</span>
                                    <?php endif; ?>
                                    <?php if ($p['is_popular']): ?>
                                    <span class="badge badge-popular">🔥 HIT</span>
                                    <?php endif; ?>
                                    <?php if (!$p['is_new'] && !$p['is_popular']): ?>
                                    <span class="badge badge-light">—</span>
                                    <?php endif; ?>
                                 </td>
                                <td>
                                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                        <a href="products.php?edit=<?= $p['id'] ?>" class="admin-btn-action admin-btn-edit">Изменить</a>
                                        <a href="../product.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="admin-btn-action admin-btn-edit">Просмотр</a>
                                        <a href="products.php?delete=<?= $p['id'] ?>&csrf_token=<?= csrfToken() ?>"
                                           class="admin-btn-action admin-btn-delete"
                                           onclick="return confirm('Удалить товар «<?= htmlspecialchars($p['name']) ?>»?')">
                                           Удалить
                                        </a>
                                    </div>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($products)): ?>
                             <tr>
                                <td colspan="8" class="empty-table">
                                    <div class="empty-state">
                                        <span class="empty-icon">🛍</span>
                                        <p>Товаров не найдено</p>
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

    <style>
    .admin-stats {
        display: flex;
        gap: 15px;
        margin-top: 10px;
    }
    .stat-badge {
        padding: 4px 12px;
        background: #f0f0f0;
        border-radius: 20px;
        font-size: 13px;
    }
    .status-badge-auto {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
    }
    .status-badge-auto.status-new { background: #e8f5e9; color: #2e7d32; }
    .status-badge-auto.status-hit { background: #fff3e0; color: #f57c00; }
    .status-badge-auto.status-normal { background: #f5f5f5; color: #757575; }
    .status-hint { font-size: 11px; font-weight: normal; opacity: 0.7; }
    
    .image-uploader { margin-top: 8px; }
    .upload-area {
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    .upload-area:hover { border-color: #c62828; background: rgba(198, 40, 40, 0.05); }
    .upload-placeholder .upload-icon { font-size: 48px; display: block; margin-bottom: 10px; }
    .upload-preview { position: relative; display: inline-block; }
    .upload-preview img { max-width: 200px; max-height: 200px; border-radius: 8px; }
    .remove-image-btn {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #c62828;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .upload-progress { margin-top: 10px; }
    .progress-bar {
        height: 4px;
        background: #c62828;
        width: 0%;
        transition: width 0.3s;
        animation: progress 2s infinite;
    }
    @keyframes progress {
        0% { width: 0%; }
        50% { width: 100%; }
        100% { width: 0%; }
    }
    
    .feature-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
    }
    .product-thumb img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
    </style>

    <script>
// Загрузка одного изображения
const fileInput = document.getElementById('productImageInput');
const uploadArea = document.querySelector('.upload-area');
const placeholder = document.querySelector('.upload-placeholder');
const preview = document.querySelector('.upload-preview');
const previewImg = preview?.querySelector('img');
const hiddenInput = document.getElementById('imagePathInput');
const removeBtn = document.querySelector('.remove-image-btn');

if (fileInput) {
    fileInput.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', 'product');
        
        const progressDiv = document.querySelector('.upload-progress');
        if (progressDiv) progressDiv.style.display = 'block';
        
        try {
            const response = await fetch('upload_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (previewImg) previewImg.src = result.url;
                if (placeholder) placeholder.style.display = 'none';
                if (preview) preview.style.display = 'block';
                if (hiddenInput) hiddenInput.value = result.path;
                
                if (removeBtn) {
                    removeBtn.onclick = () => {
                        if (placeholder) placeholder.style.display = 'block';
                        if (preview) preview.style.display = 'none';
                        if (hiddenInput) hiddenInput.value = '';
                        fileInput.value = '';
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

// Добавление характеристик (если нужно)
const addFeatureBtn = document.getElementById('add-feature-btn');
if (addFeatureBtn) {
    addFeatureBtn.addEventListener('click', function() {
        const container = document.getElementById('features-container');
        const newRow = document.createElement('div');
        newRow.className = 'feature-row';
        newRow.innerHTML = `
            <input type="text" class="admin-form-input" name="feature_name[]" placeholder="Название" style="width: 30%;">
            <input type="text" class="admin-form-input" name="feature_value[]" placeholder="Значение" style="width: 65%;">
        `;
        container.appendChild(newRow);
    });
}
</script>

<style>
.image-uploader {
    margin-top: 8px;
}

.upload-area {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.upload-area:hover {
    border-color: #c62828;
    background: rgba(198, 40, 40, 0.05);
}

.upload-placeholder .upload-icon {
    font-size: 48px;
    display: block;
    margin-bottom: 10px;
}

.upload-preview {
    position: relative;
    display: inline-block;
}

.upload-preview img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
}

.remove-image-btn {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #c62828;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-progress {
    margin-top: 10px;
}

.progress-bar {
    height: 4px;
    background: #c62828;
    width: 0%;
    transition: width 0.3s;
    animation: progress 2s infinite;
}

@keyframes progress {
    0% { width: 0%; }
    50% { width: 100%; }
    100% { width: 0%; }
}

.feature-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}
</style>

<?php include 'admin_footer.php'; ?>
