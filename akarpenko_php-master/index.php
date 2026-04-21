<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

startSession();

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    } else {
        $error = 'Заполните все поля';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SchoolDesk — Вход</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #0f0e17;
    --paper: #fffcf5;
    --cream: #f5f0e8;
    --accent: #e8572a;
    --accent2: #2a6be8;
    --muted: #7a7570;
    --border: #d8d0c4;
    --shadow: rgba(15,14,23,.12);
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
  }

  /* Decorative background */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
      radial-gradient(ellipse 60% 50% at 10% 20%, rgba(232,87,42,.08) 0%, transparent 60%),
      radial-gradient(ellipse 50% 60% at 90% 80%, rgba(42,107,232,.07) 0%, transparent 60%);
    pointer-events: none;
  }

  .grid-bg {
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(var(--border) 1px, transparent 1px),
      linear-gradient(90deg, var(--border) 1px, transparent 1px);
    background-size: 40px 40px;
    opacity: .3;
    pointer-events: none;
  }

  .login-wrap {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 440px;
    padding: 24px;
  }

  .brand {
    text-align: center;
    margin-bottom: 40px;
  }

  .brand-icon {
    width: 64px;
    height: 64px;
    background: var(--ink);
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 16px;
    box-shadow: 6px 6px 0 var(--accent);
  }

  .brand h1 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    color: var(--ink);
    letter-spacing: -1px;
    line-height: 1;
  }

  .brand p {
    color: var(--muted);
    font-size: 14px;
    margin-top: 6px;
    font-weight: 300;
  }

  .card {
    background: var(--paper);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 2px 0 var(--border), 0 20px 60px var(--shadow);
    border: 1px solid var(--border);
  }

  .card h2 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    color: var(--ink);
    margin-bottom: 28px;
  }

  .field {
    margin-bottom: 20px;
  }

  .field label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 8px;
  }

  .field input {
    width: 100%;
    padding: 14px 16px;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    background: var(--cream);
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    color: var(--ink);
    transition: border-color .2s, box-shadow .2s;
    outline: none;
  }

  .field input:focus {
    border-color: var(--ink);
    box-shadow: 0 0 0 3px rgba(15,14,23,.07);
    background: var(--paper);
  }

  .btn-primary {
    width: 100%;
    padding: 15px;
    background: var(--ink);
    color: var(--paper);
    border: none;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 3px 3px 0 var(--accent);
    margin-top: 8px;
    letter-spacing: .02em;
  }

  .btn-primary:hover {
    transform: translate(-1px, -1px);
    box-shadow: 4px 4px 0 var(--accent);
  }

  .btn-primary:active {
    transform: translate(1px, 1px);
    box-shadow: 2px 2px 0 var(--accent);
  }

  .error-box {
    background: rgba(232,87,42,.1);
    border: 1.5px solid rgba(232,87,42,.3);
    border-radius: 10px;
    padding: 12px 16px;
    color: var(--accent);
    font-size: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .demo-hint {
    margin-top: 24px;
    padding: 16px;
    background: var(--cream);
    border-radius: 10px;
    border: 1px dashed var(--border);
  }

  .demo-hint p {
    font-size: 12px;
    color: var(--muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 8px;
  }

  .demo-hint .accounts {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .demo-hint .account-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--ink);
  }

  .demo-hint .role-badge {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 500;
  }

  .role-badge.teacher { background: rgba(232,87,42,.15); color: var(--accent); }
  .role-badge.student { background: rgba(42,107,232,.12); color: var(--accent2); }

  .fill-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 11px;
    color: var(--muted);
    text-decoration: underline;
    padding: 0;
    font-family: 'DM Sans', sans-serif;
  }

  .fill-btn:hover { color: var(--ink); }
</style>
</head>
<body>
<div class="grid-bg"></div>
<div class="login-wrap">
  <div class="brand">
    <div class="brand-icon">📚</div>
    <h1>SchoolDesk</h1>
    <p>Электронный дневник</p>
  </div>

  <div class="card">
    <h2>Войти в систему</h2>

    <?php if ($error): ?>
    <div class="error-box">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>Логин</label>
        <input type="text" name="username" id="uname" autocomplete="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Введите логин">
      </div>
      <div class="field">
        <label>Пароль</label>
        <input type="password" name="password" id="upass" autocomplete="current-password" placeholder="••••••••">
      </div>
      <button type="submit" class="btn-primary">Войти →</button>
    </form>

    <div class="demo-hint">
      <p>Демо-аккаунты</p>
      <div class="accounts">
        <div class="account-row">
          <span><b>teacher</b> / teacher123</span>
          <span style="display:flex;gap:6px;align-items:center">
            <span class="role-badge teacher">Учитель</span>
            <button class="fill-btn" onclick="fillLogin('teacher','teacher123')">Войти</button>
          </span>
        </div>
        <div class="account-row">
          <span><b>student1</b> / student123</span>
          <span style="display:flex;gap:6px;align-items:center">
            <span class="role-badge student">Ученик</span>
            <button class="fill-btn" onclick="fillLogin('student1','student123')">Войти</button>
          </span>
        </div>
        <div class="account-row">
          <span><b>student2</b> / student123</span>
          <span style="display:flex;gap:6px;align-items:center">
            <span class="role-badge student">Ученик</span>
            <button class="fill-btn" onclick="fillLogin('student2','student123')">Войти</button>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function fillLogin(u, p) {
  document.getElementById('uname').value = u;
  document.getElementById('upass').value = p;
}
</script>
</body>
</html>
