<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

requireLogin();
$user = currentUser();
$db = getDB();
$isTeacher = $user['role'] === 'teacher';

function subjectClass($subject) {
    $map = ['Математика'=>'subject-math','Русский язык'=>'subject-rus',
            'Физика'=>'subject-sci','Биология'=>'subject-sci',
            'История'=>'subject-hist','Химия'=>'subject-sci'];
    return $map[$subject] ?? 'subject-default';
}

function dueLabel($due_date) {
    if (!$due_date) return '';
    $days = (strtotime($due_date) - time()) / 86400;
    if ($days < 0) return '⚠ Просрочено';
    if ($days < 1) return '🔴 Сегодня';
    if ($days < 2) return '🟠 Завтра';
    return '📅 ' . date('d.m.Y', strtotime($due_date));
}

ob_start();

if ($isTeacher) {
    // Teacher stats
    $hwCount   = $db->query("SELECT COUNT(*) FROM homework WHERE teacher_id = {$user['id']}")->fetchColumn();
    $subCount  = $db->query("SELECT COUNT(*) FROM submissions s JOIN homework h ON h.id=s.homework_id WHERE h.teacher_id={$user['id']}")->fetchColumn();
    $gradedCount = $db->query("SELECT COUNT(*) FROM submissions s JOIN homework h ON h.id=s.homework_id WHERE h.teacher_id={$user['id']} AND s.grade IS NOT NULL")->fetchColumn();
    $pendingCount = $subCount - $gradedCount;

    // Recent HW
    $stmt = $db->prepare("SELECT h.*, (SELECT COUNT(*) FROM submissions s WHERE s.homework_id=h.id) as sub_count FROM homework h WHERE h.teacher_id=? ORDER BY h.created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $recentHW = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="topbar">
  <div>
    <div class="topbar-title">Добро пожаловать, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>!</div>
    <div class="topbar-sub"><?= date('l, d F Y', time()) ?></div>
  </div>
  <a href="add_homework.php" class="btn btn-dark">✏️ Новое задание</a>
</div>
<div class="content">
  <div class="stats-grid">
    <div class="stat-card orange">
      <div class="stat-num"><?= $hwCount ?></div>
      <div class="stat-label">Заданий выдано</div>
    </div>
    <div class="stat-card blue">
      <div class="stat-num"><?= $subCount ?></div>
      <div class="stat-label">Ответов получено</div>
    </div>
    <div class="stat-card green">
      <div class="stat-num"><?= $gradedCount ?></div>
      <div class="stat-label">Проверено</div>
    </div>
    <div class="stat-card gray">
      <div class="stat-num"><?= $pendingCount ?></div>
      <div class="stat-label">Ожидают проверки</div>
    </div>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <h2 style="font-family:'Playfair Display',serif;font-size:20px">Последние задания</h2>
    <a href="homework.php" class="btn btn-outline btn-sm">Все задания →</a>
  </div>

  <?php if (empty($recentHW)): ?>
  <div class="card">
    <div class="empty-state">
      <span class="icon">📝</span>
      <h3>Заданий пока нет</h3>
      <p>Создайте первое домашнее задание для учеников</p>
      <br><a href="add_homework.php" class="btn btn-dark">Создать задание</a>
    </div>
  </div>
  <?php else: ?>
  <div class="hw-grid">
    <?php foreach($recentHW as $hw): ?>
    <a class="hw-card" href="homework_detail.php?id=<?= $hw['id'] ?>">
      <div>
        <span class="hw-subject <?= subjectClass($hw['subject']) ?>"><?= htmlspecialchars($hw['subject']) ?></span>
        <div class="hw-title"><?= htmlspecialchars($hw['title']) ?></div>
        <div class="hw-desc"><?= htmlspecialchars($hw['description']) ?></div>
        <div class="hw-meta">
          <span>📬 <?= $hw['sub_count'] ?> ответов</span>
          <?php if ($hw['due_date']): ?><span><?= dueLabel($hw['due_date']) ?></span><?php endif; ?>
          <span>📅 <?= date('d.m.Y', strtotime($hw['created_at'])) ?></span>
        </div>
      </div>
      <div class="hw-actions">
        <span class="badge badge-new">Подробнее →</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php

} else {
    // Student stats
    $totalHW  = $db->query("SELECT COUNT(*) FROM homework")->fetchColumn();
    $mySubs   = $db->query("SELECT COUNT(*) FROM submissions WHERE student_id={$user['id']}")->fetchColumn();
    $myGraded = $db->query("SELECT COUNT(*) FROM submissions WHERE student_id={$user['id']} AND grade IS NOT NULL")->fetchColumn();
    $pending  = $totalHW - $mySubs;

    // Recent homework not yet submitted
    $stmt = $db->prepare("
        SELECT h.*, t.full_name as teacher_name,
               (SELECT id FROM submissions WHERE homework_id=h.id AND student_id=?) as my_sub_id,
               (SELECT grade FROM submissions WHERE homework_id=h.id AND student_id=?) as my_grade
        FROM homework h
        JOIN users t ON t.id = h.teacher_id
        ORDER BY h.created_at DESC LIMIT 6
    ");
    $stmt->execute([$user['id'], $user['id']]);
    $homeworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="topbar">
  <div>
    <div class="topbar-title">Привет, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>!</div>
    <div class="topbar-sub"><?= date('l, d F Y', time()) ?></div>
  </div>
</div>
<div class="content">
  <div class="stats-grid">
    <div class="stat-card orange">
      <div class="stat-num"><?= $totalHW ?></div>
      <div class="stat-label">Всего заданий</div>
    </div>
    <div class="stat-card blue">
      <div class="stat-num"><?= $mySubs ?></div>
      <div class="stat-label">Мои ответы</div>
    </div>
    <div class="stat-card green">
      <div class="stat-num"><?= $myGraded ?></div>
      <div class="stat-label">Проверено</div>
    </div>
    <div class="stat-card gray">
      <div class="stat-num"><?= max(0,$pending) ?></div>
      <div class="stat-label">Не сдано</div>
    </div>
  </div>

  <?php
  // Avg grade
  $avgStmt = $db->prepare("SELECT AVG(grade) FROM submissions WHERE student_id=? AND grade IS NOT NULL");
  $avgStmt->execute([$user['id']]);
  $avg = round($avgStmt->fetchColumn(), 1);
  if ($avg) {
  ?>
  <div class="card card-sm" style="margin-bottom:24px;display:flex;align-items:center;gap:16px">
    <div class="grade-circle grade-<?= floor($avg) ?>"><?= $avg ?></div>
    <div>
      <div style="font-weight:500;font-size:15px">Средний балл</div>
      <div style="font-size:13px;color:var(--muted)">По всем проверенным заданиям</div>
      <div class="progress-bar" style="width:200px">
        <div class="progress-fill" style="width:<?= ($avg/5)*100 ?>%;background:<?= $avg>=4?'var(--green)':($avg>=3?'var(--accent2)':'var(--accent)') ?>"></div>
      </div>
    </div>
  </div>
  <?php } ?>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <h2 style="font-family:'Playfair Display',serif;font-size:20px">Задания</h2>
    <a href="homework.php" class="btn btn-outline btn-sm">Все →</a>
  </div>

  <?php if (empty($homeworks)): ?>
  <div class="card">
    <div class="empty-state">
      <span class="icon">🎉</span>
      <h3>Заданий нет!</h3>
      <p>Пока учитель не задал ни одного задания</p>
    </div>
  </div>
  <?php else: ?>
  <div class="hw-grid">
    <?php foreach($homeworks as $hw): ?>
    <a class="hw-card" href="homework_detail.php?id=<?= $hw['id'] ?>">
      <div>
        <span class="hw-subject <?= subjectClass($hw['subject']) ?>"><?= htmlspecialchars($hw['subject']) ?></span>
        <div class="hw-title"><?= htmlspecialchars($hw['title']) ?></div>
        <div class="hw-desc"><?= htmlspecialchars($hw['description']) ?></div>
        <div class="hw-meta">
          <span>👤 <?= htmlspecialchars($hw['teacher_name']) ?></span>
          <?php if ($hw['due_date']): ?><span><?= dueLabel($hw['due_date']) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="hw-actions">
        <?php if ($hw['my_sub_id']): ?>
          <?php if ($hw['my_grade']): ?>
            <div class="grade-circle grade-<?= $hw['my_grade'] ?>" style="width:36px;height:36px;font-size:16px"><?= $hw['my_grade'] ?></div>
            <span class="badge badge-graded">Оценено</span>
          <?php else: ?>
            <span class="badge badge-done">Сдано ✓</span>
          <?php endif; ?>
        <?php else: ?>
          <span class="badge badge-pending">Не сдано</span>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php
}

$pageContent = ob_get_clean();
echo renderLayout('Главная', 'dashboard', $pageContent);
