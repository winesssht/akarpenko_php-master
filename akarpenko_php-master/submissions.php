<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

requireRole('teacher');
$user = currentUser();
$db = getDB();

$filter = $_GET['filter'] ?? 'all';

$sql = "
    SELECT s.*, u.full_name as student_name, h.title as hw_title, h.subject
    FROM submissions s
    JOIN users u ON u.id = s.student_id
    JOIN homework h ON h.id = s.homework_id
    WHERE h.teacher_id = ?
";
$params = [$user['id']];

if ($filter === 'pending') { $sql .= " AND s.grade IS NULL"; }
if ($filter === 'graded')  { $sql .= " AND s.grade IS NOT NULL"; }

$sql .= " ORDER BY s.submitted_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$successMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $subId  = intval($_POST['sub_id']);
    $grade  = intval($_POST['grade']);
    $comment = trim($_POST['teacher_comment'] ?? '');
    if ($grade >= 1 && $grade <= 5) {
        $db->prepare("UPDATE submissions SET grade=?, teacher_comment=? WHERE id=?")
           ->execute([$grade, $comment, $subId]);
        header('Location: submissions.php?filter='.$filter.'&graded=1');
        exit;
    }
}

function subjectClass($s) {
    $map=['Математика'=>'subject-math','Русский язык'=>'subject-rus','Физика'=>'subject-sci','Биология'=>'subject-sci','История'=>'subject-hist','Химия'=>'subject-sci'];
    return $map[$s]??'subject-default';
}

ob_start();
?>
<div class="topbar">
  <div>
    <div class="topbar-title">Ответы учеников</div>
    <div class="topbar-sub"><?= count($subs) ?> записей</div>
  </div>
</div>
<div class="content">
  <?php if (isset($_GET['graded'])): ?>
  <div class="alert alert-success">✅ Оценка сохранена!</div>
  <?php endif; ?>

  <div style="display:flex;gap:8px;margin-bottom:24px">
    <a href="?filter=all" class="btn btn-sm <?= $filter==='all'?'btn-dark':'btn-outline' ?>">Все (<?= count($subs) ?>)</a>
    <?php
    $pending = $db->prepare("SELECT COUNT(*) FROM submissions s JOIN homework h ON h.id=s.homework_id WHERE h.teacher_id=? AND s.grade IS NULL");
    $pending->execute([$user['id']]);
    $pendingN = $pending->fetchColumn();
    ?>
    <a href="?filter=pending" class="btn btn-sm <?= $filter==='pending'?'btn-dark':'btn-outline' ?>">Не проверено (<?= $pendingN ?>)</a>
    <a href="?filter=graded" class="btn btn-sm <?= $filter==='graded'?'btn-dark':'btn-outline' ?>">Проверено</a>
  </div>

  <?php if (empty($subs)): ?>
  <div class="card">
    <div class="empty-state">
      <span class="icon">📭</span>
      <h3>Ответов нет</h3>
      <p>Ученики ещё не отправили ответы</p>
    </div>
  </div>
  <?php else: ?>
  <div style="display:grid;gap:16px">
    <?php foreach ($subs as $sub): ?>
    <div class="card card-sm">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px">
        <div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
            <div class="avatar student-av" style="width:30px;height:30px;font-size:12px;border-radius:8px">
              <?= mb_strtoupper(mb_substr($sub['student_name'],0,1,'UTF-8'),'UTF-8') ?>
            </div>
            <span style="font-weight:500;font-size:14px"><?= htmlspecialchars($sub['student_name']) ?></span>
            <span class="hw-subject <?= subjectClass($sub['subject']) ?>"><?= htmlspecialchars($sub['subject']) ?></span>
          </div>
          <a href="homework_detail.php?id=<?= $sub['homework_id'] ?>" style="font-size:13px;color:var(--accent2);text-decoration:none">
            📋 <?= htmlspecialchars($sub['hw_title']) ?>
          </a>
          <div style="font-size:12px;color:var(--muted);margin-top:2px">📅 <?= date('d.m.Y H:i', strtotime($sub['submitted_at'])) ?></div>
        </div>
        <?php if ($sub['grade']): ?>
          <div class="grade-circle grade-<?= $sub['grade'] ?>" style="width:40px;height:40px;font-size:18px"><?= $sub['grade'] ?></div>
        <?php else: ?>
          <span class="badge badge-pending">Ожидает проверки</span>
        <?php endif; ?>
      </div>

      <div style="background:var(--cream);border-radius:10px;padding:12px;font-size:14px;line-height:1.6;margin-bottom:14px;max-height:120px;overflow-y:auto;white-space:pre-wrap">
        <?= htmlspecialchars($sub['answer']) ?>
      </div>

      <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
        <div>
          <label class="form-label" style="margin-bottom:4px">Оценка</label>
          <select name="grade" class="form-control" style="width:85px;padding:8px 10px">
            <?php for($g=5;$g>=1;$g--): ?>
            <option value="<?= $g ?>" <?= ($sub['grade']==$g)?'selected':'' ?>><?= $g ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div style="flex:1;min-width:180px">
          <label class="form-label" style="margin-bottom:4px">Комментарий</label>
          <input type="text" name="teacher_comment" class="form-control" style="padding:9px 12px"
                 placeholder="Комментарий учителя..."
                 value="<?= htmlspecialchars($sub['teacher_comment'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-success btn-sm">✓ Сохранить</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
echo renderLayout('Ответы учеников', 'submissions', $pageContent);
