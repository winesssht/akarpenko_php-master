<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

requireLogin();
$user = currentUser();
$db = getDB();
$isTeacher = $user['role'] === 'teacher';

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

function subjectClass($s) {
    $map=['Математика'=>'subject-math','Русский язык'=>'subject-rus','Физика'=>'subject-sci','Биология'=>'subject-sci','История'=>'subject-hist','Химия'=>'subject-sci'];
    return $map[$s]??'subject-default';
}

function dueLabel($due_date) {
    if (!$due_date) return '';
    $days = (strtotime($due_date) - time()) / 86400;
    if ($days < 0) return '<span style="color:var(--accent)">⚠ Просрочено</span>';
    if ($days < 1) return '<span style="color:var(--accent)">🔴 Сегодня</span>';
    if ($days < 2) return '<span style="color:#b08000">🟠 Завтра</span>';
    return '📅 ' . date('d.m.Y', strtotime($due_date));
}

if ($isTeacher) {
    $sql = "SELECT h.*, (SELECT COUNT(*) FROM submissions s WHERE s.homework_id=h.id) as sub_count FROM homework h WHERE h.teacher_id=?";
    $params = [$user['id']];
} else {
    $sql = "SELECT h.*, t.full_name as teacher_name,
            (SELECT id FROM submissions WHERE homework_id=h.id AND student_id=?) as my_sub_id,
            (SELECT grade FROM submissions WHERE homework_id=h.id AND student_id=?) as my_grade
            FROM homework h JOIN users t ON t.id=h.teacher_id WHERE 1=1";
    $params = [$user['id'], $user['id']];
}

if ($search) {
    $sql .= " AND (h.title LIKE ? OR h.subject LIKE ? OR h.description LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

if (!$isTeacher && $filter === 'pending') {
    $sql .= " AND (SELECT id FROM submissions WHERE homework_id=h.id AND student_id={$user['id']}) IS NULL";
} elseif (!$isTeacher && $filter === 'done') {
    $sql .= " AND (SELECT id FROM submissions WHERE homework_id=h.id AND student_id={$user['id']}) IS NOT NULL";
}

$sql .= " ORDER BY h.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$homeworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="topbar">
  <div>
    <div class="topbar-title">Задания</div>
    <div class="topbar-sub"><?= count($homeworks) ?> заданий</div>
  </div>
  <?php if ($isTeacher): ?>
  <a href="add_homework.php" class="btn btn-dark">✏️ Добавить</a>
  <?php endif; ?>
</div>
<div class="content">
  <!-- Search & Filter -->
  <div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;align-items:center">
    <form method="GET" style="display:flex;gap:8px;flex:1;min-width:200px">
      <input class="form-control" style="padding:10px 14px" name="q" placeholder="🔍 Поиск заданий..." value="<?= htmlspecialchars($search) ?>">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <button type="submit" class="btn btn-dark btn-sm">Найти</button>
      <?php if ($search): ?><a href="homework.php" class="btn btn-outline btn-sm">✕</a><?php endif; ?>
    </form>
    <?php if (!$isTeacher): ?>
    <div style="display:flex;gap:6px">
      <a href="?filter=all<?= $search?"&q=".urlencode($search):'' ?>" class="btn btn-sm <?= $filter==='all'?'btn-dark':'btn-outline' ?>">Все</a>
      <a href="?filter=pending<?= $search?"&q=".urlencode($search):'' ?>" class="btn btn-sm <?= $filter==='pending'?'btn-dark':'btn-outline' ?>">Не сдано</a>
      <a href="?filter=done<?= $search?"&q=".urlencode($search):'' ?>" class="btn btn-sm <?= $filter==='done'?'btn-dark':'btn-outline' ?>">Сдано</a>
    </div>
    <?php endif; ?>
  </div>

  <?php if (empty($homeworks)): ?>
  <div class="card">
    <div class="empty-state">
      <span class="icon">📭</span>
      <h3>Заданий не найдено</h3>
      <p>Попробуйте изменить параметры поиска</p>
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
          <?php if ($isTeacher): ?>
            <span>📬 <?= $hw['sub_count'] ?> ответов</span>
          <?php else: ?>
            <span>👤 <?= htmlspecialchars($hw['teacher_name']) ?></span>
          <?php endif; ?>
          <?php if ($hw['due_date']): ?><span><?= dueLabel($hw['due_date']) ?></span><?php endif; ?>
          <span>🗓 <?= date('d.m.Y', strtotime($hw['created_at'])) ?></span>
        </div>
      </div>
      <div class="hw-actions">
        <?php if ($isTeacher): ?>
          <span class="badge badge-new">Открыть →</span>
        <?php elseif ($hw['my_sub_id']): ?>
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
$pageContent = ob_get_clean();
echo renderLayout('Задания', 'homework', $pageContent);
