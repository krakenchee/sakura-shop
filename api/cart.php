<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$db = getDB();
$userId = currentUser()['id'];
$action = $_GET['action'] ?? '';

function getCartCount($db, $userId): int {
    $st = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?");
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}

function getCartTotal($db, $userId): float {
    $st = $db->prepare("
        SELECT COALESCE(SUM(p.price * ci.quantity), 0)
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.user_id = ?
    ");
    $st->execute([$userId]);
    return (float)$st->fetchColumn();
}

try {
    verifyCsrf();
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Недействительный CSRF-токен']);
    exit;
}

if ($action === 'add') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity  = max(1, (int)($_POST['quantity'] ?? 1));

    $st = $db->prepare("SELECT id, stock_quantity FROM products WHERE id = ? AND stock_quantity > 0");
    $st->execute([$productId]);
    $product = $st->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Товар недоступен']);
        exit;
    }

    // Проверить, есть ли уже в корзине
    $st = $db->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
    $st->execute([$userId, $productId]);
    $existing = $st->fetch();

    if ($existing) {
        $newQty = min($existing['quantity'] + $quantity, $product['stock_quantity']);
        $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?")->execute([$newQty, $existing['id']]);
    } else {
        $qty = min($quantity, $product['stock_quantity']);
        $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?,?,?)")->execute([$userId, $productId, $qty]);
    }

    echo json_encode([
        'success'    => true,
        'cart_count' => getCartCount($db, $userId),
        'total'      => getCartTotal($db, $userId),
    ]);

} elseif ($action === 'remove') {
    $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
    $st = $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $st->execute([$cartItemId, $userId]);

    echo json_encode([
        'success'    => true,
        'cart_count' => getCartCount($db, $userId),
        'total'      => getCartTotal($db, $userId),
    ]);

} elseif ($action === 'update') {
    $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
    $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

    // Получить цену для расчёта item_total
    $st = $db->prepare("SELECT ci.id, p.price, p.stock_quantity FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.id = ? AND ci.user_id = ?");
    $st->execute([$cartItemId, $userId]);
    $item = $st->fetch();

    if ($item) {
        $qty = min($quantity, $item['stock_quantity']);
        $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$qty, $cartItemId, $userId]);

        echo json_encode([
            'success'    => true,
            'cart_count' => getCartCount($db, $userId),
            'total'      => getCartTotal($db, $userId),
            'item_total' => $item['price'] * $qty,
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Элемент не найден']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}
