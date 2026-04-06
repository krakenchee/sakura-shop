<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не разрешён']);
    exit;
}

try {
    verifyCsrf();
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Недействительный CSRF-токен']);
    exit;
}

$name    = trim($_POST['name'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$contact || !$message) {
    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
    exit;
}

// Определить email или телефон
$email = filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : null;
$phone = $email ? null : $contact;

try {
    $db = getDB();
    $st = $db->prepare("INSERT INTO feedback_messages (name, email, phone, message) VALUES (?,?,?,?)");
    $st->execute([$name, $email, $phone, $message]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении']);
}
