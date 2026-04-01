<?php
require_once 'config.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$fullName || !$email || !$password) {
        $error = 'Заполните все обязательные поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } else {
        $db = getDB();
        $st = $db->prepare("SELECT id FROM users WHERE email = ?");
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = 'Пользователь с таким email уже существует';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $db->prepare("INSERT INTO users (email, phone, password_hash, full_name, role) VALUES (?,?,?,?,'client')");
            $st->execute([$email, $phone, $hash, $fullName]);
            $userId = $db->lastInsertId();
            $_SESSION['user'] = [
                'id'        => $userId,
                'full_name' => $fullName,
                'email'     => $email,
                'role'      => 'client',
            ];
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Регистрация — Sakura Shop';
include 'header.php';
?>

<div style="min-height:60vh;display:flex;align-items:center;padding:60px 0;">
  <div class="container" style="max-width:480px;margin:0 auto;">

    <div style="text-align:center;margin-bottom:32px;">
      <div style="font-size:3rem;margin-bottom:12px;">🌸</div>
      <h1 style="font-family:var(--font-serif);font-size:1.8rem;color:var(--crimson-deep);margin-bottom:8px;">Регистрация</h1>
      <p style="color:var(--charcoal-light);font-size:0.9rem;">ようこそ — Добро пожаловать в Sakura Shop</p>
    </div>

    <?php if ($error): ?>
    <div style="background:rgba(139,0,0,0.08);border:1px solid rgba(139,0,0,0.2);border-radius:4px;padding:12px 16px;color:var(--crimson);margin-bottom:20px;font-size:0.875rem;">
      ⚠ <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div style="background:var(--white);border-radius:8px;padding:36px;box-shadow:var(--shadow-md);border:1px solid rgba(139,0,0,0.08);">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
          <label class="form-label">Имя и фамилия *</label>
          <input type="text" class="form-input" name="full_name"
                 value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                 required placeholder="Иванова Мария Петровна">
        </div>

        <div class="form-group">
          <label class="form-label">Email *</label>
          <input type="email" class="form-input" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required placeholder="your@email.com">
        </div>

        <div class="form-group">
          <label class="form-label">Телефон</label>
          <input type="tel" class="form-input" name="phone"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                 placeholder="+7 (999) 000-00-00">
        </div>

        <div class="form-group">
          <label class="form-label">Пароль * (минимум 6 символов)</label>
          <input type="password" class="form-input" name="password" required placeholder="••••••••">
        </div>

        <div class="form-group">
          <label class="form-label">Повторите пароль *</label>
          <input type="password" class="form-input" name="confirm_password" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">Зарегистрироваться</button>
      </form>

      <div style="text-align:center;margin-top:20px;font-size:0.875rem;color:var(--charcoal-light);">
        Уже есть аккаунт? <a href="login.php" style="color:var(--crimson);">Войти</a>
      </div>
    </div>

  </div>
</div>

<?php include 'footer.php'; ?>
