<?php
require_once '../config.php';
requireAdmin();

// Функция для генерации slug без intl
function generateSlug($string) {
    // Преобразуем кириллицу в латиницу
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
    
    // Убираем всё, кроме букв, цифр и пробелов
    $string = preg_replace('/[^a-z0-9\s-]/i', '', $string);
    
    // Заменяем пробелы и подчеркивания на дефисы
    $string = preg_replace('/[\s_]+/', '-', $string);
    
    // Убираем повторяющиеся дефисы
    $string = preg_replace('/-+/', '-', $string);
    
    // Приводим к нижнему регистру
    $string = strtolower($string);
    
    // Убираем дефисы в начале и конце
    $string = trim($string, '-');
    
    // Если получилась пустая строка, используем 'product'
    if (empty($string)) {
        $string = 'product';
    }
    
    return $string;
}

$db = getDB();
$msg = '';
$error = '';

// Получаем подкатегории с группировкой по основным категориям
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
    
    $st = $db->prepare("SELECT name FROM products WHERE id = ?");
    $st->execute([$id]);
    $productName = $st->fetchColumn();
    
    if ($productName) {
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $msg = 'Товар "' . htmlspecialchars($productName) . '" удален';
    } else {
        $msg = 'Товар не найден';
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

    if (!$name || !$categoryId || $price <= 0) {
        $error = 'Заполните обязательные поля';
    } else {
        if ($id) {
            $db->prepare("UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, old_price=?, stock_quantity=? WHERE id=?")
               ->execute([$categoryId, $name, $slug, $description, $price, $oldPrice, $stock, $id]);
            $msg = 'Товар обновлён';
        } else {
            $db->prepare("INSERT INTO products (category_id, name, slug, description, price, old_price, stock_quantity) VALUES (?,?,?,?,?,?,?)")
               ->execute([$categoryId, $name, $slug, $description, $price, $oldPrice, $stock]);
            $newId = $db->lastInsertId();

            $imagePath = trim($_POST['image_path'] ?? '');
            if ($imagePath) {
                $db->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?,?,1)")->execute([$newId, $imagePath]);
            }

            $msg = 'Товар добавлен';
        }
        header('Location: products.php?msg=' . urlencode($msg));
        exit;
    }
}

// Сохранение характеристик
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
$productFeatures = [];
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM products WHERE id = ?");
    $st->execute([$_GET['edit']]);
    $editProduct = $st->fetch();
    if ($editProduct) {
        $st = $db->prepare("SELECT * FROM product_features WHERE product_id = ?");
        $st->execute([$editProduct['id']]);
        $productFeatures = $st->fetchAll();
    }
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

// Список товаров с автоматическими статусами
$products = $db->query("
    SELECT p.*, c.name as cat_name
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

                <?php if (!$editProduct): ?>
                <div class="admin-form-group">
                    <label class="admin-form-label">URL главного изображения</label>
                    <input type="text" class="admin-form-input" name="image_path" placeholder="assets/uploads/products/product.jpg">
                </div>
                <?php endif; ?>

                <!-- Автоматические статусы (только для просмотра) -->
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
                        <div class="status-badge-auto status-normal">
                            📅 Не новинка
                        </div>
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
                    <div class="admin-form-help">
                        <small>⭐ Новинка: автоматически для товаров младше 30 дней</small><br>
                        <small>🔥 Хит: автоматически при 10+ заказов за последние 30 дней</small>
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
                                <td><?= htmlspecialchars($p['name']) ?> </td>
                                <td class="admin-text-small"><?= htmlspecialchars($p['cat_name'] ?? '—') ?> </td>
                                <td><?= number_format($p['price'], 0, ',', ' ') ?> ₽</td>
                                <td><?= $p['stock_quantity'] ?> </td>
                                <td>
                                    <?php if ($p['is_new']): ?>
                                    <span class="badge badge-new" title="Добавлен: <?= date('d.m.Y', strtotime($p['created_at'])) ?>">
                                        ✨ NEW
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($p['is_popular']): ?>
                                    <span class="badge badge-popular" title="<?= $p['order_count_30d'] ?> заказов за 30 дней">
                                        🔥 HIT (<?= $p['order_count_30d'] ?>)
                                    </span>
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
                                           onclick="return confirm('Удалить товар «<?= htmlspecialchars($p['name']) ?>»?\n\nИнформация о товаре сохранится в завершенных заказах.')">
                                           Удалить
                                        </a>
                                    </div>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($products)): ?>
                             <tr>
                                <td colspan="7" class="empty-table">
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
    .status-badge-auto.status-new {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .status-badge-auto.status-hit {
        background: #fff3e0;
        color: #f57c00;
    }
    .status-badge-auto.status-normal {
        background: #f5f5f5;
        color: #757575;
    }
    .status-hint {
        font-size: 11px;
        font-weight: normal;
        opacity: 0.7;
    }
    .badge-new {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .badge-popular {
        background: #fff3e0;
        color: #f57c00;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .badge-light {
        background: #f5f5f5;
        color: #9e9e9e;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
    }
    .admin-form-help {
        font-size: 12px;
        color: #6c757d;
        margin-top: 8px;
    }
    </style>

    <?php include 'admin_footer.php'; ?>