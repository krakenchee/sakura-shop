<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

// Удаление подкатегории
if (isset($_GET['delete'])) {
    verifyCsrf();
    $id = (int)$_GET['delete'];
    try {
        $check = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check->execute([$id]);
        $productsCount = $check->fetchColumn();
        
        if ($productsCount > 0) {
            $msg = "Категория удалена вместе с {$productsCount} товарами";
        } else {
            $msg = 'Категория удалена';
        }
        
        $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    } catch (Exception $e) {
        $msg = 'Ошибка при удалении: ' . $e->getMessage();
    }
    header('Location: categories.php?msg=' . urlencode($msg));
    exit;
}

// Сохранение подкатегории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subcat'])) {
    verifyCsrf();
    $id              = (int)($_POST['id'] ?? 0);
    $name            = trim($_POST['name']);
    $slug            = trim($_POST['slug']);
    $mainCategoryId  = (int)$_POST['main_category_id'];
    $image           = trim($_POST['image'] ?? '');

    if (!$name || !$slug || !$mainCategoryId) {
        $msg = 'Ошибка: заполните название, slug и выберите основную категорию';
    } else {
        $checkSlug = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $checkSlug->execute([$slug, $id]);
        if ($checkSlug->fetch()) {
            $msg = 'Ошибка: такой slug уже существует';
        } else {
            if ($id) {
                $db->prepare("UPDATE categories SET name=?, slug=?, main_category_id=?, image=? WHERE id=?")
                   ->execute([$name, $slug, $mainCategoryId, $image ?: null, $id]);
                $msg = 'Подкатегория обновлена';
            } else {
                $db->prepare("INSERT INTO categories (name, slug, main_category_id, image) VALUES (?,?,?,?)")
                   ->execute([$name, $slug, $mainCategoryId, $image ?: null]);
                $msg = 'Подкатегория добавлена';
            }
        }
    }
    header('Location: categories.php?msg=' . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

$editSubcat = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $st->execute([$_GET['edit']]);
    $editSubcat = $st->fetch();
}

$mainCategories = $db->query("SELECT * FROM main_categories ORDER BY id")->fetchAll();

