<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Доступ запрещён.');
}

$targetDir = __DIR__ . '/assets/images/';
if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

$message = '';
$uploadedFile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!in_array($extension, $allowed)) {
        $message = '❌ Недопустимый формат. Разрешены: ' . implode(', ', $allowed);
    } else {
        $newFilename = date('Ymd_His') . '_' . uniqid() . '.' . $extension;
        $destination = $targetDir . $newFilename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $message = '✅ Успешно!';
            $uploadedFile = 'assets/images/' . $newFilename;
        } else {
            $message = '❌ Ошибка сохранения. Код: ' . $file['error'];
        }
    }
}

$existingFiles = glob($targetDir . '*');
$existingFiles = array_filter($existingFiles, 'is_file');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Загрузчик изображений</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .warning { background: #fff3cd; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .upload-area { border: 2px dashed #ddd; padding: 30px; text-align: center; cursor: pointer; margin-bottom: 20px; }
        .upload-area:hover { border-color: #c62828; }
        input[type="file"] { display: none; }
        .btn { background: #c62828; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin: 15px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin: 15px 0; }
        .file-item { display: inline-block; margin: 10px; text-align: center; }
        .file-item img { max-width: 100px; max-height: 100px; border-radius: 4px; border: 1px solid #ddd; }
        .copy-btn { background: #28a745; color: white; border: none; padding: 2px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; margin-top: 5px; }
    </style>
</head>
<body>
<div>
    <h1>📸 Загрузчик изображений</h1>
    <div class="warning">⚠️ После использования <strong>удалите этот файл</strong> с сервера!</div>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="upload-area" onclick="document.getElementById('fileInput').click()">
            📁 Нажмите для выбора файла
            <input type="file" name="image" id="fileInput" accept="image/*">
        </div>
        <button type="submit" class="btn">📤 Загрузить</button>
    </form>
    
    <?php if ($message): ?>
        <div class="<?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($uploadedFile): ?>
        <div style="background:#e9ecef; padding:12px; border-radius:4px; margin:15px 0;">
            <strong>Путь:</strong> <code id="filePath"><?= htmlspecialchars($uploadedFile) ?></code>
            <button class="copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('filePath').innerText); alert('Скопировано!')">📋</button>
            <br><br>
            <img src="<?= htmlspecialchars($uploadedFile) ?>" style="max-width: 300px;">
        </div>
    <?php endif; ?>
    
    <hr>
    <strong>📁 Уже загружено:</strong><br>
    <?php foreach ($existingFiles as $file): ?>
        <?php $url = 'assets/images/' . basename($file); ?>
        <div class="file-item">
            <img src="<?= htmlspecialchars($url) ?>">
            <div style="font-size:11px;"><?= htmlspecialchars(basename($file)) ?></div>
            <button class="copy-btn" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($url) ?>'); alert('Скопировано!')">📋 Путь</button>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
