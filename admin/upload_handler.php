<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

// Диагностика
$logFile = '/tmp/upload_debug.log';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Неверный метод']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'Файл не загружен']);
    exit;
}

$file = $_FILES['file'];
$type = $_POST['type'] ?? 'product';

file_put_contents($logFile, date('Y-m-d H:i:s') . " - Запрос: type=$type, file={$file['name']}, tmp={$file['tmp_name']}\n", FILE_APPEND);

if ($file['error'] !== UPLOAD_ERR_OK) {
    file_put_contents($logFile, "Ошибка загрузки: " . $file['error'] . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Ошибка загрузки файла']);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    file_put_contents($logFile, "Недопустимый тип: " . $file['type'] . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Разрешены только JPG, PNG, WEBP, GIF']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Максимальный размер 5MB']);
    exit;
}

$uploadDir = dirname(__DIR__) . '/assets/uploads/' . $type . 's/';
file_put_contents($logFile, "Папка загрузки: $uploadDir\n", FILE_APPEND);

if (!file_exists($uploadDir)) {
    $created = mkdir($uploadDir, 0777, true);
    file_put_contents($logFile, "Создаём папку: " . ($created ? 'успешно' : 'ошибка') . "\n", FILE_APPEND);
}

// Проверка прав на папку
if (!is_writable($uploadDir)) {
    file_put_contents($logFile, "ПАПКА НЕ ДОСТУПНА ДЛЯ ЗАПИСИ!\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Папка недоступна для записи: ' . $uploadDir]);
    exit;
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = uniqid() . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

file_put_contents($logFile, "Сохраняем как: $filepath\n", FILE_APPEND);

if (move_uploaded_file($file['tmp_name'], $filepath)) {
    $relativePath = 'assets/uploads/' . $type . 's/' . $filename;
    file_put_contents($logFile, "УСПЕХ! Путь: $relativePath\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'path' => $relativePath,
        'filename' => $filename,
        'url' => BASE_URL . $relativePath
    ]);
} else {
    $error = error_get_last();
    file_put_contents($logFile, "ОШИБКА ПРИ СОХРАНЕНИИ: " . print_r($error, true) . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Ошибка сохранения файла']);
}
