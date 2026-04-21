<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

requireRole('student');
$user = currentUser();
$db = getDB();

$stmt = $db->prepare("
    SELECT s.*, h.title as hw_title, h.subject, h.due_date
    FROM submissions s
    JOIN homework h ON h.id = s.homework_id
    WHERE s.student_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$user['id']]);
$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avgStmt = $db->prepare("SELECT AVG(grade) FROM submissions WHERE student_id=? AND grade IS NOT NULL");
$avgStmt->execute([$user['id']]);
$avg = round($avgStmt->fetchColumn(), 2);

function subjectClass($s) {
    $map=['Математика'=>'subject-math','Русский язык'=>'subject-rus','Физика'=>'subject-sci','Биология'=>'subject-sci','История'=>'subject-hist','Химия'=>'subject-sci'];
    return $map[$s]??'subject-default';
}

ob_start();
?>
<div class="topbar">
  <div>
    <div class="topbar-title">Мои ответы</div>
    <div class="topbar-sub">Все сданные задания</div>
  </div>
</div>
<div class="content">

  <?php if ($avg): ?>
  <div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card blue">
      <div class="stat-num"><?= count($subs) ?></div>
      <div class="stat-label">Сдано заданий</div>
    </div>
    <div class="stat-card green">
      <div class="stat-num"><?= $avg ?></div>
      <div class="stat-label">Средний балл</div>
    </div>
    <?php
    $graded = array_filter($subs, fn($s) => $s['grade'] !== null);
    $fives  = count(array_filter($subs, fn($s) => $s['grade'] == 5));
    ?>
    <div class="stat-card orange">
      <div class="stat-num"><?= count($graded) ?></div>
      <div class="stat-label">Проверено</div>
    </div>
    <div class="stat-card gray">
      <div class="stat-num"><?= $fives ?></div>
      <div class="stat-label">Пятёрок</div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (empty($subs)): ?>
  <div class="card">
    <div class="empty-state">
      <span class="icon">📝</span>
      <h3>Вы ещё не сдали ни одного задания</h3>
      <p>Перейдите в раздел «Задания» и выполните первое</p>
      <br><a href="homework.php" class="btn btn-dark">Перейти к заданиям</a>
    </div>
  </div>
  <?php else: ?>
  <div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Задание</th>
            <th>Предмет</th>
            <th>Ответ</th>
            <th>Сдано</th>
            <th>Оценка</th>
            <th>Комментарий</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subs as $sub): ?>
          <tr>
            <td>
              <a href="homework_detail.php?id=<?= $sub['homework_id'] ?>"
                 style="color:var(--accent2);text-decoration:none;font-weight:500;font-size:14px">
                <?= htmlspecialchars($sub['hw_title']) ?>
              </a>
            </td>
            <td><span class="hw-subject <?= subjectClass($sub['subject']) ?>"><?= htmlspecialchars($sub['subject']) ?></span></td>
            <td style="max-width:200px">
              <div style="font-size:13px;color:var(--muted);overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:200px" title="<?= htmlspecialchars($sub['answer']) ?>">
                <?= htmlspecialchars(mb_substr($sub['answer'], 0, 60, 'UTF-8')) ?><?= mb_strlen($sub['answer'], 'UTF-8') > 60 ? '...' : '' ?>
              </div>
            </td>
            <td style="font-size:13px;color:var(--muted);white-space:nowrap"><?= date('d.m.Y H:i', strtotime($sub['submitted_at'])) ?></td>
            <td>
              <?php if ($sub['grade']): ?>
                <div class="grade-circle grade-<?= $sub['grade'] ?>" style="width:34px;height:34px;font-size:16px"><?= $sub['grade'] ?></div>
              <?php else: ?>
                <span class="badge badge-pending">Ожидание</span>
              <?php endif; ?>
            </td>
            <td style="font-size:13px;color:var(--muted);max-width:180px">
              <?= $sub['teacher_comment'] ? htmlspecialchars($sub['teacher_comment']) : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
echo renderLayout('Мои ответы', 'my_submissions', $pageContent);
