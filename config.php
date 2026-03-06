<?php
// ============================================================
// Конфигурация подключения к базе данных
// Измените параметры под ваш хостинг/сервер
// ============================================================

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');          // ваш пользователь MySQL
define('DB_PASS', '');              // ваш пароль MySQL
define('DB_NAME', 'sakura_shop');
define('DB_CHARSET', 'utf8mb4');

// Создание PDO-подключения
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Ошибка подключения к БД: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Базовый URL сайта
define('BASE_URL', 'http://localhost/japan/');
define('SITE_NAME', 'Sakura Shop');

// Ограничения сессии
session_start();

// Хелпер: текущий пользователь
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool {
    return (currentUser()['role'] ?? '') === 'admin';
}

function isLoggedIn(): bool {
    return currentUser() !== null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

// CSRF token
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Недействительный CSRF-токен');
    }
}
