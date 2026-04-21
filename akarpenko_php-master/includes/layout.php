<?php
function renderLayout($pageTitle, $activeNav, $content) {
    $user = currentUser();
    $isTeacher = $user['role'] === 'teacher';
    $initials = mb_strtoupper(mb_substr($user['full_name'], 0, 1, 'UTF-8'), 'UTF-8');
    $roleLabel = $isTeacher ? 'Учитель' : 'Ученик';
    $avatarClass = $isTeacher ? 'teacher-av' : 'student-av';

    ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — SchoolDesk</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <span class="logo-icon">📚</span>
      <h1>SchoolDesk</h1>
      <p>Электронный дневник</p>
    </div>
    <div class="sidebar-user">
      <div class="avatar <?= $avatarClass ?>"><?= $initials ?></div>
      <div class="user-info">
        <div class="name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="role"><?= $roleLabel ?></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">Навигация</div>
      <a href="dashboard.php" class="nav-link <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
        <span class="icon">🏠</span> Главная
        <?php if ($activeNav === 'dashboard'): ?><span class="icon-dot"></span><?php endif; ?>
      </a>
      <a href="homework.php" class="nav-link <?= $activeNav === 'homework' ? 'active' : '' ?>">
        <span class="icon">📋</span> Задания
        <?php if ($activeNav === 'homework'): ?><span class="icon-dot"></span><?php endif; ?>
      </a>

      <?php if ($isTeacher): ?>
      <div class="nav-section">Учитель</div>
      <a href="add_homework.php" class="nav-link <?= $activeNav === 'add_homework' ? 'active' : '' ?>">
        <span class="icon">✏️</span> Добавить задание
        <?php if ($activeNav === 'add_homework'): ?><span class="icon-dot"></span><?php endif; ?>
      </a>
      <a href="submissions.php" class="nav-link <?= $activeNav === 'submissions' ? 'active' : '' ?>">
        <span class="icon">📬</span> Ответы учеников
        <?php if ($activeNav === 'submissions'): ?><span class="icon-dot"></span><?php endif; ?>
      </a>
      <?php else: ?>
      <div class="nav-section">Ученик</div>
      <a href="my_submissions.php" class="nav-link <?= $activeNav === 'my_submissions' ? 'active' : '' ?>">
        <span class="icon">✅</span> Мои ответы
        <?php if ($activeNav === 'my_submissions'): ?><span class="icon-dot"></span><?php endif; ?>
      </a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn">
        <span>🚪</span> Выйти
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <?= $content ?>
  </div>
</div>
</body>
</html>
<?php
    return ob_get_clean();
}