$subcategories = $db->query("
    SELECT c.*, mc.name as main_cat_name, mc.slug as main_cat_slug,
           (SELECT COUNT(*) FROM products WHERE category_id = c.id) as products_count
    FROM categories c
    LEFT JOIN main_categories mc ON mc.id = c.main_category_id
    ORDER BY mc.id, c.name
")->fetchAll();

$pageTitle = 'Подкатегории — Admin';
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
            <a href="categories.php" class="admin-nav-item active">📂 Подкатегории</a>
            <a href="banners.php" class="admin-nav-item">🖼 Баннеры</a>
            <a href="users.php" class="admin-nav-item">👥 Пользователи</a>
            <a href="feedback.php" class="admin-nav-item">✉️ Обратная связь</a>
            <div class="admin-nav-divider"></div>
            <a href="<?= BASE_URL ?>index.php" class="admin-nav-item">🌐 На сайт</a>
            <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item">🚪 Выйти</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-page-title">Управление подкатегориями</div>

        <?php if ($msg): ?>
        <div class="admin-message success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="admin-form-card">
            <h2 class="admin-form-title">
                <?= $editSubcat ? 'Редактирование подкатегории: ' . htmlspecialchars($editSubcat['name']) : 'Добавить подкатегорию' ?>
            </h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="save_subcat" value="1">
                <?php if ($editSubcat): ?><input type="hidden" name="id" value="<?= $editSubcat['id'] ?>"><?php endif; ?>

                <div class="admin-form-grid">
                    <div class="admin-form-group">
                        <label class="admin-form-label">Название подкатегории *</label>
                        <input type="text" class="admin-form-input" name="name" required
                               value="<?= htmlspecialchars($editSubcat['name'] ?? '') ?>">
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">Slug (URL) *</label>
                        <input type="text" class="admin-form-input" name="slug" required
                               value="<?= htmlspecialchars($editSubcat['slug'] ?? '') ?>"
                               placeholder="napr-mochi, uhod-za-licom">
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">Основная категория *</label>
                        <select name="main_category_id" class="admin-form-select" required>
                            <option value="">— Выберите основную категорию —</option>
                            <?php foreach ($mainCategories as $mc): ?>
                            <option value="<?= $mc['id'] ?>" <?= ($editSubcat['main_category_id'] ?? 0) == $mc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mc['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="admin-form-help">Подкатегория будет отображаться в выбранной основной категории</small>
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">URL изображения</label>
                        <input type="text" class="admin-form-input" name="image"
                               value="<?= htmlspecialchars($editSubcat['image'] ?? '') ?>"
                               placeholder="assets/images/categories/...">
                    </div>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        <?= $editSubcat ? 'Сохранить' : 'Добавить подкатегорию' ?>
                    </button>
                    <?php if ($editSubcat): ?>
                    <a href="categories.php" class="btn btn-secondary">Отмена</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Все подкатегории</h2>
                <p class="admin-card-subtitle">Подкатегории привязаны к основным категориям</p>
            </div>
            
            <div class="admin-table-responsive">
                <?php if (empty($subcategories)): ?>
                <div class="empty-state">
                    <span class="empty-icon">📂</span>
                    <p>Подкатегорий не найдено</p>
                </div>
                <?php else: ?>
                
                <?php 
                $grouped = [];
                foreach ($subcategories as $subcat) {
                    $grouped[$subcat['main_cat_name'] ?? 'Без категории'][] = $subcat;
                }
                ?>
                
                <?php foreach ($grouped as $mainCatName => $subcats): ?>
                <div class="category-group">
                    <h3 class="category-group-title"><?= htmlspecialchars($mainCatName) ?></h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 50px">ID</th>
                                <th>Название</th>
                                <th>Slug</th>
                                <th>Товаров</th>
                                <th style="width: 120px">Действия</th>
                            </thead>
                            <tbody>
                                <?php foreach ($subcats as $subcat): ?>
                                <tr>
                                    <td><?= $subcat['id'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($subcat['name']) ?>
                                        <?php if ($subcat['image']): ?>
                                        <span class="admin-badge badge-sm">🖼</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($subcat['slug']) ?></code></td>
                                    <td>
                                        <span class="badge <?= $subcat['products_count'] > 0 ? 'badge-info' : 'badge-light' ?>">
                                            <?= $subcat['products_count'] ?> товаров
                                        </span>
                                    </td>
                                    <td>
                                        <div class="admin-actions">
                                            <a href="categories.php?edit=<?= $subcat['id'] ?>" 
                                               class="admin-btn-action admin-btn-edit">Изменить</a>
                                            <a href="categories.php?delete=<?= $subcat['id'] ?>&csrf_token=<?= csrfToken() ?>"
                                               class="admin-btn-action admin-btn-delete"
                                               onclick="return confirm('Удалить подкатегорию «<?= htmlspecialchars($subcat['name']) ?>»?\n\nВсе товары в этой подкатегории будут также удалены!')">
                                               Удалить
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="admin-info-card" style="margin-top: 20px; background: #fff3e0; border-left: 4px solid #ff9800;">
                <h4 style="margin: 0 0 8px 0;">⚠️ Важно: удаление подкатегорий</h4>
                <p style="margin: 0; font-size: 14px;">
                    При удалении подкатегории автоматически удаляются <strong>все товары</strong>, находящиеся в ней.
                    Информация об удаленных товарах сохраняется в завершенных заказах (название и цена на момент покупки).
                </p>
            </div>
        </main>
    </div>

    <style>
    .category-group {
        margin-bottom: 30px;
    }
    .category-group-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        padding: 12px 16px;
        background: #f8f9fa;
        border-radius: 8px;
        margin: 0 0 12px 0;
    }
    .admin-badge {
        display: inline-block;
        padding: 2px 6px;
        font-size: 12px;
        border-radius: 4px;
    }
    .badge-sm {
        font-size: 11px;
        margin-left: 6px;
    }
    .badge-info {
        background: #e3f2fd;
        color: #1976d2;
    }
    .badge-light {
        background: #f5f5f5;
        color: #757575;
    }
    .admin-card-subtitle {
        font-size: 13px;
        color: #6c757d;
        margin-top: 4px;
    }
    .admin-info-card {
        background: #fff3e0;
        border-radius: 12px;
        padding: 16px 20px;
    }
    .admin-form-help {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
        display: block;
    }
    </style>

    <?php include 'admin_footer.php'; ?>