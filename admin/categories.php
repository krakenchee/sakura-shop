<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$msg = '';

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
    
    if (empty($string)) $string = 'category';
    return $string;
}

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
    $slug            = trim($_POST['slug']) ?: generateSlug($name);
    $mainCategoryId  = (int)$_POST['main_category_id'];
   
    if (!$name || !$mainCategoryId) {
        $msg = 'Ошибка: заполните название и выберите основную категорию';
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
            <a href="categories.php" class="admin-nav-item active">📂 Категории</a>
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
                        <input type="text" class="admin-form-input" name="name" id="catName" required
                               value="<?= htmlspecialchars($editSubcat['name'] ?? '') ?>"
                               oninput="if(!<?= $editSubcat ? 'true' : 'false' ?>) document.getElementById('slugPreview').value = generateSlugFromName(this.value)">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">Slug (URL)</label>
                        <input type="text" class="admin-form-input" name="slug" id="slugPreview"
                               value="<?= htmlspecialchars($editSubcat['slug'] ?? '') ?>"
                               placeholder="Оставьте пустым для автоматической генерации">
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
                                    <td><?= htmlspecialchars($subcat['name']) ?></td>
                                    <td><code><?= htmlspecialchars($subcat['slug']) ?></code></td>
                                    <td>
                                        <span class="badge <?= $subcat['products_count'] > 0 ? 'badge-info' : 'badge-light' ?>">
                                            <?= $subcat['products_count'] ?> товаров
                                        </span>
                                    </td>
                                    <td>
                                        <div class="admin-actions">
                                            <a href="categories.php?edit=<?= $subcat['id'] ?>" class="admin-btn-action admin-btn-edit">Изменить</a>
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
            
        </main>
    </div>

    <script>
    // Функция генерации slug из названия
    function generateSlugFromName(name) {
        const cyrillic = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts','ч':'ch','ш':'sh','щ':'shch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya',' ':'-','_':'-'};
        let slug = name.toLowerCase().split('').map(ch => cyrillic[ch] || (ch.match(/[a-z0-9]/i) ? ch : '')).join('');
        slug = slug.replace(/-+/g, '-').replace(/^-|-$/g, '');
        return slug || 'category';
    }
    
    // Автогенерация slug при вводе названия (только для новых категорий)
    const catNameInput = document.getElementById('catName');
    const slugInput = document.getElementById('slugPreview');
    const isEdit = <?= $editSubcat ? 'true' : 'false' ?>;
    
    if (catNameInput && slugInput && !isEdit) {
        catNameInput.addEventListener('input', function() {
            slugInput.value = generateSlugFromName(this.value);
        });
    }
    
    </script>

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
