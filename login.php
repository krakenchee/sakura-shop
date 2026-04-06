<?php
// login.php
require_once 'config.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Заполните все поля';
    } else {
        $db = getDB();
        $st = $db->prepare("SELECT * FROM users WHERE email = ?");
        $st->execute([$email]);
        $user = $st->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id'        => $user['id'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'role'      => $user['role'],
            ];
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Неверный email или пароль';
        }
    }
}

$pageTitle = 'Вход — Sakura Shop';
include 'header.php';
?>

<div style="min-height:60vh;display:flex;align-items:center;padding:60px 0;">
  <div class="container" style="max-width:440px;margin:0 auto;">

    <div style="text-align:center;margin-bottom:32px;">
      <div style="font-size:3rem;margin-bottom:12px;">⛩</div>
      <h1 style="font-family:var(--font-serif);font-size:1.8rem;color:var(--crimson-deep);margin-bottom:8px;">Добро пожаловать</h1>
      <p style="color:var(--charcoal-light);font-size:0.9rem;">いらっしゃいませ</p>
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
          <label class="form-label">Email</label>
          <input type="email" class="form-input" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required autocomplete="email" placeholder="your@email.com">
        </div>
        <div class="form-group">
          <label class="form-label">Пароль</label>
          <input type="password" class="form-input" name="password" required autocomplete="current-password" placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">Войти</button>
      </form>
      <div style="text-align:center;margin-top:20px;font-size:0.875rem;color:var(--charcoal-light);">
        Нет аккаунта? <a href="register.php" style="color:var(--crimson);">Зарегистрироваться</a>
      </div>
    </div>

  </div>
</div>

<?php include 'footer.php'; ?>
