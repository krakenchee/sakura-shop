<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

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

// Проверка ошибок
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Ошибка загрузки файла']);
    exit;
}

// Проверка типа
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Разрешены только JPG, PNG, WEBP, GIF']);
    exit;
}

// Проверка размера (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Максимальный размер 5MB']);
    exit;
}

// Создаём папку
$uploadDir = dirname(__DIR__) . '/assets/uploads/' . $type . 's/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Уникальное имя
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = uniqid() . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Сохраняем
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    $relativePath = 'assets/uploads/' . $type . 's/' . $filename;
    
    echo json_encode([
        'success' => true,
        'path' => $relativePath,
        'filename' => $filename,
        'url' => BASE_URL . $relativePath
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ошибка сохранения файла']);
}
